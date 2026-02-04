<?php

namespace app\modules\shop\controllers;

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

    public function actionView($id) {
        $model = Logist::find()->with('image', 'logistRegions')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
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
        $model = LogistRegion::find()->with('region', 'logistRegionPrices', 'logistRegionPrices.unit')->where(['id'=>$tariff_id, 'logist_id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('tariff/view', [
            'model' => $model
        ]);
    }
}
