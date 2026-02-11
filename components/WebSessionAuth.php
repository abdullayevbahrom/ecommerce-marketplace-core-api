<?php 

namespace app\components;

use app\models\session\WebSession;
use app\models\user\User;
use Yii;

class WebSessionAuth extends \yii\base\ActionFilter
{
    public function beforeAction($action)
    {
        $token = Yii::$app->request->headers->get('Web-Session');

        if (!$token) {
            return true;
        }

        $session = WebSession::find()
            ->where([
                'access_token' => $token,
                'is_revoked' => 0
            ])
            ->andWhere(['>', 'expires_at', time()])
            ->one();

        if ($session) {
            $user = User::findOne($session->user_id);
            Yii::$app->user->setIdentity($user);
        }

        return true;
    }
}
