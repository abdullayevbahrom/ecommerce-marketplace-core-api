<?php
namespace app\modules\api\controllers;


use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\services\Sms;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\Category;
use app\models\filter\Filter;

class CategoryController extends Controller {
    
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
        $query = Category::find()->with('image', 'filter', 'filter.childs', 'brands', 'childs', 'childs.categoryFilters')->where(['parent_id'=>0]);

        if (Yii::$app->request->get('type')) {
            $query->andWhere(['type'=>Yii::$app->request->get('type')]);
        }

        if (Yii::$app->request->get('popular')) {
            $query->andWhere(['popular'=>Yii::$app->request->get('popular')]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['sort'=>'desc']]
        ]);
    }

    public function actionSubCategory($id) {
        $query = Category::find()->with('image', 'filter', 'filter.childs')->where(['parent_id'=>$id]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id'=>'desc']]
        ]);
    }

    public function actionFilter($category_id) {
        $cat = Category::find()->with('parent')->where(['id'=>$category_id])->one();

        if ($cat && $cat->parent) {
            $query = Filter::find()->with('childs')->where(['parent_id'=>0])->andWhere(['category_id'=>$category_id])->orWhere(['category_id'=>$cat->parent->id]);
        } else {
            $query = Filter::find()->with('childs')->where(['category_id'=>$category_id, 'parent_id'=>0]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id'=>'desc']]
        ]);
    }
}
?>