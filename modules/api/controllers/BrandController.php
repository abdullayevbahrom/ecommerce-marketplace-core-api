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
use app\models\product\Product;
use app\models\product\ProductFilter;
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
        $context = $this->getCatalogFilterContext();

        if ($this->hasFacetContext($context)) {
            $brandIds = $this->buildFacetProductQuery($context, true)
                ->select('product.brand_id')
                ->andWhere(['not', ['product.brand_id' => null]])
                ->distinct()
                ->column();

            if (empty($brandIds)) {
                $query->andWhere(['id' => -1]);
            } else {
                $query->andWhere(['id' => $brandIds]);
            }
        }

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

    private function getCatalogFilterContext(): array
    {
        $params = Yii::$app->request->get();
        $referer = Yii::$app->request->referrer;

        if ($referer) {
            $refererPath = parse_url($referer, PHP_URL_PATH) ?: '';
            $refererQuery = parse_url($referer, PHP_URL_QUERY) ?: '';

            if ($refererQuery !== '' && str_contains($refererPath, '/filter')) {
                parse_str($refererQuery, $refererParams);
                $params = array_replace_recursive($refererParams, $params);
            }
        }

        return [
            'query' => trim((string)($params['query'] ?? $params['q'] ?? '')),
            'category_id' => isset($params['category_id']) ? (int)$params['category_id'] : null,
            'brand_id' => isset($params['brand_id']) ? (int)$params['brand_id'] : null,
            'shop_id' => isset($params['shop_id']) ? (int)$params['shop_id'] : null,
            'price_min' => isset($params['price_min']) ? (float)$params['price_min'] : null,
            'price_max' => isset($params['price_max']) ? (float)$params['price_max'] : null,
            'filter' => is_array($params['filter'] ?? null) ? $params['filter'] : [],
        ];
    }

    private function hasFacetContext(array $context): bool
    {
        return $context['query'] !== ''
            || !empty($context['category_id'])
            || !empty($context['brand_id'])
            || !empty($context['shop_id'])
            || $context['price_min'] !== null
            || $context['price_max'] !== null
            || !empty($context['filter']);
    }

    private function buildFacetProductQuery(array $context, bool $ignoreBrand = false)
    {
        $products = Product::find()
            ->alias('product')
            ->where(['product.status' => 1])
            ->marketplaceVisible();

        if ($context['query'] !== '') {
            $searchFields = [
                'product.name_ru',
                'product.name_uz',
                'product.name_en',
                'product.name_trans_ru',
                'product.name_trans_en',
                'product.description_ru',
                'product.description_uz',
                'product.description_en',
                'product.composition_ru',
                'product.composition_uz',
                'product.composition_en',
                'product.recommendation_ru',
                'product.recommendation_uz',
                'product.recommendation_en',
            ];

            foreach (preg_split('/\s+/u', $context['query'], -1, PREG_SPLIT_NO_EMPTY) as $term) {
                $termCondition = ['or'];
                foreach ($searchFields as $field) {
                    $termCondition[] = ['like', $field, $term];
                }
                $products->andWhere($termCondition);
            }
        }

        if (!empty($context['category_id'])) {
            $categoryIds = [(int)$context['category_id']];
            $subcategories = Category::find()
                ->select('id')
                ->where(['parent_id' => (int)$context['category_id']])
                ->column();

            foreach ($subcategories as $subcatId) {
                $categoryIds[] = (int)$subcatId;
            }

            $products->andWhere(['product.category_id' => array_values(array_unique($categoryIds))]);
        }

        if (!$ignoreBrand && !empty($context['brand_id'])) {
            $products->andWhere(['product.brand_id' => (int)$context['brand_id']]);
        }

        if (!empty($context['shop_id'])) {
            $products->andWhere(['product.shop_id' => (int)$context['shop_id']]);
        }

        if ($context['price_min'] !== null) {
            $products->andWhere(['>=', 'product.price', $context['price_min']]);
        }

        if ($context['price_max'] !== null) {
            $products->andWhere(['<=', 'product.price', $context['price_max']]);
        }

        if (!empty($context['filter'])) {
            $productIds = [];
            $filterCount = 0;

            foreach ($context['filter'] as $filterId => $filterValue) {
                $filterCount++;

                $subQuery = ProductFilter::find()
                    ->select('product_id')
                    ->where(['filter_id' => $filterId])
                    ->andWhere([
                        'or',
                        ['id' => $filterValue],
                        ['value_ru' => $filterValue],
                        ['value_en' => $filterValue],
                        ['value_uz' => $filterValue],
                    ]);

                if ($filterCount === 1) {
                    $productIds = $subQuery->column();
                } else {
                    $productIds = array_intersect($productIds, $subQuery->column());
                }
            }

            $products->andWhere(['product.id' => empty($productIds) ? -1 : $productIds]);
        }

        return $products;
    }
}
?>
