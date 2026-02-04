<?php
namespace app\modules\api\controllers;


use Yii;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\feedback\Feedback;
use app\models\shop\Shop;

class FeedbackController extends Controller {
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['send'],
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
            ]
        ];

        $behaviors['authenticator']['except'] = ['options'];

        $behaviors['authenticator'] = $auth;
        
        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    public function actionSend() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $feedback = new Feedback;
        $feedback->setAttributes($post);

        if ($user) {
            $feedback->user_id = $user->id;
        }

        if (!$feedback->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$feedback->errors];
        }

        $feedback->save();

        return ['data'=>$feedback];
    }

    public function actionSendShop() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!array_key_exists('shop_id', $post)) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['shop_id'=>'Укажите магазин']];
        }

        $shop = Shop::findOne($post['shop_id']);
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop_id'=>'Магазин не найден']];
        }

        $feedback = new Feedback;
        $feedback->setAttributes($post);

        if ($user) {
            $feedback->user_id = $user->id;
        }

        if (!$feedback->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$feedback->errors];
        }

        $feedback->save();

        return ['data'=>$feedback];
    }
}
?>