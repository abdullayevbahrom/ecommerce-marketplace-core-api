<?php

namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\stock\Stock;
use app\models\stock\StockSearch;

/**
 * StockController implements the CRUD actions for Stock model.
 */
class StockController extends Controller
{
    public $user;
    public $shop;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/stock/default']);
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

            if (!in_array('stock', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($status = null) {
        $searchModel = new StockSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('products', 'image')->andWhere(['shop_id'=>$this->shop->id]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionView($id) {
        $model = Stock::find()->with('products', 'image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionCreate($id = null) {
        $model = new Stock;

        // edit
        if ($id) {
            $model = Stock::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('stock_saved', 'Склад успешно сохранен');
                return $this->redirect(['/shop/stock/view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionLock($id) {
        $model = Stock::findOne(['id'=>$id, 'shop_id'=>$this->shop->id]);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Склад успешно заблокирован';
        } else {
            $model->status = 1;
            $msg = 'Склад успешно разблокирован';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('stock_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemove($id) {
        $model = Stock::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('stock_removed', 'Склад успешно удален');
        }

        return $this->redirect(['/shop/stock']);
    }
}
