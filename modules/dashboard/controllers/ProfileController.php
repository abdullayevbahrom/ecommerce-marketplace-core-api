<?php
namespace app\modules\dashboard\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\Images;

class ProfileController extends Controller {
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
            'optional' => []
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
        $user = User::find()->with('image')->where(['id'=>Yii::$app->user->identity->id])->one();
        $data = $user->toArray();
        $data['login'] = $user->login;
        return ['data'=>$data];
    }

    public function actionUpdate() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $model = User::findOne(['id'=>$user->id, 'role'=>User::ROLE_SHOP]);
        $password = $model->password;
        $model->scenario = User::UPDATE_ADMIN;

        $model->setAttributes($post);

        $model->password = $post['password'] ? Yii::$app->security->generatePasswordHash($post['password']) : $password;

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        if ($model->save()) {
            if (array_key_exists('shop_name', $post)) {
                $shop = Shop::findOne(['user_id'=>$user->id]);
                $shop->name_ru = $post['shop_name'];
                $shop->save(false);
            }
            $image = new Images;
            if ($model->image) {
                $image = $model->image;
            }
            if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
                $image->uploadPhoto($model->id, 'user');
            }

            $user = User::find()->with('image')->where(['id'=>Yii::$app->user->identity->id])->one();
            return $user;
        }

        Yii::$app->response->statusCode = 422;
        return ['errors'=>$model->errors];
    }
}