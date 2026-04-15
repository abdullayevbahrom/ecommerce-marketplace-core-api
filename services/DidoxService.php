<?php

namespace app\services;

use Yii;
use yii\web\HttpException;

class DidoxService
{
    // const STAGE_URL = 'https://stage.goodsign.biz';
    const STAGE_URL = 'https://testapi.einvoice.example.com';
    const PROD_URL = 'https://api.einvoice.example.com';
    
    private $baseUrl;
    private $partnerToken;
    
    public function __construct()
    {
        $configuredBaseUrl = '';
        try {
            $config = $this->getDidoxSettingMap(['didox_url']);
            $configuredBaseUrl = trim((string)($config['didox_url'] ?? ''));
        } catch (\Throwable $e) {
            $configuredBaseUrl = '';
        }

        if ($configuredBaseUrl === '') {
            $configuredBaseUrl = trim((string)(Yii::$app->params['didoxApiUrl'] ?? ''));
        }

        $this->baseUrl = $configuredBaseUrl !== '' ? rtrim($configuredBaseUrl, '/') : (YII_ENV_DEV ? self::STAGE_URL : self::PROD_URL);

        $this->partnerToken = isset(Yii::$app->params['didoxPartnerToken']) ? Yii::$app->params['didoxPartnerToken'] : '';
    }

    private function getDidoxSettingMap(array $types): array
    {
        $settings = \app\models\Settings::find()
            ->where(['type' => $types])
            ->all();

        return \yii\helpers\ArrayHelper::map($settings, 'type', 'content');
    }

    private function getConfiguredDidoxAccountId(): string
    {
        $config = $this->getDidoxSettingMap(['didox_seller_inn']);
        return trim((string)($config['didox_seller_inn'] ?? ''));
    }

    private function normalizeSenderIdentity(array $documentData): array
    {
        $configuredAccountId = $this->getConfiguredDidoxAccountId();
        if ($configuredAccountId === '') {
            return $documentData;
        }

        $normalized = $documentData;
        $override = function (array &$target, string $field) use ($configuredAccountId) {
            if (isset($target[$field]) && $target[$field] !== $configuredAccountId) {
                Yii::warning("Overriding {$field} from {$target[$field]} to {$configuredAccountId}", __METHOD__);
                $target[$field] = $configuredAccountId;
            }
        };

        $override($normalized, 'SellerTin');
        $override($normalized, 'sellertin');

        if (isset($normalized['ProductList']) && is_array($normalized['ProductList'])) {
            $override($normalized['ProductList'], 'Tin');
            $override($normalized['ProductList'], 'tin');
        }

        if (isset($normalized['data']) && is_array($normalized['data'])) {
            $override($normalized['data'], 'SellerTin');
            $override($normalized['data'], 'sellertin');

            if (isset($normalized['data']['ProductList']) && is_array($normalized['data']['ProductList'])) {
                $override($normalized['data']['ProductList'], 'Tin');
                $override($normalized['data']['ProductList'], 'tin');
            }
        }

        return $normalized;
    }
    
    /**
     * Get automated authentication token using local PFX and Signer Service
     * Mirroring logic from Python: manager.py -> get_token
     * @return array ['success' => bool, 'token' => string|null, 'error' => string|null]
     */
    public function getAuthTokenFromPfx()
    {
        $pfxValidation = $this->validateConfiguredPfx();
        if (!$pfxValidation['success']) {
            return [
                'success' => false,
                'error' => $pfxValidation['error'],
            ];
        }

        $config = $this->getDidoxSettingMap(['didox_seller_inn']);
        $targetTaxId = trim((string)($config['didox_seller_inn'] ?? ''));

        if ($targetTaxId === '') {
            return ['success' => false, 'error' => 'Seller INN/JSHIR not configured.'];
        }

        $pfxIdentity = $this->extractConfiguredPfxIdentity();
        $individualTaxId = trim((string)($pfxIdentity['uid'] ?? ''));

        if ($individualTaxId !== '' && $individualTaxId !== $targetTaxId) {
            return $this->authenticateCompanyWithConfiguredPfx($targetTaxId);
        }

        return $this->authenticateWithConfiguredPfx($targetTaxId);
    }

    /**
     * Authenticate a Didox user by signing the provided tax ID with the configured PFX.
     *
     * @param string $taxId
     * @return array
     */
    public function authenticateWithConfiguredPfx(string $taxId): array
    {
        $taxId = trim($taxId);
        if ($taxId === '') {
            return ['success' => false, 'error' => 'Tax ID is required.'];
        }

        try {
            $signed = $this->signConfiguredPfxPayload($taxId);
            if (!$signed['success']) {
                return $signed;
            }

            $timestampRes = $this->createTimestamp($signed['pkcs7'], $signed['signature']);
            if (!$timestampRes['success'] || empty($timestampRes['data']['timeStampTokenB64'])) {
                return ['success' => false, 'error' => 'Failed to get timestamp: ' . json_encode($timestampRes)];
            }

            $authUrl = "/v1/auth/{$taxId}/token/ru";
            $authRes = $this->makeRequest('POST', $authUrl, ['signature' => $timestampRes['data']['timeStampTokenB64']]);

            if ($authRes['isOk'] && isset($authRes['data']['token'])) {
                return [
                    'success' => true,
                    'token' => $authRes['data']['token'],
                    'taxId' => $taxId,
                    'data' => $authRes['data'],
                ];
            }

            return ['success' => false, 'error' => 'Didox Auth failed: ' . json_encode($authRes)];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Exception during auto-auth: ' . $e->getMessage()];
        }
    }

    /**
     * Authenticate with configured PFX as individual, then login to a company account.
     *
     * @param string $companyTaxId
     * @return array
     */
    public function authenticateCompanyWithConfiguredPfx(string $companyTaxId): array
    {
        $companyTaxId = trim($companyTaxId);
        if ($companyTaxId === '') {
            return ['success' => false, 'error' => 'Company Tax ID is required.'];
        }

        $pfxIdentity = $this->extractConfiguredPfxIdentity();
        $individualTaxId = trim((string)($pfxIdentity['uid'] ?? ''));
        if ($individualTaxId === '') {
            $individualTaxId = trim((string)($pfxIdentity['tin'] ?? ''));
        }

        if ($individualTaxId === '') {
            return ['success' => false, 'error' => 'Could not extract individual tax ID from configured PFX.'];
        }

        $authResult = $this->authenticateWithConfiguredPfx($individualTaxId);
        if (!$authResult['success']) {
            return $authResult;
        }

        $companyLoginResult = $this->loginToCompany($companyTaxId, $authResult['token']);
        if (!$companyLoginResult['success']) {
            return [
                'success' => false,
                'error' => 'Individual authentication succeeded, but company login failed: ' . ($companyLoginResult['error'] ?? 'Unknown error'),
                'individualTaxId' => $individualTaxId,
            ];
        }

        return [
            'success' => true,
            'token' => $companyLoginResult['token'],
            'taxId' => $companyTaxId,
            'individualTaxId' => $individualTaxId,
            'permissions' => $companyLoginResult['permissions'] ?? null,
            'data' => $companyLoginResult['data'] ?? null,
        ];
    }

    /**
     * Refresh and store Didox token automatically using configured PFX
     * Called by console command or queue job every 3 hours
     * 
     * @return array ['success' => bool, 'token' => string|null, 'expires_at' => string|null, 'error' => string|null]
     */
    public function refreshAndStoreToken(): array
    {
        $now = date('Y-m-d H:i:s');
        
        try {
            // Check if auto-refresh is enabled
            $settings = $this->getDidoxSettingMap([
                'didox_auto_refresh_status',
                'didox_seller_inn',
            ]);
            
            $status = $settings['didox_auto_refresh_status'] ?? 'manual';
            if ($status === 'disabled') {
                return ['success' => false, 'error' => 'Auto-refresh is disabled', 'token' => null, 'expires_at' => null, 'skipped' => true];
            }

            $pfxValidation = $this->validateConfiguredPfx();
            if (!$pfxValidation['success']) {
                $message = $this->cleanupInvalidAutoRefreshState($pfxValidation['error'], $now);

                return [
                    'success' => false,
                    'error' => $message,
                    'token' => null,
                    'expires_at' => null,
                    'skipped' => true
                ];
            }
            
            // Get new token using PFX
            $result = $this->getAuthTokenFromPfx();
            
            // Update last attempt timestamp
            $this->updateSetting('didox_auto_refresh_last_attempt', $now);
            
            if (!$result['success']) {
                // Log error
                $this->updateSetting('didox_auto_refresh_status', 'failed');
                $this->updateSetting('didox_auto_refresh_error', $result['error'] ?? 'Unknown error');
                
                Yii::error('Didox token refresh failed: ' . ($result['error'] ?? 'Unknown error'), __METHOD__);
                
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to get token from PFX',
                    'token' => null,
                    'expires_at' => null
                ];
            }
            
            // Calculate expiry (3 hours from now)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+3 hours'));
            
            // Save token and metadata to settings
            $this->updateSetting('didox_eimzo_token', $result['token']);
            $this->updateSetting('didox_eimzo_tax_id', $result['taxId'] ?? '');
            $this->updateSetting('didox_eimzo_last_login', $now);
            $this->updateSetting('didox_token_expires_at', $expiresAt);
            $this->updateSetting('didox_auto_refresh_status', 'active');
            $this->updateSetting('didox_auto_refresh_error', ''); // Clear any previous error
            
            // Save certificate info if available
            if (isset($result['data']['certificate'])) {
                $this->updateSetting('didox_eimzo_certificate', json_encode($result['data']['certificate']));
            }
            
            Yii::info('Didox token refreshed successfully. Expires at: ' . $expiresAt, __METHOD__);
            
            return [
                'success' => true,
                'token' => $result['token'],
                'expires_at' => $expiresAt,
                'tax_id' => $result['taxId'] ?? null,
                'error' => null
            ];
            
        } catch (\Throwable $e) {
            $error = 'Exception during token refresh: ' . $e->getMessage();
            
            $this->updateSetting('didox_auto_refresh_status', 'failed');
            $this->updateSetting('didox_auto_refresh_error', $error);
            $this->updateSetting('didox_auto_refresh_last_attempt', $now);
            
            Yii::error($error, __METHOD__);
            
            return [
                'success' => false,
                'error' => $error,
                'token' => null,
                'expires_at' => null
            ];
        }
    }
    
    /**
     * Update or create a setting value
     * 
     * @param string $type
     * @param string $content
     * @return bool
     */
    private function updateSetting(string $type, string $content): bool
    {
        try {
            $model = \app\models\Settings::findOne(['type' => $type]);
            
            if (!$model) {
                $model = new \app\models\Settings();
                $model->type = $type;
            }
            
            $model->content = $content;
            $model->date = date('Y-m-d H:i:s');
            
            return $model->save(false);
        } catch (\Throwable $e) {
            Yii::error("Failed to update setting {$type}: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
    
    /**
     * Check if token is expired or about to expire (within 10 minutes)
     * 
     * @return bool
     */
    public function isTokenExpiringSoon(): bool
    {
        $settings = $this->getDidoxSettingMap(['didox_token_expires_at', 'didox_eimzo_token']);
        
        // No token exists
        if (empty($settings['didox_eimzo_token'])) {
            return true;
        }
        
        // No expiry set
        if (empty($settings['didox_token_expires_at'])) {
            return true;
        }
        
        $expiresAt = strtotime($settings['didox_token_expires_at']);
        $now = time();
        $tenMinutes = 600; // 10 minutes buffer
        
        return ($expiresAt - $now) <= $tenMinutes;
    }
    
    /**
     * Get token status info for admin panel
     * 
     * @return array
     */
    public function getTokenStatus(): array
    {
        $settings = $this->getDidoxSettingMap([
            'didox_eimzo_token',
            'didox_token_expires_at',
            'didox_eimzo_last_login',
            'didox_auto_refresh_status',
            'didox_auto_refresh_error',
            'didox_auto_refresh_last_attempt',
            'didox_seller_inn'
        ]);
        
        $expiresAt = $settings['didox_token_expires_at'] ?? null;
        $hasToken = !empty($settings['didox_eimzo_token']);
        $isExpired = $expiresAt ? strtotime($expiresAt) <= time() : !$hasToken;
        $expiringSoon = $this->isTokenExpiringSoon();
        
        return [
            'has_token' => $hasToken,
            'is_expired' => $isExpired,
            'expiring_soon' => $expiringSoon && !$isExpired,
            'expires_at' => $expiresAt,
            'expires_in' => $expiresAt ? $this->formatTimeRemaining($expiresAt) : null,
            'last_login' => $settings['didox_eimzo_last_login'] ?? null,
            'status' => $settings['didox_auto_refresh_status'] ?? 'manual',
            'last_error' => $settings['didox_auto_refresh_error'] ?? null,
            'last_attempt' => $settings['didox_auto_refresh_last_attempt'] ?? null,
            'seller_inn' => $settings['didox_seller_inn'] ?? null,
        ];
    }
    
    /**
     * Format time remaining for display
     * 
     * @param string $expiresAt
     * @return string
     */
    private function formatTimeRemaining(string $expiresAt): string
    {
        $diff = strtotime($expiresAt) - time();
        
        if ($diff <= 0) {
            return 'Expired';
        }
        
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }

    /**
     * Make HTTP request using cURL
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    private function makeRequest($method, $url, $data = [])
    {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Partner-Authorization: ' . $this->partnerToken
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE && $response !== false && $response !== '') {
            $decodedResponse = $response;
        }
        
        return [
            'httpCode' => $httpCode,
            'data' => $decodedResponse,
            'isOk' => $httpCode >= 200 && $httpCode < 300
        ];
    }
    
    /**
     * Validate Didox token
     * @param string $token - Didox token to validate
     * @param string $taxId - Tax ID for additional validation
     * @return bool
     */
    public function validateToken($token, $taxId)
    {
        try {
            // For now, we'll assume the token is valid if it's properly formatted
            // In a real implementation, you might want to call a Didox validation endpoint
            if (empty($token) || strlen($token) < 10) {
                return false;
            }
            
            // Additional validation logic can be added here
            // For example, checking token format, expiration, etc.
            
            return true;
        } catch (\Exception $e) {
            Yii::error('Token validation error: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Extract INN from E-IMZO certificate alias
     * @param string $alias
     * @return string|null
     */
    public function extractInnFromAlias($alias)
    {
        if (preg_match('/1\.2\.860\.3\.16\.1\.1=(\d+)/', $alias, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function resolveAppPfxPath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (strpos($path, '/var/www/html/keys/') === 0) {
            return $path;
        }

        $filename = basename(str_replace('\\', '/', $path));
        return Yii::getAlias('@app/keys/' . $filename);
    }

    private function getConfiguredPfxSettings(): array
    {
        $config = $this->getDidoxSettingMap(['didox_pfx_path', 'didox_pfx_password', 'didox_signer_url']);

        $pfxPath = trim((string)($config['didox_pfx_path'] ?? ''));
        $password = (string)($config['didox_pfx_password'] ?? '');
        $signerUrl = trim((string)($config['didox_signer_url'] ?? 'http://eimzo-signer:8080/generate'));
        $appPfxPath = $this->resolveAppPfxPath($pfxPath);

        return [
            'pfxPath' => $pfxPath,
            'appPfxPath' => $appPfxPath,
            'password' => $password,
            'signerUrl' => $signerUrl,
        ];
    }

    public function validateConfiguredPfx(): array
    {
        $settings = $this->getConfiguredPfxSettings();

        if ($settings['pfxPath'] === '') {
            return [
                'success' => false,
                'error' => 'PFX file not uploaded.',
            ];
        }

        if ($settings['password'] === '') {
            return [
                'success' => false,
                'error' => 'PFX password not configured.',
            ];
        }

        if ($settings['appPfxPath'] === '' || !file_exists($settings['appPfxPath'])) {
            return [
                'success' => false,
                'error' => 'Uploaded PFX file is missing on the server.',
            ];
        }

        return [
            'success' => true,
            'pfxPath' => $settings['pfxPath'],
            'appPfxPath' => $settings['appPfxPath'],
        ];
    }

    public function cleanupInvalidAutoRefreshState(string $reason, ?string $attemptedAt = null): string
    {
        $attemptedAt = $attemptedAt ?: date('Y-m-d H:i:s');
        $message = 'Auto-refresh disabled: ' . trim($reason);

        $this->updateSetting('didox_auto_refresh_status', 'disabled');
        $this->updateSetting('didox_auto_refresh_error', $message);
        $this->updateSetting('didox_auto_refresh_last_attempt', $attemptedAt);

        Yii::warning($message, __METHOD__);

        return $message;
    }

    private function signConfiguredPfxPayload(string $data): array
    {
        $settings = $this->getConfiguredPfxSettings();

        if (empty($settings['appPfxPath']) || empty($settings['password']) || !file_exists($settings['appPfxPath'])) {
            return ['success' => false, 'error' => 'PFX file not configured or missing.'];
        }

        $signerData = [
            'pfxFilePath' => $settings['pfxPath'],
            'password' => $settings['password'],
            'alias' => '',
            'data' => $data,
            'dataBase64' => true,
            'attached' => true,
            'includeChain' => false,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $settings['signerUrl'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($signerData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
        ]);
        $signerResponse = curl_exec($ch);
        $signerHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $signerError = curl_error($ch);
        curl_close($ch);

        if ($signerError || $signerHttpCode != 200) {
            return ['success' => false, 'error' => 'Signer Service failed: ' . ($signerError ?: "HTTP $signerHttpCode")];
        }

        $tokenData = json_decode($signerResponse, true);
        if (!isset($tokenData['pkcs7']) || !isset($tokenData['signature'])) {
            return ['success' => false, 'error' => 'Invalid response from Signer Service'];
        }

        return [
            'success' => true,
            'pkcs7' => $tokenData['pkcs7'],
            'signature' => $tokenData['signature'],
        ];
    }

    /**
     * Extract identity fields from the configured PFX certificate.
     *
     * @return array{success?: bool, error?: string, subject?: array, uid?: string|null, tin?: string|null, pinfl?: string|null}
     */
    public function extractConfiguredPfxIdentity(): array
    {
        $settings = $this->getConfiguredPfxSettings();
        $identitySettings = $this->getDidoxSettingMap(['didox_eimzo_tax_id']);
        $configuredIdentity = trim((string)($identitySettings['didox_eimzo_tax_id'] ?? ''));
        if (empty($settings['appPfxPath']) || empty($settings['password']) || !file_exists($settings['appPfxPath'])) {
            return [
                'success' => false,
                'error' => 'PFX file not configured or missing.',
                'uid' => $configuredIdentity !== '' ? $configuredIdentity : null,
            ];
        }

        if (!function_exists('openssl_pkcs12_read')) {
            return [
                'success' => false,
                'error' => 'OpenSSL PKCS#12 support is not available.',
                'uid' => $configuredIdentity !== '' ? $configuredIdentity : null,
            ];
        }

        $pkcs12 = @file_get_contents($settings['appPfxPath']);
        if ($pkcs12 === false) {
            return [
                'success' => false,
                'error' => 'Unable to read configured PFX file.',
                'uid' => $configuredIdentity !== '' ? $configuredIdentity : null,
            ];
        }

        $certs = [];
        if (!@openssl_pkcs12_read($pkcs12, $certs, $settings['password'])) {
            return [
                'success' => false,
                'error' => 'Unable to parse configured PFX file.',
                'uid' => $configuredIdentity !== '' ? $configuredIdentity : null,
            ];
        }

        $parsed = @openssl_x509_parse($certs['cert'] ?? '');
        if (!$parsed || !is_array($parsed)) {
            return [
                'success' => false,
                'error' => 'Unable to parse configured PFX certificate.',
                'uid' => $configuredIdentity !== '' ? $configuredIdentity : null,
            ];
        }

        $subject = $parsed['subject'] ?? [];
        $uid = trim((string)($subject['UID'] ?? ''));
        $tin = trim((string)($subject['1.2.860.3.16.1.1'] ?? ''));
        $pinfl = trim((string)($subject['1.2.860.3.16.1.2'] ?? ''));

        return [
            'success' => true,
            'subject' => $subject,
            'uid' => $uid !== '' ? $uid : ($configuredIdentity !== '' ? $configuredIdentity : null),
            'tin' => $tin !== '' ? $tin : null,
            'pinfl' => $pinfl !== '' ? $pinfl : null,
        ];
    }

    /**
     * Create timestamp for signature according to Didox documentation
     * @param string $pkcs7_64 - PKCS7 signature in base64
     * @param string $signature_hex - Signature in hex format
     * @return array
     */
    public function createTimestamp($pkcs7_64, $signature_hex)
    {
        try {
            $timestampData = [
                'pkcs7' => $pkcs7_64,
                'signatureHex' => $signature_hex
            ];
            
            $response = $this->makeRequest('POST', '/v1/dsvs/timestamp', $timestampData);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('Didox timestamp creation error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Register user with Didox following the official documentation
     * @param array $userData - User registration data
     * @return array
     */
    public function registerUser($userData)
    {
        try {
            // Validate required fields according to Didox documentation
            $requiredFields = ['email', 'mobile', 'password', 'accept', 'signature'];
            foreach ($requiredFields as $field) {
                if (!isset($userData[$field]) || empty($userData[$field])) {
                    return [
                        'success' => false,
                        'error' => "Missing required field: $field",
                        'userExists' => false
                    ];
                }
            }
            
            // Prepare registration data exactly as per Didox API
            $registrationData = [
                'email' => $userData['email'],
                'mobile' => $userData['mobile'], 
                'password' => $userData['password'],
                'accept' => $userData['accept'],
                'signature' => $userData['signature'] // Signed INN with attached timestamp in base64
            ];
            
            $response = $this->makeRequest('POST', '/v1/auth/signup', $registrationData);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'userExists' => $response['httpCode'] === 422 && 
                               isset($response['data']['taxId']) && 
                               in_array('validation.unique', $response['data']['taxId'])
            ];
            
        } catch (\Exception $e) {
            Yii::error('Didox registration error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'userExists' => false
            ];
        }
    }

    /**
     * Extract certificate information from E-IMZO token/certificate data
     * @param array $certificateData - Certificate data from E-IMZO
     * @return array
     */
    public function extractCertificateInfo($certificateData)
    {
        $extractedInfo = [];
        
        try {
            // Extract common certificate fields
            if (isset($certificateData['serialNumber'])) {
                $extractedInfo['serial_number'] = $certificateData['serialNumber'];
            }
            
            if (isset($certificateData['validFrom'])) {
                $extractedInfo['valid_from'] = $certificateData['validFrom'];
            }
            
            if (isset($certificateData['validTo'])) {
                $extractedInfo['valid_to'] = $certificateData['validTo'];
            }
            
            if (isset($certificateData['subject'])) {
                $subject = $certificateData['subject'];
                
                // Extract common name
                if (isset($subject['CN'])) {
                    $extractedInfo['common_name'] = $subject['CN'];
                }
                
                // Extract organization
                if (isset($subject['O'])) {
                    $extractedInfo['organization'] = $subject['O'];
                }
                
                // Extract country
                if (isset($subject['C'])) {
                    $extractedInfo['country'] = $subject['C'];
                }
                
                // Extract email
                if (isset($subject['emailAddress'])) {
                    $extractedInfo['email'] = $subject['emailAddress'];
                }
                
                // Extract INN/Tax ID from subject
                if (isset($subject['1.2.860.3.16.1.1'])) {
                    $extractedInfo['inn'] = $subject['1.2.860.3.16.1.1'];
                }
            }
            
            if (isset($certificateData['issuer'])) {
                $extractedInfo['issuer'] = $certificateData['issuer'];
            }
            
            if (isset($certificateData['keyUsage'])) {
                $extractedInfo['key_usage'] = $certificateData['keyUsage'];
            }
            
            if (isset($certificateData['alias'])) {
                $extractedInfo['alias'] = $certificateData['alias'];
                
                // Try to extract INN from alias as fallback
                if (!isset($extractedInfo['inn'])) {
                    $innFromAlias = $this->extractInnFromAlias($certificateData['alias']);
                    if ($innFromAlias) {
                        $extractedInfo['inn'] = $innFromAlias;
                    }
                }
            }
            
            // Extract personal information if available
            if (isset($certificateData['personalInfo'])) {
                $personalInfo = $certificateData['personalInfo'];
                
                if (isset($personalInfo['firstName'])) {
                    $extractedInfo['first_name'] = $personalInfo['firstName'];
                }
                
                if (isset($personalInfo['lastName'])) {
                    $extractedInfo['last_name'] = $personalInfo['lastName'];
                }
                
                if (isset($personalInfo['middleName'])) {
                    $extractedInfo['middle_name'] = $personalInfo['middleName'];
                }
                
                if (isset($personalInfo['fullName'])) {
                    $extractedInfo['full_name'] = $personalInfo['fullName'];
                }
                
                if (isset($personalInfo['passport'])) {
                    $extractedInfo['passport'] = $personalInfo['passport'];
                }
                
                if (isset($personalInfo['birthDate'])) {
                    $extractedInfo['birth_date'] = $personalInfo['birthDate'];
                }
                
                if (isset($personalInfo['gender'])) {
                    $extractedInfo['gender'] = $personalInfo['gender'];
                }
            }
            
            // Store the original certificate data for future reference
            $extractedInfo['original_certificate'] = $certificateData;
            
        } catch (\Exception $e) {
            Yii::error('Certificate info extraction error: ' . $e->getMessage(), __METHOD__);
        }
        
        return $extractedInfo;
    }

    /**
     * Authenticate user with existing Didox account
     * @param string $token - Didox authentication token
     * @param string $taxId - Tax ID for validation
     * @return array
     */
    public function authenticateWithDidox($token, $taxId)
    {
        try {
            // Validate the token format first
            if (!$this->validateToken($token, $taxId)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid token format'
                ];
            }
            
            // In a real implementation, you would call Didox API to validate the token
            // and get user information. For now, we'll simulate this process.
            
            // This would be something like: GET /v1/auth/user with Bearer token
            // $response = $this->makeRequest('GET', '/v1/auth/user', [], $token);
            
            // For now, return success if token format is valid
            return [
                'valid' => true,
                'token' => $token,
                'tax_id' => $taxId,
                'authenticated_at' => date('Y-m-d H:i:s'),
                'user_info' => [
                    'tax_id' => $taxId,
                    'token_valid' => true
                ]
            ];
            
        } catch (\Exception $e) {
            Yii::error('Didox authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate and extract information from Didox token
     * @param string $token - Didox token
     * @param string $taxId - Tax ID for validation
     * @return array
     */
    public function validateAndExtractTokenInfo($token, $taxId)
    {
        try {
            // First validate the token
            if (!$this->validateToken($token, $taxId)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid token'
                ];
            }
            
            // In a real implementation, you would call Didox API to get detailed token info
            // For now, we'll return basic validation result
            return [
                'valid' => true,
                'token' => $token,
                'tax_id' => $taxId,
                'validated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            Yii::error('Token validation and extraction error: ' . $e->getMessage(), __METHOD__);
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get list of documents from DIDOX
     * @param array $params - Search parameters (page, limit, status, etc.)
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getDocuments($params = [], $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $defaultParams = [
                'page' => 1,
                'limit' => 20,
                'owner' => 1 // Outgoing documents by default
            ];
            
            $queryParams = array_merge($defaultParams, $params);
            $url = '/v2/documents?' . http_build_query($queryParams);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new \Exception('cURL Error: ' . $error);
            }
            
            $decodedResponse = json_decode($response, true);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'data' => $decodedResponse,
                'httpCode' => $httpCode
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get documents error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create new document in DIDOX
     * @param array $documentData - Document data according to DIDOX API
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function createDocument($documentData, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $documentData = $this->normalizeSenderIdentity($documentData);

            // Get document type for the endpoint URL and remove from data
            $docType = isset($documentData['doctype']) ? $documentData['doctype'] : '000';
            
            // Remove doctype from the JSON body as it should be in the URL path
            $createData = $documentData;
            if (isset($createData['doctype'])) {
                unset($createData['doctype']);
            }
            
            // Use correct DIDOX endpoint: /v1/documents/:docType/create
            $response = $this->makeRequestWithHeaders('POST', "/v1/documents/{$docType}/create", $createData, $headers);
            
            // Extract error information if request failed
            $error = null;
            if (!$response['isOk']) {
                if (isset($response['data']['error'])) {
                    $error = $response['data']['error'];
                } elseif (isset($response['data']['message'])) {
                    $error = $response['data']['message'];
                } elseif (isset($response['data']['errors'])) {
                    $error = $response['data']['errors'];
                } else {
                    $error = "HTTP {$response['httpCode']}";
                    if (!empty($response['data'])) {
                        $error .= ": " . json_encode($response['data'], JSON_UNESCAPED_UNICODE);
                    }
                }
            }

            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => $error
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX create document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send document to partner after signing
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function sendDocumentToPartner($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // Didox expects PUT for the send transition.
            $response = $this->makeRequestWithHeaders('PUT', "/v1/documents/{$docId}/send", [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX send document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing document in DIDOX
     * @param string $docId - Document ID
     * @param array $documentData - Updated document data
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function updateDocument($docId, $documentData, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $documentData = $this->normalizeSenderIdentity($documentData);

            // Get document type for the endpoint URL and remove from data
            $docType = isset($documentData['doctype']) ? $documentData['doctype'] : '002';
            
            // Remove doctype from the JSON body as it should be in the URL path
            $updateData = $documentData;
            if (isset($updateData['doctype'])) {
                unset($updateData['doctype']);
            }
            
            // Use correct DIDOX endpoint: POST /v1/documents/:docId/update/:doctype
            $response = $this->makeRequestWithHeaders('POST', "/v1/documents/{$docId}/update/{$docType}", $updateData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX update document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document details from DIDOX
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getDocument($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('GET', '/v1/documents/' . $docId, [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'debug' => $response['debug'] ?? null,
                'error' => !$response['isOk'] ? ($response['data']['message'] ?? $response['data']['error'] ?? 'Unknown error') : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document PDF from DIDOX
     * URL format: /v1/documents/view/{docId}/pdf/{lang}
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @param string $lang - Language code (uz, ru, en)
     * @return array - Contains 'success', 'data' (PDF binary), 'contentType', 'error'
     */
    public function getDocumentPdf($docId, $userKey = '', $lang = 'uz')
    {
        try {
            $headers = [
                'Partner-Authorization: ' . $this->partnerToken
            ];

            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }

            $url = '/v1/documents/view/' . $docId . '/pdf/' . $lang;
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 60, // Longer timeout for PDF download
                CURLOPT_FOLLOWLOCATION => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception('cURL Error: ' . $error);
            }

            // Check if response is PDF (application/pdf) or error (application/json)
            $isPdf = strpos($contentType, 'application/pdf') !== false;
            
            if ($httpCode >= 200 && $httpCode < 300 && $isPdf) {
                return [
                    'success' => true,
                    'data' => $response,
                    'contentType' => $contentType,
                    'httpCode' => $httpCode
                ];
            } else {
                // Try to decode error response
                $errorData = json_decode($response, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : (isset($errorData['error']) ? $errorData['error'] : 'Failed to get PDF');
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $httpCode,
                    'debug' => [
                        'request_url' => $this->baseUrl . $url,
                        'response_raw' => substr($response, 0, 500)
                    ]
                ];
            }

        } catch (\Exception $e) {
            Yii::error('DIDOX get document PDF error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document data for signing from DIDOX
     * @param string $docId - Document ID
     * @param string $action - Action type (accept, cancel, reject, etc.)
     * @param string $userKey - User key for authentication
     * @param array $additionalData - Additional data like comment
     * @return array
     */
    public function getDocumentToSign($docId, $action = 'accept', $userKey = '', $additionalData = [])
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $requestData = array_merge(['action' => $action], $additionalData);
            
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/tosign', $requestData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get document to sign error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document data for signing (step 1 of DIDOX signing process)
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getDocumentForSigning($docId, $userKey = '')
    {
        try {
            // Prepare headers for DIDOX API request
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $endpoint = '/v1/documents/' . $docId . '?owner=1';
            Yii::info("DIDOX API CALL: GET {$this->baseUrl}{$endpoint}", __METHOD__);
            
            // Make the actual DIDOX API request (Step 3 from documentation)
            $response = $this->makeRequestWithHeaders('GET', $endpoint, [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get document for signing error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract the canonical outgoing document JSON payload that Didox expects to be signed.
     * For owner=1 flow the API returns a wrapper object and only data.json should be signed.
     *
     * @param array $responseData
     * @return array|null
     */
    public function extractOutgoingDocumentJson(array $responseData): ?array
    {
        if (isset($responseData['data']['json']) && is_array($responseData['data']['json'])) {
            return $responseData['data']['json'];
        }

        if (isset($responseData['json']) && is_array($responseData['json'])) {
            return $responseData['json'];
        }

        return null;
    }

    /**
     * Build the exact JSON/base64 payload for outgoing document signing.
     *
     * @param array $responseData
     * @return array{success: bool, documentJson?: string, documentBase64?: string, error?: string}
     */
    public function buildOutgoingDocumentSignaturePayload(array $responseData): array
    {
        $documentJsonData = $this->extractOutgoingDocumentJson($responseData);
        if ($documentJsonData === null) {
            return [
                'success' => false,
                'error' => 'Didox response does not contain signable data.json payload.',
            ];
        }

        $documentJson = json_encode($documentJsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($documentJson === false) {
            return [
                'success' => false,
                'error' => 'Failed to encode signable document JSON.',
            ];
        }

        return [
            'success' => true,
            'documentJson' => $documentJson,
            'documentBase64' => base64_encode($documentJson),
        ];
    }

    /**
     * Extract seller TIN from outgoing document payload returned by Didox.
     *
     * @param array $responseData
     * @return string|null
     */
    public function extractOutgoingSellerTin(array $responseData): ?string
    {
        $documentJsonData = $this->extractOutgoingDocumentJson($responseData);
        if ($documentJsonData === null) {
            return null;
        }

        $candidates = [
            $documentJsonData['sellertin'] ?? null,
            $documentJsonData['SellerTin'] ?? null,
            $documentJsonData['data']['sellertin'] ?? null,
            $documentJsonData['data']['SellerTin'] ?? null,
            $documentJsonData['seller']['tin'] ?? null,
            $documentJsonData['seller']['Tin'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string)$candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Automatically sign and send an outgoing Didox document using the configured PFX signer.
     *
     * @param string $docId
     * @return array
     */
    public function autoSignAndSendDocumentWithConfiguredPfx(string $docId): array
    {
        $docId = trim($docId);
        if ($docId === '') {
            return ['success' => false, 'error' => 'Document ID is required.'];
        }

        $pfxValidation = $this->validateConfiguredPfx();
        if (!$pfxValidation['success']) {
            return ['success' => false, 'error' => $pfxValidation['error'], 'stage' => 'validate_pfx'];
        }

        $authResult = $this->getAuthTokenFromPfx();
        if (empty($authResult['success']) || empty($authResult['token'])) {
            return [
                'success' => false,
                'error' => $authResult['error'] ?? 'Failed to authenticate with configured PFX.',
                'stage' => 'authenticate',
                'auth_result' => $authResult,
            ];
        }

        $userKey = $authResult['token'];
        $documentForSigning = $this->getDocumentForSigning($docId, $userKey);
        if (empty($documentForSigning['success']) || empty($documentForSigning['data']) || !is_array($documentForSigning['data'])) {
            return [
                'success' => false,
                'error' => $documentForSigning['error'] ?? 'Failed to fetch document for signing.',
                'stage' => 'get_document_for_signing',
                'document_result' => $documentForSigning,
            ];
        }

        $sellerTin = $this->extractOutgoingSellerTin($documentForSigning['data']);
        $signerTaxId = trim((string)($authResult['taxId'] ?? ''));
        if ($sellerTin !== null && $signerTaxId !== '' && $sellerTin !== $signerTaxId) {
            return [
                'success' => false,
                'error' => "Configured signer tax ID {$signerTaxId} does not match document seller TIN {$sellerTin}.",
                'stage' => 'identity_check',
                'seller_tin' => $sellerTin,
                'signer_tax_id' => $signerTaxId,
            ];
        }

        $payload = $this->buildOutgoingDocumentSignaturePayload($documentForSigning['data']);
        if (empty($payload['success']) || empty($payload['documentBase64'])) {
            return [
                'success' => false,
                'error' => $payload['error'] ?? 'Failed to build signing payload.',
                'stage' => 'build_payload',
                'payload_result' => $payload,
            ];
        }

        $signed = $this->signConfiguredPfxPayload($payload['documentBase64']);
        if (empty($signed['success'])) {
            return [
                'success' => false,
                'error' => $signed['error'] ?? 'Configured signer failed to sign the payload.',
                'stage' => 'sign_payload',
                'sign_result' => $signed,
            ];
        }

        $timestampRes = $this->createTimestamp($signed['pkcs7'], $signed['signature']);
        if (empty($timestampRes['success']) || empty($timestampRes['data']['timeStampTokenB64'])) {
            return [
                'success' => false,
                'error' => $timestampRes['error'] ?? 'Failed to create timestamp for Didox signature.',
                'stage' => 'create_timestamp',
                'timestamp_result' => $timestampRes,
            ];
        }

        $finalSignature = $timestampRes['data']['timeStampTokenB64'];
        $signResult = $this->signDocument($docId, $finalSignature, $userKey);
        if (empty($signResult['success'])) {
            return [
                'success' => false,
                'error' => $signResult['error'] ?? 'Failed to sign Didox document.',
                'stage' => 'didox_sign',
                'didox_sign_result' => $signResult,
                'seller_tin' => $sellerTin,
                'signer_tax_id' => $signerTaxId,
            ];
        }

        $sendResult = $this->sendDocumentToPartner($docId, $userKey);
        if (empty($sendResult['success'])) {
            $documentState = $this->getDocument($docId, $userKey);
            $stateDocument = $documentState['data']['data']['document'] ?? ($documentState['data']['document'] ?? null);
            $stateStatus = is_array($stateDocument)
                ? (isset($stateDocument['doc_status']) ? (int)$stateDocument['doc_status'] : (isset($stateDocument['status']) ? (int)$stateDocument['status'] : null))
                : null;

            // Some Didox flows move the document to waiting-partner state as part of signing,
            // and an explicit /send request then returns "Нет такого документа".
            if (!empty($documentState['success']) && $stateStatus === \app\models\didox\DidoxDocument::STATUS_WAITING_PARTNER_SIGNATURE) {
                return [
                    'success' => true,
                    'stage' => 'completed',
                    'token_tax_id' => $signerTaxId,
                    'seller_tin' => $sellerTin,
                    'auth_result' => $authResult,
                    'sign_result' => $signResult,
                    'send_result' => $sendResult,
                    'document_state' => $documentState,
                    'note' => 'Didox moved the document to waiting partner signature during sign; explicit send was not required.',
                ];
            }

            return [
                'success' => false,
                'error' => $sendResult['error'] ?? 'Document signed, but failed to send to partner.',
                'stage' => 'didox_send',
                'sign_result' => $signResult,
                'send_result' => $sendResult,
                'seller_tin' => $sellerTin,
                'signer_tax_id' => $signerTaxId,
            ];
        }

        $documentState = $this->getDocument($docId, $userKey);

        return [
            'success' => true,
            'stage' => 'completed',
            'token_tax_id' => $signerTaxId,
            'seller_tin' => $sellerTin,
            'auth_result' => $authResult,
            'sign_result' => $signResult,
            'send_result' => $sendResult,
            'document_state' => $documentState,
        ];
    }

    /**
     * Sign document in DIDOX (step 2 of DIDOX signing process)
     * @param string $docId - Document ID
     * @param string $signature - timeStampTokenB64 signature
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function signDocument($docId, $signature, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $requestData = ['signature' => $signature];
            
            // FIXED: Use correct endpoint for signing outgoing documents
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/sign', $requestData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'debug' => $response['debug'] ?? null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX sign document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'request_url' => $this->baseUrl . '/v1/documents/' . $docId . '/sign',
                    'request_method' => 'POST',
                    'request_body' => json_encode(['signature' => $signature], JSON_PRETTY_PRINT),
                    'signature_length' => strlen($signature),
                    'exception' => $e->getMessage(),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * Cancel/Delete document in DIDOX
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function cancelDocument($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/cancel', [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX cancel document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reject a document in DIDOX
     * @param string $docId DIDOX document ID
     * @param string $comment Rejection reason
     * @param string $userKey User authentication key
     * @return array
     */
    public function rejectDocument($docId, $comment, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];

            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }

            $data = ['comment' => $comment];
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/reject', $data, $headers);

            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];

        } catch (\Exception $e) {
            Yii::error('DIDOX reject document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate user with E-IMZO signature via DIDOX API
     * @param string $taxId
     * @param string $signature
     * @return array
     */
    public function authenticateWithEimzo($taxId, $signature)
    {
        try {
            // Validate input parameters
            if (empty($taxId) || empty($signature)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID and signature are required'
                ];
            }
            
            // Validate tax ID format (should be 9 digits)
            if (!preg_match('/^\d{9}$/', $taxId)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID must be 9 digits'
                ];
            }
            
            // Prepare request data according to DIDOX API documentation
            $requestData = [
                'signature' => $signature
            ];
            
            // Make request to DIDOX authentication endpoint
            $url = "/v1/auth/{$taxId}/token/ru";
            $response = $this->makeRequest('POST', $url, $requestData);
            
            if ($response['isOk'] && isset($response['data']['token'])) {
                return [
                    'success' => true,
                    'token' => $response['data']['token'],
                    'data' => $response['data'],
                    'message' => 'E-IMZO authentication successful'
                ];
            } else {
                // Handle different error scenarios
                $errorMessage = 'Authentication failed';
                
                if (isset($response['data']['message'])) {
                    $errorMessage = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } elseif (isset($response['data']['errors'])) {
                    $errorDetails = is_array($response['data']['errors']) 
                        ? implode(', ', array_values($response['data']['errors'])) 
                        : $response['data']['errors'];
                    $errorMessage = "Validation errors: {$errorDetails}";
                } elseif ($response['httpCode'] === 422) {
                    $errorMessage = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } elseif ($response['httpCode'] === 401) {
                    $errorMessage = 'Authentication failed - invalid credentials';
                } elseif ($response['httpCode'] === 404) {
                    $errorMessage = 'User not found or not registered in DIDOX';
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $response['httpCode'],
                    'data' => $response['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('E-IMZO authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Authentication service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate user with password via DIDOX API
     * According to API documentation: POST /v1/auth/:taxId/password/:locale
     * @param string $taxId
     * @param string $password
     * @param string $locale
     * @return array
     */
    public function authenticateWithPassword($taxId, $password, $locale = 'ru')
    {
        try {
            // Validate input parameters
            if (empty($taxId) || empty($password)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID and password are required'
                ];
            }
            
            // Validate tax ID format (should be 9 digits)
            if (!preg_match('/^\d{9}$/', $taxId)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID must be 9 digits'
                ];
            }
            
            // Prepare request data according to DIDOX API documentation
            $requestData = [
                'password' => $password
            ];
            
            // Make request to DIDOX password authentication endpoint
            $url = "/v1/auth/{$taxId}/password/{$locale}";
            $response = $this->makeRequest('POST', $url, $requestData);
            
            if ($response['isOk'] && isset($response['data']['token'])) {
                return [
                    'success' => true,
                    'token' => $response['data']['token'],
                    'data' => $response['data'],
                    'message' => 'Password authentication successful',
                    'related_companies' => $response['data']['related_companies'] ?? null,
                    'related_branches' => $response['data']['related_branches'] ?? null
                ];
            } else {
                // Handle different error scenarios
                $errorMessage = 'Authentication failed';
                
                if (isset($response['data']['message'])) {
                    $errorMessage = $response['data']['message'];
                } elseif (isset($response['data']['errors'])) {
                    $errorDetails = is_array($response['data']['errors']) 
                        ? implode(', ', array_values($response['data']['errors'])) 
                        : $response['data']['errors'];
                    $errorMessage = "Validation errors: {$errorDetails}";
                } elseif ($response['httpCode'] === 422) {
                    if (isset($response['data']['taxId']) && in_array('validation.exists', $response['data']['taxId'])) {
                        $errorMessage = 'User not registered in DIDOX system';
                    } else {
                        $errorMessage = 'Invalid password or user credentials';
                    }
                } elseif ($response['httpCode'] === 401) {
                    $errorMessage = 'Authentication failed - invalid password';
                } elseif ($response['httpCode'] === 404) {
                    $errorMessage = 'User not found or not registered in DIDOX';
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $response['httpCode'],
                    'data' => $response['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('Password authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Authentication service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Login to company as individual person via DIDOX API
     * According to API documentation: POST /v1/auth/company/:companyTaxId/login/:locale
     * Requires individual's token first (from authenticateWithPassword or authenticateWithEimzo)
     * @param string $companyTaxId
     * @param string $individualToken
     * @param string $locale
     * @return array
     */
    public function loginToCompany($companyTaxId, $individualToken, $locale = 'ru')
    {
        try {
            // Validate input parameters
            if (empty($companyTaxId) || empty($individualToken)) {
                return [
                    'success' => false,
                    'error' => 'Company Tax ID and individual token are required'
                ];
            }
            
            // Validate company tax ID format (should be 9 digits)
            if (!preg_match('/^\d{9}$/', $companyTaxId)) {
                return [
                    'success' => false,
                    'error' => 'Company Tax ID must be 9 digits'
                ];
            }
            
            // Prepare headers with individual's token
            $headers = [
                'Content-Type: application/json',
                'user-key: ' . $individualToken
            ];
            
            // Make request to DIDOX company login endpoint
            $url = "/v1/auth/company/{$companyTaxId}/login/{$locale}";
            $response = $this->makeRequestWithHeaders('POST', $url, [], $headers);
            
            if ($response['isOk'] && isset($response['data']['token'])) {
                return [
                    'success' => true,
                    'token' => $response['data']['token'],
                    'data' => $response['data'],
                    'message' => 'Company login successful',
                    'permissions' => $response['data']['permissions'] ?? null
                ];
            } else {
                // Handle different error scenarios
                $errorMessage = 'Company login failed';
                
                if (isset($response['data']['message'])) {
                    $errorMessage = $response['data']['message'];
                } elseif (isset($response['data']['errors'])) {
                    $errorDetails = is_array($response['data']['errors']) 
                        ? implode(', ', array_values($response['data']['errors'])) 
                        : $response['data']['errors'];
                    $errorMessage = "Validation errors: {$errorDetails}";
                } elseif ($response['httpCode'] === 422) {
                    if (isset($response['data']['taxId']) && in_array('validation.exists', $response['data']['taxId'])) {
                        $errorMessage = 'Company not found or not accessible';
                    } else {
                        $errorMessage = 'No permission to access this company';
                    }
                } elseif ($response['httpCode'] === 401) {
                    $errorMessage = 'Authentication failed - invalid or expired individual token';
                } elseif ($response['httpCode'] === 403) {
                    $errorMessage = 'Access denied - no permission to login to this company';
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $response['httpCode'],
                    'data' => $response['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('Company login error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Company login service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user profile data from DIDOX
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getUserProfile($userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('GET', '/v1/profile', [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format error message from DIDOX API response
     * @param mixed $error - Error response (can be string or array)
     * @return string
     */
    private function formatErrorMessage($error)
    {
        if (is_array($error)) {
            return json_encode($error);
        }
        return (string)$error;
    }

    /**
     * Get incoming document for signing (owner=0)
     * According to DIDOX documentation: GET /v1/documents/{doc_id}?owner=0
     * Returns the toSign value which contains sender's signature in base64
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getIncomingDocumentForSigning($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // For incoming documents, use owner=0 to get toSign value
            $response = $this->makeRequestWithHeaders('GET', '/v1/documents/' . $docId . '?owner=0', [], $headers);
            
            error_log('getIncomingDocumentForSigning response:');
            error_log(json_encode($response));
            
            if ($response['isOk']) {
                // Extract toSign value from DIDOX response
                $toSignValue = null;
                if (isset($response['data']['toSign'])) {
                    $toSignValue = $response['data']['toSign'];
                } elseif (isset($response['data']['data']['toSign'])) {
                    $toSignValue = $response['data']['data']['toSign'];
                } elseif (is_string($response['data'])) {
                    // Sometimes DIDOX returns the toSign value directly as a string
                    $toSignValue = $response['data'];
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'toSign' => $toSignValue,
                        'original_response' => $response['data']
                    ],
                    'httpCode' => $response['httpCode'],
                    'debug' => [
                        'endpoint' => '/v1/documents/' . $docId . '?owner=0',
                        'method' => 'GET',
                        'toSign_found' => !empty($toSignValue),
                        'toSign_length' => $toSignValue ? strlen($toSignValue) : 0
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'data' => $response['data'],
                    'httpCode' => $response['httpCode'],
                    'error' => !$response['isOk'] && isset($response['data']['error']) ? 
                        $this->formatErrorMessage($response['data']['error']) : null,
                    'debug' => [
                        'endpoint' => '/v1/documents/' . $docId . '?owner=0',
                        'method' => 'GET'
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Accept/Sign incoming document 
     * @param string $docId - Document ID
     * @param string $signature - Final signature with timestamp
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function acceptIncomingDocument($docId, $signature, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $requestData = [
                'signature' => $signature
            ];
            
            // Use the accept endpoint for incoming documents
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/sign', $requestData, $headers);
            
            error_log('acceptIncomingDocument');
            error_log(json_encode($response));
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? 
                    $this->formatErrorMessage($response['data']['error']) : null,
                'debug' => [
                    'endpoint' => '/v1/documents/' . $docId . '/sign',
                    'method' => 'POST',
                    'request_data' => $requestData
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make HTTP request with custom headers
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    private function makeRequestWithHeaders($method, $url, $data = [], $headers = [])
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE && $response !== false && $response !== '') {
            $decodedResponse = $response;
        }
        
        return [
            'httpCode' => $httpCode,
            'data' => $decodedResponse,
            'isOk' => $httpCode >= 200 && $httpCode < 300,
            'debug' => [
                'request_url' => $this->baseUrl . $url,
                'request_method' => $method,
                'request_headers' => $headers,
                'request_body' => !empty($data) ? json_encode($data, JSON_PRETTY_PRINT) : null,
                'response_raw' => $response,
                'curl_error' => $error
            ]
        ];
    }

    /**
     * Get linked ИКПУ codes for current profile
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getProfileProductClassCodes($userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('GET', '/v1/profile/productClassCodes', [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get profile product class codes error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add ИКПУ code to current profile
     * @param array $classData - Class code data
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function addProfileProductClass($classData, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('POST', '/v1/profile/productClasses', $classData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX add profile product class error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove ИКПУ code from current profile
     * @param string $classCode - Class code to remove
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function removeProfileProductClass($classCode, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('DELETE', '/v1/profile/productClasses/' . urlencode($classCode), [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX remove profile product class error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search available ИКПУ codes
     * @param int $page - Page number
     * @param string $lang - Language (ru/uz)
     * @param string $search - Search query
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function searchProductClasses($page = 1, $lang = 'ru', $search = '', $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $params = [
                'page' => $page,
                'lang' => $lang
            ];
            
            if (!empty($search)) {
                $params['search'] = $search;
            }
            
            $queryString = http_build_query($params);
            $endpoint = '/v1/profile/productClasses/?' . $queryString;
            
            $response = $this->makeRequestWithHeaders('GET', $endpoint, [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX search product classes error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check specific ИКПУ code by its number
     * @param string $classCode - ИКПУ class code (17 digits)
     * @param string $lang - Language (ru/uz)
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function checkProductClassByCode($classCode, $lang = 'ru', $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // Use search endpoint with exact code match
            $params = [
                'page' => 1,
                'lang' => $lang,
                'search' => $classCode
            ];
            
            $queryString = http_build_query($params);
            $endpoint = '/v1/profile/productClasses/?' . $queryString;
            
            $response = $this->makeRequestWithHeaders('GET', $endpoint, [], $headers);
            
            if ($response['isOk']) {
                $responseData = $response['data'] ?? [];
                $searchResults = $responseData['data'] ?? $responseData ?? [];
                
                // Find exact match by classCode
                $exactMatch = null;
                if (is_array($searchResults)) {
                    foreach ($searchResults as $result) {
                        $resultCode = $result['classCode'] ?? $result['code'] ?? '';
                        if ($resultCode === $classCode) {
                            $exactMatch = $result;
                            break;
                        }
                    }
                }
                
                if ($exactMatch) {
                    return [
                        'success' => true,
                        'data' => $exactMatch,
                        'httpCode' => $response['httpCode']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'ИКПУ код не найден',
                        'httpCode' => 404
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'data' => $response['data'],
                    'httpCode' => $response['httpCode'],
                    'error' => isset($response['data']['error']) ? $response['data']['error'] : 'Ошибка запроса DIDOX API'
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('DIDOX check product class by code error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 
