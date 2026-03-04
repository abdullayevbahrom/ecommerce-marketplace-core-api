<?php

namespace app\services;

use Yii;
use app\models\user\User;
use app\models\user\UserMyid;

/**
 * MyID Service - Handles communication with MyID API
 *
 * Supports three flows:
 * 1. WebSDK (session-based browser verification)
 * 2. Mobile SDK Legacy (code exchange via OAuth)
 * 3. SDK New Flow (mobile-first session-based with extended fields)
 *
 * WebSDK docs: https://docs.identity.example.com/#/en/websdk
 * SDK New Flow docs: https://docs.identity.example.com/#/en/sdknew
 */
class MyidService
{
    // API base URLs
    const PROD_URL = 'https://identity.example.com';
    const SANDBOX_URL = 'https://api.devid.example.com';

    // Web SDK URLs
    const WEB_PROD_URL = 'https://web.identity.example.com';
    const WEB_SANDBOX_URL = 'https://web.devid.example.com';

    // WebSDK / Legacy OAuth endpoints
    const OAUTH_ACCESS_TOKEN = '/api/v1/oauth2/access-token';
    const OAUTH_REFRESH_TOKEN = '/api/v1/oauth2/refresh-token';
    const USER_INFO = '/api/v1/users/me';
    const WEB_SESSIONS = '/api/v1/web/sessions';

    // SDK New Flow endpoints
    const SDK_ACCESS_TOKEN_ENDPOINT = '/api/v1/auth/clients/access-token';
    const SDK_CREATE_SESSION_ENDPOINT = '/api/v2/sdk/sessions';
    const SDK_GET_DATA_ENDPOINT = '/api/v1/sdk/data';
    const SDK_SESSION_STATUS_ENDPOINT = '/api/v1/sdk/sessions';

    private $clientId;
    private $clientSecret;
    private $clientHashId;
    private $baseUrl;
    private $webUrl;
    private $redirectUri;

    public function __construct()
    {
        $config = Yii::$app->params['myid'] ?? [];

        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->clientHashId = $config['client_hash_id'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';

        $useSandbox = $config['sandbox'] ?? false;
        $this->baseUrl = $useSandbox ? self::SANDBOX_URL : ($config['base_url'] ?? self::PROD_URL);
        $this->webUrl = $useSandbox ? self::WEB_SANDBOX_URL : ($config['web_url'] ?? self::WEB_PROD_URL);
    }

    // =========================================================================
    // SDK New Flow Methods (Mobile)
    // =========================================================================

    /**
     * Get access token for SDK New Flow using client credentials.
     * POST {myid_host}/api/v1/auth/clients/access-token
     *
     * @return array ['success' => bool, 'access_token' => string, 'expires_in' => int]
     */
    public function getSdkAccessToken()
    {
        // Check cache first (token valid for ~7 days, cache with 5-min margin)
        $cacheKey = 'myid_sdk_access_token';
        $cached = Yii::$app->cache ? Yii::$app->cache->get($cacheKey) : false;
        if ($cached !== false) {
            return $cached;
        }

        try {
            $response = $this->makeRequest('POST', self::SDK_ACCESS_TOKEN_ENDPOINT, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (isset($response['access_token'])) {
                $result = [
                    'success' => true,
                    'access_token' => $response['access_token'],
                    'expires_in' => $response['expires_in'] ?? null,
                    'token_type' => $response['token_type'] ?? 'Bearer',
                ];

                // Cache token with 5-minute safety margin
                $ttl = ($response['expires_in'] ?? 604800) - 300;
                if ($ttl > 0 && Yii::$app->cache) {
                    Yii::$app->cache->set($cacheKey, $result, $ttl);
                }

                return $result;
            }

            return [
                'success' => false,
                'error' => $response['detail'] ?? $response['error'] ?? 'Failed to get access token',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID SDK access token error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a session for the mobile SDK (New Flow).
     * POST {myid_host}/api/v2/sdk/sessions
     *
     * @param array $params Optional: pinfl, pass_data, phone_number, birth_date, is_resident, threshold, reuid
     * @return array ['success' => bool, 'session_id' => string]
     */
    public function createSdkSession($params = [])
    {
        $tokenResult = $this->getSdkAccessToken();
        if (!$tokenResult['success']) {
            return [
                'success' => false,
                'error' => 'Access token failed: ' . ($tokenResult['error'] ?? 'Unknown'),
                'step' => 'access_token',
            ];
        }

        try {
            $body = [];
            $allowedParams = ['pinfl', 'pass_data', 'phone_number', 'birth_date', 'is_resident', 'threshold', 'reuid'];
            foreach ($allowedParams as $param) {
                if (isset($params[$param]) && $params[$param] !== null) {
                    $body[$param] = $params[$param];
                }
            }

            $response = $this->makeRequest('POST', self::SDK_CREATE_SESSION_ENDPOINT, $body, [
                'Authorization: Bearer ' . $tokenResult['access_token'],
            ]);

            if (isset($response['session_id'])) {
                return [
                    'success' => true,
                    'session_id' => $response['session_id'],
                ];
            }

            return [
                'success' => false,
                'error' => $response['detail'] ?? $response['error'] ?? 'No session_id in response',
                'step' => 'create_session',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID SDK create session error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'step' => 'create_session',
            ];
        }
    }

    /**
     * Retrieve user data using code from SDK New Flow.
     * GET {myid_host}/api/v1/sdk/data?code={code}
     *
     * @param string $code Authorization code from mobile SDK
     * @return array ['success' => bool, 'data' => array]
     */
    public function getSdkUserData($code)
    {
        $tokenResult = $this->getSdkAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $response = $this->makeRequest(
                'GET',
                self::SDK_GET_DATA_ENDPOINT . '?code=' . urlencode($code),
                [],
                ['Authorization: Bearer ' . $tokenResult['access_token']]
            );

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'reuid' => $response['reuid'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response['detail'] ?? $response['err'] ?? 'Failed to get user data',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID SDK get user data error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Session recovery - get session status if code was lost (SDK New Flow).
     * GET {myid_host}/api/v1/sdk/sessions/{session_id}
     *
     * @param string $sessionId
     * @return array ['success' => bool, 'code' => string|null, 'status' => string, 'attempts' => array]
     */
    public function getSessionStatus($sessionId)
    {
        $tokenResult = $this->getSdkAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $response = $this->makeRequest(
                'GET',
                self::SDK_SESSION_STATUS_ENDPOINT . '/' . urlencode($sessionId),
                [],
                ['Authorization: Bearer ' . $tokenResult['access_token']]
            );

            if (isset($response['status'])) {
                return [
                    'success' => true,
                    'code' => $response['code'] ?? null,
                    'status' => $response['status'],
                    'attempts' => $response['attempts'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response['detail'] ?? 'Failed to get session status',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID session status error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Complete SDK New Flow verification: get user data by code and save to user_myid.
     *
     * @param string $code Code from mobile SDK
     * @param int|null $userId User ID to link verification to
     * @return array
     */
    /**
     * Minimum face comparison threshold for verification to pass.
     */
    const MIN_COMPARISON_THRESHOLD = 0.5;

    public function verifyAndSaveSdk($code, $userId = null)
    {
        $result = $this->getSdkUserData($code);
        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'],
                'step' => 'get_user_data',
            ];
        }

        $myidData = $result['data'];
        $reuid = $result['reuid'];

        // Check comparison_value threshold
        $comparisonValue = $myidData['comparison_value'] ?? 0;
        if ($comparisonValue < self::MIN_COMPARISON_THRESHOLD) {
            Yii::warning("MyID comparison_value {$comparisonValue} below threshold " . self::MIN_COMPARISON_THRESHOLD, __METHOD__);
            return [
                'success' => false,
                'error' => 'Face comparison score too low (' . round($comparisonValue, 3) . '). Minimum required: ' . self::MIN_COMPARISON_THRESHOLD,
                'step' => 'comparison_check',
                'comparison_value' => $comparisonValue,
            ];
        }

        $profile = $myidData['profile'] ?? null;
        $commonData = $profile['common_data'] ?? [];
        $pinfl = $commonData['pinfl'] ?? null;

        if (empty($pinfl)) {
            return [
                'success' => false,
                'error' => 'MyID response missing PINFL',
                'step' => 'pinfl_check',
            ];
        }

        $existingMyid = UserMyid::findByPinfl($pinfl);
        if ($existingMyid && $existingMyid->user_id && $userId && $existingMyid->user_id != $userId) {
            return [
                'success' => false,
                'error' => 'This PINFL is already linked to another account',
                'step' => 'pinfl_check',
            ];
        }

        $userMyid = UserMyid::createFromSdkData($myidData, $reuid, $userId);
        if (!$userMyid) {
            return [
                'success' => false,
                'error' => 'Failed to save verification data',
                'step' => 'save',
            ];
        }

        return [
            'success' => true,
            'verification' => $userMyid->toApiArray(),
            'user_id' => $userMyid->user_id,
        ];
    }

    // =========================================================================
    // WebSDK Flow Methods
    // =========================================================================

    /**
     * Get client access token using client_credentials grant.
     * Required as first step for WebSDK session creation.
     *
     * @return array {success, access_token, ...} or {success: false, error}
     */
    public function getClientAccessToken()
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        try {
            $response = $this->makeRequest('POST', self::OAUTH_ACCESS_TOKEN, $data, [], 'form');

            if (isset($response['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $response['access_token'],
                    'token_type' => $response['token_type'] ?? 'Bearer',
                    'expires_in' => $response['expires_in'] ?? 3600,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to get client access token',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID client token error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a WebSDK verification session.
     *
     * @param string $externalId UUID for tracking this session
     * @param string $ipAddress Real client IP address (not proxy/CDN)
     * @param int $maxRetries Max verification retries allowed
     * @return array {success, session_id, ...} or {success: false, error}
     */
    public function createWebSession($externalId, $ipAddress, $maxRetries = 3)
    {
        $tokenResult = $this->getClientAccessToken();
        if (!$tokenResult['success']) {
            return [
                'success' => false,
                'error' => 'Failed to get client token: ' . ($tokenResult['error'] ?? ''),
                'step' => 'client_token',
            ];
        }

        $data = [
            'max_retries' => $maxRetries,
            'external_id' => $externalId,
            'ip_address' => $ipAddress,
        ];

        try {
            $response = $this->makeRequest('POST', self::WEB_SESSIONS, $data, [
                'Authorization: Bearer ' . $tokenResult['access_token'],
            ], 'json');

            if (isset($response['session_id'])) {
                return [
                    'success' => true,
                    'session_id' => $response['session_id'],
                    'external_id' => $externalId,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to create session',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID create session error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build the WebSDK URL for user redirect/iframe.
     *
     * @param string $sessionId Session ID from createWebSession()
     * @param array $params Additional URL params
     * @return string Full URL for web.identity.example.com
     */
    public function buildWebUrl($sessionId, $params = [])
    {
        $queryParams = array_merge([
            'session_id' => $sessionId,
        ], $params);

        return $this->webUrl . '/?' . http_build_query($queryParams);
    }

    /**
     * Get WebSDK session result (async status check).
     *
     * @param string $sessionId
     * @return array
     */
    public function getWebSessionResult($sessionId)
    {
        $tokenResult = $this->getClientAccessToken();
        if (!$tokenResult['success']) {
            return ['success' => false, 'error' => 'Failed to get client token'];
        }

        try {
            $response = $this->makeRequest('POST', self::WEB_SESSIONS . '/' . $sessionId . '/result', [], [
                'Authorization: Bearer ' . $tokenResult['access_token'],
            ], 'json');

            if (isset($response['attempts'])) {
                foreach ($response['attempts'] as $attempt) {
                    if (isset($attempt['result_code']) && $attempt['result_code'] == 1 && isset($attempt['auth_code'])) {
                        return [
                            'success' => true,
                            'status' => 'completed',
                            'auth_code' => $attempt['auth_code'],
                            'attempts' => $response['attempts'],
                        ];
                    }
                }

                return [
                    'success' => true,
                    'status' => 'pending',
                    'attempts' => $response['attempts'],
                ];
            }

            return [
                'success' => true,
                'status' => 'pending',
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            Yii::error('MyID session result error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Close a WebSDK session.
     *
     * @param string $sessionId
     * @return array
     */
    public function closeWebSession($sessionId)
    {
        $tokenResult = $this->getClientAccessToken();
        if (!$tokenResult['success']) {
            return ['success' => false, 'error' => 'Failed to get client token'];
        }

        try {
            $response = $this->makeRequest('POST', self::WEB_SESSIONS . '/' . $sessionId . '/client/close', ['code' => 3], [
                'Authorization: Bearer ' . $tokenResult['access_token'],
            ], 'json');

            return ['success' => true, 'response' => $response];
        } catch (\Exception $e) {
            Yii::error('MyID close session error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Common OAuth Methods (used by WebSDK and Mobile SDK Legacy)
    // =========================================================================

    /**
     * Exchange authorization code for access token.
     *
     * @param string $code Authorization code
     * @param string|null $redirectUri Redirect URI used in authorization
     * @return array
     */
    public function exchangeCodeForToken($code, $redirectUri = null)
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'method' => 'strong',
            'scope' => 'common_data',
        ];

        if ($redirectUri) {
            $data['redirect_uri'] = $redirectUri;
        }

        try {
            $response = $this->makeRequest('POST', self::OAUTH_ACCESS_TOKEN, $data, [], 'form');

            if (isset($response['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $response['access_token'],
                    'token_type' => $response['token_type'] ?? 'Bearer',
                    'expires_in' => $response['expires_in'] ?? 3600,
                    'refresh_token' => $response['refresh_token'] ?? null,
                    'scope' => $response['scope'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to get access token',
                'error_description' => $response['error_description'] ?? null,
            ];
        } catch (\Exception $e) {
            Yii::error('MyID token exchange error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user data from MyID using access token (WebSDK/Legacy flow).
     * Returns normalized (flattened) user data.
     *
     * @param string $accessToken
     * @return array {success, data} where data is normalized flat structure
     */
    public function getUserData($accessToken)
    {
        try {
            $response = $this->makeRequest('GET', self::USER_INFO, [], [
                'Authorization: Bearer ' . $accessToken,
            ]);

            $normalized = $this->normalizeUserData($response);
            if ($normalized) {
                return [
                    'success' => true,
                    'data' => $normalized,
                    'raw' => $response,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to get user data',
            ];
        } catch (\Exception $e) {
            Yii::error('MyID get user data error: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalize MyID API response into a flat structure.
     *
     * @param array $response Raw API response
     * @return array|null Normalized flat data or null if no valid data
     */
    public function normalizeUserData($response)
    {
        $profile = $response['profile'] ?? $response;
        $commonData = $profile['common_data'] ?? $profile;
        $docData = $profile['doc_data'] ?? [];
        $contacts = $profile['contacts'] ?? [];
        $address = $profile['address'] ?? [];

        $pinfl = $commonData['pinfl'] ?? $response['pinfl'] ?? null;
        if (empty($pinfl)) {
            return null;
        }

        $passData = $docData['pass_data'] ?? '';
        $passportSeries = $passData ? substr($passData, 0, 2) : ($commonData['passport_series'] ?? null);
        $passportNumber = $passData ? substr($passData, 2) : ($commonData['passport_number'] ?? null);

        $livingAddress = null;
        if (is_array($address)) {
            $permanentAddr = $address['permanent_address'] ?? null;
            $temporaryAddr = $address['temporary_address'] ?? null;
            if (is_array($permanentAddr)) {
                $livingAddress = $permanentAddr['address'] ?? json_encode($permanentAddr);
            } elseif (is_string($permanentAddr)) {
                $livingAddress = $permanentAddr;
            } elseif (is_array($temporaryAddr)) {
                $livingAddress = $temporaryAddr['address'] ?? json_encode($temporaryAddr);
            } elseif (is_string($temporaryAddr)) {
                $livingAddress = $temporaryAddr;
            }
        } elseif (is_string($address)) {
            $livingAddress = $address;
        }

        return [
            'pinfl' => $pinfl,
            'inn' => $commonData['inn'] ?? null,
            'first_name' => $commonData['first_name'] ?? $commonData['first_name_en'] ?? null,
            'last_name' => $commonData['last_name'] ?? $commonData['last_name_en'] ?? null,
            'middle_name' => $commonData['middle_name'] ?? $commonData['middle_name_en'] ?? null,
            'birth_date' => $commonData['birth_date'] ?? $commonData['birthDate'] ?? null,
            'birth_place' => $commonData['birth_place'] ?? $commonData['birthPlace'] ?? null,
            'gender' => $commonData['gender'] ?? null,
            'nationality' => $commonData['nationality'] ?? null,
            'citizenship' => $commonData['citizenship'] ?? null,
            'passport_series' => $passportSeries,
            'passport_number' => $passportNumber,
            'passport_issued_by' => $docData['issued_by'] ?? null,
            'passport_issued_date' => $docData['issued_date'] ?? null,
            'passport_expiry_date' => $docData['expiry_date'] ?? null,
            'phone' => $contacts['phone'] ?? null,
            'email' => $contacts['email'] ?? null,
            'living_address' => $livingAddress,
            'photo' => $commonData['photo'] ?? $response['photo'] ?? $response['userPhoto'] ?? null,
            'sdk_hash' => $response['sdk_hash'] ?? $response['sdkHash'] ?? null,
        ];
    }

    // =========================================================================
    // Combined Flow Methods (WebSDK/Legacy)
    // =========================================================================

    /**
     * Complete verification: exchange code, get user data, save to UserMyid (WebSDK flow).
     *
     * @param string $code Authorization code
     * @param int|null $userId User ID to link verification to
     * @return array
     */
    public function verifyAndSaveUser($code, $userId = null)
    {
        $tokenResult = $this->exchangeCodeForToken($code);
        if (!$tokenResult['success']) {
            return [
                'success' => false,
                'error' => $tokenResult['error'],
                'step' => 'token_exchange',
            ];
        }

        $userResult = $this->getUserData($tokenResult['access_token']);
        if (!$userResult['success']) {
            return [
                'success' => false,
                'error' => $userResult['error'],
                'step' => 'user_data',
            ];
        }

        $myidData = $userResult['data'];

        $existingMyid = UserMyid::findByPinfl($myidData['pinfl']);
        if ($existingMyid && $existingMyid->user_id && $userId && $existingMyid->user_id != $userId) {
            return [
                'success' => false,
                'error' => 'This PINFL is already linked to another account',
                'step' => 'pinfl_check',
            ];
        }

        $userMyid = UserMyid::createFromMyidData($myidData, $userId);
        if (!$userMyid) {
            return [
                'success' => false,
                'error' => 'Failed to save verification data',
                'step' => 'save',
            ];
        }

        return [
            'success' => true,
            'verification' => $userMyid,
            'user_id' => $userMyid->user_id,
            'myid_data' => $myidData,
        ];
    }

    /**
     * Register a new user via MyID data.
     *
     * @param string $code Authorization code
     * @param string|null $phone Optional phone number
     * @return array
     */
    public function registerWithMyid($code, $phone = null)
    {
        $tokenResult = $this->exchangeCodeForToken($code);
        if (!$tokenResult['success']) {
            return [
                'success' => false,
                'error' => $tokenResult['error'],
                'step' => 'token_exchange',
            ];
        }

        $userResult = $this->getUserData($tokenResult['access_token']);
        if (!$userResult['success']) {
            return [
                'success' => false,
                'error' => $userResult['error'],
                'step' => 'user_data',
            ];
        }

        $myidData = $userResult['data'];

        $existingMyid = UserMyid::findByPinfl($myidData['pinfl']);
        if ($existingMyid && $existingMyid->user_id) {
            $user = User::findOne($existingMyid->user_id);
            if ($user) {
                return [
                    'success' => true,
                    'is_new' => false,
                    'user' => $user,
                    'verification' => $existingMyid,
                ];
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->scenario = User::USER_SIGNUP;
            $user->role = User::ROLE_USER;
            $user->name = $myidData['first_name'] ?? '';
            $user->lastname = $myidData['last_name'] ?? '';
            $user->middlename = $myidData['middle_name'] ?? '';
            $user->phone = $phone ?: ($myidData['phone'] ?? ('myid_' . $myidData['pinfl']));
            $user->birthday = $myidData['birth_date'] ?? null;
            $user->gender = null;

            if (isset($myidData['gender'])) {
                $gender = strtolower((string)$myidData['gender']);
                if ($gender === 'male' || $gender === '1') {
                    $user->gender = 1;
                } elseif ($gender === 'female' || $gender === '2') {
                    $user->gender = 2;
                }
            }

            $user->token = Yii::$app->security->generateRandomString(32);
            $user->status = 1;
            $user->myid_verified = 1;

            if (!$user->save(false)) {
                throw new \Exception('Failed to create user: ' . json_encode($user->errors));
            }

            $userMyid = UserMyid::createFromMyidData($myidData, $user->id);
            if (!$userMyid) {
                throw new \Exception('Failed to save verification data');
            }

            $transaction->commit();

            return [
                'success' => true,
                'is_new' => true,
                'user' => $user,
                'verification' => $userMyid,
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
     * Get verification status for a user.
     *
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

    // =========================================================================
    // Mobile SDK Helpers
    // =========================================================================

    /**
     * Generate SDK hash for mobile SDK initialization (legacy).
     *
     * @param int|null $timestamp Timestamp in milliseconds
     * @return array {client_id, timestamp, hash}
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

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientHashId()
    {
        return $this->clientHashId;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getWebUrl()
    {
        return $this->webUrl;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return 'common_data';
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    // =========================================================================
    // HTTP Request
    // =========================================================================

    /**
     * Make HTTP request to MyID API.
     *
     * @param string $method HTTP method (GET, POST)
     * @param string $endpoint API endpoint path
     * @param array $data Request data
     * @param array $headers Additional headers
     * @param string $contentType 'json' or 'form' (x-www-form-urlencoded)
     * @return array Decoded JSON response
     * @throws \Exception
     */
    private function makeRequest($method, $endpoint, $data = [], $headers = [], $contentType = 'json')
    {
        $url = $this->baseUrl . $endpoint;

        $defaultHeaders = [
            'Accept: application/json',
        ];

        if ($contentType === 'form') {
            $defaultHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $defaultHeaders[] = 'Content-Type: application/json';
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($contentType === 'form') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                $jsonBody = empty($data) ? '{}' : json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            }
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        Yii::info("MyID API: {$method} {$endpoint}", __METHOD__);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlErrno) {
            Yii::error("MyID cURL error #{$curlErrno}: {$curlError} for {$method} {$url}", __METHOD__);
            throw new \Exception("cURL Error #{$curlErrno}: {$curlError}");
        }

        Yii::info("MyID API Response: {$method} {$endpoint} - HTTP {$httpCode}", __METHOD__);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorDetail = $decoded['detail'] ?? $decoded['error'] ?? $decoded['message'] ?? null;
            $errorMessage = $errorDetail
                ? "HTTP {$httpCode}: {$errorDetail}"
                : "HTTP {$httpCode}: " . substr($response, 0, 500);
            Yii::error("MyID API error on {$endpoint}: {$errorMessage}", __METHOD__);
            throw new \Exception($errorMessage);
        }

        return $decoded ?: [];
    }
}
