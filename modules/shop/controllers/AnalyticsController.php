<?php

namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;

use app\models\user\User;
use app\models\order\Order;
use app\models\order\product\OrderProduct;
use app\models\shop\Shop;

class AnalyticsController extends Controller {
    public $user;
    public $shop;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/shop/default']);
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

            if (!in_array('analytics', $accesses)) {
                return $this->redirect(['/shop/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($status = null) {
        $order_products = ArrayHelper::map(OrderProduct::find()->where(['shop_id'=>$this->shop->id])->all(), 'order_id', 'order_id');

        if ($type = Yii::$app->request->get('type')) {
            $type_data = ['week'=>'7 DAY', 'month'=>'1 MONTH', 'hyear'=>'6 MONTH', 'year'=>'12 MONTH'];
            $order_statistic = Order::find()->where('date >= DATE_SUB(CURRENT_DATE, INTERVAL '.$type_data[$type].')')->andWhere(['in', 'id', $order_products])->all();
        } else {
            $order_statistic = Order::find()->where('date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)')->andWhere(['in', 'id', $order_products])->all();
        }

        $data = [];

        foreach ($order_statistic as $k => $v) {
            $date = explode(' ', $v->date);
            $data[$date[0]]['amount'] += 1;
            $data[$date[0]]['price'] += $v->price;
        }

        return $this->render('index', [
            'data' => $data
        ]);
    }
}
