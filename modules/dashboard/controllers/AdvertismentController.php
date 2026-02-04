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
use app\models\shop\advertising\ShopAdvertising;
use app\models\product\Product;
use app\models\Images;
use app\models\Category;

class AdvertismentController extends Controller {
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
        $query = ShopAdvertising::find()->with('image')->where(['shop_id'=>$shop->id]);

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
        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

        return ['data'=>$model];
    }

    public function actionCreate($id = null) {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $shop = Shop::findOne(['user_id'=>$user->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>$user->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }

        $model = new ShopAdvertising;

        if ($id) {
            $model = ShopAdvertising::findOne(['id'=>$id, 'shop_id'=>$shop->id]);
            if (!$model) {
                Yii::$app->response->statusCode = 404;
                return ['errors'=>['id'=>'Реклама не найдена']];
            }
        }
        
        $model->setAttributes($post);

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        if ($model->saveObject(true)) {
            $image = new Images;
            if ($model->image) {
                $image = $model->image;
            }
            if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
                $image->uploadPhoto($model->id, 'shop_advertising');
            }
        }

        $model = ShopAdvertising::find()->with('image')->where(['id'=>$model->id, 'shop_id'=>$shop->id])->one();
        return ['data'=>$model];
    }

    public function actionRemove() {
        $post = Yii::$app->request->post();

        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        $model = ShopAdvertising::find()->with('image')->where(['id'=>$post['id'], 'shop_id'=>$shop->id])->one();

        if ($model) {
            $model->removeObject();
        }

        $query = ShopAdvertising::find()->with('image')->where(['shop_id'=>$shop->id]);

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

        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Товар не найден']];
        }

        $model->status = 2;
        $model->save(false);

        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$shop->id])->one();
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

        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Товар не найден']];
        }

        $model->status = 1;
        $model->save(false);

        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$shop->id])->one();
        return ['data'=>$model];
    }
}