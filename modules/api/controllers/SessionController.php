<?php 

namespace app\modules\api\controllers;

use app\components\WebSessionAuth;
use app\models\session\WebSession;
use app\models\user\User;
use Yii;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\Response;

class SessionController extends Controller
{
    public function behaviors()
    {
        return [
            'authenticator' => [
                'class' => WebSessionAuth::class,
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
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