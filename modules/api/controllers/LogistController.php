<?php
namespace app\modules\api\controllers;


use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\product\Product;
use app\models\Images;
use app\models\Category;
use app\models\logist\Logist;
use app\models\logist\region\LogistRegion;
use app\models\logist\region\LogistRegionPrice;

class LogistController extends Controller {
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['*']
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator']['except'] = ['options'];

        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    public function actionIndex() {
        $user = Yii::$app->user->identity;
        $query = Logist::find()->with('image');

        if ($status = Yii::$app->request->get('status')) {
            $query->andWhere(['status'=>$status]);
        }

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionDetail($id) {
        $user = Yii::$app->user->identity;
        $model = Logist::find()->with('image', 'logistRegions', 'logistRegions.region', 'logistRegions.region', 'logistRegions.logistRegionPrices')->where(['id'=>$id])->one();

        return ['data'=>$model];
    }

    public function actionSort($region_id, $unit_id, $amount) {
        $user = Yii::$app->user->identity;

        $regions = ArrayHelper::map(LogistRegion::find()->where(['region_id'=>$region_id])->all(), 'id', 'id');
        $prices = LogistRegionPrice::find()->where(['in', 'logist_region_id', $regions])->andWhere(['unit_id'=>$unit_id])->orderBy('unit_amount')->all();

        $max = 0;
        $ids = [];
        foreach ($prices as $price) {
            if (($amount <= $price->unit_amount) && (($max == 0) || ($max == $price->unit_amount))) {
                $ids[] = $price->id;
                $max = $price->unit_amount;
            }
        }

        $query = LogistRegionPrice::find()->with('logistRegion', 'logistRegion.region', 'logistRegion.logist', 'logistRegion.logist.image')->where(['in', 'id', $ids])->all();

        $data = [];

        if ($query) {
            foreach ($query as $main_key => $item) {
                if ($item->logistRegion && $item->logistRegion->region && $item->logistRegion->logist) {
                    $data[] = [
                        'id' => $item->logistRegion->logist->id,
                        'name' => $item->logistRegion->logist->name_ru,
                        'description' => $item->logistRegion->logist->description_ru,
                        'photo' => $item->logistRegion->logist->getPhoto(),
                        'status' => $item->logistRegion->logist->status,
                        'logistRegions' => [
                            'id' => $item->logistRegion->id,
                            'region' => $item->logistRegion->region->name_ru,
                            'tariffs' => [
                                'id' => $item->id,
                                'unit_id' => $item->unit->id,
                                'unit' => $item->unit->name_ru,
                                'unit_amount' => $item->unit_amount,
                                'price' => $item->price
                            ]
                        ]
                    ];
                }
            }
        }

        return ['data' => $data];
    }
}