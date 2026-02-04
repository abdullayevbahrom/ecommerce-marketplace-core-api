<?php
namespace app\modules\api\controllers;

use Yii;
use yii\web\Response;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\seller\SellerApplication;

class SellerController extends Controller {
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
            'optional' => ['apply'], // No auth required for applying
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

        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    /**
     * Apply to become a seller
     * POST /api/seller/apply
     * 
     * @return array
     */
    public function actionApply() {
        $post = Yii::$app->request->post();

        // Basic validation
        if (empty($post['name'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['name' => ['Заполните поле']]];
        }

        if (empty($post['phone'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Заполните поле']]];
        }

        // Clean and validate phone number
        $cleanPhone = preg_replace('/\D/', '', $post['phone']); // Remove all non-digits
        
        if (strlen($cleanPhone) != 12) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Номер телефона должен содержать ровно 12 цифр']]];
        }
        
        // Update phone in post data with cleaned version
        $post['phone'] = $cleanPhone;

        // Check if application already exists for this phone
        $existingApplication = SellerApplication::findOne(['phone' => $post['phone']]);
        if ($existingApplication) {
            // If there's a pending application, return error
            if ($existingApplication->status == SellerApplication::STATUS_PENDING || $existingApplication->status == SellerApplication::STATUS_REJECTED) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['phone' => ['Заявка с этим номером телефона уже подана и находится на рассмотрении']]];
            }
            // If approved, return error (already a seller)
            if ($existingApplication->status == SellerApplication::STATUS_APPROVED) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['phone' => ['Заявка с этим номером телефона уже одобрена.']]];
            }

        } else {
            // Create new application
            $application = new SellerApplication();
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