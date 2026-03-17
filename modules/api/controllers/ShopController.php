<?php

namespace app\modules\api\controllers;


use Yii;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use app\models\shop\Shop;
use app\models\user\favorite_shop\UserShopFavorite;

class ShopController extends Controller
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
            'optional' => ['*'],
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

        $behaviors['authenticator']['except'] = ['options'];

        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    public function actionIndex()
    {
        $query = Shop::find()->with('image')->where(['status' => 1])->orderBy('id desc');

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

    public function actionSearch($query)
    {
        $products = Shop::find()->with('image')
            ->where(['status' => 1])
            ->andWhere([
                'or',
                ['like', 'name_ru', $query],
                ['like', 'name_uz', $query],
                ['like', 'name_en', $query],
                ['like', 'description_ru', $query],
                ['like', 'description_uz', $query],
                ['like', 'description_en', $query]
            ]);

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $products,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionDetail($id)
    {
        $shop = Shop::find()->with('image', 'gallery', 'shopSeller', 'user')->where(['id' => $id])->one();

        return ['data' => $shop];
    }

    public function actionFavorites()
    {
        $user = Yii::$app->user->identity;

        $ids = ArrayHelper::map(UserShopFavorite::find()->where(['user_id' => $user->id])->all(), 'shop_id', 'shop_id');
        $query = Shop::find()->with('image')->where(['status' => 1])->andWhere(['in', 'id', $ids]);

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

    public function actionSetFavorite()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $user_shop_favorite = new UserShopFavorite;
        $user_shop_favorite->setAttributes($post);

        if (!$user_shop_favorite->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $user_shop_favorite->errors];
        }

        $shop = Shop::find()->with('image')->where(['id' => $post['shop_id']])->one();
        $favorite = UserShopFavorite::findOne(['user_id' => $user->id, 'shop_id' => $post['shop_id']]);

        if ($favorite) {
            $favorite->delete();
            Yii::$app->response->statusCode = 200;
            return ['data' => $shop];
        }

        $user_shop_favorite->saveObject($user->id);

        Yii::$app->response->statusCode = 200;
        return ['data' => $shop];
    }
}
