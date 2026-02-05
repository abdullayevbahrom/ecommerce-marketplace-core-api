<?php
namespace app\modules\api\controllers;


use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;

use app\models\Category;
use app\models\brand\CategoryBrand;
use yii\web\NotFoundHttpException;

class BrandController extends Controller {
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        Yii::$app->session->set('language', 'ru');
        $langs = ['ru', 'en', 'uz'];

        $headers = Yii::$app->request->headers;
        if($headers->has('Content-Language')) {
            $lang = $headers->get('Content-Language');
            if(in_array($lang,$langs)) {
                Yii::$app->session->set('language', $lang);
            }
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
        $query = CategoryBrand::find()->with('image', 'category')->where(['status'=>1]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['sort'=>'desc']]
        ]);
    }

    public function actionByCategory($id) {
        $ids = ArrayHelper::map(Category::find()->where(['parent_id'=>$id])->all(), 'id', 'id');
        $query = CategoryBrand::find()->with('image', 'category')->where(['status'=>1])->andWhere(['category_id'=>$id])->orWhere(['in', 'category_id', $ids]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['sort'=>'desc']]
        ]);
    }
    
    public function actionCreate()
    {
        $data = Yii::$app->request->post();

        $brand = new CategoryBrand();
        $brand->category_id = $data['category_id'] ?? null;
        $brand->name_ru = $data['name_ru'];
        $brand->name_en = $data['name_en'] ?? null;
        $brand->name_uz = $data['name_uz'] ?? null;
        $brand->description_ru = $data['description_ru'] ?? null;
        $brand->description_en = $data['description_en'] ?? null;
        $brand->description_uz = $data['description_uz'] ?? null;
        $brand->sort = $data['sort'] ?? 0;
        $brand->status = 0;

        if (!$brand->save()) {
            return $this->asJson(['errors' => $brand->errors])->setStatusCode(422);
        }

        return [
            'success' => true,
            'data' => ['id' => $brand->id],
        ];
    }

    public function actionUpdate()
    {
        $data = Yii::$app->request->bodyParams;

        $brand = CategoryBrand::findOne($data['id'] ?? null);
        if (!$brand) {
            throw new NotFoundHttpException();
        }

        foreach (['category_id','name_ru','name_en','name_uz','description_ru','description_en','description_uz','sort'] as $f) {
            if (array_key_exists($f, $data)) {
                $brand->$f = $data[$f];
            }
        }

        $brand->status = 0;

        if (!$brand->save()) {
            return $this->asJson(['errors' => $brand->errors])->setStatusCode(422);
        }

        return ['success' => true];
    }

    public function actionDelete()
    {
        $data = Yii::$app->request->post();

        $brand = CategoryBrand::findOne($data['id'] ?? null);
        if (!$brand) {
            throw new NotFoundHttpException();
        }

        $brand->status = 0;
        $brand->deleted_at = date('Y-m-d H:i:s');

        $brand->save(false);

        return ['success' => true];
    }
}
?>