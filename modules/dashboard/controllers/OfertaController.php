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
use app\models\shop\oferta\ShopOferta;
use app\models\Images;

class OfertaController extends Controller {
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
        $query = ShopOferta::find()->where(['shop_id'=>$shop->id]);

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
        $model = ShopOferta::find()->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

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
        $model = ShopOferta::find()->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

        if ($model) {
            $model->delete();
        }

        $query = ShopOferta::find()->where(['shop_id'=>$shop->id]);

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

        $oferta = ShopOferta::findOne(['id'=>$post['oferta_id'], 'shop_id'=>$shop->id]);

        if (!$oferta) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Оферта не найдена']];
        }

        $oferta->status = 2;
        $oferta->save(false);

        $model = ShopOferta::find()->where(['id'=>$post['oferta_id'], 'shop_id'=>$shop->id])->one();
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

        $oferta = ShopOferta::findOne(['id'=>$post['oferta_id'], 'shop_id'=>$shop->id]);

        if (!$oferta) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Оферта не найдена']];
        }

        $oferta->status = 1;
        $oferta->save(false);

        $model = ShopOferta::find()->where(['id'=>$post['oferta_id'], 'shop_id'=>$shop->id])->one();
        return ['data'=>$model];
    }
}