<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;

use app\models\user\User;
use app\models\user\UserSearch;
use app\models\shop\Shop;
use app\models\shop\ShopSearch;

class ShopController extends Controller {
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

            if (!in_array('shop', $accesses)) {
                return $this->redirect(['/admin/default/profile']);
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
                Yii::$app->session->setFlash('shop_saved', 'Saved');
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
            $msg = 'Blocked';
        } else {
            $model->status = 1;
            $model->user->status = 1;
            $msg = 'Unblocked';
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
            Yii::$app->session->setFlash('shop_removed', 'Deleted');
        }

        return $this->redirect(['/admin/shop']);
    }

    public function actionLocationRemove($id) {
        $model = Shop::find()->with('image', 'user')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model) {
            $model->save(false);
        }

        return $this->redirect(['/admin/shop/create', 'id'=>$id]);
    }

    // managers
    public function actionManagers($id) {
        $model = Shop::find()->with('image', 'user')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->where(['shop_id'=>$model->id, 'manager'=>1]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        return $this->render('managers/index', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionManagerView($id, $manager_id) {
        $model = Shop::find()->with('image', 'user')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $manager = User::findOne(['shop_id'=>$model->id, 'id'=>$manager_id]);

        return $this->render('managers/view', [
            'model' => $model,
            'manager' => $manager
        ]);
    }

    public function actionManagerCreate($id, $manager_id = null) {
        $model = Shop::find()->with('image', 'user')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($manager_id) {
            $manager = User::findOne(['shop_id'=>$model->id, 'id'=>$manager_id]);
            $current_password = $manager->password;
        } else {
            $manager = new User;
            $current_password = null;
        }

        $manager->scenario = User::USER_SHOP_SIGNIN;

        if ($manager->load(Yii::$app->request->post()) && $manager->validate()) {
            $manager->password = !$manager->password ? $current_password : $manager->generatePassword(Yii::$app->request->post()['User']['password']);

            $manager->status = 1;
            $manager->role = User::ROLE_SHOP;
            $manager->shop_id = $model->id;
            $manager->manager = 1;

            if ($manager->save()) {
                Yii::$app->session->setFlash('manager_saved', 'Saved');
                return $this->redirect(['/admin/shop/manager-view', 'id'=>$model->id, 'manager_id' => $manager->id]);
            }
        }

        return $this->render('managers/create', [
            'model' => $model,
            'manager' => $manager
        ]);
    }

    public function actionManagerRemove($id, $manager_id) {
        $model = Shop::find()->with('image', 'user')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $manager = User::findOne(['shop_id'=>$model->id, 'id'=>$manager_id]);

        if ($manager && $manager->delete()) {
            Yii::$app->session->setFlash('manager_removed', 'Deleted');
        }

        return $this->redirect(['/admin/shop/managers', 'id'=>$model->id,]);
    }
}
