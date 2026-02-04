<?php
namespace app\modules\dashboard\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\user\User;

class AuthController extends Controller {
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
            'optional' => ['index']
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

    // authorization
    public function actionIndex() {
        $post = Yii::$app->request->post();

        $model = User::find()->where(['login'=>$post['login'], 'role'=>User::ROLE_SHOP])->one();
        if (!$model) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['login'=>['Пользователь не найден']]];
        }

        $model->scenario = User::SIGNIN_ADMIN;
        $model->setAttributes($post);

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        $model->status = 1;
        if (!$model->token) {
            $model->token = $model->generateToken();
        }
        $model->password = Yii::$app->security->generatePasswordHash($post['password']);

        if ($model->save(false)) {
            return ['data'=>User::find()->with('image')->where(['id'=>$model->id])->one()];
        }
    }

    public function actionLogOut() {
        /** @var User|null $model */
        $model = Yii::$app->user->identity;

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['user'=>['Пользователь не найден']]];
        }

        try {
            $client = new \GuzzleHttp\Client();
            $baseUrl = Yii::$app->params['warehouseApiUrl'] ?? 'http://warehouse.example.com';
            $client->post($baseUrl . '/api/v1/shop/log-out', [
                'form_params' => [
                    'login' => $model->login,
                    'token' => $model->token,
                ],
                'timeout' => 1,
                'verify' => false
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        $model->token = '';
        if ($model->save()) {
            Yii::$app->user->logout();
            throw new HttpException(200, 'OK');
        }
        
        return $model->errors;
    }
}