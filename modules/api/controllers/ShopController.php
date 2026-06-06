<?php

namespace app\modules\api\controllers;


use Yii;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use app\models\Category;
use app\models\product\Product;
use app\models\product\ProductFilter;
use app\models\shop\Shop;
use app\models\user\favorite_shop\UserShopFavorite;

class ShopController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['*'],
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
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

    public function actionIndex()
    {
        $query = Shop::find()->with('image')->where(['status' => 1])->orderBy('id desc');
        $context = $this->getCatalogFilterContext();

        if ($this->hasFacetContext($context)) {
            $shopIds = $this->buildFacetProductQuery($context, true)
                ->select('product.shop_id')
                ->andWhere(['not', ['product.shop_id' => null]])
                ->distinct()
                ->column();

            if (empty($shopIds)) {
                $query->andWhere(['id' => -1]);
            } else {
                $query->andWhere(['id' => $shopIds]);
            }
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

    public function actionSearch($query)
    {
        $products = Shop::find()->with('image')
            ->where(['status' => 1])
            ->andWhere([
                'or',
                ['like', 'name_ru', $query],
                ['like', 'name_uz', $query],
                ['like', 'name_en', $query],
                ['like', 'description_ru', $query],
                ['like', 'description_uz', $query],
                ['like', 'description_en', $query]
            ]);

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $products,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionDetail($id)
    {
        $shop = Shop::find()->with('image', 'gallery', 'shopSeller', 'user')->where(['id' => $id])->one();

        return ['data' => $shop];
    }

    public function actionFavorites()
    {
        $user = Yii::$app->user->identity;

        $ids = ArrayHelper::map(UserShopFavorite::find()->where(['user_id' => $user->id])->all(), 'shop_id', 'shop_id');
        $query = Shop::find()->with('image')->where(['status' => 1])->andWhere(['in', 'id', $ids]);

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

    public function actionSetFavorite()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $user_shop_favorite = new UserShopFavorite;
        $user_shop_favorite->setAttributes($post);

        if (!$user_shop_favorite->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $user_shop_favorite->errors];
        }

        $shop = Shop::find()->with('image')->where(['id' => $post['shop_id']])->one();
        $favorite = UserShopFavorite::findOne(['user_id' => $user->id, 'shop_id' => $post['shop_id']]);

        if ($favorite) {
            $favorite->delete();
            Yii::$app->response->statusCode = 200;
            return ['data' => $shop];
        }

        $user_shop_favorite->saveObject($user->id);

        Yii::$app->response->statusCode = 200;
        return ['data' => $shop];
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

    private function buildFacetProductQuery(array $context, bool $ignoreShop = false)
    {
        $products = Product::find()
            ->alias('product')
            ->where(['product.status' => 1])
            ->publicVisible();

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

        if (!empty($context['brand_id'])) {
            $products->andWhere(['product.brand_id' => (int)$context['brand_id']]);
        }

        if (!$ignoreShop && !empty($context['shop_id'])) {
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
