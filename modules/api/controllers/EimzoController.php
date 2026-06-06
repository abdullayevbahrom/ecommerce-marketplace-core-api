<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\filters\auth\HttpBearerAuth;
use app\services\EimzoService;
use app\modules\api\components\ApiResponseTrait;
use app\modules\api\components\ErrorCodes;

/**
 * Direct E-IMZO integration controller.
 *
 * Provides endpoints for the full E-IMZO authentication and signing flow
 * without going through Didox as an intermediary.
 *
 * Endpoints:
 *   POST /api/eimzo/challenge           — Get a challenge string (no auth required)
     *   POST /api/eimzo/timestamp           — Attach timestamp to signed PKCS#7 (no auth required)
     *   POST /api/eimzo/digest              — Calculate digest for mobile QR/deeplink payloads (no auth required)
 *   POST /api/eimzo/auth               — Verify signed challenge & authenticate user (no auth required)
 *   POST /api/eimzo/verify             — Verify a PKCS#7 signature (authenticated)
 *   POST /api/eimzo/sign               — Server-side sign data with PFX (authenticated)
 *   GET  /api/eimzo/ping               — Check E-IMZO server status (authenticated)
 *   GET  /api/eimzo/info               — Get E-IMZO server info (authenticated)
 *
 * Mobile deeplink flow:
 *   POST /api/eimzo/mobile/auth         — Initiate mobile authentication (no auth required)
 *   POST /api/eimzo/mobile/sign         — Initiate mobile document signing (authenticated)
 *   POST /api/eimzo/mobile/status       — Poll mobile operation status (no auth required)
 *   POST /api/eimzo/mobile/auth-result  — Get auth result + login user (no auth required)
 *   POST /api/eimzo/mobile/verify       — Verify mobile-signed document (authenticated)
 */
class EimzoController extends Controller
{
    use ApiResponseTrait;

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['options', 'challenge', 'timestamp', 'digest', 'auth', 'mobile-auth', 'mobile-status', 'mobile-auth-result'],
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
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

        return $behaviors;
    }

    /**
     * POST /api/eimzo/challenge
     *
     * Request a challenge from E-IMZO server for the client to sign.
     * No authentication required — this is the first step of the flow.
     */
    public function actionChallenge()
    {
        $service = new EimzoService();
        $result = $service->getChallenge();

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_SERVER_UNREACHABLE, $result['error']);
        }

        return $this->sendSuccess([
            'challenge' => $result['challenge'],
            'ttl' => $result['ttl'],
        ]);
    }

    /**
     * POST /api/eimzo/timestamp
     *
     * Attach a timestamp to a signed PKCS#7 document.
     * No authentication required — called by frontend after signing.
     *
     * Body: { "pkcs7b64": "<base64 PKCS#7>" }
     */
    public function actionTimestamp()
    {
        $pkcs7b64 = Yii::$app->request->post('pkcs7b64');
        if (empty($pkcs7b64)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_PKCS7_REQUIRED);
        }

        $service = new EimzoService();
        $userIp = Yii::$app->request->userIP ?? '127.0.0.1';
        $result = $service->attachTimestamp($pkcs7b64, $userIp);

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_TIMESTAMP_FAILED, $result['error']);
        }

        return $this->sendSuccess([
            'pkcs7b64' => $result['pkcs7b64'],
            'signers' => $result['signers'],
        ]);
    }

    /**
     * POST /api/eimzo/digest
     *
     * Calculate the digest used for mobile deeplink/QR payloads.
     *
     * Body: { "text": "<plain text to hash>" }
     */
    public function actionDigest()
    {
        $text = Yii::$app->request->post('text');
        if ($text === null || $text === '') {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_PKCS7_REQUIRED, 'text is required');
        }

        $service = new EimzoService();
        $userIp = Yii::$app->request->userIP ?? '127.0.0.1';
        $result = $service->digest($text, $userIp);

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_SERVER_UNREACHABLE, $result['error']);
        }

        return $this->sendSuccess([
            'digestHex' => $result['digestHex'],
        ]);
    }

    /**
     * POST /api/eimzo/auth
     *
     * Authenticate a user using a signed+timestamped PKCS#7 challenge.
     * No authentication required — this produces the auth token.
     *
     * Body: { "pkcs7b64": "<base64 signed challenge with timestamp>" }
     *
     * Returns user data + bearer token on success.
     */
    public function actionAuth()
    {
        $pkcs7b64 = Yii::$app->request->post('pkcs7b64');
        if (empty($pkcs7b64)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_PKCS7_REQUIRED);
        }

        $service = new EimzoService();
        $userIp = Yii::$app->request->userIP ?? '127.0.0.1';
        $authResult = $service->authenticate($pkcs7b64, $userIp);

        if (!$authResult['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_AUTH_FAILED, $authResult['error']);
        }

        $certificate = $authResult['certificate'];
        $inn = $service->extractInn($certificate['subjectName'] ?? []);
        $commonName = $service->extractCommonName($certificate['subjectName'] ?? []);

        if (empty($inn)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_INN_MISSING);
        }

        $userType = Yii::$app->request->post('user_type', 'fiz');
        if (!in_array($userType, ['fiz', 'yur'])) {
            $userType = 'fiz';
        }

        // Find user by INN, or by phone if provided
        $user = \app\models\user\User::findOne(['eimzo_tax_id' => $inn]);

        if (!$user) {
            // Check if user exists by phone (prevent duplicates)
            $phone = Yii::$app->request->post('phone');
            if (!empty($phone)) {
                $user = \app\models\user\User::findOne(['phone' => $phone]);
                if ($user && empty($user->eimzo_tax_id)) {
                    // Link E-IMZO to existing phone-registered user
                    $user->eimzo_tax_id = $inn;
                }
            }
        }

        if (!$user) {
            $user = new \app\models\user\User();
            $user->eimzo_tax_id = $inn;
            $user->role = \app\models\user\User::ROLE_USER;
            $user->status = 1;
            $user->type = $userType;
            $user->password = Yii::$app->security->generatePasswordHash(
                Yii::$app->security->generateRandomString(16)
            );
            $user->date = date('Y-m-d H:i:s');
            $user->ip = $userIp;

            // Parse name from CN
            if (!empty($commonName)) {
                $nameParts = explode(' ', trim($commonName));
                $user->lastname = $nameParts[0] ?? '';
                $user->name = $nameParts[1] ?? '';
                $user->middlename = trim(implode(' ', array_slice($nameParts, 2)));
            }
        }

        // Update E-IMZO metadata
        $user->eimzo_certificate_info = json_encode($certificate);
        $user->eimzo_last_login = date('Y-m-d H:i:s');
        $user->markEimzoAuthCompleted();

        // Generate auth token
        $user->token = $user->generateToken();

        if (!$user->save(false)) {
            Yii::error('Failed to save user from E-IMZO: ' . json_encode($user->errors), 'eimzo');
            return $this->sendError(ErrorCodes::ERROR_EIMZO_USER_CREATE_FAILED);
        }

        // Sklad provisioning
        try {
            Yii::$app->skladProvisioner->ensurePersonalWarehouse($user, 'user');
        } catch (\Exception $e) {
            Yii::warning('Sklad provisioning failed for user ' . $user->id . ': ' . $e->getMessage());
        }

        return $this->sendSuccess([
            'token' => $user->token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'phone' => $user->phone,
                'eimzo_tax_id' => $user->eimzo_tax_id,
                'type' => $user->type,
                'eimzo_auth_completed' => $user->isEimzoAuthCompleted(),
                'didox_auth_completed' => $user->isDidoxAuthCompleted(),
            ],
            'certificate' => $certificate,
        ]);
    }

    /**
     * POST /api/eimzo/verify
     *
     * Verify a PKCS#7 attached signature (e.g. for signed documents).
     * Requires authentication.
     *
     * Body: { "pkcs7b64": "<base64 PKCS#7 with timestamp>" }
     */
    public function actionVerify()
    {
        $pkcs7b64 = Yii::$app->request->post('pkcs7b64');
        if (empty($pkcs7b64)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_PKCS7_REQUIRED);
        }

        $service = new EimzoService();
        $userIp = Yii::$app->request->userIP ?? '127.0.0.1';
        $result = $service->verifyPkcs7($pkcs7b64, $userIp);

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_VERIFY_FAILED, $result['error']);
        }

        return $this->sendSuccess($result['pkcs7Info']);
    }

    /**
     * POST /api/eimzo/sign
     *
     * Server-side sign data using the configured PFX certificate.
     * Requires authentication.
     *
     * Body: { "data": "<data to sign>", "timestamp": true|false }
     */
    public function actionSign()
    {
        $data = Yii::$app->request->post('data');
        if (empty($data)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_PKCS7_REQUIRED, 'data is required');
        }

        $addTimestamp = Yii::$app->request->post('timestamp', true);

        $service = new EimzoService();
        $result = $service->signData($data, (bool) $addTimestamp);

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_SIGN_FAILED, $result['error']);
        }

        return $this->sendSuccess([
            'pkcs7b64' => $result['pkcs7b64'],
        ]);
    }

    /**
     * GET /api/eimzo/ping
     *
     * Check E-IMZO server availability. Requires authentication.
     */
    public function actionPing()
    {
        $service = new EimzoService();
        $result = $service->ping();

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_STATUS_FAILED, $result['error']);
        }

        return $this->sendSuccess($result);
    }

    /**
     * GET /api/eimzo/info
     *
     * Get E-IMZO server version and trusted certificates. Requires authentication.
     */
    public function actionInfo()
    {
        $service = new EimzoService();
        $result = $service->info();

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_STATUS_FAILED, $result['error']);
        }

        return $this->sendSuccess($result['data']);
    }

    // ==================================================================
    // Mobile deeplink flow endpoints
    // ==================================================================

    /**
     * POST /api/eimzo/mobile/auth
     *
     * Initiate mobile authentication. Returns siteId, documentId, and challenge
     * for the mobile app to build a QR code and open the E-IMZO deeplink.
     *
     * No authentication required — this is the first step of mobile auth.
     */
    public function actionMobileAuth()
    {
        $service = new EimzoService();
        $result = $service->mobileAuth();

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_MOBILE_INIT_FAILED, $result['error']);
        }

        return $this->sendSuccess([
            'siteId' => $result['siteId'],
            'documentId' => $result['documentId'],
            'challenge' => $result['challenge'],
            'pollInterval' => Yii::$app->params['eimzo']['mobileStatusPollInterval'] ?? 5,
            'timeout' => Yii::$app->params['eimzo']['mobileStatusTimeout'] ?? 120,
        ]);
    }

    /**
     * POST /api/eimzo/mobile/sign
     *
     * Initiate mobile document signing. Returns siteId and documentId.
     * Requires authentication — user must be logged in to sign documents.
     *
     * Body: { "document": "<base64 document to sign>" }
     */
    public function actionMobileSign()
    {
        $documentInput = Yii::$app->request->post('document');
        if ($documentInput === null || trim((string)$documentInput) === '') {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_PKCS7_REQUIRED, 'document is required');
        }

        $documentB64 = $this->normalizeDocumentInputToBase64((string)$documentInput);

        $service = new EimzoService();
        $result = $service->mobileSign();

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_MOBILE_INIT_FAILED, $result['error']);
        }

        return $this->sendSuccess([
            'siteId' => $result['siteId'],
            'documentId' => $result['documentId'],
            'documentSha256' => hash('sha256', $documentB64),
            'pollInterval' => Yii::$app->params['eimzo']['mobileStatusPollInterval'] ?? 5,
            'timeout' => Yii::$app->params['eimzo']['mobileStatusTimeout'] ?? 120,
        ]);
    }

    /**
     * POST /api/eimzo/mobile/status
     *
     * Poll mobile operation status.
     * No authentication required — mobile app polls this after deeplink.
     *
     * Body: { "documentId": "<documentId from auth/sign>" }
     *
     * Returns: { status: 1 } (complete) or { status: 2 } (pending)
     */
    public function actionMobileStatus()
    {
        $documentId = Yii::$app->request->post('documentId');
        if (empty($documentId)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_DOCUMENT_ID_REQUIRED);
        }

        $service = new EimzoService();
        $result = $service->mobileStatus($documentId);

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_MOBILE_EXPIRED, $result['error']);
        }

        return $this->sendSuccess([
            'status' => $result['status'],
            'complete' => $result['status'] === 1,
        ]);
    }

    /**
     * POST /api/eimzo/mobile/auth-result
     *
     * After mobile status=1, get the authentication result and log the user in.
     * No authentication required — this produces the auth token (same as actionAuth for desktop).
     *
     * Body: { "documentId": "<documentId>", "user_type": "fiz"|"yur" }
     */
    public function actionMobileAuthResult()
    {
        $documentId = Yii::$app->request->post('documentId');
        if (empty($documentId)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_DOCUMENT_ID_REQUIRED);
        }

        $service = new EimzoService();
        $userIp = Yii::$app->request->userIP ?? '127.0.0.1';

        // Avoid calling backend/mobile/authenticate while the mobile flow is
        // still pending, because upstream may return opaque server errors.
        $statusResult = $service->mobileStatus($documentId);
        if (!$statusResult['success']) {
            Yii::warning([
                'message' => 'Mobile auth result requested for expired or invalid documentId',
                'documentId' => $documentId,
                'statusResult' => $statusResult,
                'userIp' => $userIp,
            ], 'eimzo');

            return $this->sendError(
                ErrorCodes::ERROR_EIMZO_MOBILE_EXPIRED,
                $statusResult['error'] ?? 'Mobile signing session expired'
            );
        }

        if (($statusResult['status'] ?? null) !== 1) {
            Yii::info([
                'message' => 'Mobile auth result requested before signature completion',
                'documentId' => $documentId,
                'status' => $statusResult['status'] ?? null,
                'userIp' => $userIp,
            ], 'eimzo');

            return $this->sendError(
                ErrorCodes::ERROR_EIMZO_MOBILE_PENDING,
                'Mobile signing is still pending'
            );
        }

        $authResult = $service->mobileAuthenticate($documentId, $userIp);

        if (!$authResult['success']) {
            Yii::warning([
                'message' => 'Mobile authenticate failed',
                'documentId' => $documentId,
                'authResult' => $authResult,
                'userIp' => $userIp,
            ], 'eimzo');

            return $this->sendError(ErrorCodes::ERROR_EIMZO_AUTH_FAILED, $authResult['error']);
        }

        // Extract user identity from certificate
        $certificate = $authResult['certificate'];
        $inn = $service->extractInn($certificate['subjectName'] ?? []);
        $commonName = $service->extractCommonName($certificate['subjectName'] ?? []);

        if (empty($inn)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_INN_MISSING);
        }

        $userType = Yii::$app->request->post('user_type', 'fiz');
        if (!in_array($userType, ['fiz', 'yur'])) {
            $userType = 'fiz';
        }

        // Find or create user by INN (tax ID)
        $user = \app\models\user\User::findOne(['eimzo_tax_id' => $inn]);

        if (!$user) {
            $user = new \app\models\user\User();
            $user->eimzo_tax_id = $inn;
            $user->role = \app\models\user\User::ROLE_USER;
            $user->status = 1;
            $user->type = $userType;
            $user->password = Yii::$app->security->generatePasswordHash(
                Yii::$app->security->generateRandomString(16)
            );
            $user->date = date('Y-m-d H:i:s');
            $user->ip = $userIp;

            if (!empty($commonName)) {
                $nameParts = explode(' ', trim($commonName));
                $user->lastname = $nameParts[0] ?? '';
                $user->name = $nameParts[1] ?? '';
                $user->middlename = trim(implode(' ', array_slice($nameParts, 2)));
            }
        }

        // Update E-IMZO metadata
        $user->eimzo_certificate_info = json_encode($certificate);
        $user->eimzo_last_login = date('Y-m-d H:i:s');
        $user->markEimzoAuthCompleted();

        // Generate auth token
        $user->token = $user->generateToken();

        if (!$user->save(false)) {
            Yii::error('Failed to save user from mobile E-IMZO: ' . json_encode($user->errors), 'eimzo');
            return $this->sendError(ErrorCodes::ERROR_EIMZO_USER_CREATE_FAILED);
        }

        // Sklad provisioning
        try {
            Yii::$app->skladProvisioner->ensurePersonalWarehouse($user, 'user');
        } catch (\Exception $e) {
            Yii::warning('Sklad provisioning failed for user ' . $user->id . ': ' . $e->getMessage());
        }

        return $this->sendSuccess([
            'token' => $user->token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'eimzo_tax_id' => $user->eimzo_tax_id,
                'type' => $user->type,
                'eimzo_auth_completed' => $user->isEimzoAuthCompleted(),
                'didox_auth_completed' => $user->isDidoxAuthCompleted(),
            ],
            'certificate' => $certificate,
        ]);
    }

    /**
     * POST /api/eimzo/mobile/verify
     *
     * Verify a mobile-signed document after status=1.
     * Requires authentication.
     *
     * Body: { "documentId": "<documentId>", "document": "<base64 document>" }
     */
    public function actionMobileVerify()
    {
        $documentId = Yii::$app->request->post('documentId');
        $documentInput = Yii::$app->request->post('document');

        if (empty($documentId)) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_DOCUMENT_ID_REQUIRED);
        }

        if ($documentInput === null || trim((string)$documentInput) === '') {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_PKCS7_REQUIRED, 'document is required');
        }

        $documentB64 = $this->normalizeDocumentInputToBase64((string)$documentInput);

        $service = new EimzoService();
        $userIp = Yii::$app->request->userIP ?? '127.0.0.1';
        $result = $service->mobileVerify($documentId, $documentB64, $userIp);

        if (!$result['success']) {
            return $this->sendError(ErrorCodes::ERROR_EIMZO_VERIFY_FAILED, $result['error']);
        }

        return $this->sendSuccess([
            'certificate' => $result['certificate'],
            'pkcs7Attached' => $result['pkcs7Attached'],
            'verificationInfo' => $result['verificationInfo'],
            'tracking' => [
                'documentId' => $documentId,
                'documentSha256' => hash('sha256', $documentB64),
            ],
        ]);
    }

    private function normalizeDocumentInputToBase64(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $compact = preg_replace('/\s+/', '', $value);
        if ($compact !== '' && $this->looksLikeBase64($compact)) {
            return $compact;
        }

        return base64_encode($value);
    }

    private function looksLikeBase64(string $value): bool
    {
        if ($value === '' || strlen($value) % 4 !== 0) {
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
            return false;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $value;
    }
}
