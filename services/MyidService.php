<?php

namespace app\services;

use Yii;
use app\models\user\User;
use app\models\user\UserMyid;

/**
 * MyID Service - Handles communication with MyID API
 * 
 * Supports both SDK New Flow (Mobile) and SDK for Web
 * Documentation: https://docs.identity.example.com/#/en/sdknew
 */
class MyidService
{
    // API URLs
    const PROD_URL = 'https://identity.example.com';
    const SANDBOX_URL = 'https://sandbox.identity.example.com';

    // OAuth endpoints
    const OAUTH_AUTHORIZE = '/api/v1/oauth2/authorization';
    const OAUTH_ACCESS_TOKEN = '/api/v1/oauth2/access-token';
    const USER_INFO = '/api/v1/users/me';

    // SDK endpoints
    const SDK_INIT = '/api/v1/sdk/init';

    private $clientId;
    private $clientSecret;
    private $baseUrl;
    private $redirectUri;

    /**
     * Constructor
     */
    public function __construct()
    {
        $config = Yii::$app->params['myid'] ?? [];
        
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';
        
        // Use sandbox URL if configured, otherwise production
        $useSandbox = $config['sandbox'] ?? false;
        $this->baseUrl = $useSandbox ? self::SANDBOX_URL : ($config['base_url'] ?? self::PROD_URL);
    }

    /**
     * Generate SDK hash for mobile SDK initialization
     * @param string $timestamp - Timestamp in milliseconds
     * @return array
     */
    public function generateSdkHash($timestamp = null)
    {
        if (!$timestamp) {
            $timestamp = round(microtime(true) * 1000);
        }

        $payload = $this->clientId . $timestamp . $this->clientSecret;
        $hash = hash('sha256', $payload);

        return [
            'client_id' => $this->clientId,
            'timestamp' => $timestamp,
            'hash' => $hash,
        ];
    }

    /**
     * Get Web authorization URL for redirect
     * @param string|null $state - State parameter for CSRF protection
     * @param string|null $redirectUri - Override redirect URI
     * @param string $scope - OAuth scope
     * @return array
     */
    public function getWebAuthUrl($state = null, $redirectUri = null, $scope = 'profile')
    {
        if (!$state) {
            $state = Yii::$app->security->generateRandomString(32);
        }

        $redirectUri = $redirectUri ?: $this->redirectUri;

        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'state' => $state,
        ];

        $authUrl = $this->baseUrl . self::OAUTH_AUTHORIZE . '?' . http_build_query($params);

        return [
            'auth_url' => $authUrl,
            'state' => $state,
        ];
    }

    /**
     * Exchange authorization code for access token
     * @param string $code - Authorization code from MyID
     * @param string|null $redirectUri - Redirect URI used in authorization
     * @return array
     */
    public function exchangeCodeForToken($code, $redirectUri = null)
    {
        $redirectUri = $redirectUri ?: $this->redirectUri;

        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
        ];

        try {
            $response = $this->makeRequest('POST', self::OAUTH_ACCESS_TOKEN, $data);

            if (isset($response['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $response['access_token'],
                    'token_type' => $response['token_type'] ?? 'Bearer',
                    'expires_in' => $response['expires_in'] ?? null,
                    'refresh_token' => $response['refresh_token'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to get access token',
                'error_description' => $response['error_description'] ?? null,
            ];
        } catch (\Exception $e) {
            Yii::error('MyID token exchange error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user data from MyID using access token
     * @param string $accessToken - Access token
     * @return array
     */
    public function getUserData($accessToken)
    {
        try {
            $response = $this->makeRequest('GET', self::USER_INFO, [], [
                'Authorization: Bearer ' . $accessToken,
            ]);

            if (isset($response['pinfl']) || isset($response['profile'])) {
                // Normalize response structure
                $profile = $response['profile'] ?? $response;
                
                return [
                    'success' => true,
                    'data' => $profile,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to get user data',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID get user data error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete verification flow - exchange code and get user data
     * @param string $code - Authorization code
     * @param int|null $userId - User ID to link verification to
     * @return array
     */
    public function verifyAndSaveUser($code, $userId = null)
    {
        // Step 1: Exchange code for token
        $tokenResult = $this->exchangeCodeForToken($code);
        if (!$tokenResult['success']) {
            return [
                'success' => false,
                'error' => $tokenResult['error'],
                'step' => 'token_exchange',
            ];
        }

        // Step 2: Get user data
        $userResult = $this->getUserData($tokenResult['access_token']);
        if (!$userResult['success']) {
            return [
                'success' => false,
                'error' => $userResult['error'],
                'step' => 'user_data',
            ];
        }

        $myidData = $userResult['data'];

        // Step 3: Check if PINFL is already linked to another user
        $existingMyid = UserMyid::findByPinfl($myidData['pinfl']);
        if ($existingMyid && $existingMyid->user_id && $userId && $existingMyid->user_id != $userId) {
            return [
                'success' => false,
                'error' => 'This PINFL is already linked to another account',
                'step' => 'pinfl_check',
            ];
        }

        // Step 4: Save verification data
        $userMyid = UserMyid::createFromMyidData($myidData, $userId);
        if (!$userMyid) {
            return [
                'success' => false,
                'error' => 'Failed to save verification data',
                'step' => 'save',
            ];
        }

        // Step 5: Get user if exists
        $user = $userId ? User::findOne($userId) : null;

        return [
            'success' => true,
            'verification' => $userMyid->toApiArray(),
            'user' => $user,
            'myid_data' => $myidData,
        ];
    }

    /**
     * Register new user via MyID
     * @param string $code - Authorization code
     * @param string|null $phone - Optional phone number
     * @return array
     */
    public function registerWithMyid($code, $phone = null)
    {
        // Step 1: Exchange code for token
        $tokenResult = $this->exchangeCodeForToken($code);
        if (!$tokenResult['success']) {
            return [
                'success' => false,
                'error' => $tokenResult['error'],
                'step' => 'token_exchange',
            ];
        }

        // Step 2: Get user data
        $userResult = $this->getUserData($tokenResult['access_token']);
        if (!$userResult['success']) {
            return [
                'success' => false,
                'error' => $userResult['error'],
                'step' => 'user_data',
            ];
        }

        $myidData = $userResult['data'];

        // Step 3: Check if PINFL is already registered
        $existingMyid = UserMyid::findByPinfl($myidData['pinfl']);
        if ($existingMyid && $existingMyid->user_id) {
            // Return existing user
            $user = User::findOne($existingMyid->user_id);
            if ($user) {
                return [
                    'success' => true,
                    'is_new' => false,
                    'user' => $user,
                    'verification' => $existingMyid->toApiArray(),
                    'message' => 'User already registered with this PINFL',
                ];
            }
        }

        // Step 4: Create new user
        $user = new User();
        $user->scenario = User::USER_SIGNUP;
        $user->role = User::ROLE_USER;
        $user->name = $myidData['first_name'] ?? $myidData['firstNameLatin'] ?? '';
        $user->lastname = $myidData['last_name'] ?? $myidData['lastNameLatin'] ?? '';
        $user->middlename = $myidData['middle_name'] ?? $myidData['middleNameLatin'] ?? '';
        $user->phone = $phone ?: ($myidData['phone'] ?? '');
        $user->birthday = $myidData['birth_date'] ?? $myidData['birthDate'] ?? null;
        $user->gender = null;
        
        // Gender mapping
        if (isset($myidData['gender'])) {
            $gender = strtolower($myidData['gender']);
            if ($gender === 'male' || $gender === '1' || $gender === 1) {
                $user->gender = 1;
            } elseif ($gender === 'female' || $gender === '2' || $gender === 2) {
                $user->gender = 2;
            }
        }

        $user->token = Yii::$app->security->generateRandomString(32);
        $user->status = 1;
        $user->myid_verified = 1;

        // Start transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$user->save(false)) {
                throw new \Exception('Failed to create user: ' . json_encode($user->errors));
            }

            // Step 5: Save MyID verification data
            $userMyid = UserMyid::createFromMyidData($myidData, $user->id);
            if (!$userMyid) {
                throw new \Exception('Failed to save verification data');
            }

            $transaction->commit();

            return [
                'success' => true,
                'is_new' => true,
                'user' => $user,
                'verification' => $userMyid->toApiArray(),
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('MyID registration error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'step' => 'registration',
            ];
        }
    }

    /**
     * Get verification status for user
     * @param int $userId
     * @return array
     */
    public function getVerificationStatus($userId)
    {
        $userMyid = UserMyid::findByUserId($userId);
        
        if (!$userMyid) {
            return [
                'verified' => false,
                'status' => null,
                'message' => 'Not verified',
            ];
        }

        return [
            'verified' => $userMyid->isVerified(),
            'status' => $userMyid->verification_status,
            'status_label' => $userMyid->getVerificationStatusLabel(),
            'verified_at' => $userMyid->verified_at,
            'pinfl' => $userMyid->pinfl,
            'full_name' => $userMyid->getFullName(),
        ];
    }

    /**
     * Make HTTP request to MyID API
     * @param string $method - HTTP method
     * @param string $endpoint - API endpoint
     * @param array $data - Request data
     * @param array $headers - Additional headers
     * @return array
     */
    private function makeRequest($method, $endpoint, $data = [], $headers = [])
    {
        $url = $this->baseUrl . $endpoint;

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }

        Yii::info("MyID API Request: {$method} {$endpoint} - Response Code: {$httpCode}", __METHOD__);

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['error'] ?? $decodedResponse['message'] ?? 'HTTP Error ' . $httpCode;
            throw new \Exception($errorMessage);
        }

        return $decodedResponse ?: [];
    }

    /**
     * Validate SDK callback data
     * @param array $data - Callback data from SDK
     * @return bool
     */
    public function validateSdkCallback($data)
    {
        if (empty($data['code'])) {
            return false;
        }

        // Additional validation can be added here
        // e.g., validate signature, timestamp, etc.

        return true;
    }

    /**
     * Get client ID for SDK initialization
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Check if service is configured
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
}
