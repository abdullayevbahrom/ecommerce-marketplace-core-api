<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;

use app\models\seller\SellerApplication;

class SellerController extends Controller
{
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
            'optional' => ['apply'],
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
            ]
        ];

        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    public function actionApply()
    {
        $post = Yii::$app->request->post();

        if (empty($post['name'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['name' => ['Заполните поле']]];
        }

        $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;

        if (empty($phone)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Заполните поле']]];
        }

        if (strlen($phone) != 12) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Номер телефона должен содержать ровно 12 цифр']]];
        }

        $existingApplication = SellerApplication::findOne(['phone' => $phone]);
        if ($existingApplication) {
            if ($existingApplication->status == SellerApplication::STATUS_PENDING || $existingApplication->status == SellerApplication::STATUS_REJECTED) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['phone' => ['Заявка с этим номером телефона уже подана и находится на рассмотрении']]];
            }
            if ($existingApplication->status == SellerApplication::STATUS_APPROVED) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['phone' => ['Заявка с этим номером телефона уже одобрена.']]];
            }
        } else {
            $application = new SellerApplication();
            $post['phone'] = $phone;
            $application->setAttributes($post);
        }

        if (!$application->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $application->errors];
        }

        if ($application->saveApplication()) {
            return [
                'data' => [
                    'message' => 'Заявка успешно отправлена. Мы свяжемся с вами в ближайшее время.',
                    'application' => $application
                ]
            ];
        } else {
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['server' => ['Произошла ошибка при сохранении заявки']]];
        }
    }
}
