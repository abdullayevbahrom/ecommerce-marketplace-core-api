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
use yii\db\Transaction;

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

    /**
     * POST /api/category/create
     */
    public function actionCreate()
    {
        $data = Yii::$app->request->post();

        $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);

        try {
            $category = new Category();

            $category->parent_id = (int)($data['parent_id'] ?? 0);
            $category->name_ru = $data['name_ru'] ?? null;
            $category->name_en = $data['name_en'] ?? null;
            $category->name_uz = $data['name_uz'] ?? null;
            $category->description_ru = $data['description_ru'] ?? null;
            $category->description_en = $data['description_en'] ?? null;
            $category->description_uz = $data['description_uz'] ?? null;

             $category->status = 0;

            if (!$category->validate()) {
                throw new HttpException(422, json_encode($category->errors));
            }

            if (!$category->save(false)) {
                throw new HttpException(500, 'Failed to save category');
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => [
                    'id' => $category->id,
                ],
            ];


        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function actionUpdate()
    {
        $data = Yii::$app->request->bodyParams;

        if (empty($data['id'])) {
            throw new HttpException(400, 'id is required');
        }

        $category = Category::findOne($data['id']);

        if (!$category) {
            throw new HttpException(404, 'Category not found');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (array_key_exists('parent_id', $data)) {
                $category->parent_id = (int)$data['parent_id'];
            }

            foreach (['name_ru', 'name_en', 'name_uz', 'description_ru', 'description_en', 'description_uz'] as $field) {
                if (array_key_exists($field, $data)) {
                    $category->$field = $data[$field];
                }
            }

            $category->status = 0;

            if (!$category->validate()) {
                throw new HttpException(422, json_encode($category->errors));
            }

            if (!$category->save(false)) {
                throw new HttpException(500, 'Failed to update category');
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => [
                    'id' => $category->id,
                ],
            ];
        
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function actionDelete()
    {
        $data = Yii::$app->request->post();

        if (empty($data['id'])) {
            throw new HttpException(400, 'id is required');
        }

        $category = Category::findOne($data['id']);

        if (!$category) {
            throw new HttpException(404, 'Category not found');
        }

        if (Category::find()->where(['parent_id' => $category->id])->exists()) {
            throw new HttpException(409, 'Category has children');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $category->status = 0;
            $category->deleted_at = date('Y-m-d H:i:s');

            if (!$category->save(false)) {
                throw new HttpException(500, 'Failed to delete category');
            }

            $transaction->commit();

            return [
                'success' => true,
            ];

        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
?>