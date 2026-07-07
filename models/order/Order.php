<?php

namespace app\models\order;

use app\components\Bts\BtsCatalog;
use app\components\Bts\BtsComponent;
use Yii;
use app\models\didox\DidoxDocument;
use app\models\user\User;
use app\models\user\cart\UserCart;
use app\models\user\cart\UserCartFilter;
use app\models\delivery\Delivery;
use app\models\order\product\OrderProduct;
use app\models\order\product\OrderProductFilter;
use app\models\Category;
use app\models\brand\CategoryBrand;
use app\models\product\Product;
use app\models\logist\Logist;
use app\models\shop\Shop;
use app\models\stock\Stock;
use app\services\DidoxOrderService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Intervention\Image\ImageManager;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $payment_id
 * @property int|null $delivery_id
 * @property int|null $bts_region_id
 * @property int|null $bts_city_id
 * @property float|null $price
 * @property float|null $amount
 * @property float|null $delivery_cost
 * @property int $receiver
 * @property string|null $name
 * @property string|null $lastname
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $comment
 * @property int $status
 * @property int $status_payment
 * @property string $date
 * @property string|null $inn
 * @property string|null $account
 * @property string|null $bank_id
 *
 * @property Delivery $delivery
 * @property OrderProduct[] $orderProducts
 * @property Category $payment
 * @property User $user
 */
class Order extends \yii\db\ActiveRecord
{
    public $order_id;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['address', 'delivery_id'], 'required', 'message' => 'Заполните поле'],
            [['user_id', 'payment_id', 'delivery_id', 'shop_id', 'logist_id', 'tariff_id', 'receiver', 'status', 'status_payment', 'status_logist', 'status_delivery', 'status_review', 'promocode_id'], 'integer'],
            [['price', 'amount', 'delivery_cost', 'discount_amount'], 'number'],
            [['bts_region_id', 'bts_city_id'], 'string', 'max' => 10],
            [['phone', 'address', 'comment', 'inn', 'account', 'bank_id'], 'string'],
            [['date'], 'safe'],
            [['name', 'lastname', 'email'], 'string', 'max' => 255],
            [['delivery_id'], 'exist', 'skipOnError' => true, 'targetClass' => Delivery::class, 'targetAttribute' => ['delivery_id' => 'id']],
            [['payment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['payment_id' => 'id']],
            [['payment_id'], 'validatePaymentCategory'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'payment_id' => 'Payment ID',
            'delivery_id' => 'Delivery ID',
            'price' => 'Price',
            'amount' => 'Amount',
            'delivery_cost' => 'Delivery Cost',
            'receiver' => 'Receiver',
            'name' => 'Name',
            'lastname' => 'Lastname',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'comment' => 'Comment',
            'status' => 'Status',
            'status_payment' => 'Status Payment',
            'date' => 'Date',
            'inn' => 'INN',
            'account' => 'Account',
            'bank_id' => 'Bank ID (MFO)',
            'promocode_id' => 'Promocode',
            'discount_amount' => 'Discount Amount',
        ];
    }

    public function validatePaymentCategory($attribute, $params)
    {
        if (empty($this->$attribute)) {
            return;
        }

        $payment = Category::findOne($this->$attribute);
        if (!$payment || $payment->type !== 'payment') {
            $this->addError($attribute, 'Invalid payment method selected.');
        }
    }

    /**
     * Gets query for [[Promocode]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPromocode()
    {
        return $this->hasOne(\app\models\Promocode::class, ['id' => 'promocode_id']);
    }

    // Note: OrderReview relationship commented out as the model doesn't exist
    // Reviews are tracked via status_review field instead
    /*
    public function getOrderReview()
    {
        return $this->hasOne(OrderReview::class, ['order_id' => 'id']);
    }
    */

    public function saveObject($cart)
    {
        // Validate minimum order quantities before creating order
        foreach ($cart as $cartItem) {
            $product = $cartItem->product;
            if ($product && $product->min_order && ($cartItem->amount < $product->min_order)) {
                $this->addError('cart', 'Product "' . $product->name_ru . '" requires minimum order quantity of ' . $product->min_order);
                return false;
            }
        }

        /** @var User $user */
        $user = Yii::$app->user->identity;
        $this->user_id = $user->id;
        $this->status = 0;
        $walletPaymentId = Yii::$app->params['walletPaymentId'] ?? null;
        $this->status_payment = ($walletPaymentId && (int) $this->payment_id === (int) $walletPaymentId) ? 0 : 1;
        $this->status_delivery = 0;
        $this->status_review = 0;
        $this->bts_city_id = $user->bts_city_id;
        $this->bts_region_id = $user->bts_region_id;

        // Auto-fill Didox fields from User
        $this->inn = $user->inn ?? $user->eimzo_tax_id ?? null;
        $this->account = $user->account ?? null;
        $this->bank_id = $user->mfo ?? null;

        $shop_id = null;
        $delivery_id = null;

        if ($this->save()) {
            $price = 0;
            $amount = 0;
            $warehouseOrderItems = []; // Initialize the warehouse items array

            foreach ($cart as $product) {
                if (!$product->product) {
                    // Cart item references a deleted product — remove it and skip
                    $product->delete();
                    continue;
                }
                $order_product = new OrderProduct;
                $shop_id = $product->product->shop_id;

                $order_product->user_id = $this->user_id;
                $order_product->order_id = $this->id;
                $order_product->shop_id = $product->product->shop_id;
                $order_product->product_id = $product->product->id;
                $order_product->amount = $product->amount;
                $order_product->delivery_cost = $product->delivery_cost;
                $resolvedStockId = $product->product->stock_id;
                if (!$resolvedStockId && $product->product->shop && $product->product->shop->stock) {
                    $resolvedStockId = $product->product->shop->stock->id;
                }
                $order_product->stock_id = $resolvedStockId;

                // Calculate price based on quantity and wholesale tiers
                $unit_price = $product->product->getPriceByQuantity($product->amount);
                $order_product->product_price = $unit_price * $product->amount;
                $order_product->price = $order_product->product_price;

                // ToDo::change if add new delivery method
                $order_product->delivery_id = $product->delivery_id ?? 1;
                $order_product->status = 1;

                $delivery_id = $order_product->delivery_id;

                $price += $order_product->price;
                $amount += $order_product->amount;

                if ($order_product->save()) {

                    // Add item to warehouse order items array
                    $warehouseOrderItems[] = [
                        'yii_product_id' => $order_product->product_id,
                        'quantity' => $order_product->amount,
                        'price' => $unit_price, // Unit price, not total price
                    ];

                    $shop_product = Product::findOne($order_product->product_id);
                    if ($shop_product) {
                        $shop_product->amount = max(0, (float) $shop_product->amount - (float) $order_product->amount);
                        $shop_product->save(false);

                        if ($product->cartFilter) {
                            $keys = ['order_product_id', 'product_filter_id'];
                            $vals = [];
                            foreach ($product->cartFilter as $value) {
                                $vals[] = [
                                    'order_product_id' => $order_product->id,
                                    'product_filter_id' => $value->product_filter_id
                                ];
                            }

                            Yii::$app->db->createCommand()->batchInsert('order_product_filter', $keys, $vals)->execute();
                        }
                    }
                }
            }

            // Send order to warehouse with populated items array
            try {
                if (!empty($warehouseOrderItems)) {
                    $this->sendOrderToWarehouse($warehouseOrderItems);
                }
            } catch (\Exception $e) {
                $this->addError('warehouse', $e->getMessage());
                return false;
            }

            $user = User::findOne($this->user_id);
            // $user->last_address = $this->address;

            // // Auto-fill user profile for individual users (fiz) on first order when they receive it themselves
            // if ($user->type === 'fiz' && $this->receiver == 1) {
            //     if (empty($user->name) && !empty($this->name)) {
            //         $user->name = $this->name;
            //     }
            //     if (empty($user->lastname) && !empty($this->lastname)) {
            //         $user->lastname = $this->lastname;
            //     }
            //     if (empty($user->phone) && !empty($this->phone)) {
            //         $user->phone = $this->phone;
            //     }
            //     if (empty($user->email) && !empty($this->email)) {
            //         $user->email = $this->email;
            //     }
            //     if (empty($user->address) && !empty($this->address)) {
            //         $user->address = $this->address;
            //     }
            //     if(empty($user->bts_region_id) && !empty($this->bts_region_id)) {
            //         $user->bts_region_id = $this->bts_region_id;
            //     }
            //     if(empty($user->bts_city_id) && !empty($this->bts_city_id)) {
            //         $user->bts_city_id = $this->bts_city_id;
            //     }
            // }

            // $user->save(false);

            error_log("heelooo !!! 123 ->>");

            // ToDo::change if add new delivery method
            if ($delivery_id) {
                error_log("UserCart::deleteAll(234234");
                /** @var User $user */
                $user = Yii::$app->user->identity;
                UserCart::deleteAll(['user_id' => $user->id]);
                $order = self::findOne($this->id);
                $deliveryPrice = $this->createOrderProductsAndBtsIntegration($user, $order);

                // Calculate total product price
                $productsTotal = $price;

                // Apply Promocode discount (already validated in controller before saveObject)
                $discount = 0;
                if ($order->promocode_id) {
                    $promocode = \app\models\Promocode::findOne($order->promocode_id);
                    if ($promocode) {
                        $discount = $promocode->calculateDiscount($productsTotal);
                        $order->discount_amount = $discount;
                    }
                }

                $order->price = max(0, $productsTotal - $discount) + $deliveryPrice;
                $order->delivery_cost = $deliveryPrice;
                $order->amount = $amount;
                $order->shop_id = $shop_id;
                $order->delivery_id = $delivery_id;

                error_log("UserCart::deleteAll(234234");
                // Create order products based on stocks and calculate BTS delivery for each group

                // Save the final order state
                $saved = $order->save(false);

                // Automatically create Didox documents (Invoice and Contract)
                if ($saved) {
                    try {
                        DidoxOrderService::createDocuments($order);
                    } catch (\Exception $e) {
                        Yii::error("Didox auto-creation failed: " . $e->getMessage(), 'didox');
                    }
                }

                return $saved;
            }
        }

        return false;
    }

    /**
     * Create order products based on stock grouping and handle BTS integration
     * @param User $user
     * @return bool
     */
    public function createOrderProductsAndBtsIntegration($user, $orderInfo)
    {
        error_log("createOrderProductsAndBtsIntegration");

        // Get order products grouped by stock_id (cart has already been cleared)
        $orderProducts = OrderProduct::find()
            ->with(['product.stock', 'product.shop.stock'])
            ->where(['order_id' => $this->id])
            ->all();

        // Group order products by stock_id
        $stockGroups = [];
        foreach ($orderProducts as $orderProduct) {
            $resolvedStock = null;

            if ($orderProduct->stock_id) {
                $resolvedStock = Stock::findOne($orderProduct->stock_id);
            }

            if (!$resolvedStock && $orderProduct->product && $orderProduct->product->stock) {
                $resolvedStock = $orderProduct->product->stock;
            }

            if (!$resolvedStock && $orderProduct->product && $orderProduct->product->shop && $orderProduct->product->shop->stock) {
                $resolvedStock = $orderProduct->product->shop->stock;
            }

            $groupKey = $resolvedStock ? ('stock_' . $resolvedStock->id) : ('orphan_' . $orderProduct->id);
            if (!isset($stockGroups[$groupKey])) {
                $stockGroups[$groupKey] = [
                    'stock' => $resolvedStock,
                    'items' => [],
                ];
            }
            $stockGroups[$groupKey]['items'][] = $orderProduct;
        }

        $totalDeliveryCost = 0;

        // Process each stock group
        foreach ($stockGroups as $groupData) {
            $stock = $groupData['stock'];
            $groupItems = $groupData['items'];

            // Calculate total weight and volume for this group using existing order products
            $totalWeight = 0;
            $totalVolume = 0;
            $groupProducts = [];

            foreach ($groupItems as $orderProduct) {
                $product = $orderProduct->product;

                // Use existing order product (already saved)
                $groupProducts[] = $orderProduct;

                // Calculate weight and volume
                $totalWeight += ($product->weight ?? 1) * $orderProduct->amount;
                $totalVolume += (($product->length ?? 10) * ($product->width ?? 10) * ($product->height ?? 10)) * $orderProduct->amount;
            }

            error_log("errorlwe3141312");

            // Create BTS order for this stock group (if stock has BTS info)
            if ($stock && $stock->bts_city_id && !empty($groupProducts)) {
                error_log("!END??!@#!");
                $this->createBtsOrderForStockGroup($stock, $groupProducts, $user, $orderInfo, $totalWeight, $totalVolume);

                // Sum up delivery costs
                foreach ($groupProducts as $orderProduct) {
                    $totalDeliveryCost += $orderProduct->bts_price ?? 0;
                }
            } elseif (!empty($groupProducts)) {
                $errorMessage = 'BTS integration skipped: sender stock/BTS city not configured';
                foreach ($groupProducts as $orderProduct) {
                    $orderProduct->bts_status_info = $errorMessage;
                    $orderProduct->save(false);
                }
            }
        }

        return $totalDeliveryCost;
    }

    /**
     * Create BTS order for a specific stock group
     * @param Stock $stock
     * @param OrderProduct[] $orderProducts
     * @param User $user
     * @param float $totalWeight
     * @param float $totalVolume
     * @return bool
     */
    protected function createBtsOrderForStockGroup($stock, $orderProducts, $user, $orderInfo, $totalWeight, $totalVolume)
    {
        $shop = $stock->shop;

        $senderPhone = self::formatPhoneForBts($shop->contact_phone);
        $receiverPhone = self::formatPhoneForBts($orderInfo->phone ?: $user->phone);

        $fail = function (string $message, array $context = []) use ($orderProducts) {
            foreach ($orderProducts as $orderProduct) {
                $orderProduct->bts_id = null;
                $orderProduct->bts_status = null;
                $orderProduct->bts_status_info = $message;
                $orderProduct->bts_price = null;
                $orderProduct->save(false);
            }

            Yii::error($context + ['message' => $message], __METHOD__);

            return false;
        };

        if ($senderPhone === null) {
            return $fail('BTS integration failed: sender phone is invalid. Required format: +998XXXXXXXXX', [
                'shop_id' => $shop->id ?? null,
                'stock_id' => $stock->id ?? null,
                'raw_sender_phone' => $shop->contact_phone ?? null,
            ]);
        }

        if ($receiverPhone === null) {
            return $fail('BTS integration failed: receiver phone is invalid. Required format: +998XXXXXXXXX', [
                'user_id' => $user->id ?? null,
                'order_id' => $this->id ?? null,
                'raw_receiver_phone' => $orderInfo->phone ?: $user->phone,
            ]);
        }

        $senderCityCode = $stock->bts_city_id ?? null;
        $receiverCityCode = $orderInfo->bts_city_id ?? $user->bts_city_id ?? null;

        if (!$senderCityCode || !$receiverCityCode) {
            return $fail('BTS integration failed: sender or receiver BTS city is empty', [
                'stock_id' => $stock->id ?? null,
                'sender_city_code' => $senderCityCode,
                'receiver_city_code' => $receiverCityCode,
            ]);
        }

        $senderAddress = trim((string) ($stock->address ?? ''));
        $receiverAddress = trim((string) ($orderInfo->address ?: $user->address));

        if ($senderAddress === '' || $receiverAddress === '') {
            return $fail('BTS integration failed: sender or receiver address is empty', [
                'stock_id' => $stock->id ?? null,
                'sender_address' => $senderAddress,
                'receiver_address' => $receiverAddress,
            ]);
        }

        $weightKg = round((float) $totalWeight / 1000, 3);
        $volumeM3 = round((float) $totalVolume / 1000000, 4);

        $weightKg = max(0.1, $weightKg);
        $volumeM3 = max(0.001, $volumeM3);

        $data = [
            'clientId' => $user->id,
            'pickup_type' => 'courier',
            'dropoff_type' => 'courier',

            'sender' => [
                'name' => trim((string) ($shop->name_ru ?: $shop->name ?: 'Sender')),
                'phone' => $senderPhone,
                'address' => $senderAddress,
                'city_code' => (string) $senderCityCode,
            ],

            'receiver' => [
                'name' => trim($orderInfo->lastname . ' ' . $orderInfo->name) ?: 'Receiver',
                'phone' => $receiverPhone,
                'address' => $receiverAddress,
                'city_code' => (string) $receiverCityCode,
            ],

            'cargo' => [
                'weight' => $weightKg,
                'volume' => $volumeM3,
                'piece' => max(1, \count($orderProducts)),
                'packageId' => 10,
            ],
        ];

        $calculateData = [
            'senderCityCode' => (string) $senderCityCode,
            'receiverCityCode' => (string) $receiverCityCode,
            'pickup_type' => 'courier',
            'dropoff_type' => 'courier',
            'weight' => $weightKg,
        ];

        /** @var BtsComponent $bts */
        $bts = Yii::$app->bts;

        $btsPrice = $bts->calculateOrder($calculateData);
        $response = $bts->createOrder($data);

        if (!empty($response['success']) && isset($response['data']['orderId'])) {
            $btsData = $response['data'];

            $btsId = $btsData['orderId'];
            $btsStatus = $btsData['status']['code'] ?? $btsData['status']['id'] ?? null;
            $btsStatusInfo = $btsData['status']['info'] ?? $btsData['status']['name'] ?? null;

            $pricePerProduct = count($orderProducts) > 0
                ? round(((float) $btsPrice) / count($orderProducts), 2)
                : 0;

            foreach ($orderProducts as $orderProduct) {
                $orderProduct->bts_id = $btsId;
                $orderProduct->bts_status = $btsStatus;
                $orderProduct->bts_status_info = $btsStatusInfo;
                $orderProduct->bts_price = $pricePerProduct;
                $orderProduct->delivery_cost = $pricePerProduct;

                // Diqqat: qayta chaqirilsa price yana oshib ketmasligi uchun.
                // Agar eski bts_price bo‘lsa, avval ayirib tashlaymiz.
                $oldBtsPrice = (float) ($orderProduct->oldAttributes['bts_price'] ?? 0);
                $orderProduct->price = ((float) $orderProduct->price - $oldBtsPrice) + $pricePerProduct;

                $orderProduct->save(false);
            }

            return true;
        }

        return $fail('BTS order creation failed: ' . self::getBtsErrorMessage($response), [
            'order_id' => $this->id ?? null,
            'request' => $data,
            'create_response' => $response,
        ]);
    }

    protected static function getBtsErrorMessage(array $response): string
    {
        if (!empty($response['error']) && is_string($response['error'])) {
            return $response['error'];
        }

        if (!empty($response['items']) && is_array($response['items'])) {
            $messages = [];

            foreach ($response['items'] as $item) {
                if (!empty($item['field']) && !empty($item['message'])) {
                    $messages[] = $item['field'] . ': ' . $item['message'];
                } elseif (!empty($item['message'])) {
                    $messages[] = $item['message'];
                }
            }

            if ($messages) {
                return implode('; ', $messages);
            }
        }

        if (!empty($response['fields']) && is_array($response['fields'])) {
            return json_encode($response['fields'], JSON_UNESCAPED_UNICODE);
        }

        if (!empty($response['raw'])) {
            return json_encode($response['raw'], JSON_UNESCAPED_UNICODE);
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public function fields()
    {
        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $data = [
            'id',
            'user',
            'payment',
            'delivery',
            'price',
            'amount',
            'delivery_cost' => function () {
                return $this->getResolvedDeliveryCost();
            },
            'discount_amount',
            'promocode',
            'name',
            'phone',
            'address',
            'status' => function () {
                return Yii::$app->request->get('status') == 3 ? 3 : $this->status;
            },
            'status_payment',
            'date',
            'orderReceipt'
        ];

        $exception = ['send', 'detail', 'index'];

        if (($controller == 'order') && in_array($action, $exception)) {
            $data[] = 'orderProducts';
        }

        if ($controller == 'order' && $action == 'detail') {
            $data['didox_documents'] = function () {
                return $this->didoxDocuments;
            };
        }

        return $data;
    }

    /**
     * Gets query for [[Delivery]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDelivery()
    {
        return $this->hasOne(Delivery::class, ['id' => 'delivery_id']);
    }

    /**
     * Gets query for [[OrderProducts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderProducts()
    {
        return $this->hasMany(OrderProduct::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[Payment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPayment()
    {
        return $this->hasOne(Category::class, ['id' => 'payment_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getLogist()
    {
        return $this->hasOne(Logist::class, ['id' => 'logist_id']);
    }

    public function getShop()
    {
        return $this->hasOne(Shop::class, ['id' => 'shop_id']);
    }

    public function getOrderReceipt()
    {
        return $this->hasOne(OrderReceipt::class, ['order_id' => 'id']);
    }

    public function getDidoxDocuments()
    {
        return $this->hasMany(DidoxDocument::class, ['order_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    /**
     * Resolve delivery cost for API responses.
     * Uses order.delivery_cost first, then falls back to order products and finally
     * derives from price - products + discount for legacy rows.
     */
    public function getResolvedDeliveryCost(): float
    {
        $stored = (float) ($this->delivery_cost ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        $products = $this->orderProducts;
        if (empty($products)) {
            $products = OrderProduct::find()->where(['order_id' => $this->id])->all();
        }

        $deliveryFromProducts = 0.0;
        $productsTotal = 0.0;
        foreach ($products as $product) {
            $productsTotal += (float) ($product->product_price ?? 0);

            $itemDelivery = (float) ($product->delivery_cost ?? 0);
            if ($itemDelivery <= 0) {
                $itemDelivery = (float) ($product->bts_price ?? 0);
            }
            if ($itemDelivery > 0) {
                $deliveryFromProducts += $itemDelivery;
            }
        }

        if ($deliveryFromProducts > 0) {
            return $deliveryFromProducts;
        }

        $orderTotal = (float) ($this->price ?? 0);
        $discount = (float) ($this->discount_amount ?? 0);
        $derived = $orderTotal - $productsTotal + $discount;

        return $derived > 0 ? $derived : 0.0;
    }

    /**
     * Update BTS order status for all order products
     * @return bool
     */
    public function updateBtsStatus()
    {
        $updated = false;
        foreach ($this->orderProducts as $orderProduct) {
            if ($orderProduct->bts_id) {
                /** @var BtsComponent $bts */
                $bts = Yii::$app->bts;
                $response = $bts->getOrderStatus($orderProduct->bts_id);

                if ($response['success'] && isset($response['data']['status'])) {
                    $statusData = $response['data']['status'];
                    $orderProduct->bts_status = $statusData['id'] ?? $orderProduct->bts_status;
                    $orderProduct->bts_status_info = $statusData['info'] ?? $orderProduct->bts_status_info;
                    $orderProduct->save(false);
                    $updated = true;
                }
            }
        }
        return $updated;
    }

    /**
     * Get BTS tracking information for all order products
     * @return array
     */
    public function getBtsTracking()
    {
        $trackingData = [];
        foreach ($this->orderProducts as $orderProduct) {
            if ($orderProduct->bts_id) {
                /** @var BtsComponent $bts */
                $bts = Yii::$app->bts;
                $response = $bts->getOrderTracking($orderProduct->bts_id);
                $trackingData[$orderProduct->bts_id] = $response;
            }
        }
        return $trackingData;
    }

    /**
     * Get full BTS order information for all order products
     * @return array
     */
    public function getBtsOrderInfo()
    {
        $orderInfo = [];
        foreach ($this->orderProducts as $orderProduct) {
            if ($orderProduct->bts_id) {
                /** @var BtsComponent $bts */
                $bts = Yii::$app->bts;
                $response = $bts->getOrderInfo($orderProduct->bts_id);
                $orderInfo[$orderProduct->bts_id] = $response;
            }
        }
        return $orderInfo;
    }

    /**
     * Check if order has BTS integration
     * @return bool
     */
    public function hasBtsIntegration()
    {
        foreach ($this->orderProducts as $orderProduct) {
            if ($orderProduct->hasBtsIntegration()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get BTS status label summary
     * @param string $language Language code (ru, uz, en)
     * @return string
     */
    public function getBtsStatusLabel($language = 'ru')
    {
        $statuses = [];
        foreach ($this->orderProducts as $orderProduct) {
            if ($orderProduct->bts_id) {
                $label = Yii::$app->bts->getBtsStatusLabel($orderProduct->bts_status, $language);
                if ($label) {
                    $statuses[] = $label;
                } else {
                    $statuses[] = $orderProduct->bts_status_info ?: 'Unknown status';
                }
            }
        }
        return empty($statuses) ? 'No BTS integration' : implode('; ', array_unique($statuses));
    }

    /**
     * Get all BTS statuses with details
     * @param string $language Language code (ru, uz, en)
     * @return array
     */
    public function getBtsStatusesDetailed($language = 'ru')
    {
        $statuses = [];
        foreach ($this->orderProducts as $orderProduct) {
            if ($orderProduct->bts_id) {
                $statuses[] = [
                    'bts_id' => $orderProduct->bts_id,
                    'status_id' => $orderProduct->bts_status,
                    'status_info' => $orderProduct->bts_status_info,
                    'status_label' => Yii::$app->bts->getBtsStatusLabel($orderProduct->bts_status, $language),
                    'order_product_id' => $orderProduct->id,
                    'stock_name' => $orderProduct->stock ? $orderProduct->stock->name_ru : null
                ];
            }
        }
        return $statuses;
    }

    private function sendOrderToWarehouse($items)
    {
        // Skip warehouse sync in development/local environment
        // Can be overridden via params: Yii::$app->params['warehouseSyncEnabled'] = true/false
        $warehouseSyncDisabled = isset(Yii::$app->params['warehouseSyncEnabled'])
            ? Yii::$app->params['warehouseSyncEnabled']
            : true; // Default: enabled

        if (!$warehouseSyncDisabled) {
            Yii::info("Warehouse sync skipped (development mode) for Order #{$this->id}", 'warehouse_sync');
            return;
        }

        $baseUrl = Yii::$app->params['warehouseApiUrl'] ?? 'http://warehouse.example.com';
        $apiUrl = $baseUrl . '/api/sales/create-from-ecommerce';

        $client = new Client(['timeout' => 10.0]);

        $dataToSend = [
            'id' => $this->id,
            'yii_order_id' => $this->id,
            'buyer' => [
                'yii_user_id' => $this->user_id,
                'global_user_id' => $this->user->global_user_id ?? null,
                'name' => $this->user->fio ?? $this->user->name,
                'phone' => $this->user->phone,
            ],
            'items' => $items,
        ];

        $token = md5($this->id . Yii::$app->params['apiSecretKey']);

        try {
            $response = $client->post($apiUrl, [
                'json' => $dataToSend,
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 201 || empty($body['success'])) {
                $errorMessage = $body['message'] ?? 'Неизвестная ошибка склада';
                throw new \Exception('Ошибка склада: ' . $errorMessage);
            }

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $resp = $e->getResponse();
                $status = $resp->getStatusCode();
                $content = (string) $resp->getBody();
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($data['message'])) {
                    Yii::error(
                        "Склад вернул ошибку ($status): " . $data['message'],
                        'warehouse_sync'
                    );
                    throw new \Exception('Ошибка склада: ' . $data['message']);
                } else {
                    Yii::error("Склад вернул некорректный ответ ($status): $content", 'warehouse_sync');
                    throw new \Exception('Ошибка склада: некорректный ответ.');
                }
            }

            Yii::error(
                'Не удалось связаться со складом. Guzzle: ' . $e->getMessage(),
                'warehouse_sync'
            );
            throw new \Exception('Не удалось связаться со складом. Попробуйте позже.');
        }
    }

    /**
     * Normalize phone to BTS required format: +998XXXXXXXXX
     */
    public static function formatPhoneForBts($phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone ?? '');

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 9) {
            $digits = '998' . $digits;
        }

        if (strlen($digits) === 12 && strpos($digits, '998') === 0) {
            return '+' . $digits;
        }

        return null;
    }
}
