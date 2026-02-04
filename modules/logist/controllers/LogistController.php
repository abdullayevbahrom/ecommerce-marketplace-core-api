<?php

namespace app\modules\logist\controllers;

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
use app\models\Category;

class LogistController extends Controller {
    public $user;
    public $logist;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/logist/default']);
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

            if (!in_array('logist', $accesses)) {
                return $this->redirect(['/shop/default/profile']);
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

    public function actionView() {
        $model = Logist::find()->with('image', 'logistRegions')->where(['id'=>$this->logist->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionCreate() {
        $model = Logist::find()->with('image')->where(['id'=>$this->logist->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('logist_saved', 'Товар успешно сохранен');
                return $this->redirect(['/logist/logist/view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    // tariffs
    public function actionTariff() {
        $model = Logist::findOne($this->logist->id);

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

    public function actionTariffCreate($tariff_id = null) {
        $model = new LogistRegion;

        // edit
        if ($tariff_id) {
            $model = LogistRegion::find()->with('logistRegionPrices')->where(['id'=>$tariff_id, 'logist_id'=>$this->logist->id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = explode('/', $model->region_tree);
            $current_regions = [];

            foreach ($tree as $key => $value_id) {
                $current_regions[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$value_id])->all(), 'id', 'name_ru');
            }
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('logist_tariff_saved', 'Данные успешно сохранены');
                return $this->redirect(['/logist/logist/tariff-view', 'tariff_id' => $model->id]);
            }
        }

        $regions = ArrayHelper::map(Category::find()->with('childs')->where(['parent_id'=>0, 'type'=>'region'])->all(), 'id', 'name_ru');
        $units = ArrayHelper::map(Category::find()->with('childs')->where(['parent_id'=>0, 'type'=>'unit'])->all(), 'id', 'name_ru');

        return $this->render('tariff/create', [
            'model' => $model,
            'current_regions' => $current_regions,
            'regions' => $regions,
            'units' => $units,
            'tree' => $tree
        ]);
    }

    public function actionTariffView($tariff_id) {
        $model = LogistRegion::find()->with('region', 'logistRegionPrices', 'logistRegionPrices.unit')->where(['id'=>$tariff_id, 'logist_id'=>$this->logist->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('tariff/view', [
            'model' => $model
        ]);
    }

    public function actionTariffRemove($tariff_id) {
        $model = LogistRegion::find()->where(['logist_id'=>$this->logist->id, 'id'=>$tariff_id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('logist_tariff_removed', 'Данные успешно удалены');
        }

        return $this->redirect(['/logist/logist/tariff']);
    }
}
