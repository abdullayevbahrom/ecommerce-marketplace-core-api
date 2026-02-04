<?php
namespace app\modules\logist\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\logist\Logist;
use app\models\order\Order;
use app\models\order\OrderSearch;
use app\models\order\product\OrderProduct;
use app\models\order\product\OrderProductSearch;

class OrderController extends Controller{
	public $user;
    public $logist;
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();
        $this->logist = Logist::findOne(['user_id'=>$this->user->id]);

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
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('user', 'payment', 'delivery')->andWhere(['status'=>1, 'status_delivery'=>1, 'logist_id' => $this->logist->id])->orderBy('id desc');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionView($id) {
        $model = Order::find()->with('user', 'orderProducts', 'shop')->where(['status'=>1, 'status_delivery'=>1, 'id'=>$id, 'logist_id'=>$this->logist->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $products = OrderProduct::find()->with('product', 'product.image', 'orderProductFilter', 'orderProductFilter.productFilter', 'orderProductFilter.productFilter.filter')->where(['order_id'=>$model->id])->all();

        return $this->render('view', [
            'model' => $model,
            'products' => $products
        ]);
    }

    public function actionRemove($id) {
        $model = Order::find()->with('user', 'orderProducts')->where(['id'=>$id, 'logist_id'=>$this->logist->id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_LOGIST) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('order_removed', 'Заказ успешно удален');
        }

        return $this->redirect(['/logist/order']);
    }

    public function actionSetStatus($id, $status) {
        $model = Order::findOne(['id'=>$id, 'logist_id'=>$this->logist->id]);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model) {
            if ($status == 1) {
                $message = 'Заказ успешно принят';
            } else if ($status == 2) {
                $message = 'Заказ успешно отклонен';
            } else if ($status == 3) {
                $message = 'Заказ успешно отправлен на доставку';
            } else if ($status == 4) {
                $message = 'Заказа в пути';
            } else if ($status == 5) {
                $message = 'Заказ успешно доставлен';
            } else {
                throw new HttpException(404, 'Page not found');
            }

            $model->status_logist = $status;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('order_set_status', $message);
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        return $this->redirect(['/logist/order/view', 'id'=>$id]);
    }
}