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

        $companyName = trim((string)($post['company_name'] ?? $post['companyName'] ?? ''));
        $responsibleName = trim($post['name'] ?? '');
        $inn = preg_replace('/\D/', '', (string)($post['inn'] ?? ''));

        if ($companyName === '') {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['company_name' => ['Заполните поле']]];
        }

        if ($responsibleName === '') {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['responsible_name' => ['Заполните поле']]];
        }

        if ($inn === '') {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['inn' => ['Заполните поле']]];
        }

        if (!preg_match('/^\d{9}$/', $inn)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['inn' => ['ИНН должен состоять ровно из 9 цифр']]];
        }

        if (empty($responsibleName)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['name' => ['Заполните поле']]];
        }

        $phone = isset($post['phone']) ? preg_replace('/\D/', '', trim((string) $post['phone'])) : null;

        if (!empty($phone) && strlen($phone) === 9) {
            $phone = '998' . $phone;
        }

        if (empty($phone)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Заполните поле']]];
        }

        if (strlen($phone) !== 12) {
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

            $application = $existingApplication;
        } else {
            $application = new SellerApplication();
        }

        $post['phone'] = $phone;
        $post['name'] = $responsibleName;
        $application->setAttributes($post);

        // Keep additional fields even if dedicated columns are absent.
        $application->admin_notes = json_encode([
            'company_name' => $companyName,
            'inn' => $inn,
            'responsible_name' => $responsibleName,
        ], JSON_UNESCAPED_UNICODE);

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
