<?php

namespace app\modules\api\controllers;

use app\components\WebSessionAuth;
use app\models\session\WebSession;
use app\models\user\User;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\Response;

class SessionController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'warehouse' => ['POST'],
                'operator' => ['POST'],
            ],
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];

        return $behaviors;
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

        $payload = [
            'yii_id' => (int)$user->id,
            'phone' => (string)$user->phone,
            'name' => trim($user->name . ' ' . $user->lastname . ' ' . $user->middlename),
            'role' => (int)$user->role,
            'is_active' => $user->status === User::STATUS_ACTIVE,
        ];

        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');
        try {
            /** @var \GuzzleHttp\Client $client */
            $client = Yii::$app->httpClient;
            $response = $client->post($warehouseApiUrl . '/api/shopLogin', ['json' => $payload]);

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

        $payload = [
            'yii_id' => (int)$user->id,
            'phone' => (string)$user->phone,
            'name' => trim($user->name . ' ' . $user->lastname . ' ' . $user->middlename),
            'role' => (int)$user->role,
            'email' => (string)$user->email,
            'is_active' => $user->status === User::STATUS_ACTIVE,
        ];

        $operatorApiUrl = rtrim(Yii::$app->params['operatorApiUrl'], '/');

        try {
            /** @var \GuzzleHttp\Client $client */
            $client = Yii::$app->httpClient;
            $response = $client->post($operatorApiUrl . '/api/auth/shopLogin', ['json' => $payload]);

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
