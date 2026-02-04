<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\order\Order;
use app\models\order\OrderSearch;
use app\models\order\product\OrderProduct;
use app\models\order\product\OrderProductSearch;

class OrderController extends Controller{
	public $user;
    public $shop;
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();
        $this->shop = Shop::findOne(['user_id'=>$this->user->id]);

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('order', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $order_products = ArrayHelper::map(OrderProduct::find()->where(['shop_id'=>$this->shop->id])->all(), 'order_id', 'order_id');

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('user', 'payment', 'delivery')->andWhere(['in', 'id', $order_products])->orderBy('id desc');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionView($id) {
        $order_products = OrderProduct::find()->where(['shop_id'=>$this->shop->id, 'order_id'=>$id])->all();
        $model = Order::find()->with('user', 'orderProducts')->where(['id'=>$id])->one();

        if (!$model || !$order_products) {
            throw new HttpException(404, 'Page not found');
        }

        $products = OrderProduct::find()->with([
            'product', 
            'product.image', 
            'orderProductFilter', 
            'orderProductFilter.productFilter', 
            'orderProductFilter.productFilter.filter',
            'productReview', // Single review from order user
            'productReviews', // All reviews for the product
            'productReviews.user', // Users who wrote reviews
            'delivery' // Add delivery relationship
        ])->where(['order_id'=>$model->id, 'shop_id'=>$this->shop->id])->all();

        return $this->render('view', [
            'model' => $model,
            'products' => $products
        ]);
    }

    public function actionRemove($id) {
        $model = Order::find()->with('user', 'orderProducts')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('order_removed', 'Заказ успешно удален');
        }

        return $this->redirect(['/shop/order']);
    }

    public function actionAccept($id, $status) {
        $model = Order::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model) {
            if ($status == 1) {
                $message = 'Заказ успешно принят';
            } else if ($status == 2) {
                $message = 'Заказ успешно отклонен';
            } else {
                throw new HttpException(404, 'Page not found');
            }

            $model->status = $status;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('order_accepted', $message);
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        return $this->redirect(['/shop/order']);
    }
}