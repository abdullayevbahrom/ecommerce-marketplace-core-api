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
use app\models\Category;
use app\models\order\Order;
use app\models\order\product\OrderProduct;
use app\models\order\product\OrderProductRefund;
use app\models\Notification;

class OrderController extends Controller {
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
        
        $order_products = ArrayHelper::map(OrderProduct::find()->where(['shop_id'=>$shop->id])->all(), 'order_id', 'order_id');

        $query = Order::find()->with('user', 'payment', 'delivery')->where(['in', 'id', $order_products]);
        if ($user_id = Yii::$app->request->get('user_id')) {
            $query->andWhere(['user_id'=>$user_id]);
        }

        if ($status = Yii::$app->request->get('status')) {
            if ($status == '3') {
                $query->andWhere(['status_delivery'=>1, 'status_logist'=>0]);
            } else if ($status == '4') {
                $query->andWhere(['status_logist'=>$status]);
            } else if ($status == '5') {
                $query->andWhere(['status_logist'=>$status]);
            } else if ($status == '6') {
                $query->andWhere(['status_payment'=>0]);
            } else if ($status == '7') {
                $query->andWhere(['status_payment'=>1]);
            } else if ($status == '8') {
                $query->andWhere(['status'=>4]);
            } else if ($status == '9') {
                $query->andWhere(['status_logist'=>5, 'status_review'=>0]);
            } else if ($status == '10') {
                $products = ArrayHelper::map(OrderProduct::find()->where(['shop_id'=>$shop->id])->all(), 'id', 'id');
                $query = OrderProductRefund::find()->with('orderProduct', 'orderProduct.product.image')->where(['in', 'order_product_id', $products]);
            } else {
                $query->andWhere(['status'=>$status]);
            }
        }

        if (Yii::$app->request->get('status') == "0") {
            $query->andWhere(['status'=>0]);
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
        
        $model = Order::find()->with('user', 'payment', 'delivery')->where(['id'=>$id])->one();

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
        
        $model = Order::find()->with('user', 'payment', 'delivery')->where(['id'=>$id])->one();

        if ($model) {
            $model->delete();
        }

        $order_products = ArrayHelper::map(OrderProduct::find()->where(['shop_id'=>$shop->id])->all(), 'order_id', 'order_id');

        $query = Order::find()->with('user', 'payment', 'delivery')->where(['in', 'id', $order_products]);

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

    public function actionAccept() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $order = Order::findOne(['id'=>$post['id']]);

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Заказ не найден']];
        }

        $order->status = 1;
        if ($order->save(false)) {
            $notification = new Notification;
            $notification->saveObject($order->user_id, $order->id, 'order_accepted', 'Ваш заказ принят');
        }

        $model = Order::find()->with('user', 'payment', 'delivery')->where(['id'=>$post['id']])->one();
        return ['data'=>$model];
    }

    public function actionDecline() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $order = Order::findOne(['id'=>$post['id']]);

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Заказ не найден']];
        }

        $order->status = 2;
        if ($order->save(false)) {
            $notification = new Notification;
            $notification->saveObject($order->user_id, $order->id, 'order_declined', 'Ваш заказ отменен');
        }

        $model = Order::find()->with('user', 'payment', 'delivery')->where(['id'=>$post['id']])->one();
        return ['data'=>$model];
    }

    public function actionSetDelivery() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $order = Order::findOne(['id'=>$post['id']]);

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Заказ не найден']];
        }

        $order->status_delivery = 1;
        if ($order->save(false)) {
            $notification = new Notification;
            $notification->saveObject($order->user_id, $order->id, 'sended_to_delivery', 'Ваш заказ отправлен на доставку');
        }

        $model = Order::find()->with('user', 'payment', 'delivery')->where(['id'=>$post['id']])->one();
        return ['data'=>$model];
    }

    public function actionSetReturn() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $order = Order::findOne(['id'=>$post['id']]);

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Заказ не найден']];
        }

        $order->status = 4;
        if ($order->save(false)) {
            $notification = new Notification;
            $notification->saveObject($order->user_id, $order->id, 'sended_to_return', 'Ваш заказ отправлен на возврат');
        }

        $model = Order::find()->with('user', 'payment', 'delivery')->where(['id'=>$post['id']])->one();
        return ['data'=>$model];
    }

    // refunds
    public function actionRefunds() {
        $user = Yii::$app->user->identity;
        $shop = Shop::findOne(['user_id'=>$user->id]);
        if (!$shop) {
            $shop = Shop::findOne(['id'=>Yii::$app->user->identity->shop_id]);
        }
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['shop'=>'Магазин не найден']];
        }

        $products = ArrayHelper::map(OrderProduct::find()->where(['shop_id'=>$shop->id])->all(), 'id', 'id');
        $data = OrderProductRefund::find()->with('orderProduct', 'orderProduct.product.image')->where(['in', 'order_product_id', $products])->all();
        
        return ['data'=>$data];
    }

    public function actionRefundSend() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $model = new OrderProductRefund;
        $model->setAttributes($post);
        $model->user_id = $user->id;

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        $product = OrderProduct::findOne($post['order_product_id']);

        if ($model->save()) {
            $product->status = 2;
            $product->save(false);
        }

        $data = OrderProductRefund::find()->with('orderProduct', 'orderProduct.product.image')->where(['user_id'=>$user->id, 'id'=>$model->id])->one();
        return ['data' => $data];
    }

    public function actionFinish() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!array_key_exists('order_id', $post)) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['order_id'=>'Введите ID заказа']];
        }

        $order = Order::findOne($post['order_id']);
        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['order_id'=>'Заказ не найден']];
        }

        $order->status = 5;
        $order->save(false);

        $order = Order::findOne($post['order_id']);

        return ['data' => $order];
    }
}