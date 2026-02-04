<?php

namespace app\modules\logist\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\shop\ShopSearch;

class ShopController extends Controller {
    public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/logist/default']);
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

            if (!in_array('shop', $accesses)) {
                return $this->redirect(['/logist/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($status = null) {
        $searchModel = new ShopSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('user', 'image');

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
        $model = Shop::find()->with('user', 'shopSeller', 'gallery', 'image')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionCreate($id = null) {
        $model = new Shop;

        // edit
        if ($id) {
            $model = Shop::find()->with('user', 'shopSeller', 'gallery', 'image')->where(['id'=>$id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        } else {
            $model->scenario = Shop::SHOP_CREATE;
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('shop_saved', 'Магазин успешно сохранен');
                return $this->redirect(['/admin/shop/view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionLock($id) {
        $model = Shop::find()->with('user')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $model->user->status = 2;
            $msg = 'Магазин успешно заблокирован';
        } else {
            $model->status = 1;
            $model->user->status = 1;
            $msg = 'Магазин успешно разблокирован';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('shop_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemove($id) {
        $model = Shop::find()->with('image')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('shop_removed', 'Магазин успешно удален');
        }

        return $this->redirect(['/admin/shop']);
    }

    public function actionLocationRemove($id) {
        $model = Shop::find()->with('image', 'user')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->redirect(['/admin/shop/create', 'id'=>$id]);
    }
}
