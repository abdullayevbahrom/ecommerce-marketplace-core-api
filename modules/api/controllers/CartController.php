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
            ->with(['product', 'product.image', 'cartFilter', 'cartFilter.productFilter'])
            ->where(['user_id'=>$user->getId()])
            ->all();

        $groups = [];

        foreach ($cartItems as $item) {
            if (!$item->product) continue;

            $key = !empty($item->product->token_key) ? 'token_' . $item->product->token_key : 'prod_' . $item->product->id;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'amount' => 0,
                    'delivery' => $item->delivery,
                    'amount_left' => 0,
                    'price' => 0,
                    'unit_price' => 0,
                    'delivery_cost' => 0,
                    'total_with_delivery' => 0,
                    'products' => [], // Array of cart items (variants)
                    'productFilter' => [],
                ];
            }

            $groups[$key]['amount'] += $item->amount;
            $groups[$key]['price'] += $item->price;
            $groups[$key]['delivery_cost'] += $item->delivery_cost;
            $groups[$key]['amount_left'] += ($item->product->amount - $item->amount);
            $groups[$key]['products'][] = $item; // Add the individual cart item to the list

            $filters = $item->getProductFilter();
            if (!empty($filters)) {
                 foreach ($filters as $f) {
                     $groups[$key]['productFilter'][] = $f;
                 }
            }
        }

        foreach ($groups as &$group) {
             if ($group['amount'] > 0) {
                 $group['unit_price'] = $group['price'] / $group['amount'];
             }
             $group['total_with_delivery'] = $group['price'] + $group['delivery_cost'];
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
            if (!$item->product) continue;
            
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
    
    public function actionAdd() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();
    
        if (!array_key_exists('product_id', $post)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Product ID is required', ['product_id' => 'Product ID is required']);
        }
    
        // Get the amount to add (default to 1 if not specified)
        $amountToAdd = isset($post['amount']) ? (int)$post['amount'] : 1;
        
        if ($amountToAdd < 1) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Amount must be at least 1', ['amount' => 'Amount must be at least 1']);
        }
    
        $product = Product::find()->with(['stock', 'shop.stock'])->where(['id' => $post['product_id']])->one();
        if (!$product) {
            return $this->sendError(ErrorCodes::ERROR_PRODUCT_NOT_FOUND, 'Product not found', ['product_id' => 'Product not found']);
        }
    
        // Check if user has BTS location set
        if (empty($user->bts_city_id)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Please set your location in profile to calculate delivery cost', ['user' => 'Please set your location in profile to calculate delivery cost']);
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

        $product = Product::find()->with(['stock', 'shop.stock'])->where(['id' => $post['product_id']])->one();
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
        $user = Yii::$app->user->identity;
        
        // Get custom BTS city ID from request parameter or use user's default
        $customBtsCityId = Yii::$app->request->get('bts_city_id') ?: Yii::$app->request->post('bts_city_id');
        $receiverCityId = $customBtsCityId ?: $user->bts_city_id;

        // Check if we have a valid BTS city ID (either custom or from user profile)
        if (empty($receiverCityId)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['bts_city_id' => 'Please provide bts_city_id parameter or set your location in profile to calculate delivery cost']];
        }

        // Get all cart items with related data
        $cartItems = UserCart::find()
            ->with(['product', 'product.stock', 'product.shop.stock'])
            ->where(['user_id' => $user->id])
            ->all();

        if (empty($cartItems)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['cart' => 'Your cart is empty']];
        }

        // Group cart items by stock_id (same logic as order creation)
        $stockGroups = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            
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
            $calculatorData = [
                'senderCityId' => (int)$stock->bts_city_id,
                'receiverCityId' => (int)$receiverCityId,
                'weight' => max(1.0, $totalWeight), // Minimum 1kg
                'senderDelivery' => 1, // Default: courier pickup from city
                'receiverDelivery' => 1, // Default: courier delivery to city
            ];

            // Add volume if calculated
            if ($totalVolume > 0) {
                $calculatorData['volume'] = $totalVolume;
            }

            // Calculate delivery cost using BTS service
            try {
                $bts = new \yii\services\BTS();
                $response = $bts->calculateDelivery($calculatorData);

                if ($response && isset($response['success']) && $response['success'] && isset($response['data']['summaryPrice'])) {
                    $deliveryCost = (float)$response['data']['summaryPrice'];
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
                                'volume' => $totalVolume,
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
                    'bts_region_id' => $user->bts_region_id,
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