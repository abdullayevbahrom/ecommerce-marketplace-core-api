<?php

namespace app\modules\api\controllers;

use Yii;
use app\models\user\User;
use yii\rest\Controller;
use yii\web\UnauthorizedHttpException;
use yii\web\BadRequestHttpException;

class AuthController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['jwtAuth'] = [
            'class' => \app\components\Jwt\JwtAuthBehavior::class,
            'audience' => 'marketplace',
            'requiredPermissions' => [],
            'except' => [
                'login',
                'refresh',
                'options',
            ],
        ];

        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'login' => ['POST', 'OPTIONS'],
                'refresh' => ['POST', 'OPTIONS'],
                'logout' => ['POST', 'OPTIONS'],
                'logout-all' => ['POST', 'OPTIONS'],
                'me' => ['GET', 'OPTIONS'],
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        return [
            'options' => [
                'class' => \yii\rest\OptionsAction::class,
            ],
        ];
    }

    public function actionMe()
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->params['authUser'] ?? null;

        if (!$user) {
            throw new UnauthorizedHttpException('User not authenticated');
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'roles' => [$user->getRoleName()],
            'permissions' => [],
            'accesses' => $user->getAccesses()
        ];
    }

    public function actionLogin()
    {
        $body = Yii::$app->request->bodyParams;

        $login = $body['login'] ?? null;
        $password = $body['password'] ?? null;

        if (!$login || !$password) {
            throw new BadRequestHttpException('login and password required');
        }
        $phone = preg_replace('/[^\d]/', '', trim($login));

        $user = User::find()
            ->where(['phone' => $phone])
            ->one();

        if (!$user || !$user->validatePassword($password)) {
            throw new UnauthorizedHttpException('Invalid credentials');
        }

        if ((int) $user->status !== 1) {
            throw new UnauthorizedHttpException('User disabled');
        }

        $roles = [$user->getRoleName()];
        $permissions = [];

        $audiences = $user->getAccesses();

        $accessToken = Yii::$app->jwtService->issueAccessToken(
            $user,
            $roles,
            $permissions,
            $audiences
        );

        $refreshToken = Yii::$app->security->generateRandomString(64);

        Yii::$app->db->createCommand()->insert('auth_refresh_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => date('Y-m-d H:i:s', time() + Yii::$app->params['jwt']['refreshTtl']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'ip' => Yii::$app->request->userIP,
            'user_agent' => Yii::$app->request->userAgent,
            'device_token' => Yii::$app->request->headers->get('device-token') ?? null,
        ])->execute();

        Yii::$app->response->cookies->add(new \yii\web\Cookie([
            'name' => 'refresh_token',
            'value' => $refreshToken,
            'httpOnly' => true,
            'secure' => true,
            'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
        ]));

        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => Yii::$app->params['jwt']['accessTtl'],
        ];
    }

    public function actionRefresh()
    {
        $refreshToken = Yii::$app->request->cookies->getValue('refresh_token');

        if (!$refreshToken) {
            $refreshToken = Yii::$app->request->headers->get('X-Refresh-Token');
        }

        if (!$refreshToken) {
            $refreshToken = Yii::$app->request->post('refresh_token');
        }

        if (!$refreshToken) {
            throw new UnauthorizedHttpException('Refresh token required');
        }

        $tokenHash = hash('sha256', $refreshToken);

        $row = Yii::$app->db->createCommand("
            SELECT * FROM auth_refresh_tokens
            WHERE token_hash = :token_hash
              AND revoked_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ", [
            ':token_hash' => $tokenHash,
        ])->queryOne();

        if (!$row) {
            throw new UnauthorizedHttpException('Invalid refresh token');
        }

        $user = User::findOne($row['user_id']);

        if (!$user || (int) $user->status !== 1) {
            throw new UnauthorizedHttpException('User disabled');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            Yii::$app->db->createCommand()->update(
                'auth_refresh_tokens',
                [
                    'revoked_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                ['id' => $row['id']]
            )->execute();

            $newRefreshToken = Yii::$app->security->generateRandomString(64);

            Yii::$app->db->createCommand()->insert('auth_refresh_tokens', [
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $newRefreshToken),
                'device_token' => $row['device_token'] ?? null,
                'ip' => Yii::$app->request->userIP,
                'user_agent' => Yii::$app->request->userAgent,
                'expires_at' => date('Y-m-d H:i:s', time() + Yii::$app->params['jwt']['refreshTtl']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->execute();

            $roles = [$user->getRoleName()];
            $permissions = [];
            $audiences = $user->getAccesses();

            $accessToken = Yii::$app->jwtService->issueAccessToken(
                $user,
                $roles,
                $permissions,
                $audiences
            );

            $transaction->commit();

            Yii::$app->response->cookies->add(new \yii\web\Cookie([
                'name' => 'refresh_token',
                'value' => $newRefreshToken,
                'httpOnly' => true,
                'secure' => true,
                'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
                'path' => '/',
            ]));

            return [
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => Yii::$app->params['jwt']['accessTtl'],
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function actionLogout()
    {
        $refreshToken = Yii::$app->request->cookies->getValue('refresh_token');

        if (!$refreshToken) {
            $refreshToken = Yii::$app->request->headers->get('X-Refresh-Token');
        }

        if (!$refreshToken) {
            $refreshToken = Yii::$app->request->post('refresh_token');
        }

        if ($refreshToken) {
            Yii::$app->db->createCommand()->update(
                'auth_refresh_tokens',
                [
                    'revoked_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'token_hash' => hash('sha256', $refreshToken),
                    'revoked_at' => null,
                ]
            )->execute();
        }

        Yii::$app->response->cookies->remove('refresh_token');

        return [
            'success' => true,
            'message' => 'Logged out',
        ];
    }

    public function actionLogoutAll()
    {
        $header = Yii::$app->request->headers->get('Authorization');

        if (!$header || !preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            throw new UnauthorizedHttpException('Bearer token required');
        }

        $payload = Yii::$app->jwtService->decodeAndVerify(
            $matches[1],
            Yii::$app->params['jwt']['issuer']
        );

        $user = User::findOne(['id' => $payload->sub]);

        if (!$user) {
            throw new UnauthorizedHttpException('User not found');
        }

        Yii::$app->db->createCommand()->update(
            'auth_refresh_tokens',
            [
                'revoked_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => $user->id,
                'revoked_at' => null,
            ]
        )->execute();

        Yii::$app->response->cookies->remove('refresh_token');

        return [
            'success' => true,
            'message' => 'Logged out from all devices',
        ];
    }
}