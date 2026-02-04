<?php

namespace app\modules\admin\controllers;

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

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

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
                return $this->redirect(['/admin/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($status = null) {
        $searchModel = new StockSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('shop', 'products', 'image');

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        $shops = ArrayHelper::map(Shop::find()->where(['status'=>1])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'shops' => $shops
        ]);
    }

    public function actionView($id) {
        $model = Stock::find()->with('shop', 'products', 'image')->where(['id'=>$id])->one();

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
            $model = Stock::find()->with('shop', 'image')->where(['id'=>$id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('stock_saved', 'Saved');
                return $this->redirect(['/admin/stock/view', 'id' => $model->id]);
            }
        }

        $shops = ArrayHelper::map(Shop::find()->where(['status'=>1])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'shops' => $shops
        ]);
    }

    public function actionLock($id) {
        $model = Stock::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Blocked';
        } else {
            $model->status = 1;
            $msg = 'Unblocked';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('stock_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemove($id) {
        $model = Stock::find()->with('image')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('stock_removed', 'Deleted');
        }

        return $this->redirect(['/admin/stock']);
    }
}
