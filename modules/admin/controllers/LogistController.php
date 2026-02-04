<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;

use app\models\user\User;
use app\models\logist\Logist;
use app\models\logist\LogistSearch;
use app\models\logist\region\LogistRegion;
use app\models\logist\region\LogistRegionSearch;
use app\models\logist\region\LogistRegionPrice;
use app\models\shop\Shop;
use app\models\Category;

class LogistController extends Controller {
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

            if (!in_array('logist', $accesses)) {
                return $this->redirect(['/admin/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($status = null) {
        $searchModel = new LogistSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image');

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
        $model = Logist::find()->with('image', 'logistRegions')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionCreate($id = null) {
        $model = new Logist;

        // edit
        if ($id) {
            $model = Logist::find()->with('image')->where(['id'=>$id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        } else {
            $model->scenario = Logist::LOGIST_CREATE;
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('logist_saved', 'Saved');
                return $this->redirect(['/admin/logist/view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionLock($id) {
        $model = Logist::findOne($id);

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
            Yii::$app->session->setFlash('logist_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemove($id) {
        $model = Logist::find()->with('image')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('logist_removed', 'Deleted');
        }

        return $this->redirect(['/admin/logist']);
    }

    // tariffs
    public function actionTariff($id) {
        $model = Logist::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $searchModel = new LogistRegionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('region')->andWhere(['in', 'logist_id', $model->id]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        return $this->render('tariff/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionTariffView($id, $tariff_id) {
        $model = LogistRegion::find()->with('region', 'regionA', 'logistRegionPrices', 'logistRegionPrices.unit')->where(['id'=>$tariff_id, 'logist_id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('tariff/view', [
            'model' => $model
        ]);
    }

    public function actionTariffCreate($id, $tariff_id = null) {
        $model = new LogistRegion;

        // edit
        if ($tariff_id) {
            $model = LogistRegion::find()->with('logistRegionPrices')->where(['id'=>$tariff_id, 'logist_id'=>$id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = explode('/', $model->region_tree);
            $current_regions = [];

            foreach ($tree as $key => $value_id) {
                $current_regions[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$value_id])->all(), 'id', 'name_ru');
            }

            $tree_a = explode('/', $model->region_a_tree);
            $current_regions_a = [];

            foreach ($tree_a as $key_a => $value_a_id) {
                $current_regions_a[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$value_a_id])->all(), 'id', 'name_ru');
            }
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('logist_tariff_saved', 'Saved');
                return $this->redirect(['/admin/logist/tariff-view', 'id'=>$id, 'tariff_id' => $model->id]);
            }
        }

        $regions = ArrayHelper::map(Category::find()->with('childs')->where(['parent_id'=>0, 'type'=>'region'])->all(), 'id', 'name_ru');
        $units = ArrayHelper::map(Category::find()->with('childs')->where(['parent_id'=>0, 'type'=>'unit'])->all(), 'id', 'name_ru');

        return $this->render('tariff/create', [
            'model' => $model,
            'current_regions_a' => $current_regions_a,
            'regions' => $regions,
            'units' => $units,
            'tree' => $tree,
            'tree_a' => $tree_a
        ]);
    }

    public function actionTariffRemove($id, $tariff_id) {
        $model = LogistRegion::find()->where(['logist_id'=>$id, 'id'=>$tariff_id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('logist_tariff_removed', 'Saved');
        }

        return $this->redirect(['/admin/logist/tariff', 'id'=>$id]);
    }
}
