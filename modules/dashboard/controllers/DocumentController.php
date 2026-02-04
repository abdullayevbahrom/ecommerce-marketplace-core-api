<?php
namespace app\modules\dashboard\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\shop\document\ShopDocument;
use app\models\Images;

class DocumentController extends Controller {
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
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        $query = ShopDocument::find()->where(['shop_id'=>$shop->id]);

        if ($status = Yii::$app->request->get('status')) {
            $query->andWhere(['status'=>$status]);
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
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        $model = ShopDocument::find()->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

        return ['data'=>$model];
    }

    public function actionRemove($id) {
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        $model = ShopDocument::find()->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

        if ($model) {
            $model->delete();
        }

        $query = ShopDocument::find()->where(['shop_id'=>$shop->id]);

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

    public function actionLock() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $shop = Shop::findOne(['user_id'=>$user->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }

        $document = ShopDocument::findOne(['id'=>$post['document_id'], 'shop_id'=>$shop->id]);

        if (!$document) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Оферта не найдена']];
        }

        $document->status = 2;
        $document->save(false);

        $model = ShopDocument::find()->where(['id'=>$post['document_id'], 'shop_id'=>$shop->id])->one();
        return ['data'=>$model];
    }

    public function actionUnlock() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $shop = Shop::findOne(['user_id'=>$user->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }

        $document = ShopDocument::findOne(['id'=>$post['document_id'], 'shop_id'=>$shop->id]);

        if (!$document) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Оферта не найдена']];
        }

        $document->status = 1;
        $document->save(false);

        $model = ShopDocument::find()->where(['id'=>$post['document_id'], 'shop_id'=>$shop->id])->one();
        return ['data'=>$model];
    }
}