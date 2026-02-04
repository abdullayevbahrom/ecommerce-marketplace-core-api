<?php
namespace app\modules\api\controllers;


use Yii;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\Notification;

class NotificationController extends Controller {
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
            'optional' => [''],
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

    public function actionIndex() {
        $user = Yii::$app->user->identity;
        if ($type = Yii::$app->request->get('type')) {
            if ($type == 'new') {
                $query = Notification::find()->with('order')->where(['user_id'=>$user->id])->andWhere(['status'=>0]);
            }
            if ($type == 'readed') {
                $query = Notification::find()->with('order')->where(['user_id'=>$user->id])->andWhere(['status'=>1]);
            }
        } else {
            $query = Notification::find()->with('order')->where(['user_id'=>$user->id]);
        }

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionDetail($id) {
        $user = Yii::$app->user->identity;
        $notification = Notification::find()->where(['id'=>$id, 'user_id'=>$user->id])->one();
        return ['data'=>$notification];
    }

    public function actionSetReaded() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!array_key_exists('id', $post)) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['id'=>'Введите ID уведомления']];
        }

        $notification = Notification::find()->with('order')->where(['user_id'=>$user->id, 'id'=>$post['id']])->one();

        if (!$notification) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Уведомление не найдено']];
        }

        $notification->status = 1;
        $notification->save(false);

        return ['data'=>$notification];
    }
}
?>