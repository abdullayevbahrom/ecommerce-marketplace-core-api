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

class ShopController extends Controller {
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
        $model = Shop::find()->with('shopSeller', 'gallery', 'image')->where(['user_id'=>Yii::$app->user->identity->id])->one();
        if (!$model) {
            $model = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return ['data'=>$model];
    }

    public function actionUpdate() {
        $post = Yii::$app->request->post();
        $model = Shop::find()->with('user', 'shopSeller', 'gallery', 'image')->where(['user_id'=>Yii::$app->user->identity->id])->one();
        if (!$model) {
            $model = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }

        $model->scenario = Shop::SHOP_UPDATE;
        $model->setAttributes($post);

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        if ($model->save()) {
            $model->saveSeller();

            $image = new Images;
            if ($model->image) {
                $image = $model->image;
            }
            if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
                $image->uploadPhoto($model->id, 'shop');
            }

            // banner
            $image = new Images;
            if ($model->banner) {
                $image = $model->banner;
            }
            if ($image->imageFiles[] = UploadedFile::getInstanceByName('banner')) {
                $image->uploadPhoto($model->id, 'shop', 3);
            }
        }

        $model = Shop::find()->with('shopSeller', 'gallery', 'image', 'banner')->where(['user_id'=>Yii::$app->user->identity->id])->one();

        return ['data'=>$model];
    }

    public function actionAddUser() {
        $post = Yii::$app->request->post();
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }

        $user = new User;
        if (array_key_exists('user_id', $post)) {
            $user = User::findOne($post['user_id']);
        }
        
        $user->scenario = User::USER_SHOP_SIGNIN;
        $user->setAttributes($post);

        if (!$user->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$user->errors];
        }

        $user->password = $user->generatePassword($post['password']);

        $user->status = 1;
        $user->role = User::ROLE_SHOP;
        $user->shop_id = $shop->id;
        $user->save();

        $user = User::findOne($user->id);

        return $user;
    }

    public function actionUsers() {
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        $query = User::find()->where(['shop_id'=>$shop->id])->andWhere(['!=', 'id', Yii::$app->user->identity->id]);

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

    public function actionUserDetail($id) {
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        $user = User::find()->where(['shop_id'=>$shop->id, 'id'=>$id])->one();

        return $user;
    }

    public function actionUserRemove() {
        $post = Yii::$app->request->post();
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }

        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }

        $user = User::find()->where(['shop_id'=>$shop->id, 'id'=>$post['user_id']])->one();

        if ($user) {
            $user->delete();
        }

        $query = User::find()->where(['shop_id'=>$shop->id])->andWhere(['!=', 'id', Yii::$app->user->identity->id]);

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