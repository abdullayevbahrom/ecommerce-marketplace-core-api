<?php
namespace app\modules\api\controllers;


use Yii;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\news\News;
use app\models\news\NewsView;

class NewsController extends Controller {
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
            'optional' => ['index', 'detail', 'search', 'last'],
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
        $query = News::find()->with('image')->where(['status'=>1]);

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

    public function actionSearch($query) {
        $news = News::find()->with('image')
            ->where(['status'=>1])
            ->andWhere(['or', 
                ['like', 'name_ru', $query],
                ['like', 'name_uz', $query],
                ['like', 'name_en', $query],
                ['like', 'description_mini_ru', $query],
                ['like', 'description_mini_uz', $query],
                ['like', 'description_mini_en', $query],
                ['like', 'description_ru', $query],
                ['like', 'description_uz', $query],
                ['like', 'description_en', $query]
            ]);

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $news,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionDetail($id) {
        $news = News::find()->with('image')->where(['id'=>$id])->one();

        // set views
        if ($news) {
            $view = NewsView::findOne(['news_id'=>$news->id, 'ip'=>Yii::$app->request->userIP]);
            if (!$view) {
                $view = new NewsView;
                $view->saveObject($news);
            }
        }
        // end set views

        return ['data'=>$news];
    }

    public function actionLast($id = null) {
        $query = News::find()->with('image')->where(['status'=>1]);
        if ($id) {
            $query->andWhere(['!=', 'id', $id]);
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
}
?>