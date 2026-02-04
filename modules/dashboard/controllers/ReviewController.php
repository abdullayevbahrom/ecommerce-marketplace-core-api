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
use app\models\product\Product;
use app\models\Images;
use app\models\product\review\ProductReview;

class ReviewController extends Controller {
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

    public function actionIndex($product_id) {
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        $ids = ArrayHelper::map(Product::find()->where(['shop_id'=>$shop->id])->all(), 'id', 'id');
        $query = ProductReview::find()->where(['in', 'product_id', $ids])->andWhere(['product_id'=>$product_id]);

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
        $model = ProductReview::find()->with('product')->where(['id'=>$id])->one();

        if (!$model || ($model && $model->product && ($model->product->shop_id != $shop->id))) {
            throw new HttpException(404, 'Page not found');
        }

        return ['data'=>$model];
    }

    public function actionRemove() {
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
        $model = ProductReview::find()->with('product')->where(['id'=>$post['product_id']])->one();

        if (!$model || ($model && $model->product && ($model->product->shop_id != $shop->id))) {
            throw new HttpException(404, 'Page not found');
        }

        $model->delete();

        $ids = ArrayHelper::map(Product::find()->where(['shop_id'=>$shop->id])->all(), 'id', 'id');
        $query = ProductReview::find()->where(['in', 'product_id', $ids]);

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
}