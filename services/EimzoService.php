<?php

namespace app\services;

use Yii;
use app\models\Log;

/**
 * Direct E-IMZO Server integration service.
 *
 * Communicates with a local e-imzo-server instance (not through Didox).
 * Endpoints reference: https://github.com/qo0p/e-imzo-doc
 *
 * Flow:
 *  1. Frontend calls /frontend/challenge to get a random challenge
 *  2. User signs the challenge with E-IMZO desktop app (WebSocket on wss://127.0.0.1:64443)
 *  3. Frontend attaches timestamp via /frontend/timestamp/pkcs7
 *  4. Backend verifies the signed+timestamped PKCS#7 via /backend/auth
 */
class EimzoService
{
    private const MOBILE_SIGN_CACHE_PREFIX = 'eimzo.mobile.sign.';

    private string $serverUrl;

    public function __construct()
    {
        $this->serverUrl = rtrim(
            Yii::$app->params['eimzo']['serverUrl'] ?? 'http://127.0.0.1:8080',
            '/'
        );
    }

    // ------------------------------------------------------------------
    // Frontend-accessible endpoints (proxied through our API)
    // ------------------------------------------------------------------

    /**
     * Request a challenge string from E-IMZO server.
     *
     * @return array{success: bool, challenge?: string, ttl?: int, error?: string}
     */
    public function getChallenge(): array
    {
        $response = $this->get('/frontend/challenge');

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to get challenge',
            ];
        }

        return [
            'success' => true,
            'challenge' => $response['challenge'],
            'ttl' => $response['ttl'] ?? 120,
        ];
    }

    /**
     * Calculate an E-IMZO digest for mobile deeplink payload generation.
     *
     * @param string $text Plain text to hash
     * @param string $userIp Client IP for X-Real-IP header
     * @return array{success: bool, digestHex?: string, error?: string}
     */
    public function digest(string $text, string $userIp): array
    {
        $response = $this->post('/backend/digest', $text, [
            'Content-Type: application/text',
            'X-Real-IP: ' . $userIp,
        ]);

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to calculate digest',
            ];
        }

        return [
            'success' => true,
            'digestHex' => $response['digestHex'] ?? null,
        ];
    }

    /**
     * Attach a timestamp to a PKCS#7 document.
     *
     * @param string $pkcs7b64 Base64-encoded PKCS#7 document
     * @param string $userIp   Client IP for X-Real-IP header
     * @return array{success: bool, pkcs7b64?: string, signers?: array, error?: string}
     */
    public function attachTimestamp(string $pkcs7b64, string $userIp): array
    {
        $response = $this->post('/frontend/timestamp/pkcs7', $pkcs7b64, [
            'Content-Type: application/text',
            'X-Real-IP: ' . $userIp,
        ]);

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $response['message'] ?? 'Failed to attach timestamp',
                'status' => $response['status'] ?? null,
            ];
        }

        return [
            'success' => true,
            'pkcs7b64' => $response['pkcs7b64'],
            'signers' => $response['timestampedSignerList'] ?? [],
        ];
    }

    // ------------------------------------------------------------------
    // Backend-only endpoints (must be network-restricted)
    // ------------------------------------------------------------------

    /**
     * Authenticate a user by verifying their signed challenge.
     *
     * @param string $pkcs7b64 Base64-encoded PKCS#7 containing the signed challenge
     * @param string $userIp   Client IP for X-Real-IP header
     * @return array{success: bool, certificate?: array, error?: string, status?: int}
     */
    public function authenticate(string $pkcs7b64, string $userIp): array
    {
        $response = $this->post('/backend/auth', $pkcs7b64, [
            'Content-Type: application/text',
            'X-Real-IP: ' . $userIp,
        ]);

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $this->mapAuthError($response['status'] ?? 0, $response['message'] ?? ''),
                'status' => $response['status'] ?? null,
            ];
        }

        $cert = $response['subjectCertificateInfo'] ?? [];

        return [
            'success' => true,
            'certificate' => [
                'serialNumber' => $cert['serialNumber'] ?? null,
                'subjectName' => $cert['subjectName'] ?? [],
                'validFrom' => $cert['validFrom'] ?? null,
                'validTo' => $cert['validTo'] ?? null,
            ],
        ];
    }

    /**
     * Verify an attached PKCS#7 signature with timestamp.
     *
     * @param string $pkcs7b64 Base64-encoded PKCS#7 with timestamp
     * @param string $userIp   Client IP
     * @return array{success: bool, pkcs7Info?: array, error?: string}
     */
    public function verifyPkcs7(string $pkcs7b64, string $userIp): array
    {
        $response = $this->post('/backend/pkcs7/verify/attached', $pkcs7b64, [
            'Content-Type: application/text',
            'X-Real-IP: ' . $userIp,
        ]);

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $response['message'] ?? 'PKCS#7 verification failed',
                'status' => $response['status'] ?? null,
            ];
        }

        return [
            'success' => true,
            'pkcs7Info' => $response['pkcs7Info'] ?? [],
        ];
    }

    /**
     * Sign arbitrary data server-side using a PFX certificate.
     *
     * Uses the local signer service (same as Didox flow) to produce
     * a PKCS#7 signature, then optionally timestamps it via Didox.
     *
     * @param string $data        Data to sign (e.g. document hash, JSON payload)
     * @param bool   $addTimestamp Whether to attach a timestamp after signing
     * @return array{success: bool, pkcs7b64?: string, error?: string}
     */
    public function signData(string $data, bool $addTimestamp = true): array
    {
        $settings = \app\models\Settings::find()
            ->where(['type' => ['didox_pfx_path', 'didox_pfx_password', 'didox_signer_url']])
            ->all();
        $config = \yii\helpers\ArrayHelper::map($settings, 'type', 'content');

        $pfxPath = $config['didox_pfx_path'] ?? '';
        $password = $config['didox_pfx_password'] ?? '';
        $signerUrl = $config['didox_signer_url'] ?? 'http://eimzo-signer:8080/generate';

        $appPfxPath = $this->resolveAppPfxPath($pfxPath);

        if (empty($appPfxPath) || !file_exists($appPfxPath)) {
            return ['success' => false, 'error' => 'PFX certificate not configured'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $signerUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'pfxFilePath' => $pfxPath,
                'password' => $password,
                'alias' => '',
                'data' => $data,
                'attached' => true,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            return ['success' => false, 'error' => 'Signer service failed: ' . ($error ?: "HTTP $httpCode")];
        }

        $decoded = json_decode($result, true);
        $pkcs7b64 = $decoded['pkcs7'] ?? $decoded['pkcs7b64'] ?? null;
        $signatureHex = $decoded['signatureHex'] ?? $decoded['signature'] ?? null;

        if (empty($pkcs7b64)) {
            return ['success' => false, 'error' => 'Signer returned empty PKCS#7'];
        }

        if ($addTimestamp) {
            if (empty($signatureHex)) {
                return ['success' => false, 'error' => 'Signer returned empty signature hex'];
            }

            $didoxService = new DidoxService();
            $tsResult = $didoxService->createTimestamp($pkcs7b64, $signatureHex);
            if (!$tsResult['success'] || empty($tsResult['data']['timeStampTokenB64'])) {
                return ['success' => false, 'error' => 'Didox timestamp failed'];
            }

            $pkcs7b64 = $tsResult['data']['timeStampTokenB64'];
        }

        return ['success' => true, 'pkcs7b64' => $pkcs7b64];
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

    /**
     * Ping the E-IMZO server to check availability.
     *
     * @return array{success: bool, version?: string, serverTime?: string, error?: string}
     */
    public function ping(): array
    {
        $response = $this->get('/ping');

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        return [
            'success' => true,
            'version' => $response['version'] ?? null,
            'serverTime' => $response['serverTime'] ?? null,
            'vpnKeyInfo' => $response['vpnKeyInfo'] ?? null,
        ];
    }

    /**
     * Get E-IMZO server info including trusted certificates.
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function info(): array
    {
        $response = $this->get('/info');

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        return ['success' => true, 'data' => $response];
    }

    // ------------------------------------------------------------------
    // Mobile endpoints (deeplink-based signing from phone)
    // ------------------------------------------------------------------

    /**
     * Initiate mobile authentication.
     *
     * Returns siteId, documentId, and challenge for the mobile app
     * to build a QR code and open the E-IMZO deeplink.
     *
     * @return array{success: bool, siteId?: string, documentId?: string, challenge?: string, error?: string}
     */
    public function mobileAuth(): array
    {
        $response = $this->post('/frontend/mobile/auth');

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $this->mapMobileError($response['status'] ?? 0, $response['message'] ?? ''),
                'status' => $response['status'] ?? null,
            ];
        }

        return [
            'success' => true,
            'siteId' => $response['siteId'] ?? '',
            'documentId' => $response['documentId'] ?? '',
            'challenge' => $response['challange'] ?? '',
        ];
    }

    /**
     * Initiate mobile document signing.
     *
     * Returns siteId and documentId for the mobile app to sign a document.
     *
     * @return array{success: bool, siteId?: string, documentId?: string, error?: string}
     */
    public function mobileSign(): array
    {
        $response = $this->post('/frontend/mobile/sign');

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $this->mapMobileError($response['status'] ?? 0, $response['message'] ?? ''),
                'status' => $response['status'] ?? null,
            ];
        }

        return [
            'success' => true,
            'siteId' => $response['siteId'] ?? '',
            'documentId' => $response['documentId'] ?? '',
        ];
    }

    /**
     * Poll mobile operation status.
     *
     * Status values:
     *  1 = complete (PKCS#7 uploaded by mobile service)
     *  2 = pending (awaiting signature)
     * -2 = documentId expired
     *
     * @param string $documentId
     * @return array{success: bool, status?: int, error?: string}
     */
    public function mobileStatus(string $documentId): array
    {
        $response = $this->post(
            '/frontend/mobile/status',
            'documentId=' . urlencode($documentId),
            ['Content-Type: application/x-www-form-urlencoded']
        );

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        $status = $response['status'] ?? -99;

        if ($status === -2) {
            return ['success' => false, 'error' => 'DocumentID expired', 'status' => -2];
        }

        return [
            'success' => true,
            'status' => $status, // 1=complete, 2=pending
        ];
    }

    /**
     * Get mobile authentication result after status=1.
     *
     * @param string $documentId
     * @param string $userIp Client IP
     * @return array{success: bool, certificate?: array, error?: string}
     */
    public function mobileAuthenticate(string $documentId, string $userIp): array
    {
        $response = $this->get('/backend/mobile/authenticate/' . urlencode($documentId), [
            'X-Real-IP: ' . $userIp,
        ]);

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $this->mapAuthError($response['status'] ?? 0, $response['message'] ?? ''),
                'status' => $response['status'] ?? null,
            ];
        }

        $cert = $response['subjectCertificateInfo'] ?? [];

        return [
            'success' => true,
            'certificate' => [
                'serialNumber' => $cert['serialNumber'] ?? null,
                'subjectName' => $cert['subjectName'] ?? [],
                'validFrom' => $cert['validFrom'] ?? null,
                'validTo' => $cert['validTo'] ?? null,
            ],
        ];
    }

    /**
     * Verify a mobile-signed document after status=1.
     *
     * @param string $documentId
     * @param string $documentB64 Base64-encoded document that was signed
     * @param string $userIp Client IP
     * @return array{success: bool, certificate?: array, pkcs7Attached?: string, verificationInfo?: array, error?: string}
     */
    public function mobileVerify(string $documentId, string $documentB64, string $userIp): array
    {
        $body = 'documentId=' . urlencode($documentId) . '&document=' . urlencode($documentB64);
        $response = $this->post('/backend/mobile/verify', $body, [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Real-IP: ' . $userIp,
        ]);

        if ($response === null) {
            return ['success' => false, 'error' => 'E-IMZO server is unreachable'];
        }

        if (($response['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'error' => $this->mapAuthError($response['status'] ?? 0, $response['message'] ?? ''),
                'status' => $response['status'] ?? null,
            ];
        }

        $cert = $response['subjectCertificateInfo'] ?? [];

        return [
            'success' => true,
            'certificate' => [
                'serialNumber' => $cert['serialNumber'] ?? null,
                'subjectName' => $cert['subjectName'] ?? [],
                'validFrom' => $cert['validFrom'] ?? null,
                'validTo' => $cert['validTo'] ?? null,
            ],
            'pkcs7Attached' => $response['pkcs7Attached'] ?? null,
            'verificationInfo' => $response['verificationInfo'] ?? [],
        ];
    }

    /**
     * Persist mapping between E-IMZO mobile documentId and the local document payload.
     * This allows us to later answer which exact document was signed.
     *
     * @param string $documentId
     * @param string $documentB64 Base64-encoded document payload
     * @param array $meta Optional context (user_id, refs, source flow, etc.)
     * @return bool
     */
    public function storeMobileSignDocument(string $documentId, string $documentB64, array $meta = []): bool
    {
        $documentId = trim($documentId);
        $documentB64 = trim($documentB64);

        if ($documentId === '' || $documentB64 === '') {
            return false;
        }

        $payload = [
            'document_id' => $documentId,
            'document_b64' => $documentB64,
            'document_sha256' => hash('sha256', $documentB64),
            'meta' => $meta,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $timeout = (int) (Yii::$app->params['eimzo']['mobileStatusTimeout'] ?? 120);
        $ttl = max($timeout + 900, 1800); // keep a bit longer than mobile session

        $saved = Yii::$app->cache->set($this->mobileSignCacheKey($documentId), $payload, $ttl);

        if ($saved) {
            Log::log(
                'eimzo_mobile_sign',
                "Stored mobile sign mapping for documentId={$documentId}",
                [
                    'document_id' => $documentId,
                    'document_sha256' => $payload['document_sha256'],
                    'meta' => $meta,
                    'ttl' => $ttl,
                ],
                'info'
            );
        } else {
            Yii::warning("Failed to cache mobile sign mapping for documentId={$documentId}", 'eimzo');
        }

        return (bool) $saved;
    }

    /**
     * Retrieve previously stored mobile sign mapping by E-IMZO documentId.
     *
     * @param string $documentId
     * @return array|null
     */
    public function getMobileSignDocument(string $documentId): ?array
    {
        $documentId = trim($documentId);
        if ($documentId === '') {
            return null;
        }

        $data = Yii::$app->cache->get($this->mobileSignCacheKey($documentId));
        if (!is_array($data) || empty($data['document_b64'])) {
            return null;
        }

        return $data;
    }

    private function mobileSignCacheKey(string $documentId): string
    {
        return self::MOBILE_SIGN_CACHE_PREFIX . $documentId;
    }

    // ------------------------------------------------------------------
    // Certificate helpers
    // ------------------------------------------------------------------

    /**
     * Extract INN (tax ID) from E-IMZO certificate subject name.
     *
     * The OID 1.2.860.3.16.1.2 contains the 14-digit INN/PINFL.
     *
     * @param array $subjectName Certificate subjectName map
     * @return string|null
     */
    public function extractInn(array $subjectName): ?string
    {
        return $subjectName['1.2.860.3.16.1.2'] ?? null;
    }

    /**
     * Extract common name (CN) from certificate subject name.
     *
     * @param array $subjectName Certificate subjectName map
     * @return string|null
     */
    public function extractCommonName(array $subjectName): ?string
    {
        return $subjectName['CN'] ?? null;
    }

    // ------------------------------------------------------------------
    // HTTP helpers
    // ------------------------------------------------------------------

    private function post(string $path, $body = '', array $headers = []): ?array
    {
        $ch = curl_init();

        $defaultHeaders = ['Host: ' . parse_url($this->serverUrl, PHP_URL_HOST)];
        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->serverUrl . $path,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 500) {
            Yii::error("E-IMZO request failed: $path — $error (HTTP $httpCode)", 'eimzo');
            return null;
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            Yii::error("E-IMZO invalid JSON from $path: " . substr($result, 0, 500), 'eimzo');
            return null;
        }

        return $decoded;
    }

    private function get(string $path, array $headers = []): ?array
    {
        $ch = curl_init();

        $defaultHeaders = ['Host: ' . parse_url($this->serverUrl, PHP_URL_HOST)];
        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->serverUrl . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Yii::error("E-IMZO GET failed: $path — $error", 'eimzo');
            return null;
        }

        return json_decode($result, true);
    }

    private function mapAuthError(int $status, string $message): string
    {
        $errors = [
            -1 => 'Certificate status verification failed',
            -5 => 'Signature timestamp is invalid',
            -10 => 'Invalid digital signature',
            -11 => 'Invalid certificate',
            -12 => 'Certificate was expired at signing time',
            -20 => 'Challenge not found or expired',
        ];

        return $errors[$status] ?? ($message ?: "Unknown error (status: $status)");
    }

    private function mapMobileError(int $status, string $message): string
    {
        $errors = [
            -1 => 'Redis connection error (mobile storage unavailable)',
            -2 => 'DocumentID not found or expired',
        ];

        return $errors[$status] ?? ($message ?: "Mobile error (status: $status)");
    }
}
