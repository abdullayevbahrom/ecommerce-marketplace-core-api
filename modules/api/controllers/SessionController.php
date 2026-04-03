<?php

namespace app\modules\api\controllers;

use app\models\session\WebSession;
use app\models\user\User;
use Yii;
use yii\helpers\ArrayHelper;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

class SessionController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['Authorization', 'Content-Type'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options', 'create-operator'],
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'warehouse' => ['POST'],
                'operator' => ['POST'],
                'create-operator' => ['POST'],
                'options' => ['OPTIONS'],
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => \yii\rest\OptionsAction::class,
        ];

        return $actions;
    }

    public function actionSessions()
    {
        $user = Yii::$app->user->identity;

        $sessions = WebSession::find()
            ->where([
                'user_id' => $user->id,
                'is_revoked' => 0
            ])
            ->all();

        return [
            'success' => true,
            'sessions' => $sessions
        ];
    }

    public function actionWarehouse()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        if (!\in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR, User::ROLE_SHOP])) {
            Yii::$app->response->statusCode = 200;

            return ['success' => false, 'message' => 'Forbidden'];
        }

        // Find shop_id for shop/merchant users
        $shopId = null;
        if ($user->role === User::ROLE_SHOP) {
            $shop = \app\models\shop\Shop::findOne(['user_id' => $user->id]);
            $shopId = $shop ? (int)$shop->id : null;
        }

        $payload = [
            'id' => (int)$user->id,
            'yii_id' => (int)$user->id,
            'phone' => (string)$user->phone,
            'name' => trim($user->name . ' ' . $user->lastname . ' ' . $user->middlename),
            'role' => (int)$user->role,
            'is_active' => $user->status === User::STATUS_ACTIVE,
            'shop_id' => $shopId,
        ];

        $token = md5($user->id . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        try {
            /** @var \GuzzleHttp\Client $client */
            $client = Yii::$app->httpClient;
            $response = $client->post(
                $warehouseApiUrl . '/api/shopLogin',
                [
                    'json' => $payload,
                    'headers' => [
                        'X-Api-Token' => $token,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $status = $response->getStatusCode();
            $body = (string)$response->getBody();
            $data = json_decode($body, true);

            if ($status >= 400) {
                Yii::warning("Warehouse sync failed: HTTP {$status} Body: {$body}", __METHOD__);
            }

            return [
                'success' => $status < 400,
                'status' => $status,
                'data' => $data ?? $body,
            ];
        } catch (\Throwable $e) {
            Yii::error("Warehouse sync fatal: {$e->getMessage()}", __METHOD__);
            Yii::$app->response->statusCode = 500;

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionOperator()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        if (!\in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR, User::ROLE_USER, User::ROLE_OPERATOR])) {
            Yii::$app->response->statusCode = 200;

            return ['success' => false, 'message' => 'Forbidden'];
        }

        $payload = [
            'id' => (int)$user->id,
            'yii_id' => (int)$user->id,
            'phone' => (string)$user->phone,
            'name' => trim($user->name . ' ' . $user->lastname . ' ' . $user->middlename),
            'role' => (int)$user->role,
            'email' => (string)$user->email,
            'is_active' => $user->status === User::STATUS_ACTIVE,
        ];

        $operatorApiUrl = rtrim(Yii::$app->params['operatorApiUrl'], '/');
        $token = md5($user->id . Yii::$app->params['apiSecretKey']);

        try {
            /** @var \GuzzleHttp\Client $client */
            $client = Yii::$app->httpClient;
            $response = $client->post(
                $operatorApiUrl . '/api/auth/shopLogin',
                [
                    'json' => $payload,
                    'headers' => [
                        'X-Api-Token' => $token,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $status = $response->getStatusCode();
            $body = (string)$response->getBody();
            $res = json_decode($body, true);

            if ($status >= 400) {
                Yii::warning("Operator sync failed: HTTP {$status} Body: {$body}", __METHOD__);
            }

            return [
                'success' => $status < 400,
                'status' => $status,
                'data' => $res ?? $body,
            ];
        } catch (\Throwable $e) {
            Yii::error("Operator sync fatal: {$e->getMessage()}", __METHOD__);
            Yii::$app->response->statusCode = 500;

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionCreateOperator()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $payload = Yii::$app->request->post();
        $phone = (string) ArrayHelper::getValue($payload, 'phone', '');
        $secret = Yii::$app->params['apiSecretKey'] ?? null;

        if (!$secret) {
            throw new HttpException(500, 'Missing apiSecretKey');
        }

        $expectedToken = md5($phone . $secret);
        $providedToken = Yii::$app->request->headers->get('X-Api-Token');

        if (!$providedToken || $providedToken !== $expectedToken) {
            throw new UnauthorizedHttpException('Invalid X-Api-Token');
        }

        $email = trim((string) ArrayHelper::getValue($payload, 'email', ''));
        $name = trim((string) ArrayHelper::getValue($payload, 'name', ''));
        $password = (string) ArrayHelper::getValue($payload, 'password', '');
        $isActive = (bool) ArrayHelper::getValue($payload, 'is_active', true);

        if ($phone === '' || $name === '' || $password === '') {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'name, phone and password are required',
            ];
        }

        $user = User::find()
            ->where(['role' => User::ROLE_OPERATOR])
            ->andWhere(['or', ['phone' => $phone], ['email' => $email]])
            ->one();

        if (!$user) {
            $conflict = User::find()
                ->andWhere(['or', ['phone' => $phone], ['email' => $email]])
                ->one();

            if ($conflict) {
                Yii::$app->response->statusCode = 409;

                return [
                    'success' => false,
                    'message' => 'Phone or email already used by another user',
                ];
            }

            $user = new User();
            $user->scenario = User::SIGNUP_ADMIN_USER;
        } else {
            $user->scenario = User::UPDATE_ADMIN_USER;
        }

        $nameParts = preg_split('/\s+/', $name, 3, PREG_SPLIT_NO_EMPTY) ?: [];
        $user->name = $nameParts[0] ?? $name;
        $user->lastname = $nameParts[1] ?? null;
        $user->middlename = $nameParts[2] ?? null;
        $user->phone = $phone;
        $user->email = $email;
        $user->role = User::ROLE_OPERATOR;
        $user->status = $isActive ? User::STATUS_ACTIVE : User::STATUS_BLOCKED;
        $user->password = $user->generatePassword($password);

        if (!$user->validate()) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $user->errors,
            ];
        }

        $savedUser = $user->saveObject(User::ROLE_OPERATOR);

        if (!$savedUser) {
            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'Failed to create operator user',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'yii_user_id' => (int) $savedUser->id,
                'phone' => $savedUser->phone,
                'email' => $savedUser->email,
                'role' => (int) $savedUser->role,
                'status' => (int) $savedUser->status,
            ],
        ];
    }


    public function actionProfile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $token = Yii::$app->request->headers->get('Web-Session');

        if (!$token) {
            return ['success' => false, 'message' => 'Session token missing'];
        }

        $session = WebSession::find()
            ->where([
                'access_token' => $token,
                'is_revoked' => 0
            ])
            ->andWhere(['>', 'expires_at', time()])
            ->one();

        if (!$session) {
            return ['success' => false, 'message' => 'Invalid session'];
        }

        $user = User::findOne($session->user_id);

        return [
            'success' => true,
            'data' => $user
        ];
    }

    public function actionRefresh()
    {
        $refreshToken = Yii::$app->request->post('refresh_token');

        $session = WebSession::find()
            ->where(['refresh_token' => $refreshToken])
            ->andWhere(['is_revoked' => 0])
            ->one();

        if (!$session) {
            throw new HttpException(401, 'Invalid refresh token');
        }

        $session->access_token = Yii::$app->security->generateRandomString(64);
        $session->expires_at = time() + (60 * 60 * 24 * 7);
        $session->updated_at = time();
        $session->save(false);

        return [
            'access_token' => $session->access_token
        ];
    }

    public function actionLogout()
    {
        $token = Yii::$app->request->headers->get('Web-Session');

        WebSession::updateAll(
            ['is_revoked' => 1],
            ['access_token' => $token]
        );

        return ['success' => true];
    }

    public function actionLogoutAll()
    {
        $currentToken = Yii::$app->request->headers->get('Authorization');
        $currentToken = str_replace('Bearer ', '', $currentToken);

        $user = Yii::$app->user->identity;

        WebSession::updateAll(
            ['is_revoked' => 1],
            ['user_id' => $user->id]
        );

        return ['success' => true];
    }
}
