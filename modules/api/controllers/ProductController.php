<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\filters\auth\HttpBearerAuth;

use app\models\Images;
use app\models\Category;
use app\models\product\Product;
use app\models\product\ProductView;
use app\models\product\ProductViewRecently;
use app\models\product\ProductFilter;
use app\models\product\review\ProductReview;
use app\models\product\ProductRequest;
use app\models\user\favorite\UserFavorite;
use app\models\user\compare\UserCompare;
use app\models\user\User;
use app\models\user\activity\UserActivity;
use app\models\brand\CategoryBrand; // Added Brand model
use app\models\Brand; // Added Brand model
use app\models\Shop; // Added Shop model

use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Jenssegers\ImageHash\Hash;
use app\modules\api\components\ErrorCodes;
use app\modules\api\components\ApiResponseTrait;


class ProductController extends Controller
{
    use ApiResponseTrait;

    public $minMaxPrices = [];

    protected function serializeData($data)
    {
        $result = parent::serializeData($data);

        if (is_array($result) && isset($result['_meta']) && !empty($this->minMaxPrices)) {
            $result['_meta']['price_min'] = $this->minMaxPrices['min'];
            $result['_meta']['price_max'] = $this->minMaxPrices['max'];
        }

        return $result;
    }

    /**
     * Calculates min/max prices for the current query (ignoring price filters)
     * and applies price filters to the query.
     */
    protected function applyPriceFilterWithBounds($query, $column = 'price')
    {
        // Calculate bounds based on current query state (before price filter)
        $boundsQuery = clone $query;
        $boundsQuery->orderBy(null);
        $boundsQuery->limit(null)->offset(null);

        // When query has groupBy, Yii2 wraps min/max in a subquery where table aliases are lost.
        // Strip the alias prefix so the aggregate works on the subquery's bare column names.
        $aggregateColumn = !empty($boundsQuery->groupBy) ? preg_replace('/^\w+\./', '', $column) : $column;

        $min = $boundsQuery->min($aggregateColumn);
        $boundsQuery2 = clone $query;
        $boundsQuery2->orderBy(null)->limit(null)->offset(null);
        $max = $boundsQuery2->max($aggregateColumn);

        $this->minMaxPrices = [
            'min' => $min !== null ? (float)$min : 0,
            'max' => $max !== null ? (float)$max : 0
        ];

        // Apply filters
        if ($price_min = Yii::$app->request->get('price_min')) {
            $query->andWhere(['>=', $column, $price_min]);
        }
        if ($price_max = Yii::$app->request->get('price_max')) {
            $query->andWhere(['<=', $column, $price_max]);
        }
    }

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
            'class' => HttpBearerAuth::className(),
            'optional' => ['index', 'by-category', 'by-brand', 'by-shop', 'by-filter', 'search', 'search-suggestions', 'detail', 'reviews', 'recently-viewed', 'related-products', 'by-photo', 'for-you', 'best-products'], // Removed 'request' - now requires auth
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

    /**
     * Check authentication for Sklad integration
     */
    protected function checkSkladAuth()
    {
        $headers = Yii::$app->request->headers;
        $token = $headers->get('X-Api-Token');

        if (!$token) {
            throw new \yii\web\UnauthorizedHttpException('Missing X-Api-Token header');
        }

        $branchId = $headers->get('X-Branch-ID');
        if (!$branchId) {
            throw new \yii\web\UnauthorizedHttpException('Missing X-Branch-ID header');
        }

        $expectedToken = md5($branchId . Yii::$app->params['apiSecretKey']);
        if ($token !== $expectedToken) {
            throw new \yii\web\UnauthorizedHttpException('Invalid API Token');
        }
    }

    /**
     * Create Product (Sklad Integration)
     * POST /api/product/create
     */
    public function actionCreate()
    {
        $this->checkSkladAuth();

        $request = Yii::$app->request;
        $userId = $request->post('user_id');

        if (!$userId) {
            return $this->sendError(400, 'user_id is required');
        }

        $user = User::findOne($userId);
        if (!$user) {
            return $this->sendError(404, 'User not found');
        }

        // Login the user so saveObject logic works
        Yii::$app->user->login($user);

        $model = new Product();
        $post = $request->post();

        // Ensure status is active by default
        if (!isset($post['status'])) {
            $post['status'] = 1;
        }

        // Map Branch to Stock if needed
        if (isset($post['branch_id']) && !isset($post['stock_id'])) {
            $post['stock_id'] = $post['branch_id'];
        }

        // Validate basic load
        if ($model->load($post, '')) {
            // Fix: shop_id is required but auto-detected in saveObject. 
            // We need to set it here manually for $model->validate() to pass.
            if (empty($model->shop_id)) {
                $shop = \app\models\shop\Shop::findOne(['user_id' => $user->id]);
                if (!$shop && $user->shop_id) {
                    $shop = \app\models\shop\Shop::findOne($user->shop_id);
                }
                if ($shop) {
                    $model->shop_id = $shop->id;
                    $post['shop_id'] = $shop->id;
                }
            }

            // Update Request with modified POST data so saveObject sees it (stock_id, shop_id, etc.)
            Yii::$app->request->setBodyParams($post);

            if ($model->validate()) {
                $colors = $post['colors'] ?? ($post['color_id'] ? [$post['color_id']] : []);
                $productTypes = $post['product_types'] ?? [];
                $callbackUrl = $post['callback_url'] ?? null;

                // Fix: Sklad sends product_types as a list of objects [{yii_product_type_id:1, yii_product_type_value_id:35}, ...]
                // We need to convert this to the format expected by generateTypeCombinations: [type_id => [val1, val2]]
                if (!empty($productTypes)) {
                    // Check if first element is an object/array (Sklad format) vs associative map (Admin format)
                    $firstItem = reset($productTypes);
                    $isSkladFormat = is_array($firstItem) || is_object($firstItem);

                    // Also check by looking for 'yii_product_type_id' key
                    if ($isSkladFormat) {
                        $firstItem = (array) $firstItem;
                        $isSkladFormat = isset($firstItem['yii_product_type_id']);
                    }

                    if ($isSkladFormat) {
                        $normalizedTypes = [];
                        foreach ($productTypes as $item) {
                            // Cast to array in case it's stdClass from JSON decode
                            $item = (array) $item;

                            $ptId = $item['yii_product_type_id'] ?? null;
                            if (!$ptId) continue;

                            // Use value_id if present and not null, otherwise custom_value
                            // Note: array key can exist with null value, ?? handles this
                            $val = null;
                            if (isset($item['yii_product_type_value_id']) && $item['yii_product_type_value_id'] !== null) {
                                $val = $item['yii_product_type_value_id'];
                            } elseif (isset($item['custom_value']) && $item['custom_value'] !== null) {
                                $val = $item['custom_value'];
                            }

                            if ($val !== null) {
                                $normalizedTypes[$ptId][] = $val;
                            }
                        }
                        $productTypes = $normalizedTypes;
                    }
                }

                $tokenKey = $post['token_key'] ?? Yii::$app->security->generateRandomString();

                // Calculate expected products count BEFORE sending response
                $typeCombinations = [];
                if (!empty($productTypes)) {
                    $typeCombinations = $this->generateTypeCombinations($productTypes);
                }
                if (empty($typeCombinations)) {
                    $typeCombinations = [null];
                }
                $colorsCount = !empty($colors) ? count($colors) : 1;
                $expectedCount = count($typeCombinations) * $colorsCount;

                // Send SUCCESS response IMMEDIATELY (before creating products)
                $responseData = [
                    'success' => true,
                    'message' => 'Product creation started',
                    'data' => [
                        'status' => 'processing',
                        'token_key' => $tokenKey,
                        'expected_count' => $expectedCount,
                    ]
                ];

                // Set response and send it
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                Yii::$app->response->data = $responseData;
                Yii::$app->response->send();

                // Close connection to client, continue processing in background
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    // Fallback for non-FPM environments
                    if (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                    flush();
                }

                // ========== BACKGROUND PROCESSING ==========
                // Client has already received response, now create products

                try {
                    $createdProducts = [];
                    $failedProducts = [];

                    // Iterate Combinations (Types x Colors)
                    foreach ($typeCombinations as $combination) {
                        $colorsToLoop = !empty($colors) ? $colors : [null];

                        foreach ($colorsToLoop as $colorId) {
                            // Pass specific combination of types to saveObject
                            $product = $model->saveObject(true, $colorId, $tokenKey, null, $combination);

                            if ($product) {
                                $createdProducts[] = $product;
                            } else {
                                $failedProducts[] = [
                                    'color_id' => $colorId,
                                    'combination' => $combination,
                                    'errors' => $model->errors
                                ];
                            }
                        }
                    }

                    // Send callback to Sklad with results (if callback_url provided)
                    if ($callbackUrl) {
                        $callbackData = [
                            'status' => 'completed',
                            'user_id' => $userId,
                            'token_key' => $tokenKey,
                            'product_ids' => array_column($createdProducts, 'id'),
                            'products' => $createdProducts,
                            'count' => count($createdProducts),
                            'failed' => $failedProducts,
                        ];
                        $this->sendCallbackToSklad($callbackUrl, $callbackData);
                    }

                    Yii::info("Background product creation completed: " . count($createdProducts) . " products created", 'api');
                } catch (\Exception $e) {
                    Yii::error("Background product creation failed: " . $e->getMessage(), 'api');

                    // Send error callback if URL provided
                    if ($callbackUrl) {
                        $this->sendCallbackToSklad($callbackUrl, [
                            'status' => 'error',
                            'user_id' => $userId,
                            'token_key' => $tokenKey,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Exit to prevent Yii from trying to send response again
                Yii::$app->end();
            }
        }

        return $this->sendError(422, 'Validation error', $model->errors);
    }

    // GET /api/sklad/products/changed
    public function actionChanged()
    {
        $this->checkSkladAuth();

        $products = Product::find()
            ->where(['sync_status' => 0])
            ->limit(100)
            ->all();

        return [
            'success' => true,
            'products' => $products,
        ];
    }

    /**
     * Send callback to Sklad with created product data
     * @param string $callbackUrl
     * @param array $data
     */
    private function sendCallbackToSklad($callbackUrl, $data)
    {
        try {
            $ch = curl_init($callbackUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Callback-Source: shop-api',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                Yii::error('Sklad callback failed: ' . curl_error($ch), 'api');
            } else {
                Yii::info("Sklad callback sent to {$callbackUrl}, HTTP {$httpCode}", 'api');
            }

            curl_close($ch);
        } catch (\Exception $e) {
            Yii::error('Sklad callback exception: ' . $e->getMessage(), 'api');
        }
    }

    /**
     * Generate combinations of product types for creating product variants
     * @param array $product_types
     * @return array
     */
    private function generateTypeCombinations($product_types)
    {
        $combinations = [];

        // If only one type is selected, return simple combinations
        if (count($product_types) == 1) {
            foreach ($product_types as $type_id => $values) {
                if (is_array($values)) {
                    foreach ($values as $value_id) {
                        $combinations[] = [$type_id => $value_id];
                    }
                } else {
                    $combinations[] = [$type_id => $values];
                }
            }
            return $combinations;
        }

        // For multiple types, generate all combinations
        $type_arrays = [];
        foreach ($product_types as $type_id => $values) {
            if (is_array($values)) {
                foreach ($values as $value_id) {
                    $type_arrays[$type_id][] = $value_id;
                }
            } else {
                $type_arrays[$type_id][] = $values;
            }
        }

        // Generate cartesian product of all type combinations
        $keys = array_keys($type_arrays);
        $values = array_values($type_arrays);
        $total = array_product(array_map('count', $values));

        for ($i = 0; $i < $total; $i++) {
            $combination = [];
            $temp = $i;
            for ($j = count($values) - 1; $j >= 0; $j--) {
                $combination[$keys[$j]] = $values[$j][$temp % count($values[$j])];
                $temp = intval($temp / count($values[$j]));
            }
            $combinations[] = array_reverse($combination, true);
        }

        return $combinations;
    }

    // general product methods
    public function actionIndex()
    {
        $query = Product::find()
            ->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')
            ->where(['status' => 1])
            ->orderBy('id desc');

        if ($sort = Yii::$app->request->get('sort')) {
            if (($sort == 'new') || ($sort == 'recently')) {
                $query->orderBy('id desc');
            }

            if ($sort == 'price_down') {
                $query->orderBy('price asc');
            }

            if ($sort == 'price_up') {
                $query->orderBy('price desc');
            }

            if ($sort == 'popular') {
                $query->orderBy('views desc');
            }
        }

        if ($category_id = Yii::$app->request->get('category_id')) {
            // Get all subcategories for the given category_id
            $categoryIds = [$category_id];
            $subcategories = Category::find()->where(['parent_id' => $category_id])->all();
            foreach ($subcategories as $subcat) {
                $categoryIds[] = $subcat->id;
            }
            $query->andWhere(['in', 'category_id', $categoryIds]);
        }

        if ($tag_id = Yii::$app->request->get('tag_id')) {
            $query->andWhere(['tag_id' => $tag_id]);
        }

        if ($brand_id = Yii::$app->request->get('brand_id')) {
            $query->andWhere(['brand_id' => $brand_id]);
        }

        if ($shop_id = Yii::$app->request->get('shop_id')) {
            $query->andWhere(['shop_id' => $shop_id]);
        }

        if ($filter = Yii::$app->request->get('filter')) {
            // Check if we should use OR logic instead of AND
            $filterLogic = Yii::$app->request->get('filter_logic', 'and'); // 'and' or 'or'

            // For each filter, find products that match the specified values
            $productIds = [];
            $filterCount = 0;

            foreach ($filter as $filterId => $filterValue) {
                $filterCount++;

                // Find products that have this filter_id with the specified value
                // The value can be either a ProductFilter ID or a text value
                $subQuery = ProductFilter::find()
                    ->select('product_id')
                    ->where(['filter_id' => $filterId])
                    ->andWhere([
                        'or',
                        ['id' => $filterValue],           // Match by ProductFilter ID
                        ['value_ru' => $filterValue],     // Match by text value
                        ['value_en' => $filterValue],     // Match by text value (English)
                        ['value_uz' => $filterValue]      // Match by text value (Uzbek)
                    ]);

                if ($filterCount === 1) {
                    $productIds = $subQuery->column();
                } else {
                    $currentProductIds = $subQuery->column();
                    if ($filterLogic === 'or') {
                        // Union with previous results (OR logic - product can have ANY filter)
                        $productIds = array_unique(array_merge($productIds, $currentProductIds));
                    } else {
                        // Intersect with previous results (AND logic - product must have ALL filters)
                        $productIds = array_intersect($productIds, $currentProductIds);
                    }
                }
            }

            if (!empty($productIds)) {
                $query->andWhere(['in', 'id', $productIds]);
            } else {
                // No products match the filters
                $query->andWhere(['id' => -1]);
            }
        }

        // Price filtering with bounds calculation
        $this->applyPriceFilterWithBounds($query, 'price');

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;
        foreach ($query->all() as $product) {
            if ($product->status == 2) {
                $product->delete();
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);

        return $dataProvider;
    }

    protected function applyEsPriceFilterWithBoundsEs(array &$filters, array $must, string $field = 'price'): void
    {
        $boundsBody = [
            'size' => 0,
            'query' => [
                'bool' => [
                    'must' => $must ?: [['match_all' => (object)[]]],
                    'filter' => $filters,
                ],
            ],
            'aggs' => [
                'min_price' => ['min' => ['field' => $field]],
                'max_price' => ['max' => ['field' => $field]],
            ],
        ];

        $boundsRes = \Yii::$app->elasticsearch->post(
            'products/_search',
            [],
            Json::encode($boundsBody)
        );

        $min = (float)($boundsRes['aggregations']['min_price']['value'] ?? 0);
        $max = (float)($boundsRes['aggregations']['max_price']['value'] ?? 0);

        $this->minMaxPrices = ['min' => $min, 'max' => $max];

        $req = \Yii::$app->request;
        $priceMin = $req->get('price_min');
        $priceMax = $req->get('price_max');

        if ($priceMin !== null || $priceMax !== null) {
            $range = [];
            if ($priceMin !== null) $range['gte'] = (float)$priceMin;
            if ($priceMax !== null) $range['lte'] = (float)$priceMax;
            $filters[] = ['range' => [$field => $range]];
        }
    }

    private function buildOneNestedFilterClause(int $filterId, $filterValue): array
    {
        if (is_numeric($filterValue)) {
            return [
                'nested' => [
                    'path' => 'filters',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['term' => ['filters.filter_id' => $filterId]],
                                ['term' => ['filters.pf_id' => (int)$filterValue]],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $v = trim((string)$filterValue);

        return [
            'nested' => [
                'path' => 'filters',
                'query' => [
                    'bool' => [
                        'must' => [
                            ['term' => ['filters.filter_id' => $filterId]],
                        ],
                        'should' => [
                            ['term' => ['filters.value_ru' => $v]],
                            ['term' => ['filters.value_en' => $v]],
                            ['term' => ['filters.value_uz' => $v]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
            ],
        ];
    }

    public function actionIndexEs()
    {
        $req = \Yii::$app->request;
        $q = trim((string)$req->get('q', ''));
        $sort = (string)$req->get('sort', 'new');
        $categoryId = $req->get('category_id');
        $brandId = $req->get('brand_id');
        $shopId = $req->get('shop_id');
        $tagId = $req->get('tag_id');
        $perPage = (int)($req->get('per-page', 12));
        $page = max(1, (int)$req->get('page', 1));
        $filter = $req->get('filter');
        $filterLogic = $req->get('filter_logic', 'and');

        $perPage = max(1, min(50, $perPage));
        $from = ($page - 1) * $perPage;

        $must = [];
        if ($q !== '') {
            $must[] = [
                'multi_match' => [
                    'query' => $q,
                    'fields' => [
                        'search_name^5',
                        'name_uz^4',
                        'name_ru^4',
                        'name_en^4',
                        'search_desc',
                        'sku^10',
                        'barcode^10',
                    ],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ]
            ];
        }
        $filters = [];
        $filters[] = ['term' => ['status' => 1]];
        $filters[] = ['bool' => ['must_not' => [['exists' => ['field' => 'deleted_at']]]]];
        if ($brandId) $filters[] = ['term' => ['brand_id' => (int)$brandId]];
        if ($shopId)  $filters[] = ['term' => ['shop_id' => (int)$shopId]];
        if ($tagId)  $filters[] = ['term' => ['tag_id' => (int)$tagId]];

        if ($categoryId) {
            $catIds = [(int)$categoryId];

            $subcats = Category::find()->select('id')->where(['parent_id' => (int)$categoryId])->column();
            foreach ($subcats as $sid) $catIds[] = (int)$sid;

            $filters[] = ['terms' => ['category_id' => array_values(array_unique($catIds))]];
        }

        $esSort = [];
        if ($sort === 'price_down') $esSort[] = ['price' => 'desc'];
        elseif ($sort === 'price_up') $esSort[] = ['price' => 'asc'];
        elseif ($sort === 'popular') $esSort[] = ['views' => 'desc'];
        else $esSort[] = ['id' => 'desc'];

        $nestedClauses = [];

        if (\is_array($filter)) {
            foreach ($filter as $filterId => $filterValue) {
                $nestedClauses[] = $this->buildOneNestedFilterClause((int)$filterId, $filterValue);
            }
        }

        if ($nestedClauses) {
            if ($filterLogic === 'or') {
                $filters[] = [
                    'bool' => [
                        'should' => $nestedClauses,
                        'minimum_should_match' => 1,
                    ],
                ];
            } else {
                foreach ($nestedClauses as $c) {
                    $filters[] = $c;
                }
            }
        }

        $this->applyEsPriceFilterWithBoundsEs($filters, $must, 'price');


        $body = [
            '_source' => ['id'],
            'from' => $from,
            'size' => $perPage,
            'query' => [
                'bool' => [
                    'must' => $must ?: [['match_all' => (object)[]]],
                    'filter' => $filters,
                ],
            ],
            'sort' => $esSort,
        ];

        $esRes = \Yii::$app->elasticsearch->post('products/_search', [], Json::encode($body));

        $hits = $esRes['hits']['hits'] ?? [];
        $total = $esRes['hits']['total']['value'] ?? 0;


        $ids = [];
        foreach ($hits as $h) {
            if (!empty($h['_source']['id'])) $ids[] = (int)$h['_source']['id'];
        }

        if (!$ids) {
            return new ArrayDataProvider([
                'allModels' => [],
                'totalCount' => 0,
                'pagination' => [
                    'pageSize' => $perPage,
                    'page' => $page - 1,
                ],
            ]);
        }

        $models = Product::find()
            ->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')
            ->where(['id' => $ids, 'status' => 1])
            ->andWhere(['deleted_at' => null])
            ->indexBy('id')
            ->all();

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($models[$id])) $ordered[] = $models[$id];
        }

        $pageCount = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

        $params = $req->getQueryParams();
        $params['per-page'] = $perPage;

        $buildUrl = function (int $p) use ($req, $params) {
            $params['page'] = $p;

            return Url::toRoute(array_merge(['/api/product/index-es'], $params), true);
        };

        $data = array_map(function ($m) {
            return $m->toArray([], [
                'image',
                'category',
                'gallery',
                'productFilters',
                'productColors',
                'productColors.color',
            ]);
        }, $ordered);

        return [
            'data' => $data,
            '_links' => [
                'self'  => ['href' => $buildUrl($page)],
                'first' => ['href' => $buildUrl(1)],
                'last'  => ['href' => $buildUrl(max(1, $pageCount))],
                'prev'  => $page > 1 ? ['href' => $buildUrl($page - 1)] : null,
                'next'  => $page < $pageCount ? ['href' => $buildUrl($page + 1)] : null,
            ],
            '_meta' => [
                'totalCount' => (int)$total,
                'pageCount' => (int)$pageCount,
                'currentPage' => (int)$page,
                'perPage' => (int)$perPage,
                'bounds' => [
                    'min' => (float)($this->minMaxPrices['min'] ?? 0),
                    'max' => (float)($this->minMaxPrices['max'] ?? 0),
                ],
                'selected' => [
                    'min' => $req->get('price_min'),
                    'max' => $req->get('price_max'),
                ],
            ],
        ];
    }

    public function actionBestProducts()
    {
        $query = Product::find()
            ->alias('p')
            ->select([
                'p.*',
                'orders_count' => 'COUNT(DISTINCT o.id)',
                'reviews_count' => 'COUNT(DISTINCT pr.id)',
                'avg_rate' => 'AVG(pr.rate)',
                'good_reviews_count' => 'COUNT(DISTINCT CASE WHEN pr.rate >= 4 THEN pr.id END)',
            ])
            ->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')
            ->leftJoin('order_product op', 'op.product_id = p.id')
            ->leftJoin('`order` o', 'op.order_id = o.id AND o.status IN (1, 2, 3)')
            ->leftJoin('product_review pr', 'pr.product_id = p.id AND pr.status IN (1, 3)')
            ->where(['p.status' => 1])
            ->groupBy('p.id');

        if ($sort = Yii::$app->request->get('sort')) {
            switch ($sort) {
                case 'rating':
                    $query->orderBy(new \yii\db\Expression('COALESCE(AVG(pr.rate), 0) DESC, p.views DESC'));
                    break;

                case 'popular':
                    $query->orderBy(new \yii\db\Expression('p.views DESC, COALESCE(AVG(pr.rate), 0) DESC'));
                    break;

                case 'orders':
                    $query->orderBy(new \yii\db\Expression('COUNT(DISTINCT o.id) DESC, COALESCE(AVG(pr.rate), 0) DESC'));
                    break;

                case 'price_down':
                    $query->orderBy(['p.price' => SORT_ASC]);
                    break;

                case 'price_up':
                    $query->orderBy(['p.price' => SORT_DESC]);
                    break;

                default:
                    $query->orderBy(new \yii\db\Expression('COALESCE(AVG(pr.rate), 0) DESC, COUNT(DISTINCT o.id) DESC, p.views DESC'));
                    break;
            }
        } else {
            $query->orderBy(new \yii\db\Expression('COALESCE(AVG(pr.rate), 0) DESC, COUNT(DISTINCT o.id) DESC, p.views DESC'));
        }

        if ($category_id = Yii::$app->request->get('category_id')) {
            $categoryIds = [$category_id];
            $subcategories = Category::find()->where(['parent_id' => $category_id])->all();
            foreach ($subcategories as $subcat) {
                $categoryIds[] = $subcat->id;
            }
            $query->andWhere(['in', 'p.category_id', $categoryIds]);
        }

        if ($brand_id = Yii::$app->request->get('brand_id')) {
            $query->andWhere(['p.brand_id' => $brand_id]);
        }

        if ($shop_id = Yii::$app->request->get('shop_id')) {
            $query->andWhere(['p.shop_id' => $shop_id]);
        }

        // Price filtering with bounds calculation
        $this->applyPriceFilterWithBounds($query, 'p.price');

        $perPage = Yii::$app->request->get('per-page', 12);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
        ]);
    }

    public function actionForYou()
    {
        $limit = Yii::$app->request->get('per-page', 12);

        if (Yii::$app->user->isGuest) {
            $query = Product::find()
                ->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')
                ->where(['status' => 1])
                ->orderBy('views DESC, RAND()')
                ->limit($limit * 2);

            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $limit,
                    'validatePage' => false
                ],
            ]);
        }

        $userId = Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;
        $ip = Yii::$app->request->userIP;

        $whereCondition = ['user_id' => $userId];

        $recentCategories = UserActivity::find()
            ->select(['category_id', 'COUNT(*) as cnt'])
            ->where($whereCondition)
            ->andWhere(['not', ['category_id' => null]])
            ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', strtotime('-30 days'))])
            ->groupBy('category_id')
            ->orderBy('cnt DESC, created_at DESC')
            ->limit(4)
            ->asArray()
            ->all();

        $categoryIds = array_column($recentCategories, 'category_id');

        $recentSearches = UserActivity::find()
            ->select('search_query')
            ->where($whereCondition)
            ->andWhere(['activity_type' => UserActivity::TYPE_SEARCH])
            ->andWhere(['not', ['search_query' => null]])
            ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', strtotime('-30 days'))])
            ->orderBy('created_at DESC')
            ->limit(10)
            ->asArray()
            ->all();

        $searchQueries = array_column($recentSearches, 'search_query');

        $viewedProducts = UserActivity::find()
            ->select('product_id')
            ->where($whereCondition)
            ->andWhere(['activity_type' => UserActivity::TYPE_VIEW])
            ->andWhere(['not', ['product_id' => null]])
            ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', strtotime('-30 days'))])
            ->orderBy('created_at DESC')
            ->limit(20)
            ->asArray()
            ->all();

        $viewedProductIds = array_column($viewedProducts, 'product_id');

        $query = Product::find()
            ->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')
            ->where(['status' => 1]);

        if (!empty($viewedProductIds)) {
            $query->andWhere(['not in', 'id', $viewedProductIds]);
        }

        $conditions = ['or'];

        if (!empty($categoryIds)) {
            $allCategoryIds = $categoryIds;
            foreach ($categoryIds as $catId) {
                $subcats = Category::find()->select('id')->where(['parent_id' => $catId])->column();
                $allCategoryIds = array_merge($allCategoryIds, $subcats);
            }
            $conditions[] = ['in', 'category_id', array_unique($allCategoryIds)];
        }

        if (!empty($searchQueries)) {
            foreach ($searchQueries as $searchQuery) {
                $terms = explode(' ', $searchQuery);
                foreach ($terms as $term) {
                    $term = trim($term);
                    if (strlen($term) > 2) {
                        $conditions[] = ['like', 'name_ru', $term];
                        $conditions[] = ['like', 'name_uz', $term];
                        $conditions[] = ['like', 'name_en', $term];
                        $conditions[] = ['like', 'description_ru', $term];
                        $conditions[] = ['like', 'description_uz', $term];
                        $conditions[] = ['like', 'description_en', $term];
                    }
                }
            }
        }

        if (count($conditions) > 1) {
            $query->andWhere($conditions);
        } else {
            $query->orderBy('RAND()');
        }

        $query->orderBy('views DESC, id DESC');

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $limit,
                'validatePage' => false
            ],
        ]);
    }

    public function actionByCategory($id)
    {
        UserActivity::trackCategory($id);

        $ids = ArrayHelper::map(Category::find()->where(['parent_id' => $id])->all(), 'id', 'id');
        $query = Product::find()
            ->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')
            ->where(['status' => 1])
            ->andWhere(['category_id' => $id])
            ->orWhere(['in', 'category_id', $ids])->andWhere(['status' => 1]);

        // Price filtering
        if ($price_min = Yii::$app->request->get('price_min')) {
            $query->andWhere(['>=', 'price', $price_min]);
        }
        if ($price_max = Yii::$app->request->get('price_max')) {
            $query->andWhere(['<=', 'price', $price_max]);
        }

        if ($sort = Yii::$app->request->get('sort')) {
            if (($sort == 'new') || ($sort == 'recently')) {
                $query->orderBy('id desc');
            }
            if ($sort == 'price_down') {
                $query->orderBy('price asc');
            }
            if ($sort == 'price_up') {
                $query->orderBy('price desc');
            }
            if ($sort == 'popular') {
                $query->orderBy('views desc');
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

    public function actionByBrand($id)
    {
        $query = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1])->andWhere(['brand_id' => $id]);

        // Price filtering
        if ($price_min = Yii::$app->request->get('price_min')) {
            $query->andWhere(['>=', 'price', $price_min]);
        }
        if ($price_max = Yii::$app->request->get('price_max')) {
            $query->andWhere(['<=', 'price', $price_max]);
        }

        if ($sort = Yii::$app->request->get('sort')) {
            if (($sort == 'new') || ($sort == 'recently')) {
                $query->orderBy('id desc');
            }
            if ($sort == 'price_down') {
                $query->orderBy('price asc');
            }
            if ($sort == 'price_up') {
                $query->orderBy('price desc');
            }
            if ($sort == 'popular') {
                $query->orderBy('views desc');
            }
        }

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        foreach ($query->all() as $product) {
            if ($product->status == 2) {
                $product->delete();
            }
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionByShop($id)
    {
        $query = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1])->andWhere(['shop_id' => $id]);

        if ($category_id = Yii::$app->request->get('category_id')) {
            $query->andWhere(['category_id' => $category_id]);
        }

        // Price filtering
        if ($price_min = Yii::$app->request->get('price_min')) {
            $query->andWhere(['>=', 'price', $price_min]);
        }
        if ($price_max = Yii::$app->request->get('price_max')) {
            $query->andWhere(['<=', 'price', $price_max]);
        }

        if ($sort = Yii::$app->request->get('sort')) {
            if (($sort == 'new') || ($sort == 'recently')) {
                $query->orderBy('id desc');
            }
            if ($sort == 'price_down') {
                $query->orderBy('price asc');
            }
            if ($sort == 'price_up') {
                $query->orderBy('price desc');
            }
            if ($sort == 'popular') {
                $query->orderBy('views desc');
            }
        }

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        foreach ($query->all() as $product) {
            if ($product->status == 2) {
                $product->delete();
            }
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionByFilter()
    {
        $products = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1]);

        if ($filter = Yii::$app->request->get('filter')) {
            // Check if we should use OR logic instead of AND
            $filterLogic = Yii::$app->request->get('filter_logic', 'and'); // 'and' or 'or'

            $productIds = [];
            $filterCount = 0;

            foreach ($filter as $filterId => $filterValue) {
                $filterCount++;

                // Find products that have this filter with the specified value
                $subQuery = ProductFilter::find()
                    ->select('product_id')
                    ->where(['filter_id' => $filterId]);

                // Apply value matching (supports single value or array for checkboxes)
                if (!empty($filterValue)) {
                    $subQuery->andWhere([
                        'or',
                        ['id' => $filterValue],
                        ['value_ru' => $filterValue],
                        ['value_en' => $filterValue],
                        ['value_uz' => $filterValue]
                    ]);
                }

                if ($filterCount === 1) {
                    $productIds = $subQuery->column();
                } else {
                    $currentProductIds = $subQuery->column();
                    if ($filterLogic === 'or') {
                        // Union with previous results (OR logic - product can have ANY filter)
                        $productIds = array_unique(array_merge($productIds, $currentProductIds));
                    } else {
                        // Intersect with previous results (AND logic - product must have ALL filters)
                        $productIds = array_intersect($productIds, $currentProductIds);
                    }
                }
            }

            if (!empty($productIds)) {
                $products->andWhere(['in', 'id', $productIds]);
            } else {
                // No products match the filters
                $products->andWhere(['id' => -1]);
            }
        }

        if ($category_id = Yii::$app->request->get('category_id')) {
            $ids1 = ArrayHelper::map(Category::find()->where(['parent_id' => $category_id])->all(), 'id', 'id');
            $products->andWhere([
                'or',
                ['category_id' => $category_id],
                ['in', 'category_id', $ids1],
            ]);
        }

        $this->applyPriceFilterWithBounds($products, 'price');

        if ($sort = Yii::$app->request->get('sort')) {
            if (($sort == 'new') || ($sort == 'recently')) {
                $products->orderBy('id desc');
            }
            if ($sort == 'price_down') {
                $products->orderBy('price asc');
            }
            if ($sort == 'price_up') {
                $products->orderBy('price desc');
            }
            if ($sort == 'popular') {
                $products->orderBy('views desc');
            }
        }


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

    /**
     * The most advanced and sophisticated product search action.
     * Supports keyword search, category filtering, brand filtering, price range, and sorting.
     * Also incorporates fuzzy matching and potential for search suggestions.
     *
     * @param string|null $query The search keyword(s).
     * @return ActiveDataProvider
     */
    public function actionSearch($query = null)
    {
        $products = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['product.status' => 1]);

        if ($query) {
            UserActivity::trackSearch($query);
            $searchTerms = explode(' ', $query);

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

            // Each term must appear in at least one field (AND between terms, OR between fields)
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (empty($term)) continue;

                $termCondition = ['or'];
                foreach ($searchFields as $field) {
                    $termCondition[] = ['like', $field, $term];
                }
                $products->andWhere($termCondition);
            }
        }

        // --- Additional Filters for Search Action ---

        // Filter by category_id (including subcategories)
        if ($category_id = Yii::$app->request->get('category_id')) {
            $categoryIds = [$category_id];
            $subcategories = Category::find()->where(['parent_id' => $category_id])->all();
            foreach ($subcategories as $subcat) {
                $categoryIds[] = $subcat->id;
            }
            $products->andWhere(['in', 'product.category_id', $categoryIds]);
        }

        // Filter by brand_id
        if ($brand_id = Yii::$app->request->get('brand_id')) {
            $products->andWhere(['product.brand_id' => $brand_id]);
        }

        // Filter by shop_id
        if ($shop_id = Yii::$app->request->get('shop_id')) {
            $products->andWhere(['product.shop_id' => $shop_id]);
        }

        // Filter by product filters (attributes)
        if ($filter = Yii::$app->request->get('filter')) {
            // For each filter, find products that match the specified values
            $productIds = [];
            $filterCount = 0;

            foreach ($filter as $filterId => $filterValue) {
                $filterCount++;

                // Find products that have this filter_id with the specified value
                // The value can be either a ProductFilter ID or a text value
                $subQuery = ProductFilter::find()
                    ->select('product_id')
                    ->where(['filter_id' => $filterId])
                    ->andWhere([
                        'or',
                        ['id' => $filterValue],           // Match by ProductFilter ID
                        ['value_ru' => $filterValue],     // Match by text value
                        ['value_en' => $filterValue],     // Match by text value (English)
                        ['value_uz' => $filterValue]      // Match by text value (Uzbek)
                    ]);

                if ($filterCount === 1) {
                    $productIds = $subQuery->column();
                } else {
                    // Intersect with previous results (AND logic - product must have ALL filters)
                    $currentProductIds = $subQuery->column();
                    $productIds = array_intersect($productIds, $currentProductIds);
                }
            }

            if (!empty($productIds)) {
                $products->andWhere(['in', 'product.id', $productIds]);
            } else {
                // No products match all filters
                $products->andWhere(['product.id' => -1]);
            }
        }

        // Price range filtering with bounds calculation
        $this->applyPriceFilterWithBounds($products, 'product.price');

        $colorId  = Yii::$app->request->get('color_id');
        $colorIds = Yii::$app->request->get('color_ids', []);

        if ($colorId || !empty($colorIds)) {
            $ids = $colorIds;
            if ($colorId) {
                $ids[] = $colorId;
            }
            $products->joinWith(['productColors.color'])
                ->andWhere(['color.id' => $ids]);
        }

        // Sorting options
        if ($sort = Yii::$app->request->get('sort')) {
            switch ($sort) {
                case 'new':
                case 'recently':
                    $products->orderBy('product.id desc');
                    break;
                case 'price_down':
                    $products->orderBy('product.price asc');
                    break;
                case 'price_up':
                    $products->orderBy('product.price desc');
                    break;
                case 'popular':
                    $products->orderBy('product.views desc');
                    break;
                case 'rating': // Sort by average review rating
                    $products->leftJoin('product_review', 'product_review.product_id = product.id AND product_review.status IN (1, 3)')
                        ->groupBy('product.id')
                        ->orderBy('AVG(product_review.rate) DESC');
                    break;
                // Add more complex sorting options here (e.g., by availability, discount)
                default:
                    $products->orderBy('product.id desc'); // Default sort
                    break;
            }
        } else {
            $products->orderBy('product.id desc'); // Default sort if no sort parameter is provided
        }

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        // Clean up products with status 2 (already handled in general index, but good to keep here for consistency if not universally applied)
        // Note: For a truly high-performance search, this deletion should ideally be a background task
        // or handled by a database trigger, not in the search query itself.
        foreach ($products->all() as $product) {
            if ($product->status == 2) {
                $product->delete();
            }
        }

        return new ActiveDataProvider([
            'query' => $products,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            // The sort property in ActiveDataProvider is often overridden by the query's orderBy,
            // but it's good practice to define a default here as well.
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionSearchSuggestions($query = null)
    {
        if (empty($query) || strlen($query) < 2) {
            return ['data' => []];
        }

        $suggestions = [];
        $limit = Yii::$app->request->get('limit', 10);

        $products = Product::find()
            ->select(['name_ru', 'name_uz', 'name_en'])
            ->where(['status' => 1])
            ->andWhere([
                'or',
                ['like', 'name_ru', $query],
                ['like', 'name_uz', $query],
                ['like', 'name_en', $query],
            ])
            ->limit($limit * 3)
            ->asArray()
            ->all();

        $seen = [];

        foreach ($products as $product) {
            if (!empty($product['name_ru'])) {
                $key = strtolower($product['name_ru']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $product['name_ru'],
                        'type' => 'product',
                        'lang' => 'ru'
                    ];
                }
            }
            if (!empty($product['name_uz'])) {
                $key = strtolower($product['name_uz']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $product['name_uz'],
                        'type' => 'product',
                        'lang' => 'uz'
                    ];
                }
            }
            if (!empty($product['name_en'])) {
                $key = strtolower($product['name_en']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $product['name_en'],
                        'type' => 'product',
                        'lang' => 'en'
                    ];
                }
            }
        }

        $categories = Category::find()
            ->select(['name_ru', 'name_uz', 'name_en'])
            ->where([
                'or',
                ['like', 'name_ru', $query],
                ['like', 'name_uz', $query],
                ['like', 'name_en', $query],
            ])
            ->limit(5)
            ->asArray()
            ->all();

        foreach ($categories as $category) {
            if (!empty($category['name_ru'])) {
                $key = strtolower($category['name_ru']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $category['name_ru'],
                        'type' => 'category',
                        'lang' => 'ru'
                    ];
                }
            }
            if (!empty($category['name_uz'])) {
                $key = strtolower($category['name_uz']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $category['name_uz'],
                        'type' => 'category',
                        'lang' => 'uz'
                    ];
                }
            }
            if (!empty($category['name_en'])) {
                $key = strtolower($category['name_en']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $category['name_en'],
                        'type' => 'category',
                        'lang' => 'en'
                    ];
                }
            }
        }

        $brands = CategoryBrand::find()
            ->select(['name_ru', 'name_uz', 'name_en'])
            ->where([
                'or',
                ['like', 'name_ru', $query],
                ['like', 'name_uz', $query],
                ['like', 'name_en', $query],
            ])
            ->limit(5)
            ->asArray()
            ->all();

        foreach ($brands as $brand) {
            if (!empty($brand['name_ru'])) {
                $key = strtolower($brand['name_ru']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $brand['name_ru'],
                        'type' => 'brand',
                        'lang' => 'ru'
                    ];
                }
            }
            if (!empty($brand['name_uz'])) {
                $key = strtolower($brand['name_uz']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $brand['name_uz'],
                        'type' => 'brand',
                        'lang' => 'uz'
                    ];
                }
            }
            if (!empty($brand['name_en'])) {
                $key = strtolower($brand['name_en']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = [
                        'text' => $brand['name_en'],
                        'type' => 'brand',
                        'lang' => 'en'
                    ];
                }
            }
        }

        usort($suggestions, function ($a, $b) use ($query) {
            $aPos = stripos($a['text'], $query);
            $bPos = stripos($b['text'], $query);

            if ($aPos === 0 && $bPos !== 0) return -1;
            if ($bPos === 0 && $aPos !== 0) return 1;

            return strcmp($a['text'], $b['text']);
        });

        return ['data' => array_slice($suggestions, 0, $limit)];
    }

    public function actionByPhoto()
    {
        if ($image = UploadedFile::getInstanceByName('photo')) {
            $rnd = mt_rand(0, 1000000);
            $name = time() + $rnd . '.' . $image->extension;
            $original = 'uploads/search/' . $name;
            $image->saveAs($original);

            $hasher = new ImageHash(new DifferenceHash());
            $hash = $hasher->hash(Yii::getAlias('@webroot') . '/' . $original); // Use Yii alias for webroot

            unlink($original);

            $images = Images::find()->where(['type' => 'product', 'main' => 1])->andWhere(['!=', 'hash', ''])->all();

            $ids = [];
            foreach ($images as $image) {
                if ($image->hash) {
                    $distance = $hasher->distance(Hash::fromHex($hash), Hash::fromHex($image->hash));
                    if ($distance < 15) { // Threshold for image similarity
                        $ids[] = $image->object_id;
                    }
                }
            }

            $products = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1]);
            if (!empty($ids)) {
                $products->andWhere(['in', 'id', $ids]);
            } else {
                // If no similar images found, return an empty data provider
                $products->andWhere('0=1'); // Force no results
            }

            // Apply general filters and sorting to photo search results
            if ($category_id = Yii::$app->request->get('category_id')) {
                $categoryIds = [$category_id];
                $subcategories = Category::find()->where(['parent_id' => $category_id])->all();
                foreach ($subcategories as $subcat) {
                    $categoryIds[] = $subcat->id;
                }
                $products->andWhere(['in', 'category_id', $categoryIds]);
            }

            if ($brand_id = Yii::$app->request->get('brand_id')) {
                $products->andWhere(['brand_id' => $brand_id]);
            }

            if ($shop_id = Yii::$app->request->get('shop_id')) {
                $products->andWhere(['shop_id' => $shop_id]);
            }

            $this->applyPriceFilterWithBounds($products, 'price');

            if ($sort = Yii::$app->request->get('sort')) {
                switch ($sort) {
                    case 'new':
                    case 'recently':
                        $products->orderBy('id desc');
                        break;
                    case 'price_down':
                        $products->orderBy('price asc');
                        break;
                    case 'price_up':
                        $products->orderBy('price desc');
                        break;
                    case 'popular':
                        $products->orderBy('views desc');
                        break;
                    case 'rating':
                        $products->leftJoin('product_review', 'product_review.product_id = product.id AND product_review.status IN (1, 3)')
                            ->groupBy('product.id')
                            ->orderBy('AVG(product_review.rate) DESC');
                        break;
                    default:
                        $products->orderBy('id desc');
                        break;
                }
            } else {
                $products->orderBy('id desc');
            }

            $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

            foreach ($products->all() as $product) {
                if ($product->status == 2) {
                    $product->delete();
                }
            }

            return new ActiveDataProvider([
                'query' => $products,
                'pagination' => [
                    'pageSize' => $perPage,
                    'validatePage' => false
                ],
                'sort' => ['defaultOrder' => ['id' => 'desc']]
            ]);
        }

        Yii::$app->response->statusCode = 400;
        return ['errors' => ['photo' => 'No photo uploaded.']];
    }

    public function actionDetail($id)
    {
        $product = Product::find()->with([
            'image',
            'category',
            'gallery',
            'productFilters',
            'productFilters.filter',
            'productReviews',
            'productProperties',
            'productColors',
            'productColors.color',
            'color',
            'productProductTypes',
            'productProductTypes.productType',
            'productProductTypes.productTypeValue',
            'products',
            'products.image',
            'products.color',
            'products.productProductTypes',
            'products.productProductTypes.productType',
            'products.productProductTypes.productTypeValue'
        ])->where(['id' => $id])->one();

        if ($product === null) {
            throw new \yii\web\NotFoundHttpException('Товар не найден.');
        }

        UserActivity::trackView($product->id, $product->category_id);

        // set views
        if ($product) {
            $view = ProductView::findOne(['product_id' => $product->id, 'ip' => Yii::$app->request->userIP]);
            if (!$view) {
                $view = new ProductView;
                $view->saveObject($product);
            }
        }
        // end set views

        // set view recently
        if ($product) {
            $recently = ProductViewRecently::findOne(['product_id' => $product->id, 'ip' => Yii::$app->request->userIP]);
            if (!$recently) {
                $recently = new ProductViewRecently;
                $recently->saveObject($product);
            }
        }
        // end set view recently

        return ['data' => $product];
    }
    // end general product methods

    // favorites
    public function actionFavorites()
    {
        $user = Yii::$app->user->identity;

        $ids = ArrayHelper::map(UserFavorite::find()->where(['user_id' => $user->id])->all(), 'product_id', 'product_id');
        $query = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1])->andWhere(['in', 'id', $ids]);

        if ($sort = Yii::$app->request->get('sort')) {
            if (($sort == 'new') || ($sort == 'recently')) {
                $query->orderBy('id desc');
            }

            if ($sort == 'price_down') {
                $query->orderBy('price asc');
            }

            if ($sort == 'price_up') {
                $query->orderBy('price desc');
            }

            if ($sort == 'popular') {
                $query->orderBy('views desc');
            }
        }

        if ($category_id = Yii::$app->request->get('category_id')) {
            $query->andWhere(['category_id' => $category_id]);
        }

        // Price filtering with bounds calculation
        $this->applyPriceFilterWithBounds($query, 'price');

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        foreach ($query->all() as $product) {
            if ($product->status == 2) {
                $product->delete();
            }
        }

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

        $user_favorite = new UserFavorite;
        $user_favorite->setAttributes($post);

        if (!$user_favorite->validate()) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Validation error', $user_favorite->errors);
        }

        $product = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productReviews', 'productProperties', 'productColors', 'productColors.color')->where(['id' => $post['product_id']])->one();

        if (!$product) {
            return $this->sendError(ErrorCodes::ERROR_PRODUCT_NOT_FOUND, 'Product not found', ['product_id' => 'Product not found.']);
        }

        $favorite = UserFavorite::findOne(['user_id' => $user->id, 'product_id' => $post['product_id']]);

        if ($favorite) {
            $favorite->delete();
            return $this->sendSuccess(array_merge($product->toArray(), ['is_favorite' => false]));
        }

        $user_favorite->user_id = $user->id; // Ensure user_id is set from authenticated user
        $user_favorite->save(); // Use save instead of saveObject if saveObject is not defined in UserFavorite

        return $this->sendSuccess(array_merge($product->toArray(), ['is_favorite' => true]));
    }

    public function actionFavoriteCategories()
    {
        $user = Yii::$app->user->identity;

        $ids = ArrayHelper::map(UserFavorite::find()->where(['user_id' => $user->id])->all(), 'product_id', 'product_id');
        $category_ids = ArrayHelper::map(Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1])->andWhere(['in', 'id', $ids])->all(), 'category_id', 'category_id');

        $categories = Category::find()->where(['in', 'id', $category_ids])->all();

        return ['data' => $categories];
    }
    // end favorites

    // compares
    public function actionCompares()
    {
        $user = Yii::$app->user->identity;

        $ids = ArrayHelper::map(UserCompare::find()->where(['user_id' => $user->id])->all(), 'product_id', 'product_id');
        $query = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1])->andWhere(['in', 'id', $ids]);

        if ($sort = Yii::$app->request->get('sort')) {
            if (($sort == 'new') || ($sort == 'recently')) {
                $query->orderBy('id desc');
            }

            if ($sort == 'price_down') {
                $query->orderBy('price asc');
            }

            if ($sort == 'price_up') {
                $query->orderBy('price desc');
            }

            if ($sort == 'popular') {
                $query->orderBy('views desc');
            }
        }

        if ($category_id = Yii::$app->request->get('category_id')) {
            $query->andWhere(['category_id' => $category_id]);
        }

        // Price filtering with bounds calculation
        $this->applyPriceFilterWithBounds($query, 'price');

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        foreach ($query->all() as $product) {
            if ($product->status == 2) {
                $product->delete();
            }
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionSetCompare()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $user_compare = new UserCompare;
        $user_compare->setAttributes($post);

        if (!$user_compare->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $user_compare->errors];
        }

        $product = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productReviews', 'productProperties', 'productColors', 'productColors.color')->where(['id' => $post['product_id']])->one();

        if (!$product) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['product_id' => 'Product not found.']];
        }

        $compare = UserCompare::findOne(['user_id' => $user->id, 'product_id' => $post['product_id']]);

        if ($compare) {
            $compare->delete();
            Yii::$app->response->statusCode = 200;
            return ['data' => array_merge($product->toArray(), ['is_compared' => false])];
        }

        $user_compare->user_id = $user->id; // Ensure user_id is set from authenticated user
        $user_compare->save();

        Yii::$app->response->statusCode = 200;
        return ['data' => array_merge($product->toArray(), ['is_compared' => true])];
    }

    public function actionCompareCategories()
    {
        $user = Yii::$app->user->identity;

        $ids = ArrayHelper::map(UserCompare::find()->where(['user_id' => $user->id])->all(), 'product_id', 'product_id');
        $category_ids = ArrayHelper::map(Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1])->andWhere(['in', 'id', $ids])->all(), 'category_id', 'category_id');

        $categories = Category::find()->where(['in', 'id', $category_ids])->all();

        return ['data' => $categories];
    }
    // end compares

    // review
    public function actionSetReview()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $product_review = new ProductReview;
        $product_review->setAttributes($post);

        if (!$product_review->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $product_review->errors];
        }

        $product = Product::findOne($post['product_id']);

        if (!$product) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['product_id' => 'Product not found.']];
        }

        $product_check = ProductReview::findOne(['user_id' => $user->id, 'product_id' => $product->id]);

        if ($product_check) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['general' => 'Вы уже оставили свой голос']];
        }

        $product_review->user_id = $user->id; // Ensure user_id is set
        $product_review->save(); // Use save directly if saveObject is not defined or needed

        $product = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productReviews', 'productColors', 'productColors.color')->where(['id' => $post['product_id']])->one();

        Yii::$app->response->statusCode = 200;
        return ['data' => $product];
    }

    public function actionReviews($product_id)
    {
        $user = Yii::$app->user->identity;

        $query = ProductReview::find()->with('user', 'user.image', 'orderProduct')->where(['product_id' => $product_id, 'status' => [ProductReview::STATUS_ACCEPTED, ProductReview::STATUS_PROCESSED]])->orderBy('id desc');

        if ($sort_date = Yii::$app->request->get('sort_date')) {
            $query->orderBy('id ' . $sort_date); // 'asc' or 'desc'
        }

        if ($sort_rating = Yii::$app->request->get('sort_rating')) {
            $query->orderBy('rate ' . $sort_rating); // 'asc' or 'desc'
        }

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']] // Default sorting
        ]);
    }
    // end review

    // recently view
    public function actionRecentlyViewed()
    {
        $user = Yii::$app->user->identity;

        $ids = ArrayHelper::map(ProductViewRecently::find()->where(['ip' => Yii::$app->request->userIP])->orderBy('date DESC')->all(), 'product_id', 'product_id');

        // Ensure that recently viewed products are active (status=1)
        $query = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')->where(['status' => 1]);
        if (!empty($ids)) {
            // Maintain the order of recently viewed items
            $query->andWhere(['in', 'id', $ids])->orderBy(['FIELD(id, ' . implode(',', $ids) . ')' => SORT_ASC]);
        } else {
            $query->andWhere('0=1'); // No recently viewed products
        }


        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
        ]);
    }
    // end recently view

    public function actionRelatedProducts($product_id)
    {
        $product = Product::findOne($product_id);

        if (!$product) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['product_id' => 'Товар не найден']];
        }

        // Start with a broad search based on main product fields
        $query = Product::find()->with('image', 'category', 'gallery', 'productFilters', 'productColors', 'productColors.color')
            ->where(['status' => 1])
            ->andWhere(['!=', 'id', $product_id]); // Exclude the current product

        $orConditions = ['or'];
        $keywords = [];

        // Extract keywords from product name and description (can be refined)
        $keywords = array_merge($keywords, explode(' ', $product->name_ru));
        if ($product->name_uz) $keywords = array_merge($keywords, explode(' ', $product->name_uz));
        if ($product->name_en) $keywords = array_merge($keywords, explode(' ', $product->name_en));
        if ($product->description_ru) $keywords = array_merge($keywords, explode(' ', $product->description_ru));
        if ($product->description_uz) $keywords = array_merge($keywords, explode(' ', $product->description_uz));
        if ($product->description_en) $keywords = array_merge($keywords, explode(' ', $product->description_en));

        // Add related fields for broader matching
        if ($product->composition_ru) $keywords = array_merge($keywords, explode(' ', $product->composition_ru));
        if ($product->composition_uz) $keywords = array_merge($keywords, explode(' ', $product->composition_uz));
        if ($product->composition_en) $keywords = array_merge($keywords, explode(' ', $product->composition_en));
        if ($product->recommendation_ru) $keywords = array_merge($keywords, explode(' ', $product->recommendation_ru));
        if ($product->recommendation_uz) $keywords = array_merge($keywords, explode(' ', $product->recommendation_uz));
        if ($product->recommendation_en) $keywords = array_merge($keywords, explode(' ', $product->recommendation_en));

        // Remove duplicates and common short words, perform stemming if possible
        $keywords = array_unique(array_filter($keywords, function ($word) {
            return strlen($word) > 2; // Only consider words longer than 2 characters
        }));

        foreach ($keywords as $keyword) {
            $orConditions[] = ['like', 'name_ru', $keyword];
            $orConditions[] = ['like', 'name_uz', $keyword];
            $orConditions[] = ['like', 'name_en', $keyword];
            $orConditions[] = ['like', 'description_ru', $keyword];
            $orConditions[] = ['like', 'description_uz', $keyword];
            $orConditions[] = ['like', 'description_en', $keyword];
        }

        // Add category and brand as strong indicators of relatedness
        if ($product->category_id) {
            $orConditions[] = ['category_id' => $product->category_id];
            // Also consider siblings in the same parent category or children of the same parent
            $parentCategory = Category::findOne($product->category_id);
            if ($parentCategory && $parentCategory->parent_id) {
                $siblingCategoryIds = ArrayHelper::map(Category::find()->where(['parent_id' => $parentCategory->parent_id])->all(), 'id', 'id');
                if (!empty($siblingCategoryIds)) {
                    $orConditions[] = ['in', 'category_id', $siblingCategoryIds];
                }
            }
        }
        if ($product->brand_id) {
            $orConditions[] = ['brand_id' => $product->brand_id];
        }

        if (count($orConditions) > 1) {
            $query->andWhere($orConditions);
        } else {
            // If no significant keywords or related attributes, fallback to same category products
            if ($product->category_id) {
                $query->andWhere(['category_id' => $product->category_id]);
            }
        }

        // Order by relevance (e.g., matching keywords count, then views) - complex in SQL
        // For simplicity, let's order by views or simply id desc for now
        $query->orderBy('views DESC, id DESC'); // More popular related products first

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        // No need to delete products here; this is for fetching related, not managing status
        // The `status = 2` check and delete logic should be in a cron job or background process.

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            // 'sort' is often redundant if orderBy is set directly on the query,
            // but can be used for client-side sorting if needed.
            // 'sort' => ['defaultOrder' => ['views' => 'desc', 'id' => 'desc']]
        ]);
    }

    /**
     * Submit a product request
     * POST /api/product/request
     * 
     * @return array
     */
    public function actionRequest()
    {
        $post = Yii::$app->request->post();

        // Basic validation
        if (empty($post['product_name'])) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Заполните поле', ['product_name' => ['Заполните поле']]);
        }

        if (empty($post['quantity'])) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Заполните поле', ['quantity' => ['Заполните поле']]);
        }

        if (empty($post['phone'])) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Заполните поле', ['phone' => ['Заполните поле']]);
        }

        // Create new request
        $request = new ProductRequest();
        $request->setAttributes($post);

        // Set the user who sent the request
        $request->user_id = Yii::$app->user->id;

        // Handle file upload - photo is optional
        $uploadedFile = UploadedFile::getInstanceByName('product_photo');
        if ($uploadedFile) {
            $request->product_photo_file = $uploadedFile;
        }

        if (!$request->validate()) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Validation error', $request->errors);
        }

        if ($request->saveRequest()) {
            return $this->sendSuccess([
                'message' => 'Запрос на товар успешно отправлен. Мы свяжемся с вами в ближайшее время.',
                'request' => $request
            ], 'Запрос на товар успешно отправлен.');
        } else {
            return $this->sendError(ErrorCodes::ERROR_SERVER, 'Произошла ошибка при сохранении запроса', ['server' => ['Произошла ошибка при сохранении запроса']]);
        }
    }
}
