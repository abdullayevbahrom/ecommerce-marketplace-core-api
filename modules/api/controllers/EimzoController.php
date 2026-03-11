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
 *   POST /api/eimzo/challenge        — Get a challenge string (no auth required)
 *   POST /api/eimzo/timestamp         — Attach timestamp to signed PKCS#7 (no auth required)
 *   POST /api/eimzo/auth              — Verify signed challenge & authenticate user (no auth required)
 *   POST /api/eimzo/verify            — Verify a PKCS#7 signature (authenticated)
 *   POST /api/eimzo/sign              — Server-side sign data with PFX (authenticated)
 *   GET  /api/eimzo/ping              — Check E-IMZO server status (authenticated)
 *   GET  /api/eimzo/info              — Get E-IMZO server info (authenticated)
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
            'optional' => ['options', 'challenge', 'timestamp', 'auth'],
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

        // Find or create user by INN (tax ID)
        $user = \app\models\user\User::findOne(['eimzo_tax_id' => $inn]);

        if (!$user) {
            $user = new \app\models\user\User();
            $user->eimzo_tax_id = $inn;
            $user->role = \app\models\user\User::ROLE_USER;
            $user->status = 1;
            $user->password = Yii::$app->security->generatePasswordHash(
                Yii::$app->security->generateRandomString(16)
            );

            // Parse name from CN
            if (!empty($commonName)) {
                $nameParts = explode(' ', trim($commonName));
                $user->lastname = $nameParts[0] ?? '';
                $user->name = $nameParts[1] ?? '';
                $user->middlename = trim(implode(' ', array_slice($nameParts, 2)));
            }

            if (!$user->save(false)) {
                Yii::error('Failed to create user from E-IMZO: ' . json_encode($user->errors), 'eimzo');
                return $this->sendError(ErrorCodes::ERROR_EIMZO_USER_CREATE_FAILED);
            }
        }

        // Update E-IMZO metadata
        $user->eimzo_certificate_info = json_encode($certificate);
        $user->eimzo_last_login = date('Y-m-d H:i:s');

        // Generate auth token
        $token = Yii::$app->security->generateRandomString(64);
        $user->auth_key = $token;
        $user->save(false);

        return $this->sendSuccess([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'eimzo_tax_id' => $user->eimzo_tax_id,
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
}
