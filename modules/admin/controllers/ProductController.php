<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\Response;

use app\models\user\User;
use app\models\product\Product;
use app\models\product\ProductSearch;
use app\models\product\ProductFilter;
use app\models\product\ProductColor;
use app\models\color\Color;
use app\models\filter\Filter;
use app\models\shop\Shop;
use app\models\Category;
use app\models\brand\CategoryBrand;
use app\models\Notification;
use app\models\stock\Stock;
use app\models\delivery\Delivery;
use app\models\office\Office;
use app\models\office\ProductOffice;
use app\models\Images;
use app\models\moderator\ModerationComment;
use app\models\product\ProductModerationComment;
use app\models\Settings;
use app\models\product\ProductType;
use app\models\product\ProductTypeValue;
use app\models\product\ProductProductType;
use app\models\product\ProductAslBelgisi;
use app\services\AslBelgisiService;
use GuzzleHttp\Client;
use yii\services\Billz;
use Intervention\Image\ImageManager;

class ProductController extends Controller
{
    public $user;

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id' => Yii::$app->user->identity->id])->one();

        if (!$this->user) {
            Yii::$app->user->logout(false);
            return $this->redirect(["/admin/default"]);
        }

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('product', $accesses)) {
                return $this->redirect(['/admin/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($status = null, $page = 1)
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('category', 'image');

        if ($this->user && $this->user?->role == User::ROLE_MODERATOR) {
            $dataProvider->query->andWhere(['status' => 2])->andWhere(['deleted_at' => null]);
        }

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        $shops = ArrayHelper::map(Shop::find()->where(['status' => 1])->all(), 'id', 'name_ru');
        $users = ArrayHelper::map(User::find()->where(['status' => 1])->all(), 'id', 'name');
        $categories = ArrayHelper::map(Category::find()->where(['type' => 'product'])->all(), 'id', 'name_ru');
        $settings = Settings::findOne(['type' => 'filter_on']);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'shops' => $shops,
            'users' => $users,
            'categories' => $categories,
            'settings' => $settings,
            'page' => $page
        ]);
    }

    public function actionView($id, $page = 1)
    {
        $this->log('view' . $id);
        $model = Product::find()->with(
            'delivery',
            'category',
            'image',
            'productProperties',
            'productColors',
            'productColors.image',
            'productColors.color',
            'productOffices',
            'productProductTypes',
            'productProductTypes.productType',
            'productProductTypes.productTypeValue',
            'products',
            'products.color',
            'products.image',
            'products.productProductTypes',
            'products.productProductTypes.productType',
            'products.productProductTypes.productTypeValue',
            'colorImages',
        )->where(['id' => $id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $notification = Notification::findOne(['type' => 'product_new', 'object_id' => $id]);
        if ($notification) {
            $notification->status = 1;
            $notification->save(false);
        }

        return $this->render('view', [
            'model' => $model,
            'page' => $page
        ]);
    }

    public function actionCreate($id = null)
    {
        $this->log('create');
        $model = new Product;

        $current_categories = [];
        $current_colors = [];
        $current_product_types = [];
        $tree = [0 => ''];

        if ($this->user && $this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }

        // edit
        if ($id) {
            $model = Product::find()->with('image', 'gallery', 'category', 'productColors', 'productColors.image', 'productColors.color', 'productFilters', 'productFilters.filter', 'productProperties', 'productOffices')->where(['id' => $id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = $model->category_tree ? explode('/', $model->category_tree) : [0];

            foreach ($tree as $key => $v_id) {
                $current_categories[] = ArrayHelper::map(Category::find()->where(['parent_id' => $v_id])->all(), 'id', 'name_ru');
            }

            $current_colors = ArrayHelper::map(ProductColor::find()->where(['in', 'product_id', $v_id])->all(), 'color_id', 'color_id');

            // Load existing product type connections
            $current_product_types = ProductProductType::find()
                ->with('productType', 'productTypeValue')
                ->where(['product_id' => $id])
                ->all();
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if (!$id) {
                // Generate a shared token_key for all variants
                $shared_token_key = $model->token_key ?: Yii::$app->security->generateRandomString();

                // Get explicit variants from request (new logic)
                $post = Yii::$app->request->post();
                $variants = isset($post['Product']['variants']) ? $post['Product']['variants'] : [];

                if (!empty($variants)) {
                    // New logic: Iterate through explicitly defined variants
                    foreach ($variants as $variant) {
                        $color = isset($variant['color_id']) && $variant['color_id'] !== '' ? $variant['color_id'] : null;
                        $types = isset($variant['types']) ? $variant['types'] : [];

                        $price_data = [
                            'price' => isset($variant['price']) ? $variant['price'] : null,
                            'price_small' => isset($variant['price_small']) ? $variant['price_small'] : null,
                            'price_opt' => isset($variant['price_opt']) ? $variant['price_opt'] : null,
                            'amount' => isset($variant['amount']) ? $variant['amount'] : null,
                        ];

                        // saveObject expects product_types as [type_id => value_id] or [type_id => [value_id]]
                        // Our $types is [type_id => value_id]
                        // We need to pass it correctly. saveObject handles it.

                        $product = $model->saveObject(false, $color, $shared_token_key, null, $types, $price_data);
                    }
                } else {
                    // Fallback to old logic (if no variants generated or JS disabled/failed)

                    // Get product type prices from request
                    $product_type_prices = isset($post['Product']['product_type_prices']) ? $post['Product']['product_type_prices'] : [];

                    // Create mode: Handle product type variations
                    if ($model->product_types) {
                        $type_combinations = $this->generateTypeCombinations($model->product_types);

                        foreach ($type_combinations as $combination) {
                            // Determine price overrides for this combination
                            $price_data = null;
                            foreach ($combination as $type_id => $value_id) {
                                if (isset($product_type_prices[$value_id])) {
                                    $p_data = $product_type_prices[$value_id];
                                    if (!empty($p_data['price']) || !empty($p_data['price_small']) || !empty($p_data['price_opt'])) {
                                        $price_data = $p_data;
                                    }
                                }
                            }

                            if ($model->colors) {
                                foreach ($model->colors as $color) {
                                    $product = $model->saveObject(false, $color, $shared_token_key, null, $combination, $price_data);
                                }
                            } else {
                                $product = $model->saveObject(false, null, $shared_token_key, null, $combination, $price_data);
                            }
                        }
                    } else {
                        // Original color handling for create
                        if ($model->colors) {
                            foreach ($model->colors as $color) {
                                $product = $model->saveObject(false, $color, $shared_token_key);
                            }
                        } else {
                            $product = $model->saveObject(false);
                        }
                    }
                }
            } else {
                // Update mode: Don't modify product types, just update the main product
                $product = $model->updateObject(false);
            }

            // Auto-check ASL Belgisi if product has barcode
            $this->checkAslBelgisiForProduct($product);

            Yii::$app->session->setFlash('product_saved', 'Saved');
            return $this->redirect(['/admin/product/view', 'id' => $product->id]);
        }

        $categories = ArrayHelper::map(Category::find()->with('childs')->where(['parent_id' => 0, 'type' => 'product'])->all(), 'id', 'name_ru');
        $brands = ArrayHelper::map(CategoryBrand::find()->where(['status' => 1])->all(), 'id', 'name_ru');
        $offices = ArrayHelper::map(Office::find()->all(), 'id', 'name');
        $deliveries = ArrayHelper::map(Delivery::find()->all(), 'id', 'name_ru');
        $stocks = ArrayHelper::map(Stock::find()->all(), 'id', 'name_ru');
        $shops = ArrayHelper::map(Shop::find()->where(['status' => 1])->all(), 'id', 'name_ru');

        // Prepare warehouses grouped by shop for dynamic filtering
        $warehousesByShop = [];
        $allWarehouses = Stock::find()->where(['status' => 1])->all();

        // Debug: Log warehouse data
        error_log("🔧 DEBUG: Found " . count($allWarehouses) . " active warehouses");

        foreach ($allWarehouses as $warehouse) {
            error_log("🔧 DEBUG: Warehouse ID: {$warehouse->id}, Name: {$warehouse->name_ru}, Shop ID: {$warehouse->shop_id}");

            if ($warehouse->shop_id) {
                $warehousesByShop[$warehouse->shop_id][] = [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name_ru
                ];
            }
        }

        // Debug: Log final grouped data
        error_log("🔧 DEBUG: warehousesByShop structure: " . json_encode($warehousesByShop));
        error_log("🔧 DEBUG: Shop IDs with warehouses: " . implode(', ', array_keys($warehousesByShop)));

        $tags = ArrayHelper::map(Category::find()->where(['type' => 'tag'])->all(), 'id', 'name_ru');
        $units = ArrayHelper::map(Category::find()->where(['type' => 'unit', 'status' => 1])->all(), 'id', 'name_ru');
        $colors = ArrayHelper::map(Color::find()->all(), 'id', 'name_ru');
        $colors_object = Color::find()->all();
        $currencies = ArrayHelper::map(Category::find()->where(['type' => 'currency', 'status' => 1])->all(), 'id', 'name_ru');

        // Product types will be loaded dynamically via AJAX
        $product_types = [];
        $product_type_values = [];

        $view = $id ? 'update' : 'create';
        $this->normalizePropertiesDataForForm($model);

        return $this->render($view, [
            'model' => $model,
            'categories' => $categories,
            'brands' => $brands,
            'current_categories' => $current_categories,
            'current_colors' => $current_colors,
            'current_product_types' => $current_product_types,
            'tree' => $tree,
            'offices' => $offices,
            'deliveries' => $deliveries,
            'colors' => $colors,
            'colors_object' => $colors_object,
            'stocks' => $stocks,
            'shops' => $shops,
            'warehousesByShop' => $warehousesByShop,
            'tags' => $tags,
            'units' => $units,
            'currencies' => $currencies,
            'product_types' => $product_types,
            'product_type_values' => $product_type_values
        ]);
    }

    private function normalizePropertiesDataForForm(Product $model): void
    {
        if (!is_array($model->properties_data)) {
            return;
        }

        $keys = $model->properties_data['key_name'] ?? [];
        $values = $model->properties_data['value_name'] ?? [];

        if (!is_array($keys) || !is_array($values)) {
            $model->properties_data = [];
            return;
        }

        foreach ($keys as $index => $key) {
            $value = $values[$index] ?? null;
            if (trim((string) $key) !== '' || trim((string) $value) !== '') {
                return;
            }
        }

        $model->properties_data = [];
    }

    public function actionLock($id)
    {
        $model = Product::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Product not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role === User::ROLE_MODERATOR && $model->status != 2) {
            throw new HttpException(403, 'Moderator can only unlock products');
        }

        $oldStatus = $model->status;
        $model->status = ($model->status == 1) ? 2 : 1;
        $model->save(false);

        $commentText = $model->status == 1 ? 'Ваш товар разблокирован' : 'Ваш товар заблокирован модератором';

        $comment = new ModerationComment();
        $comment->entity_type = 'product';
        $comment->entity_id = $model->id;
        $comment->action = $model->status == 1 ? 'approve' : 'block';
        $comment->comment = $commentText;
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = (bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false);
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type' => 'product',
            'entity_id' => $model->id,
            'action' => $model->status == 1 ? 'approve' : 'block',
            'status_after' => $model->status == 1 ? 'approved' : 'pending',
            'comment' => $commentText,
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash(
            'product_locked',
            $model->status == 1 ? 'Product unlocked' : 'Product blocked'
        );

        return $user->role === User::ROLE_MODERATOR
            ? $this->redirect(['/admin/product'])
            : $this->redirect(Yii::$app->request->referrer);
    }


    protected function sendToWarehouse(array $payload)
    {
        if ((bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false)) {
            return;
        }

        try {
            $client = new Client(['timeout' => 5.0]);

            $apiUrl = rtrim(Yii::$app->params['warehouseApiUrl'] ?? 'https://api.warehouse.example.com', '/') . '/api/moderation/sync';

            $secretKey = Yii::$app->params['apiSecretKey'] ?? null;
            if (!$secretKey) {
                return;
            }

            $token = md5($payload['id'] . $secretKey);

            $client->post($apiUrl, [
                'json' => array_merge($payload, [
                    'metadata' => [
                        'source' => 'yii2',
                    ],
                ]),
                'headers' => [
                    'X-Api-Token' => $token,
                ],
            ]);

        } catch (\Throwable $e) {
            \Yii::error($e->getMessage(), 'warehouse');
        }
    }

    public function actionComment($id)
    {
        $model = Product::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Product not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role !== User::ROLE_MODERATOR) {
            throw new HttpException(403, 'Access denied');
        }

        if ($model->status != 2) {
            throw new HttpException(400, 'Comment allowed only for blocked products');
        }

        $commentText = trim(Yii::$app->request->post('comment'));
        if (!$commentText) {
            Yii::$app->session->setFlash('error', 'Комментарий обязателен');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $comment = new ModerationComment();
        $comment->entity_type = 'product';
        $comment->entity_id = $model->id;
        $comment->action = 'reject';   // approve | reject | block
        $comment->comment = $commentText;
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = 0;
        $comment->status_after = 'rejected';
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type' => 'product',
            'entity_id' => $model->id,
            'action' => 'reject',
            'status_after' => 'rejected',
            'comment' => $commentText,
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash(
            'info',
            'Комментарий отправлен. Товар остаётся заблокированным.'
        );

        return $this->redirect(['/admin/product']);
    }




    /**
     * List all ASL Belgisi check records (read-only).
     * GET /admin/product/asl-belgisi
     */
    public function actionAslBelgisi($page = 1)
    {
        $pageSize = 20;
        $records = [];
        $total = 0;
        $search = Yii::$app->request->get('search');
        $statusFilter = Yii::$app->request->get('status');

        try {
            $query = ProductAslBelgisi::find()
                ->orderBy(['checked_at' => SORT_DESC]);

            if ($search) {
                $query->andWhere([
                    'or',
                    ['like', 'gtin', $search],
                    ['like', 'product_name_ru', $search],
                    ['like', 'inn', $search],
                    ['like', 'asl_product_id', $search],
                ]);
            }

            if ($statusFilter) {
                $query->andWhere(['status' => $statusFilter]);
            }

            $total = $query->count();
            $records = $query
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->all();
        } catch (\Throwable $e) {
            Yii::error('ASL Belgisi table not available: ' . $e->getMessage(), __METHOD__);
        }

        return $this->render('asl-belgisi', [
            'records' => $records,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Check product barcode against ASL Belgisi registry.
     * GET /admin/product/check-asl-belgisi?id=123
     */
    public function actionCheckAslBelgisi($id)
    {
        $model = Product::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Product not found');
        }

        $gtin = $model->barcode;
        if (empty($gtin)) {
            Yii::$app->session->setFlash('asl_belgisi_error', 'У товара не указан штрихкод (barcode)');
            return $this->redirect(Yii::$app->request->referrer ?: ['/admin/product/view', 'id' => $id]);
        }

        $service = new AslBelgisiService();
        if (!$service->isConfigured()) {
            Yii::$app->session->setFlash('asl_belgisi_error', 'ASL Belgisi сервис не настроен');
            return $this->redirect(Yii::$app->request->referrer ?: ['/admin/product/view', 'id' => $id]);
        }

        $result = $service->searchByGtin($gtin);

        if (!$result['success']) {
            Yii::$app->session->setFlash('asl_belgisi_error', 'Ошибка ASL Belgisi: ' . $result['error']);
            return $this->redirect(Yii::$app->request->referrer ?: ['/admin/product/view', 'id' => $id]);
        }

        if (empty($result['products'])) {
            Yii::$app->session->setFlash('asl_belgisi_warning', 'Товар с GTIN ' . $gtin . ' не найден в реестре ASL Belgisi');
            return $this->redirect(Yii::$app->request->referrer ?: ['/admin/product/view', 'id' => $id]);
        }

        // Upsert ASL entry by GTIN (one entry per GTIN, products auto-match via barcode)
        $first = $result['products'][0];
        $parsed = $service->parseProduct($first);
        $entry = ProductAslBelgisi::upsertByGtin($gtin, $parsed);

        $aslStatus = $parsed['status'] ?? 'unknown';
        $aslName = $parsed['product_name_ru'] ?? '';
        $matchedCount = Product::find()->where(['barcode' => $gtin])->count();

        Yii::$app->session->setFlash(
            'asl_belgisi_success',
            "ASL Belgisi: статус {$aslStatus}. {$aslName}. Совпадающих товаров: {$matchedCount}"
        );

        return $this->redirect(Yii::$app->request->referrer ?: ['/admin/product/view', 'id' => $id]);
    }

    /**
     * View a single ASL Belgisi entry with all products sharing the same GTIN.
     * GET /admin/product/asl-belgisi-view?id=123
     */
    public function actionAslBelgisiView($id)
    {
        try {
            $entry = ProductAslBelgisi::findOne($id);
        } catch (\Throwable $e) {
            throw new HttpException(500, 'ASL Belgisi table not available');
        }

        if (!$entry) {
            throw new HttpException(404, 'ASL Belgisi record not found');
        }

        // All products whose barcode matches this GTIN (auto-connected)
        $matchedProducts = Product::find()
            ->where(['barcode' => $entry->gtin])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        return $this->render('asl-belgisi-view', [
            'entry' => $entry,
            'matchedProducts' => $matchedProducts,
        ]);
    }

    public function actionRemoves($id, $page = 1)
    {
        if (Yii::$app->user->identity->role == User::ROLE_ADMIN) {
            $this->log('remove' . $id);
            $model = Product::find()->where(['id' => $id])->one();


            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
            $model->button_id = 1;
            $model->save();
            $model->softDelete();
        }

        if ($this->user && $this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }

        return $this->redirect(['/admin/product/index?page=' . $page]);
    }

    public function actionRemove($id)
    {
        if ($this->user && $this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }

        $this->log('del' . $id);
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionImport()
    {
        $billz = new Billz();

        $data = [
            "jsonrpc" => "2.0",
            "method" => "products.get",
            "params" => [
                "LastUpdatedDate" => "2018-03-21T18:19:25Z",
                "WithProductPhotoOnly" => 0,
                "IncludeEmptyStocks" => 0
            ],
            "id" => "1"
        ];

        $products = $billz->request(json_encode($data));

        if ($products && $products->result) {
            foreach ($products->result as $k => $v) {
                // category
                if ($v->properties->CATEGORY) {
                    $category = Category::findOne(['type' => 'product', 'name_ru' => $v->properties->CATEGORY]);
                    if (!$category) {
                        $category = new Category;
                    }
                    $category->type = 'product';
                    $category->name_ru = $v->properties->CATEGORY;
                    $category->parent_id = 0;
                    $category->status = 1;
                    $category->save(false);
                }
                // brand
                if ($v->properties->BRAND) {
                    $brand = CategoryBrand::findOne(['name_ru' => $v->properties->BRAND]);
                    if (!$brand) {
                        $brand = new CategoryBrand;
                    }
                    $brand->name_ru = $v->properties->BRAND;
                    $brand->status = 1;
                    $brand->save(false);
                }
                // color
                if ($v->properties->COLOR) {
                    $color = Color::findOne(['name_ru' => $v->properties->COLOR]);
                    if (!$color) {
                        $color = new Color;
                    }
                    $color->name_ru = $v->properties->COLOR;
                    $color->save(false);
                }
                // office
                if ($v->offices) {
                    foreach ($v->offices as $k_office => $v_office) {
                        $office = Office::findOne(['office_id' => $v_office->officeID, 'name' => $v_office->officeName]);
                        if (!$office) {
                            $office = new Office;
                        }

                        $office->office_id = $v_office->officeID;
                        $office->name = $v_office->officeName;
                        $office->save(false);
                    }
                }
                // product
                $product = Product::findOne(['name_ru' => $v->name, 'billz_id' => $v->ID]);
                if (!$product) {
                    $product = new Product;
                }

                $product->billz_id = $v->ID;
                if ($category) {
                    $product->category_id = $category->id;
                }
                if ($brand) {
                    $product->brand_id = $brand->id;
                }
                $product->name_ru = $v->name;
                $product->sku = $v->sku;
                $product->barcode = $v->barcode;
                $product->price = $v->price;
                $product->discount = $v->discountAmount;
                $product->qty = $v->qty;
                $product->amount = $v->qty;
                if ($v->properties->DESCRIPTION) {
                    $product->description_ru = $v->properties->DESCRIPTION;
                }
                $product->status = 2;
                $product->save(false);

                // product color
                if ($product && $color) {
                    $product_color = ProductColor::findOne(['product_id' => $product->id, 'color_id' => $color->id]);
                    if (!$product_color) {
                        $product_color = new ProductColor;
                    }
                    $product_color->product_id = $product->id;
                    $product_color->color_id = $color->id;
                    $product_color->status = 1;
                    $product_color->save(false);
                }
                // product office
                if ($product && $office && $v->offices) {
                    foreach ($v->offices as $pr_office_k => $pr_office_v) {
                        $product_office = ProductOffice::findOne(['product_id' => $product->id]);
                        if (!$product_office) {
                            $product_office = new ProductOffice;
                        }
                        $product_office->product_id = $product->id;
                        $product_office->office_id = $office->id;
                        $product_office->price = $pr_office_v->price;
                        $product_office->discount = $pr_office_v->discountAmount;
                        $product_office->qty = $pr_office_v->qty;
                        $product_office->save(false);
                    }
                }
                // images
                if ($v->imageUrls && $product) {
                    foreach ($v->imageUrls as $k_img => $v_img) {
                        if ($v_img && $v_img->url) {
                            $img = str_replace('_square', '', $v_img->url);
                            $image = Images::findOne(['object_id' => $product->id, 'type' => 'product', 'web' => 1, 'photo' => $img]);
                            if (!$image) {
                                $image = new Images;
                            }
                            $image->object_id = $product->id;
                            $image->type = 'product';
                            $image->photo = $img;
                            $image->web = 1;
                            $image->status = 1;
                            $image->main = ($k_img == 0) ? 1 : 2;
                            $image->save(false);
                        }
                    }
                }
            }
        }

        Yii::$app->session->setFlash('product_downloaded', 'Downloaded');

        return $this->redirect(['/admin/product']);
    }

    public function actionFilterOn()
    {
        $settings = Settings::findOne(['type' => 'filter_on']);

        if (!$settings) {
            $settings = new Settings;
            $settings->content = '0';
            $settings->type = 'filter_on';
            $settings->save();

            Yii::$app->session->setFlash('filter_off', 'Settings off in the shop');
        } else {
            if ($settings->content == '0') {
                $settings->content = '1';
                Yii::$app->session->setFlash('filter_on', 'Settings on in the shop');
            } else {
                $settings->content = '0';
                Yii::$app->session->setFlash('filter_off', 'Settings off in the shop');
            }
            $settings->save();
        }

        return $this->redirect(Yii::$app->request->referrer);
    }



    function log($type)
    {
        $userId = Yii::$app->user->identity ? Yii::$app->user->identity->id : 'guest';
        $get = @file_get_contents('log.txt') ?: '';
        @file_put_contents('log.txt', $get . "\n" . date("Y-m-d H:i:s") . ' - ' . $type . ' - ' . $userId);
    }

    /**
     * Auto-check product barcode against ASL Belgisi registry (silent, non-blocking).
     */
    private function checkAslBelgisiForProduct($product)
    {
        if (empty($product->barcode)) {
            return;
        }

        try {
            $service = new AslBelgisiService();
            if (!$service->isConfigured()) {
                return;
            }

            $result = $service->searchByGtin($product->barcode);
            if (!$result['success'] || empty($result['products'])) {
                return;
            }

            // Upsert one ASL entry per GTIN — products auto-match via barcode
            $parsed = $service->parseProduct($result['products'][0]);
            ProductAslBelgisi::upsertByGtin($product->barcode, $parsed);
        } catch (\Throwable $e) {
            Yii::error('ASL Belgisi auto-check failed: ' . $e->getMessage(), __METHOD__);
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
}
