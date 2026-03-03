<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use app\models\user\User;
use app\models\user\UserMyid;
use app\services\MyidService;
use app\modules\api\components\ApiResponseTrait;
use app\modules\api\components\ErrorCodes;

/**
 * MyID API Controller
 *
 * Handles MyID identity verification for both WebSDK and Mobile SDK flows.
 * Backend-only API - frontend and mobile have their own SDK integrations.
 *
 * WebSDK flow: init-web -> (user verifies on web.identity.example.com) -> callback
 * Mobile SDK flow: sdk-config -> (user verifies in app) -> verify
 */
class MyidController extends Controller
{
    use ApiResponseTrait;

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 200;
            return false;
        }

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['options', 'init-web', 'callback', 'verify', 'register', 'sdk-config', 'session-result', 'create-session', 'session-status'],
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    // =========================================================================
    // WebSDK Endpoints
    // =========================================================================

    /**
     * Initialize WebSDK verification session.
     *
     * Creates a session on MyID and returns the web URL for user redirect/iframe.
     *
     * POST /api/myid/init-web
     * Body: {
     *   "redirect_uri": "https://yoursite.com/callback",  // required
     *   "pinfl": "12345678901234",                         // optional
     *   "birth_date": "2000-12-31",                        // optional
     *   "lang": "en"                                       // optional (en|ru|uz)
     * }
     */
    public function actionInitWeb()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Method not allowed', [], 405);
        }

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        $post = Yii::$app->request->post();
        $redirectUri = $post['redirect_uri'] ?? null;

        if (empty($redirectUri)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'redirect_uri is required');
        }

        // Generate external_id for tracking
        $externalId = $this->generateUuid();

        // Get real client IP
        $ipAddress = $this->getClientIp();

        // Create session on MyID
        $sessionResult = $myidService->createWebSession($externalId, $ipAddress);
        if (!$sessionResult['success']) {
            Yii::error('MyID init-web failed: ' . ($sessionResult['error'] ?? ''), __METHOD__);
            return $this->sendError(ErrorCodes::ERROR_MYID_SESSION_FAILED, $sessionResult['error'] ?? null);
        }

        // Build web URL with params
        $urlParams = [
            'redirect_uri' => $redirectUri,
        ];
        if (!empty($post['pinfl'])) {
            $urlParams['pinfl'] = $post['pinfl'];
        }
        if (!empty($post['birth_date'])) {
            $urlParams['birth_date'] = $post['birth_date'];
        }
        if (!empty($post['lang'])) {
            $urlParams['lang'] = $post['lang'];
        }

        $webUrl = $myidService->buildWebUrl($sessionResult['session_id'], $urlParams);

        return $this->sendSuccess([
            'session_id' => $sessionResult['session_id'],
            'external_id' => $externalId,
            'web_url' => $webUrl,
        ]);
    }

    /**
     * Handle WebSDK callback after user completes verification.
     *
     * Frontend calls this after receiving auth_code from MyID redirect.
     *
     * POST /api/myid/callback
     * Body: {
     *   "code": "auth_code_from_redirect",    // required
     *   "session_id": "session_id"             // optional
     * }
     * Authorization: Bearer {token}            // optional - links to existing user
     */
    public function actionCallback()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Method not allowed', [], 405);
        }

        $post = Yii::$app->request->post();
        $code = $post['code'] ?? Yii::$app->request->get('code');

        if (empty($code)) {
            return $this->sendError(ErrorCodes::ERROR_MYID_CODE_REQUIRED);
        }

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        // Get current authenticated user (if any)
        $currentUser = Yii::$app->user->identity;
        $userId = $currentUser ? $currentUser->id : null;

        // Complete verification
        $result = $myidService->verifyAndSaveUser($code, $userId);
        if (!$result['success']) {
            $errorCode = $this->mapStepToErrorCode($result['step'] ?? '');
            return $this->sendError($errorCode, $result['error'] ?? null);
        }

        $verification = $result['verification'];
        $user = $result['user_id'] ? User::findOne($result['user_id']) : null;

        return $this->sendSuccess([
            'user' => $user ? $this->formatUserResponse($user) : null,
            'verification' => $verification->toApiArray(),
        ], 'Verification successful');
    }

    /**
     * Check WebSDK session result (async polling).
     *
     * GET /api/myid/session-result?session_id=xxx
     */
    public function actionSessionResult()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $sessionId = Yii::$app->request->get('session_id');
        if (empty($sessionId)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'session_id is required');
        }

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        $result = $myidService->getWebSessionResult($sessionId);
        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_MYID_SESSION_FAILED, $result['error'] ?? null);
        }

        return $this->sendSuccess([
            'status' => $result['status'],
            'auth_code' => $result['auth_code'] ?? null,
            'attempts' => $result['attempts'] ?? [],
        ]);
    }

    // =========================================================================
    // SDK New Flow Endpoints (Mobile)
    // =========================================================================

    /**
     * Create a session for mobile SDK initialization (SDK New Flow).
     *
     * POST /api/myid/create-session
     * Body (all optional): {
     *   "pinfl": "12345678901234",
     *   "pass_data": "AA1234567",
     *   "phone_number": "998901234567",
     *   "birth_date": "1990-01-15",
     *   "is_resident": true,
     *   "threshold": 0.7
     * }
     */
    public function actionCreateSession()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Method not allowed', [], 405);
        }

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        $post = Yii::$app->request->post();

        $params = [];
        $allowedFields = ['pinfl', 'pass_data', 'phone_number', 'birth_date', 'is_resident', 'threshold'];
        foreach ($allowedFields as $field) {
            if (isset($post[$field]) && $post[$field] !== '') {
                $params[$field] = $post[$field];
            }
        }

        // If authenticated user has a reuid, support secondary flow
        $currentUser = Yii::$app->user->identity;
        if ($currentUser && isset($post['use_reuid']) && $post['use_reuid']) {
            $existingMyid = UserMyid::findByUserId($currentUser->id);
            if ($existingMyid && $existingMyid->hasValidReuid()) {
                $params['reuid'] = $existingMyid->reuid;
            }
        }

        $result = $myidService->createSdkSession($params);

        if (!$result['success']) {
            $step = $result['step'] ?? 'unknown';
            $error = $result['error'] ?? 'Unknown error';
            return $this->sendError(ErrorCodes::ERROR_MYID_SESSION_FAILED, "MyID {$step} failed: {$error}");
        }

        return $this->sendSuccess([
            'session_id' => $result['session_id'],
        ]);
    }

    /**
     * Session recovery - check session status if code was lost (SDK New Flow).
     *
     * GET /api/myid/session-status?session_id={session_id}
     */
    public function actionSessionStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $sessionId = Yii::$app->request->get('session_id');
        if (empty($sessionId)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'session_id is required');
        }

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        $result = $myidService->getSessionStatus($sessionId);

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_MYID_SESSION_FAILED, 'Failed to get session status: ' . ($result['error'] ?? 'Unknown error'));
        }

        return $this->sendSuccess([
            'code' => $result['code'],
            'status' => $result['status'],
            'attempts' => $result['attempts'],
        ]);
    }

    // =========================================================================
    // Mobile SDK Legacy Endpoints
    // =========================================================================

    /**
     * Verify user with MyID (supports both Legacy and SDK New Flow).
     *
     * Mobile app sends the authorization code received from SDK after biometric check.
     *
     * POST /api/myid/verify
     * Body: {
     *   "code": "auth_code_from_sdk",   // required
     *   "flow": "sdk_new",              // optional - "sdk_new" for new flow, default is legacy
     *   "register": false               // optional - true to create new user if not exists
     * }
     * Authorization: Bearer {token}     // optional - links to existing user
     */
    public function actionVerify()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Method not allowed', [], 405);
        }

        $post = Yii::$app->request->post();
        $code = $post['code'] ?? null;
        $flow = $post['flow'] ?? 'legacy';
        $registerNew = $post['register'] ?? false;

        if (empty($code)) {
            return $this->sendError(ErrorCodes::ERROR_MYID_CODE_REQUIRED);
        }

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        $currentUser = Yii::$app->user->identity;
        $userId = $currentUser ? $currentUser->id : null;

        // SDK New Flow path
        if ($flow === 'sdk_new') {
            $result = $myidService->verifyAndSaveSdk($code, $userId);

            if (!$result['success']) {
                $errorCode = $this->mapStepToErrorCode($result['step'] ?? '');
                return $this->sendError($errorCode, $result['error'] ?? null);
            }

            $linkedUserId = $result['user_id'] ?? $userId;
            $user = $linkedUserId ? User::findOne($linkedUserId) : null;

            return $this->sendSuccess([
                'user' => $user ? $this->formatUserResponse($user) : null,
                'verification' => $result['verification'],
            ], 'Verification successful');
        }

        // Legacy flow: Exchange code for token and get user data
        $tokenResult = $myidService->exchangeCodeForToken($code);
        if (!$tokenResult['success']) {
            return $this->sendError(ErrorCodes::ERROR_MYID_TOKEN_EXCHANGE_FAILED, $tokenResult['error'] ?? null);
        }

        $userDataResult = $myidService->getUserData($tokenResult['access_token']);
        if (!$userDataResult['success']) {
            return $this->sendError(ErrorCodes::ERROR_MYID_USER_DATA_FAILED, $userDataResult['error'] ?? null);
        }

        $myidData = $userDataResult['data'];
        $pinfl = $myidData['pinfl'] ?? null;

        if (empty($pinfl)) {
            return $this->sendError(ErrorCodes::ERROR_MYID_PINFL_MISSING);
        }

        $existingMyid = UserMyid::findByPinfl($pinfl);

        if ($existingMyid && $existingMyid->user_id && $userId && $existingMyid->user_id !== $userId) {
            return $this->sendError(ErrorCodes::ERROR_MYID_PINFL_LINKED);
        }

        if (!$userId && !$registerNew) {
            if ($existingMyid && $existingMyid->user_id) {
                $user = $existingMyid->user;
                if ($user) {
                    return $this->sendSuccess([
                        'user' => $this->formatUserResponse($user),
                        'verification' => $existingMyid->toApiArray(),
                        'is_new_user' => false,
                    ], 'User found by PINFL');
                }
            }
            return $this->sendError(ErrorCodes::ERROR_USER_NOT_FOUND, 'No user linked to this PINFL. Set register=true to create new user.');
        }

        if (!$userId && $registerNew) {
            $user = new User();
            $user->scenario = User::USER_SIGNUP;
            $user->role = User::ROLE_USER;
            $user->status = 1;
            $user->token = Yii::$app->security->generateRandomString(32);
            $user->name = $myidData['first_name'] ?? '';
            $user->lastname = $myidData['last_name'] ?? '';
            $user->middlename = $myidData['middle_name'] ?? '';
            $user->phone = 'myid_' . $pinfl;
            $user->myid_verified = 1;

            if (!$user->save(false)) {
                Yii::error('Failed to create user from MyID: ' . json_encode($user->errors), __METHOD__);
                return $this->sendError(ErrorCodes::ERROR_USER_SAVE_FAILED);
            }

            $userId = $user->id;
        }

        $verification = UserMyid::createFromMyidData($myidData, $userId);
        if (!$verification) {
            return $this->sendError(ErrorCodes::ERROR_MYID_VERIFICATION_FAILED, 'Failed to save verification data');
        }

        $user = $userId ? User::findOne($userId) : null;

        return $this->sendSuccess([
            'user' => $user ? $this->formatUserResponse($user) : null,
            'verification' => $verification->toApiArray(),
            'is_new_user' => $registerNew && !$existingMyid,
        ], 'Verification successful');
    }

    /**
     * Register new user via MyID.
     *
     * POST /api/myid/register
     * Body: {
     *   "code": "auth_code_from_sdk",     // required
     *   "phone": "998901234567"            // optional
     * }
     */
    public function actionRegister()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Method not allowed', [], 405);
        }

        $post = Yii::$app->request->post();
        $code = $post['code'] ?? null;
        $phone = $post['phone'] ?? null;

        if (empty($code)) {
            return $this->sendError(ErrorCodes::ERROR_MYID_CODE_REQUIRED);
        }

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        // Check if phone already exists
        if ($phone) {
            $existingPhone = User::findOne(['phone' => $phone]);
            if ($existingPhone) {
                return $this->sendError(ErrorCodes::ERROR_USER_EXISTS, 'Phone number already registered');
            }
        }

        $result = $myidService->registerWithMyid($code, $phone);
        if (!$result['success']) {
            $errorCode = $this->mapStepToErrorCode($result['step'] ?? '');
            return $this->sendError($errorCode, $result['error'] ?? null);
        }

        $user = $result['user'];
        $verification = $result['verification'];

        return $this->sendSuccess([
            'user' => $this->formatUserResponse($user),
            'verification' => $verification->toApiArray(),
            'is_new_user' => $result['is_new'],
        ], $result['is_new'] ? 'User registered successfully' : 'User already registered with this PINFL');
    }

    // =========================================================================
    // Common Endpoints
    // =========================================================================

    /**
     * Get current user's MyID verification status.
     *
     * GET /api/myid/status
     * Authorization: Bearer {token}  // required
     */
    public function actionStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        if (!$user) {
            return $this->sendError(ErrorCodes::ERROR_UNAUTHORIZED);
        }

        $verification = UserMyid::findByUserId($user->id);

        if (!$verification) {
            return $this->sendSuccess([
                'verified' => false,
            ], 'User not verified with MyID');
        }

        return $this->sendSuccess([
            'verified' => $verification->isVerified(),
            'verified_at' => $verification->verified_at,
            'pinfl' => $verification->pinfl,
            'full_name' => $verification->getFullName(),
            'verification' => $verification->toApiArray(),
        ]);
    }

    /**
     * Get SDK configuration for mobile app initialization.
     *
     * GET /api/myid/sdk-config
     */
    public function actionSdkConfig()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $myidService = new MyidService();
        if (!$myidService->isConfigured()) {
            return $this->sendError(ErrorCodes::ERROR_MYID_NOT_CONFIGURED);
        }

        $sdkHash = $myidService->generateSdkHash();

        return $this->sendSuccess([
            'client_id' => $myidService->getClientId(),
            'client_hash_id' => $myidService->getClientHashId(),
            'base_url' => $myidService->getBaseUrl(),
            'scope' => $myidService->getScope(),
            'sdk_hash' => $sdkHash['hash'],
            'timestamp' => $sdkHash['timestamp'],
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Format user data for API response.
     */
    private function formatUserResponse($user)
    {
        return [
            'id' => $user->id,
            'token' => $user->token,
            'name' => $user->name,
            'lastname' => $user->lastname,
            'middlename' => $user->middlename,
            'phone' => $user->phone,
            'myid_verified' => (int)$user->myid_verified,
        ];
    }

    /**
     * Map MyID service step errors to ErrorCodes.
     */
    private function mapStepToErrorCode($step)
    {
        $map = [
            'token_exchange' => ErrorCodes::ERROR_MYID_TOKEN_EXCHANGE_FAILED,
            'user_data' => ErrorCodes::ERROR_MYID_USER_DATA_FAILED,
            'get_user_data' => ErrorCodes::ERROR_MYID_USER_DATA_FAILED,
            'pinfl_check' => ErrorCodes::ERROR_MYID_PINFL_LINKED,
            'save' => ErrorCodes::ERROR_MYID_VERIFICATION_FAILED,
            'registration' => ErrorCodes::ERROR_MYID_VERIFICATION_FAILED,
            'client_token' => ErrorCodes::ERROR_MYID_SESSION_FAILED,
            'access_token' => ErrorCodes::ERROR_MYID_SESSION_FAILED,
        ];

        return $map[$step] ?? ErrorCodes::ERROR_MYID_VERIFICATION_FAILED;
    }

    /**
     * Get real client IP address, checking proxy headers.
     */
    private function getClientIp()
    {
        $request = Yii::$app->request;

        // Check common proxy headers
        $headers = ['X-Real-IP', 'X-Forwarded-For', 'CF-Connecting-IP'];
        foreach ($headers as $header) {
            $value = $request->headers->get($header);
            if ($value) {
                // X-Forwarded-For can contain multiple IPs
                $ips = explode(',', $value);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->getUserIP() ?: '127.0.0.1';
    }

    /**
     * Generate a UUID v4.
     */
    private function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
