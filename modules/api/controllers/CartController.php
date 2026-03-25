<?php
namespace app\modules\api\controllers;


use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

use app\models\Category;
use app\models\user\cart\UserCart;
use app\models\product\Product;
use app\modules\api\components\ErrorCodes;
use app\modules\api\components\ApiResponseTrait;

class CartController extends Controller {
    use ApiResponseTrait;
    public $user;

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
            'optional' => ['']
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

        $cartItems = UserCart::find()
            ->with([
                'product', 
                'product.image', 
                'product.color',
                'product.productProductTypes',
                'product.productProductTypes.productType',
                'product.productProductTypes.productTypeValue',
                'cartFilter', 
                'cartFilter.productFilter'
            ])
            ->where(['user_id'=>$user->getId()])
            ->all();

        $groups = [];

        foreach ($cartItems as $item) {
            if (!$item->product || !$item->product->isAvailableForMarketplace()) continue;

            // Group by token_key if available, otherwise by product ID
            $key = !empty($item->product->token_key) ? 'token_' . $item->product->token_key : 'prod_' . $item->product->id;

            if (!isset($groups[$key])) {
                // Initialize group with common product data
                $groups[$key] = [
                    'group_id' => $key,
                    'product_id' => $item->product->id, // Main product ID (first one found)
                    'name_ru' => $item->product->name_ru,
                    'name_uz' => $item->product->name_uz,
                    'name_en' => $item->product->name_en,
                    'image' => $item->product->getPhoto(), // Main product image
                    'total_amount' => 0,
                    'total_price' => 0,
                    'total_delivery_cost' => 0,
                    'total_with_delivery' => 0,
                    'items' => [], // List of specific variants in cart
                ];
            }

            // Calculate totals
            $groups[$key]['total_amount'] += $item->amount;
            $groups[$key]['total_price'] += $item->price;
            $groups[$key]['total_delivery_cost'] += $item->delivery_cost;
            $groups[$key]['total_with_delivery'] += ($item->price + $item->delivery_cost);

            // Format individual item (variant)
            $variantData = [
                'id' => $item->id,
                'product_id' => $item->product->id,
                'name_ru' => $item->product->name_ru,
                'name_uz' => $item->product->name_uz,
                'name_en' => $item->product->name_en,
                'image' => $item->product->getPhoto(),
                'amount' => $item->amount,
                'price' => $item->price,
                'unit_price' => $item->amount > 0 ? $item->price / $item->amount : 0,
                'delivery_cost' => $item->delivery_cost,
                'stock_amount' => $item->product->amount,
                // Variant specific details
                'color' => $item->product->color ? [
                    'id' => $item->product->color->id,
                    'name' => $item->product->color->name_ru,
                    'code' => $item->product->color->color
                ] : null,
                'product_types' => [], // To be filled below
                'filters' => $item->getProductFilter(),
            ];

            // Add product types info (e.g., Size: XL)
            if ($item->product->productProductTypes) {
                foreach ($item->product->productProductTypes as $ppt) {
                    if ($ppt->productType && ($ppt->productTypeValue || $ppt->custom_value)) {
                        $variantData['product_types'][] = [
                            'type_id' => $ppt->productType->id,
                            'type_name' => $ppt->productType->name_ru,
                            'value_id' => $ppt->productTypeValue ? $ppt->productTypeValue->id : null,
                            'value' => $ppt->productTypeValue ? $ppt->productTypeValue->value_ru : $ppt->custom_value
                        ];
                    }
                }
            }

            $groups[$key]['items'][] = $variantData;
        }

        return array_values($groups);
    }

    /**
     * Get cart items grouped by product variants (token_key)
     * GET /api/cart/group
     */
    public function actionGroup() {
        $user = Yii::$app->user->identity;
        
        $cartItems = UserCart::find()
            ->with(['product', 'product.image', 'cartFilter', 'cartFilter.productFilter'])
            ->where(['user_id' => $user->getId()])
            ->all();
            
        $groups = [];
        
        foreach ($cartItems as $item) {
            if (!$item->product || !$item->product->isAvailableForMarketplace()) continue;
            
            // Group by token_key if available, otherwise by product ID
            // Using a prefix to ensure keys are strings and distinct
            $key = !empty($item->product->token_key) ? 'token_' . $item->product->token_key : 'prod_' . $item->product->id;
            
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_id' => $key,
                    'is_variant_group' => !empty($item->product->token_key),
                    'common_data' => [
                        'id' => $item->product->id, // Use ID of the first product found in group
                        'name_ru' => $item->product->name_ru,
                        'name_uz' => $item->product->name_uz,
                        'name_en' => $item->product->name_en,
                        'image' => $item->product->getPhoto(),
                    ],
                    'items' => []
                ];
            }
            
            $groups[$key]['items'][] = $item;
        }
        
        return ['data' => array_values($groups)];
    }
    
    /**
     * Add product(s) to cart
     * Supports both single product and batch adding
     * 
     * Single product:
     * POST { "product_id": 123, "amount": 2 }
     * 
     * Batch adding:
     * POST { "products": [{"product_id": 123, "amount": 2}, {"product_id": 456, "amount": 1}] }
     */
    public function actionAdd() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();
    
        // Check if user has BTS location set
        if (empty($user->bts_city_id)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Please set your location in profile to calculate delivery cost', ['user' => 'Please set your location in profile to calculate delivery cost']);
        }
        
        // Check if batch adding (products array provided)
        if (isset($post['products']) && is_array($post['products'])) {
            return $this->addMultipleProducts($user, $post['products']);
        }
        
        // Single product add (backward compatible)
        if (!array_key_exists('product_id', $post)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Product ID is required', ['product_id' => 'Product ID is required']);
        }
    
        return $this->addSingleProduct($user, $post);
    }
    
    /**
     * Add a single product to cart
     * @param \app\models\user\User $user
     * @param array $post
     * @return array
     */
    protected function addSingleProduct($user, $post) {
        // Get the amount to add (default to 1 if not specified)
        $amountToAdd = isset($post['amount']) ? (int)$post['amount'] : 1;
        
        if ($amountToAdd < 1) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Amount must be at least 1', ['amount' => 'Amount must be at least 1']);
        }
    
        $product = Product::find()->with(['stock', 'shop.stock'])->where(['product.id' => $post['product_id']])->marketplaceVisible()->one();
        if (!$product) {
            return $this->sendError(ErrorCodes::ERROR_PRODUCT_NOT_FOUND, 'Product not found', ['product_id' => 'Product not found']);
        }
    
        // Find existing cart item
        $existingCart = UserCart::findOne(['user_id'=>$user->id, 'product_id'=>$post['product_id']]);
        
        if ($existingCart) {
            // Replace the amount instead of adding
            $newAmount = $amountToAdd;
            
            // Check stock availability
            if ($product->amount < $newAmount) {
                return $this->sendError(ErrorCodes::ERROR_NOT_ENOUGH_STOCK, 'Not enough stock available. Available: ' . $product->amount, ['amount' => 'Not enough stock available. Available: ' . $product->amount]);
            }
            
            // Check minimum order quantity
            if ($product->min_order && $newAmount < $product->min_order) {
                return $this->sendError(ErrorCodes::ERROR_MIN_ORDER_QUANTITY, 'Minimum order quantity for this product is ' . $product->min_order, ['amount' => 'Minimum order quantity for this product is ' . $product->min_order]);
            }
            
            $existingCart->amount = $newAmount;
            
            // Calculate price based on quantity and wholesale tiers
            $unit_price = $product->getPriceByQuantity($existingCart->amount);
            $existingCart->price = $existingCart->amount * $unit_price;
            
            // Recalculate BTS delivery cost based on new amount
            if ($user->bts_city_id) {
                $existingCart->delivery_cost = $existingCart->calculateBtsDeliveryCost($product, $user, $existingCart->amount);
            }
            
            $existingCart->save(false);
            $result = $existingCart;
        } else {
            // Create new cart item
            $model = new UserCart();
            $model->user_id = $user->id;
            $model->product_id = $post['product_id'];
            $model->amount = $amountToAdd;
            
            // Check stock availability
            if ($product->amount < $model->amount) {
                 return $this->sendError(ErrorCodes::ERROR_NOT_ENOUGH_STOCK, 'Not enough stock available. Available: ' . $product->amount, ['amount' => 'Not enough stock available. Available: ' . $product->amount]);
            }
            
            // Check minimum order quantity for new items
            if ($product->min_order && $model->amount < $product->min_order) {
                 return $this->sendError(ErrorCodes::ERROR_MIN_ORDER_QUANTITY, 'Minimum order quantity for this product is ' . $product->min_order, ['amount' => 'Minimum order quantity for this product is ' . $product->min_order]);
            }
            
            // Calculate price based on quantity and wholesale tiers
            $unit_price = $product->getPriceByQuantity($model->amount);
            $model->price = $model->amount * $unit_price;
            
            // Set delivery_id if provided
            if (isset($post['delivery_id'])) {
                $model->delivery_id = $post['delivery_id'];
            }
            
            // Calculate BTS delivery cost
            if ($user->bts_city_id) {
                $model->delivery_cost = $model->calculateBtsDeliveryCost($product, $user, $model->amount);
            }
            
            if (!$model->save(false)) {
                return $this->sendError(ErrorCodes::ERROR_SERVER, 'Failed to save cart item', ['save' => 'Failed to save cart item']);
            }
            
            // Handle filter values if provided
            if (isset($post['filter_value_id']) && is_array($post['filter_value_id'])) {
                $keys = ['user_cart_id', 'product_filter_id'];
                $vals = [];
                foreach ($post['filter_value_id'] as $value) {
                    $vals[] = [
                        'user_cart_id' => $model->id,
                        'product_filter_id' => $value
                    ];
                }
                if (!empty($vals)) {
                    Yii::$app->db->createCommand()->batchInsert('user_cart_filter', $keys, $vals)->execute();
                }
            }
            
            $result = $model;
        }
    
        $cart = UserCart::find()->with('product', 'product.image')->where(['id'=>$result->id, 'user_id'=>$user->id])->one();
    
        return $this->sendSuccess($cart);
    }
    
    /**
     * Add multiple products to cart (batch operation)
     * @param \app\models\user\User $user
     * @param array $products Array of products [{"product_id": 1, "amount": 2}, ...]
     * @return array
     */
    protected function addMultipleProducts($user, $products) {
        if (empty($products)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Products array cannot be empty', ['products' => 'Products array cannot be empty']);
        }
        
        $results = [
            'success' => [],
            'errors' => [],
            'summary' => [
                'total_requested' => count($products),
                'successful' => 0,
                'failed' => 0
            ]
        ];
        
        // Collect all product IDs for batch loading
        $productIds = array_filter(array_column($products, 'product_id'));
        
        if (empty($productIds)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'No valid product IDs provided', ['products' => 'No valid product IDs provided']);
        }
        
        // Batch load products for performance
        $productModels = Product::find()
            ->with(['stock', 'shop.stock'])
            ->where(['product.id' => $productIds])
            ->marketplaceVisible()
            ->indexBy('id')
            ->all();
        
        // Batch load existing cart items for performance
        $existingCarts = UserCart::find()
            ->where(['user_id' => $user->id, 'product_id' => $productIds])
            ->indexBy('product_id')
            ->all();
        
        // Process each product
        foreach ($products as $index => $item) {
            $productId = isset($item['product_id']) ? (int)$item['product_id'] : null;
            $amount = isset($item['amount']) ? (int)$item['amount'] : 1;
            $filterValues = isset($item['filter_value_id']) ? $item['filter_value_id'] : [];
            
            // Validation
            if (!$productId) {
                $results['errors'][] = [
                    'index' => $index,
                    'product_id' => null,
                    'error' => 'Product ID is required'
                ];
                $results['summary']['failed']++;
                continue;
            }
            
            if ($amount < 1) {
                $results['errors'][] = [
                    'index' => $index,
                    'product_id' => $productId,
                    'error' => 'Amount must be at least 1'
                ];
                $results['summary']['failed']++;
                continue;
            }
            
            // Check if product exists
            if (!isset($productModels[$productId])) {
                $results['errors'][] = [
                    'index' => $index,
                    'product_id' => $productId,
                    'error' => 'Product not found'
                ];
                $results['summary']['failed']++;
                continue;
            }
            
            $product = $productModels[$productId];
            
            // Check stock availability
            if ($product->amount < $amount) {
                $results['errors'][] = [
                    'index' => $index,
                    'product_id' => $productId,
                    'error' => 'Not enough stock available. Available: ' . $product->amount
                ];
                $results['summary']['failed']++;
                continue;
            }
            
            // Check minimum order quantity
            if ($product->min_order && $amount < $product->min_order) {
                $results['errors'][] = [
                    'index' => $index,
                    'product_id' => $productId,
                    'error' => 'Minimum order quantity is ' . $product->min_order
                ];
                $results['summary']['failed']++;
                continue;
            }
            
            // Calculate price based on quantity
            $unit_price = $product->getPriceByQuantity($amount);
            $totalPrice = $amount * $unit_price;
            
            // Check if cart item exists
            if (isset($existingCarts[$productId])) {
                // Update existing cart item
                $cartItem = $existingCarts[$productId];
                $cartItem->amount = $amount;
                $cartItem->price = $totalPrice;
                
                // Recalculate BTS delivery cost
                if ($user->bts_city_id) {
                    $cartItem->delivery_cost = $cartItem->calculateBtsDeliveryCost($product, $user, $amount);
                }
                
                $cartItem->save(false);
            } else {
                // Create new cart item
                $cartItem = new UserCart();
                $cartItem->user_id = $user->id;
                $cartItem->product_id = $productId;
                $cartItem->amount = $amount;
                $cartItem->price = $totalPrice;
                
                // Calculate BTS delivery cost
                if ($user->bts_city_id) {
                    $cartItem->delivery_cost = $cartItem->calculateBtsDeliveryCost($product, $user, $amount);
                }
                
                if (!$cartItem->save(false)) {
                    $results['errors'][] = [
                        'index' => $index,
                        'product_id' => $productId,
                        'error' => 'Failed to save cart item'
                    ];
                    $results['summary']['failed']++;
                    continue;
                }
                
                // Handle filter values if provided
                if (!empty($filterValues) && is_array($filterValues)) {
                    $keys = ['user_cart_id', 'product_filter_id'];
                    $vals = [];
                    foreach ($filterValues as $value) {
                        $vals[] = [
                            'user_cart_id' => $cartItem->id,
                            'product_filter_id' => $value
                        ];
                    }
                    if (!empty($vals)) {
                        Yii::$app->db->createCommand()->batchInsert('user_cart_filter', $keys, $vals)->execute();
                    }
                }
                
                // Add to existing carts for future iterations (in case of duplicates)
                $existingCarts[$productId] = $cartItem;
            }
            
            $results['success'][] = [
                'index' => $index,
                'product_id' => $productId,
                'cart_id' => $cartItem->id,
                'amount' => $amount,
                'unit_price' => $unit_price,
                'total_price' => $totalPrice,
                'product_name' => $product->name_ru ?: $product->name_en ?: $product->name_uz
            ];
            $results['summary']['successful']++;
        }
        
        // Load all cart items with products for response
        $cartIds = array_column($results['success'], 'cart_id');
        $cartItems = [];
        if (!empty($cartIds)) {
            $cartItems = UserCart::find()
                ->with(['product', 'product.image'])
                ->where(['id' => $cartIds, 'user_id' => $user->id])
                ->all();
        }
        
        return [
            'data' => [
                'items' => $cartItems,
                'results' => $results
            ]
        ];
    }

    public function actionMinus() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!array_key_exists('product_id', $post)) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['product_id'=>'Product ID is required']];
        }

        // Get the amount to subtract (default to 1 if not specified)
        $amountToSubtract = isset($post['amount']) ? (int)$post['amount'] : 1;
        
        if ($amountToSubtract < 1) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['amount'=>'Amount must be at least 1']];
        }

        $product = Product::find()->with(['stock', 'shop.stock'])->where(['product.id' => $post['product_id']])->marketplaceVisible()->one();
        if (!$product) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['product_id'=>'Product not found']];
        }
        
        $model = UserCart::findOne(['user_id'=>$user->id, 'product_id'=>$post['product_id']]);
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['product_id'=>'Product not found in cart']];
        }

        $newAmount = $model->amount - $amountToSubtract;
        
        // If new amount would be 0 or less, remove the item from cart
        if ($newAmount <= 0) {
            $model->delete();
        } else {
            // Check minimum order quantity - cannot go below minimum
            if ($product->min_order && $newAmount < $product->min_order) {
                Yii::$app->response->statusCode = 422;
                return ['errors'=>['amount'=>'Cannot reduce quantity below minimum order of ' . $product->min_order . '. Current quantity: ' . $model->amount]];
            }
            
            $model->amount = $newAmount;
            
            // Calculate price based on quantity and wholesale tiers
            $unit_price = $product->getPriceByQuantity($model->amount);
            $model->price = $model->amount * $unit_price;
            
            // Recalculate BTS delivery cost based on new amount
            if ($user->bts_city_id) {
                $model->delivery_cost = $model->calculateBtsDeliveryCost($product, $user, $model->amount);
            }

            $model->save(false);
        }

        $query = UserCart::find()->with('product')->where(['user_id'=>$user->id]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    public function actionRemove() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $model = new UserCart;
        $model->setAttributes($post);

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        $product = UserCart::findOne(['user_id'=>$user->id, 'product_id'=>$post['product_id']]);
        if (!$product) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['product_id'=>'Продукт не найден в корзине']];
        }

        $product->delete();
        $cart = UserCart::find()->with('product', 'product.image')->where(['user_id'=>$user->id])->all();

        return ['data'=>$cart];
    }

    public function actionRemoves() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $productIds = isset($post['product_ids']) ? $post['product_ids'] : null;
        if (!is_array($productIds) || empty($productIds)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['product_ids' => 'product_ids must be a non-empty array']];
        }

        // Sanitize: ensure all IDs are integers
        $productIds = array_map('intval', $productIds);

        $deleted = UserCart::deleteAll([
            'user_id' => $user->id,
            'product_id' => $productIds,
        ]);

        $cart = UserCart::find()
            ->with('product', 'product.image')
            ->where(['user_id' => $user->id])
            ->all();

        return [
            'data' => $cart,
            'deleted_count' => $deleted,
        ];
    }

    public function actionClear() {
        $user = Yii::$app->user->identity;

        UserCart::deleteAll(['user_id'=>$user->id]);

        return ['date'=>[]];
    }

    /**
     * Calculate delivery costs for all products in cart
     * Groups products by stock and calculates BTS delivery for each group
     * Similar to order calculation logic but for cart preview
     * 
     * @return array
     */
    public function actionCalculate() {
        $user = Yii::$app->user->identity ?? null;
        
        // Get custom BTS city ID from request parameter or use user's default
        $customBtsCityId = Yii::$app->request->get('bts_city_id') ?: Yii::$app->request->post('bts_city_id');
        $receiverCityId = $customBtsCityId ?: $user?->bts_city_id;

        // Check if we have a valid BTS city ID (either custom or from user profile)
        if (empty($receiverCityId)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['bts_city_id' => 'Please provide bts_city_id parameter or set your location in profile to calculate delivery cost']];
        }

        if ($user === null) {
            Yii::$app->response->statusCode = 401;
            return ['errors' => ['user' => 'Unauthorized']];
        }

        // Get all cart items with related data
        $cartItems = UserCart::find()
            ->with(['product', 'product.stock', 'product.shop.stock'])
            ->where(['user_id' => $user?->id])
            ->all();

        if (empty($cartItems)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['cart' => 'Your cart is empty']];
        }

        // Group cart items by stock_id (same logic as order creation)
        $stockGroups = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product || !$product->isAvailableForMarketplace()) {
                continue;
            }
            
            // Get stock ID (priority: product's direct stock, then shop's stock)
            $stockId = null;
            if ($product->stock && $product->stock->bts_city_id) {
                $stockId = $product->stock_id;
            } elseif ($product->shop && $product->shop->stock && $product->shop->stock->bts_city_id) {
                $stockId = $product->shop->stock->id;
            }
            
            if (!$stockId) {
                continue; // Skip products without proper stock configuration
            }
            
            if (!isset($stockGroups[$stockId])) {
                $stockGroups[$stockId] = [
                    'stock' => $product->stock ?: $product->shop->stock,
                    'items' => []
                ];
            }
            $stockGroups[$stockId]['items'][] = $cartItem;
        }

        $calculations = [];
        $totalDeliveryCost = 0;
        $totalProductCost = 0;

        // Calculate delivery for each stock group
        foreach ($stockGroups as $stockId => $group) {
            $stock = $group['stock'];
            $items = $group['items'];
            
            // Skip if same city (no delivery needed)
            if ($stock->bts_city_id == $receiverCityId) {
                $calculations[] = [
                    'stock_id' => $stockId,
                    'stock_name' => $stock->name_ru,
                    'sender_city_id' => $stock->bts_city_id,
                    'receiver_city_id' => $receiverCityId,
                    'delivery_cost' => 0,
                    'reason' => 'Same city - no delivery needed',
                    'products' => $this->formatCartItemsForCalculation($items),
                    'totals' => $this->calculateGroupTotals($items)
                ];
                continue;
            }

            // Calculate total weight and volume for this group
            $totalWeight = 0;
            $totalVolume = 0;
            $groupProductCost = 0;

            foreach ($items as $cartItem) {
                $product = $cartItem->product;
                if (!$product) continue;
                
                // Calculate weight (fallback to 1kg if not set)
                $unitWeight = (float)($product->weight ?: 1.0);
                $totalWeight += $unitWeight * $cartItem->amount;
                
                // Calculate volume (convert mm to cubic meters)
                if ($product->length && $product->width && $product->height) {
                    $unitVolume = ($product->length * $product->width * $product->height) / 1000000000; // mm³ to m³
                    $totalVolume += $unitVolume * $cartItem->amount;
                }
                
                // Use quantity-based pricing for accurate cost calculation
                $unitPrice = $product->getPriceByQuantity($cartItem->amount);
                $groupProductCost += $unitPrice * $cartItem->amount;
            }

            // Prepare BTS calculation data
            // Calculate equivalent cubic dimensions for total volume
            // We approximate a cube that would hold the total volume
            $side = 10; // Default 10cm
            if ($totalVolume > 0) {
                // totalVolume is in m3 (from loop below), convert to cm3 for API if needed?
                // Wait, let's check the loop calculation first.
            }
            
            // Re-calculating loop to ensure correct volume units
            $totalWeight = 0;
            $totalVolumeCm3 = 0;
            $groupProductCost = 0;
            
            // For smart stacking: track max base dimensions and sum of heights
            $maxLength = 10;
            $maxWidth = 10;
            $totalStackedHeight = 0;

            foreach ($items as $cartItem) {
                $product = $cartItem->product;
                if (!$product) continue;
                
                // Calculate weight (fallback to 1kg if not set)
                $unitWeight = (float)($product->weight ?: 1.0);
                $totalWeight += $unitWeight * $cartItem->amount;
                
                // Calculate volume
                // Dimensions in DB are in cm (based on view labels)
                $l = $product->length ?: 10;
                $w = $product->width ?: 10;
                $h = $product->height ?: 10;
                
                $unitVolume = $l * $w * $h; // cm3
                $totalVolumeCm3 += $unitVolume * $cartItem->amount;
                
                // Smart stacking: use max base dimensions, sum heights
                $maxLength = max($maxLength, $l);
                $maxWidth = max($maxWidth, $w);
                $totalStackedHeight += $h * $cartItem->amount;
                
                // Use quantity-based pricing for accurate cost calculation
                $unitPrice = $product->getPriceByQuantity($cartItem->amount);
                $groupProductCost += $unitPrice * $cartItem->amount;
            }
            
            // Smart stacking dimensions (avoids cube inflation)
            $volumeX = max(10, (int)$maxLength);
            $volumeY = max(10, (int)$maxWidth);
            $volumeZ = max(10, (int)$totalStackedHeight);

            $calculatorData = [
                'senderCityCode' => (string)$stock->bts_city_id,
                'receiverCityCode' => (string)$receiverCityId,
                'pickup_type' => 'branch', // Warehouse drops off or is a branch
                'dropoff_type' => 'courier', // Deliver to user door
                'is_multiple_cost' => 0,
                'weight' => max(1.0, $totalWeight), // Minimum 1kg
                'volume' => [
                    'x' => $volumeX,
                    'y' => $volumeY,
                    'z' => $volumeZ
                ]
            ];

            // Calculate delivery cost using BTS service
            try {
                $bts = new \yii\services\BTS();
                $response = $bts->calculateOrder($calculatorData);

                if ($response && isset($response['success']) && $response['success'] && isset($response['data'])) {
                    // Extract price based on pickup_type and dropoff_type (branch_to_courier by default)
                    $priceKey = 'branch_to_courier';
                    $deliveryCost = 0;
                    
                    if (isset($response['data'][$priceKey]['price'])) {
                        $deliveryCost = (float)$response['data'][$priceKey]['price'];
                    } elseif (isset($response['data']['price'])) {
                        // Fallback for single price response
                        $deliveryCost = (float)$response['data']['price'];
                    } else {
                        // Try to get any available price
                        foreach (['branch_to_branch', 'branch_to_courier', 'courier_to_branch', 'courier_to_courier'] as $key) {
                            if (isset($response['data'][$key]['available']) && $response['data'][$key]['available'] && isset($response['data'][$key]['price'])) {
                                $deliveryCost = (float)$response['data'][$key]['price'];
                                break;
                            }
                        }
                    }
                    $totalDeliveryCost += $deliveryCost;
                    
                    $calculations[] = [
                        'stock_id' => $stockId,
                        'stock_name' => $stock->name_ru,
                        'sender_city_id' => $stock->bts_city_id,
                        'receiver_city_id' => $receiverCityId,
                        'delivery_cost' => $deliveryCost,
                        'calculation_data' => $calculatorData,
                        'bts_response' => $response['data'],
                        'products' => $this->formatCartItemsForCalculation($items),
                        'totals' => array_merge(
                            $this->calculateGroupTotals($items),
                            [
                                'weight' => $totalWeight,
                                'volume_cm3' => $totalVolumeCm3,
                                'packed_dimensions' => [
                                    'x' => $volumeX,
                                    'y' => $volumeY,
                                    'z' => $volumeZ
                                ],
                                'product_cost' => $groupProductCost
                            ]
                        )
                    ];
                } else {
                    $calculations[] = [
                        'stock_id' => $stockId,
                        'stock_name' => $stock->name_ru,
                        'sender_city_id' => $stock->bts_city_id,
                        'receiver_city_id' => $receiverCityId,
                        'delivery_cost' => 0,
                        'error' => 'BTS calculation failed',
                        'bts_response' => $response,
                        'products' => $this->formatCartItemsForCalculation($items),
                        'totals' => $this->calculateGroupTotals($items)
                    ];
                }
            } catch (\Exception $e) {
                $calculations[] = [
                    'stock_id' => $stockId,
                    'stock_name' => $stock->name_ru,
                    'sender_city_id' => $stock->bts_city_id,
                    'receiver_city_id' => $receiverCityId,
                    'delivery_cost' => 0,
                    'error' => 'Calculation error: ' . $e->getMessage(),
                    'products' => $this->formatCartItemsForCalculation($items),
                    'totals' => $this->calculateGroupTotals($items)
                ];
            }
        }

        // Calculate total product cost using quantity-based pricing
        foreach ($cartItems as $cartItem) {
            if ($cartItem->product) {
                $unitPrice = $cartItem->product->getPriceByQuantity($cartItem->amount);
                $totalProductCost += $unitPrice * $cartItem->amount;
            }
        }

        return [
            'data' => [
                'calculations' => $calculations,
                'summary' => [
                    'total_product_cost' => $totalProductCost,
                    'total_delivery_cost' => $totalDeliveryCost,
                    'grand_total' => $totalProductCost + $totalDeliveryCost,
                    'currency' => 'UZS',
                    'stock_groups_count' => count($stockGroups),
                    'total_items' => count($cartItems)
                ],
                'user_info' => [
                    'bts_city_id' => $receiverCityId,
                    'bts_region_id' => $user?->bts_region_id,
                    'is_custom_city' => !empty($customBtsCityId)
                ]
            ]
        ];
    }

    /**
     * Format cart items for calculation response
     * @param array $items
     * @return array
     */
    private function formatCartItemsForCalculation($items) {
        $products = [];
        foreach ($items as $cartItem) {
            $product = $cartItem->product;
            if (!$product) continue;
            
            $unitPrice = $product->getPriceByQuantity($cartItem->amount);
            $products[] = [
                'id' => $product->id,
                'name' => $product->name_ru,
                'amount' => $cartItem->amount,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $cartItem->amount,
                'weight' => $product->weight ?: 1.0,
                'dimensions' => [
                    'length' => $product->length,
                    'width' => $product->width,
                    'height' => $product->height
                ]
            ];
        }
        return $products;
    }

    /**
     * Calculate totals for a group of cart items
     * @param array $items
     * @return array
     */
    private function calculateGroupTotals($items) {
        $totalAmount = 0;
        $totalPrice = 0;
        
        foreach ($items as $cartItem) {
            $totalAmount += $cartItem->amount;
            
            // Use quantity-based pricing for accurate total calculation
            if ($cartItem->product) {
                $unitPrice = $cartItem->product->getPriceByQuantity($cartItem->amount);
                $totalPrice += $unitPrice * $cartItem->amount;
            }
        }
        
        return [
            'total_amount' => $totalAmount,
            'total_price' => $totalPrice,
            'items_count' => count($items)
        ];
    }
}
?>
