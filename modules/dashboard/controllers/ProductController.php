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
use app\models\product\ProductColor;
use app\models\Images;
use app\models\Category;
use app\models\delivery\Delivery;

class ProductController extends Controller {
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
        $query = Product::find()->with('image', 'user', 'stock', 'category', 'brand')->where(['shop_id'=>$shop->id]);

        if ($category_id = Yii::$app->request->get('category_id')) {
            $ids = ArrayHelper::map(Category::find()->where(['parent_id'=>$id])->all(), 'id', 'id');
            $query->andWhere(['category_id'=>$category_id])->orWhere(['in', 'category_id', $ids]);
        }

        if ($brand_id = Yii::$app->request->get('brand_id')) {
            $query->andWhere(['brand_id'=>$brand_id]);
        }

        if ($shop_id = Yii::$app->request->get('shop_id')) {
            $query->andWhere(['shop_id'=>$shop_id]);
        }

        if ($status = Yii::$app->request->get('status')) {
            $query->andWhere(['status'=>$status]);
        }

        if ($stock_id = Yii::$app->request->get('stock_id')) {
            $query->andWhere(['stock_id'=>$stock_id]);
        }

        if ($tag_id = Yii::$app->request->get('tag_id')) {
            $query->andWhere(['tag_id'=>$tag_id]);
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
        $model = Product::find()->with('image', 'user', 'stock', 'category', 'brand')->where(['id'=>$id, 'shop_id'=>$shop->id])->one();

        return ['data'=>$model];
    }

    public function actionCreate() {
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

        $model = new Product;

        if ($post['product_id']) {
            $model = Product::findOne(['id'=>$post['product_id'], 'shop_id'=>$shop->id]);
            if (!$model) {
                Yii::$app->response->statusCode = 404;
                return ['errors'=>['id'=>'Товар не найден']];
            }
        }

        if ($post['delivery_id']) {
            $delivery = Delivery::findOne($post['delivery_id']);
            if (!$delivery) {
                Yii::$app->response->statusCode = 404;
                return ['errors'=>['id'=>'Способ доставки не найден']];
            }
        }
        
        $model->setAttributes($post);

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        $category_tree = $model->setCategoryDashboard();

        if (!$post['product_id'] && $post['colors']) {
            $token_key = Yii::$app->security->generateRandomString();

            foreach ($post['colors'] as $key => $color) {
                $product = $model->saveObject(true, $color, $token_key, $category_tree);

                if ($key == 0) {
                    $image = new Images;
                    if ($product->image) {
                        $image = $product->image;
                    }
                    if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
                        $image->uploadPhoto($product->id, 'product');
                    }

                    $image = new Images;
                    if ($image->imageFiles = UploadedFile::getInstancesByName('galleryPhoto')) {
                        $image->uploadPhoto($product->id, 'product', 2);
                    }
                }
            }
        } else if (!$post['product_id'] && !$post['colors']) {
            $token_key = Yii::$app->security->generateRandomString();

            $product = $model->saveObject(true, null, $token_key, $category_tree);

            $image = new Images;

            if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
                $image->uploadPhoto($product->id, 'product');
            }

            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstancesByName('galleryPhoto')) {
                $image->uploadPhoto($product->id, 'product', 2);
            }
        } else {
            if ($product = $model->updateObject(true)) {
                $image = new Images;
                if ($model->image) {
                    $image = $model->image;
                }
                if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
                    $image->uploadPhoto($model->id, 'product');
                }

                $image = new Images;
                if ($image->imageFiles = UploadedFile::getInstancesByName('galleryPhoto')) {
                    $image->uploadPhoto($model->id, 'product', 2);
                }

                $image = new Images;
                if ($image->imageFiles = UploadedFile::getInstancesByName('colors[image]')) {
                    $image->colors = $model->colors['color'];
                    $image->uploadPhotoColor($model->id);
                }
            }
        }

        $model = Product::find()->with('image', 'user', 'stock', 'category', 'brand')->where(['id'=>$product->id])->one();
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
        $model = Product::find()->with('image', 'user', 'stock', 'category', 'brand')->where(['id'=>$post['id'], 'shop_id'=>$shop->id])->one();

        $get = file_get_contents('log.txt');
        file_put_contents('log.txt',$get.'
        '.date("Y-m-s H:i:s").' - dashrem '.$post['id'].' - '.Yii::$app->user->identity->id);
        
        // if ($model && (Yii::$app->user->identity->role == User::ROLE_ADMIN)) {
        //     $model->removeObject();
        // }

        $query = Product::find()->with('image', 'user', 'stock', 'category', 'brand')->where(['shop_id'=>$shop->id]);

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

        $product = Product::findOne(['id'=>$post['product_id'], 'shop_id'=>$shop->id]);

        if (!$product) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Товар не найден']];
        }

        $product->status = 2;
        $product->save(false);

        $model = Product::find()->with('image', 'user', 'stock', 'category', 'brand')->where(['id'=>$post['product_id'], 'shop_id'=>$shop->id])->one();
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

        $product = Product::findOne(['id'=>$post['product_id'], 'shop_id'=>$shop->id]);

        if (!$product) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Товар не найден']];
        }

        $product->status = 1;
        $product->save(false);

        $model = Product::find()->with('image', 'user', 'stock', 'category', 'brand')->where(['id'=>$post['product_id'], 'shop_id'=>$shop->id])->one();
        return ['data'=>$model];
    }

    public function actionSearch($query) {
        $user = Yii::$app->user->identity;
        $shop = Shop::findOne(['user_id'=>$user->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>$user->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }
        
        $products = Product::find()->with('image', 'category', 'gallery', 'productFilters')
            ->where(['status'=>1])->andWhere(['shop_id'=>$shop->id])
            ->andWhere(['or', 
                ['like', 'name_ru', $query],
                ['like', 'name_uz', $query],
                ['like', 'name_en', $query],
                ['like', 'name_trans_ru', $query],
                ['like', 'name_trans_en', $query],
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
}