# app E-Commerce Platform -- Source Code Documentation

> **Platform:** Yii2 (PHP) with REST API architecture
> **Generated:** 2026-02-10
> **Scope:** Core business logic, authentication, orders, payments, products, delivery, invoicing, and admin panel

---

## Table of Contents

1. [User Authentication](#1-user-authentication)
2. [Order Lifecycle](#2-order-lifecycle)
3. [Payment Processing](#3-payment-processing)
4. [Product Management](#4-product-management)
5. [Shopping Cart](#5-shopping-cart)
6. [Delivery / BTS Integration](#6-delivery--bts-integration)
7. [Didox E-Invoicing](#7-didox-e-invoicing)
8. [Admin Panel](#8-admin-panel)

---

## 1. User Authentication

**File:** `modules/api/controllers/UserController.php`

### Feature Description

The authentication system supports two independent login flows:

- **Phone + SMS OTP** -- The primary flow for individual users (`fiz` type). The user submits their phone number, receives an SMS code, and verifies it to receive a Bearer token.
- **E-IMZO / Didox Digital Signature** -- For legal entities (`yur` type) and government-compliant authentication. Users authenticate via a PKCS7 digital certificate through the Uzbekistan Didox e-invoicing platform, which returns a token. The platform then either finds or creates a user account using the `eimzo_tax_id` field.

All subsequent API calls use `HttpBearerAuth` (the `Authorization: Bearer <token>` header) to identify the user.

### Code Snippet -- Phone+SMS Login (Two-Step)

```php
// Step 1: Send phone number, generate OTP code, save user
public function actionSendPhone() {
    try {
        $post = Yii::$app->request->post();

        if (empty($post['phone'])) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Fill in the field', ['phone' => ['Fill in the field']]);
        }

        $post['phone'] = preg_replace('/[^\d]/', '', trim($post['phone']));

        $model = User::find()->where(['phone' => $post['phone']])->one();

        if (!$model) {
            $model = new User();
        }

        $model->status = 1;
        $model->phone_code = '123456'; // In production: random code + SMS send
        $model->phone = $post['phone'];
        $model->token = '';
        $model->role = User::ROLE_USER;
        $model->type = 'fiz';
        $model->sms_live = strtotime('+3 minute');

        if ($model->save()) {
            // SMS service (production): $service = new Sms; $service->send(...)
            return $this->sendSuccess([
                'user_id' => $model->id,
                'message' => 'Confirmation code sent to the specified number.'
            ]);
        }
    } catch (\Exception $e) {
        return $this->sendError(ErrorCodes::ERROR_SERVER, 'Internal server error');
    }
}

// Step 2: Verify SMS code, issue Bearer token
public function actionSendCode() {
    $post = Yii::$app->request->post();
    $user = User::findOne($post['user_id']);

    if ($user->phone_code !== $post['code'] || time() > $user->sms_live) {
        return $this->sendError(ErrorCodes::ERROR_INVALID_CODE, 'Invalid or expired code');
    }

    $user->token = $user->generateToken();
    $user->status = 1;
    $user->phone_code = null;
    $user->sms_live = null;

    if ($user->save()) {
        $userData = $user->toArray();
        $userData['bts_region_id'] = $user->bts_region_id;
        $userData['bts_city_id'] = $user->bts_city_id;
        return $this->sendSuccess($userData);
    }
}
```

### Code Snippet -- E-IMZO / Didox Authentication

```php
public function actionEimzoAuth() {
    $post = Yii::$app->request->post();

    // Validate: didox_token, tax_id required
    $userType = isset($post['user_type']) && in_array($post['user_type'], ['fiz', 'yur'])
        ? $post['user_type'] : 'fiz';

    $didoxService = new DidoxService();

    // 1. Authenticate with Didox using the provided token
    $didoxAuth = $didoxService->authenticateWithDidox($post['didox_token'], $post['tax_id']);

    // 2. Get profile data from DIDOX (organization name, address, bank details)
    $profileResult = $didoxService->getUserProfile($post['didox_token']);

    // 3. Find or create user by eimzo_tax_id
    $user = User::findOne(['eimzo_tax_id' => $post['tax_id']]);
    if (!$user) {
        $user = new User();
        $user->eimzo_tax_id = $post['tax_id'];
        $user->role = User::ROLE_USER;
        $user->type = $userType;
        // Populate fields from Didox profile: name, email, address, OKED, bank account, MFO...
    }

    $user->eimzo_didox_token = $post['didox_token'];
    $user->eimzo_last_login = date('Y-m-d H:i:s');
    $user->token = $user->generateToken();
    $user->save(false);

    return ['data' => User::find()->with('image')->where(['id' => $user->id])->one()];
}
```

### Screen/Form Mapping

| Screen | Endpoint | Description |
|--------|----------|-------------|
| Login -- Phone Entry | `POST /api/user/send-phone` | User enters phone number |
| Login -- SMS Code Entry | `POST /api/user/send-code` | User enters the OTP code |
| Login -- E-IMZO | `POST /api/user/eimzo-auth` | Digital signature login |
| Registration -- E-IMZO | `POST /api/user/eimzo-register` | New user via Didox registration |
| Profile View | `GET /api/user/profile` | Current user profile with BTS location |
| Profile Edit | `POST /api/user/update` | Update name, address, photo, BTS city |

---

## 2. Order Lifecycle

**File:** `modules/api/controllers/OrderController.php` (method `actionSend`)

### Feature Description

The order creation process (`actionSend`) converts a user's shopping cart into a finalized order. The flow is:

1. Validate that the cart is not empty.
2. Resolve and validate any promocode submitted by the user.
3. Save the order record and create `OrderProduct` entries for each cart item (using quantity-based pricing tiers).
4. Send the order to the warehouse system (Sklad) for stock management.
5. Auto-fill the user's profile from order data if it is their first order.
6. Clear the user's cart.
7. Group products by stock/warehouse and create BTS courier delivery orders for each group.
8. Apply promocode discount and compute the final price (products - discount + delivery).
9. Automatically create Didox e-invoicing documents (invoice + contract).
10. If the payment method is "wallet", execute a blockchain payment via WalletService.

### Code Snippet -- actionSend (Cart to Order)

```php
public function actionSend()
{
    $user = Yii::$app->user->identity;
    $post = Yii::$app->request->post();

    // 1. Load cart items
    $cart = UserCart::find()->with(['cartFilter', 'product'])
        ->where(['user_id' => $user->id])->all();
    if (!$cart) {
        return ['errors' => ['cart' => 'Your cart is empty']];
    }

    $model = new Order;
    $model->load($post, '');

    // 2. Prevent direct promocode_id injection -- must use code string
    $model->promocode_id = null;
    $model->discount_amount = null;

    if (!empty($post['promocode'])) {
        $promocode = \app\models\Promocode::findOne(['code' => $post['promocode']]);
        // Validate: exists, not expired, min order amount, etc.
        $cartTotal = 0;
        foreach ($cart as $item) {
            if ($item->product) {
                $unitPrice = $item->product->getPriceByQuantity($item->amount);
                $cartTotal += $unitPrice * $item->amount;
            }
        }
        list($isValid, $error) = $promocode->checkValidity($user, $cartTotal, $cart);
        if (!$isValid) {
            return ['errors' => ['promocode' => $error]];
        }
        $model->promocode_id = $promocode->id;
    }

    // 3. Save order, create order products, send to warehouse, handle BTS
    $saveResult = $model->saveObject($cart);
    if ($saveResult) {
        $order = Order::find()->with('orderProducts', ...)->where(['id' => $model->id])->one();

        // 4. Auto-create Didox documents (invoice + contract)
        try {
            \app\services\DidoxOrderService::createDocuments($order);
        } catch (\Exception $e) {
            \app\models\Log::log('didox_order', "Auto-creation exception", $e->getMessage(), 'error');
        }

        // 5. Process wallet payment if selected
        $walletPaymentId = Yii::$app->params['walletPaymentId'] ?? null;
        if ($walletPaymentId && $order->payment_id == $walletPaymentId) {
            $walletService = new WalletService();
            $walletService->init();
            $result = $walletService->pay($user->id, $merchantId, (string)$order->price, $walletToken);
            $order->status_payment = 1;
            $order->save(false);
            // Create Transaction record...
        }

        return ['data' => $order];
    }
}
```

### Screen/Form Mapping

| Screen | Endpoint | Description |
|--------|----------|-------------|
| Checkout -- Place Order | `POST /api/order/send` | Submit cart as a new order |
| My Orders List | `GET /api/order` | List user orders with status filtering |
| Order Detail | `GET /api/order/detail?id=X` | Single order with products |
| Calculate Delivery | `POST /api/order/calculate` | Estimate BTS delivery cost for a product |
| BTS Tracking | `GET /api/order/get-order-tracking?id=X` | Live courier tracking |

---

## 3. Payment Processing

**Files:** `services/WalletService.php` and `modules/api/controllers/WalletController.php`

### Feature Description

The platform implements a **crypto wallet payment system** backed by Ethereum Account Abstraction (AA wallets). Each user gets a deterministic EOA (Externally Owned Account) and an AA (Smart Account) wallet, managed by an external Node.js microservice running at `walletServiceUrl`. Supported tokens include USDT and USDC (on Sepolia testnet currently).

The payment flow uses a "batch payment" approach: `approve` -> `build-batch` -> `execute-batch`. The `pay()` method on WalletService executes the full batch in one call, transferring tokens from the payer's AA wallet to the merchant's AA wallet.

Additionally, the platform supports **Payme Subscribe** (Uzbekistan payment gateway) for traditional card-based payments via receipt creation and payment.

### Code Snippet -- WalletService (Core Methods)

```php
class WalletService extends Component
{
    private $serviceUrl;
    private $client;

    const TOKENS = [
        'USDT' => '0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16',
        'USDC' => '0x3f4A04341122360b304C9A896a2Dbfe4cca5B4AE',
    ];

    public function init()
    {
        parent::init();
        $this->serviceUrl = Yii::$app->params['walletServiceUrl'] ?? 'http://localhost:3001';
        $this->client = new Client(['base_uri' => $this->serviceUrl]);
    }

    // Get wallet balance (ETH + tokens + deployment status)
    public function getBalance($userOrAddress)
    {
        $query = [];
        $query['userId'] = $userOrAddress;
        $query['userLogin'] = $this->getUserLogin($userOrAddress);

        $response = $this->client->get('wallet/balance', ['query' => $query]);
        return json_decode($response->getBody()->getContents(), true);
    }

    // Execute batch payment: payer -> merchant
    public function pay($payerId, $merchantId, $amount, $symbol)
    {
        $response = $this->client->post('payment/execute-batch', [
            'json' => [
                'payerId'    => (int)$payerId,
                'merchantId' => (int)$merchantId,
                'amount'     => (string)$amount,
                'symbol'     => strtoupper($symbol),
            ]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    // Deploy AA smart wallet on-chain
    public function deployWallet($userId)
    {
        $response = $this->client->post('wallet/deploy', [
            'json' => [
                'userId'    => $userId,
                'userLogin' => $this->getUserLogin($userId)
            ]
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        return $data['txHash'] ?? 'Deployed';
    }

    private function getUserLogin($userId)
    {
        $user = User::findOne($userId);
        return $user ? ($user->phone ?: $user->login ?: $user->email ?: 'user_' . $userId) : 'unknown';
    }
}
```

### Code Snippet -- WalletController (API Endpoints)

```php
// GET /api/wallet/balance -- Returns balance, tokens, deployment status, recent transactions
public function actionBalance()
{
    $id = Yii::$app->user->id;

    $cacheKey = 'wallet_balance_' . $id;
    $cachedResponse = Yii::$app->cache->get($cacheKey);
    if ($cachedResponse !== false) {
        return $cachedResponse;
    }

    $balanceData = $this->walletService->getBalance($id);
    $addresses = $this->walletService->predictWallet($id);
    $tokens = $balanceData['tokens'] ?? [];

    // Enhance tokens with icon images
    $images = [
        'ETH'  => 'https://...ethereum/info/logo.png',
        'USDT' => 'https://...logo.png',
        'USDC' => 'https://...logo.png',
    ];

    $response = [
        'aaAddress'  => $addresses['aa_address'],
        'balance'    => (string)($balanceData['balance'] ?? '0.0'),
        'isDeployed' => (bool)($balanceData['isDeployed'] ?? false),
        'tokens'     => $tokens,
        'recentTransactions' => $balanceData['recentTransactions'] ?? []
    ];

    Yii::$app->cache->set($cacheKey, $response, 60); // Cache 1 minute
    return $response;
}

// POST /api/wallet/pay -- Execute payment from authenticated user to merchant
public function actionPay()
{
    $payerId = Yii::$app->user->id;
    $request = Yii::$app->request;
    $result = $this->walletService->pay(
        $payerId,
        $request->post('merchantId'),
        $request->post('amount'),
        $request->post('symbol')
    );
    return $result;
}
```

### Screen/Form Mapping

| Screen | Endpoint | Description |
|--------|----------|-------------|
| Wallet Home | `GET /api/wallet/balance` | Show ETH balance, token balances, recent txns |
| Wallet Address | `GET /api/wallet/address` | Get EOA + AA addresses |
| Make Payment | `POST /api/wallet/pay` | Transfer tokens from user to merchant |
| Deploy Wallet | `POST /api/wallet/deploy` | Deploy AA smart contract |
| Supported Tokens | `GET /api/wallet/supported-tokens` | List available tokens |
| Admin: Mint Tokens | `POST /api/wallet/mint` | Test/admin token minting |

---

## 4. Product Management

**File:** `models/product/Product.php`

### Feature Description

The `Product` model is the central entity of the catalog. Key characteristics:

- **Multi-language support**: `name_ru`, `name_en`, `name_uz` with automatic transliteration.
- **Three-tier wholesale pricing**: `price` (retail), `price_small` (small wholesale at `qty_small_wholesale`), `price_opt` (bulk wholesale at `qty_big_wholesale`).
- **Product variants**: Linked via `token_key` -- a shared random string that groups color/size/type variants of the same product.
- **IKPU integration**: Uzbekistan product classification codes for e-invoicing compliance.
- **Stock & warehouse sync**: Products are linked to a `Stock` (warehouse) and synced to an external warehouse management system (Sklad).
- **Physical dimensions**: `weight`, `height`, `width`, `length` used for BTS delivery cost calculation.

### Code Snippet -- Pricing Tiers

```php
/**
 * Get price based on quantity (wholesale pricing)
 * @param int $quantity
 * @return float
 */
public function getPriceByQuantity($quantity)
{
    $price = $this->price;

    // Small wholesale tier
    if ($this->qty_small_wholesale
        && $quantity >= $this->qty_small_wholesale
        && $this->price_small
        && $quantity < $this->qty_big_wholesale) {
        $price = $this->price_small;
    }
    // Big wholesale tier
    else if ($this->qty_big_wholesale
        && $quantity >= $this->qty_big_wholesale
        && $this->price_opt) {
        $price = $this->price_opt;
    }

    return $price;
}

/**
 * Get pricing tiers for frontend display
 * @return array
 */
public function getPricingTiers()
{
    $tiers = [
        [
            'min_quantity' => 1,
            'max_quantity' => $this->qty_small_wholesale ? $this->qty_small_wholesale - 1 : null,
            'price' => $this->price,
            'type' => 'regular'
        ]
    ];

    if ($this->qty_small_wholesale && $this->price_small) {
        $tiers[] = [
            'min_quantity' => $this->qty_small_wholesale,
            'max_quantity' => $this->qty_big_wholesale ? $this->qty_big_wholesale - 1 : null,
            'price' => $this->price_small,
            'type' => 'small_wholesale'
        ];
    }

    if ($this->qty_big_wholesale && $this->price_opt) {
        $tiers[] = [
            'min_quantity' => $this->qty_big_wholesale,
            'max_quantity' => null,
            'price' => $this->price_opt,
            'type' => 'big_wholesale'
        ];
    }

    return $tiers;
}
```

### Code Snippet -- Variant System (fields() API output)

```php
public function fields() {
    $language = $headers->get('Content-Language') ?: 'ru';

    $data = [
        'id',
        'name' => function() use($language) {
            return $this->{'name_'.$language} ?: $this->name_ru;
        },
        'color' => function() {
            if ($this->color) {
                return ['id' => $this->color->id, 'name' => $this->color->name_ru, 'color' => $this->color->color];
            }
            return null;
        },
        'price', 'price_small', 'price_opt',
        'qty_small_wholesale', 'qty_big_wholesale',
        'min_order', 'amount', 'weight', 'height', 'width', 'length',
        'image', 'gallery', 'rating', 'review_count',
        'isFavorite' => function() { return $this->isFavorite(); },
        'pricing_tiers' => function() { return $this->getPricingTiers(); },
        // ... on detail pages:
        'variants' => function() use($language) { return $this->getVariantsData($language); },
        'shop'
    ];
    return $data;
}
```

### Screen/Form Mapping

| Screen | Related Fields | Description |
|--------|---------------|-------------|
| Product Card (List) | name, price, image, rating, isFavorite | Catalog grid/list view |
| Product Detail | All fields + description, reviews, variants, pricing_tiers | Full product page |
| Admin: Product Form | All DB fields including IKPU, weight, dimensions | Create/edit product |
| Cart Calculations | getPriceByQuantity() | Dynamic pricing based on quantity |

---

## 5. Shopping Cart

**File:** `modules/api/controllers/CartController.php`

### Feature Description

The cart system (`UserCart` model) manages per-user product selections. Key features:

- **Add to cart** (`actionAdd`): Supports both single and batch product addition. Validates stock availability and minimum order quantity. Automatically calculates BTS delivery cost for each item based on the user's BTS city.
- **Quantity-based pricing**: Uses `Product::getPriceByQuantity()` to apply the correct wholesale tier at each quantity change.
- **Variant grouping** (`actionIndex`): Cart items are grouped by `token_key` so that variants of the same product (different colors/sizes) appear as one group with sub-items.
- **Delivery cost calculation** (`actionCalculate`): Groups all cart items by warehouse stock, then calls the BTS API to compute delivery for each group separately (smart stacking of dimensions).

### Code Snippet -- Add to Cart (Single Product)

```php
protected function addSingleProduct($user, $post) {
    $amountToAdd = isset($post['amount']) ? (int)$post['amount'] : 1;

    $product = Product::find()->with(['stock', 'shop.stock'])
        ->where(['id' => $post['product_id']])->one();

    // Find existing cart item
    $existingCart = UserCart::findOne(['user_id' => $user->id, 'product_id' => $post['product_id']]);

    if ($existingCart) {
        // Replace the amount (not add)
        $newAmount = $amountToAdd;

        if ($product->amount < $newAmount) {
            return $this->sendError(ErrorCodes::ERROR_NOT_ENOUGH_STOCK, 'Not enough stock');
        }
        if ($product->min_order && $newAmount < $product->min_order) {
            return $this->sendError(ErrorCodes::ERROR_MIN_ORDER_QUANTITY, 'Minimum order is ' . $product->min_order);
        }

        $existingCart->amount = $newAmount;

        // Calculate price based on quantity and wholesale tiers
        $unit_price = $product->getPriceByQuantity($existingCart->amount);
        $existingCart->price = $existingCart->amount * $unit_price;

        // Recalculate BTS delivery cost
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

        $unit_price = $product->getPriceByQuantity($model->amount);
        $model->price = $model->amount * $unit_price;

        if ($user->bts_city_id) {
            $model->delivery_cost = $model->calculateBtsDeliveryCost($product, $user, $model->amount);
        }
        $model->save(false);
        $result = $model;
    }

    return $this->sendSuccess($cart);
}
```

### Code Snippet -- Cart Calculate (Delivery Cost by Stock Group)

```php
public function actionCalculate() {
    $user = Yii::$app->user->identity;
    $receiverCityId = Yii::$app->request->post('bts_city_id') ?: $user->bts_city_id;

    $cartItems = UserCart::find()
        ->with(['product', 'product.stock', 'product.shop.stock'])
        ->where(['user_id' => $user->id])->all();

    // Group by stock_id
    $stockGroups = [];
    foreach ($cartItems as $cartItem) {
        $stockId = $cartItem->product->stock_id ?? $cartItem->product->shop->stock->id ?? null;
        $stockGroups[$stockId]['items'][] = $cartItem;
    }

    foreach ($stockGroups as $stockId => $group) {
        // Calculate total weight, stacked volume per group
        // Call BTS API: $bts->calculateOrder($calculatorData);
        // Extract branch_to_courier price
    }

    return [
        'data' => [
            'calculations' => $calculations,
            'summary' => [
                'total_product_cost'  => $totalProductCost,
                'total_delivery_cost' => $totalDeliveryCost,
                'grand_total'         => $totalProductCost + $totalDeliveryCost,
                'currency'            => 'UZS',
            ]
        ]
    ];
}
```

### Screen/Form Mapping

| Screen | Endpoint | Description |
|--------|----------|-------------|
| Cart View | `GET /api/cart` | Grouped cart items with variants |
| Add to Cart Button | `POST /api/cart/add` | Single or batch add |
| Quantity -1 | `POST /api/cart/minus` | Decrease quantity (removes if 0) |
| Remove Item | `POST /api/cart/remove` | Remove product from cart |
| Clear Cart | `POST /api/cart/clear` | Delete all cart items |
| Delivery Summary | `POST /api/cart/calculate` | Full delivery cost breakdown by warehouse |

---

## 6. Delivery / BTS Integration

**File:** `models/order/Order.php` (method `createBtsOrderForStockGroup`)

### Feature Description

BTS (Business Transport Service) is the courier/logistics provider integrated into the platform. When an order is placed, the system:

1. Groups all order products by their `stock_id` (warehouse location).
2. For each warehouse group, calculates total weight and volume.
3. Calls the BTS API (`$bts->createOrder()`) to create a real courier order, providing sender/receiver city IDs, addresses, phone numbers, package info, and delivery dates.
4. Stores the returned `bts_id`, `bts_status`, and `bts_price` on each `OrderProduct`.
5. The delivery cost is distributed evenly among products in the group.

The BTS integration also supports real-time status tracking (`updateBtsStatus`), full tracking history (`getBtsTracking`), and delivery cost pre-calculation before order placement.

### Code Snippet -- BTS Order Creation

```php
protected function createBtsOrderForStockGroup($stock, $orderProducts, $user, $orderInfo, $totalWeight, $totalVolume)
{
    $shop = $stock->shop;

    $data = [
        "senderDelivery"  => 1,                          // 1 = courier pickup from sender
        "senderCityId"    => $stock->bts_city_id,
        "senderAddress"   => $stock->address,
        "senderReal"      => $shop->name_ru,
        "senderPhone"     => $shop->contact_phone,
        "weight"          => max(1, $totalWeight / 1000), // Convert grams to kg
        "packageId"       => 4,                           // Package type ID
        "postTypeId"      => 22,                          // Delivery type ID
        "receiverDelivery" => 1,                          // 1 = courier delivers to receiver
        "receiver"        => $orderInfo->lastname . ' ' . $orderInfo->name,
        "receiverCityId"  => $orderInfo->bts_city_id ?? $user->bts_city_id,
        "receiverAddress" => $orderInfo->address ?? $user->address,
        "volume"          => max(1, $totalVolume / 1000000),
        "urgent"          => 0,
        "takePhoto"       => 1,    // Require photo of receiver
        "piece"           => count($orderProducts),
        "is_test"         => 1,    // Test mode flag
        "senderDate"      => date('Y-m-d', strtotime($orderInfo->date)),
        "receiverDate"    => date('Y-m-d', strtotime('+1 day', strtotime($orderInfo->date))),
        "receiverPhone"   => $orderInfo->phone ?: $user->phone,
    ];

    $bts = new BTS();
    $response = $bts->createOrder($data);

    if ($response['success'] && isset($response['data']['orderId'])) {
        $btsData  = $response['data'];
        $btsId    = $btsData['orderId'];
        $btsPrice = $btsData['cost'] ?? null;

        // Distribute delivery cost evenly among order products
        $pricePerProduct = $btsPrice ? $btsPrice / count($orderProducts) : 0;

        foreach ($orderProducts as $orderProduct) {
            $orderProduct->bts_id          = $btsId;
            $orderProduct->bts_status      = $btsData['status']['id'] ?? null;
            $orderProduct->bts_status_info = $btsData['status']['info'] ?? null;
            $orderProduct->bts_price       = $pricePerProduct / count($orderProducts);
            $orderProduct->delivery_cost   = $orderProduct->bts_price;
            $orderProduct->price           = $orderProduct->price + $orderProduct->bts_price;
            $orderProduct->save(false);
        }
    } else {
        // Log error but don't fail the order
        foreach ($orderProducts as $orderProduct) {
            $orderProduct->bts_status_info = 'BTS integration failed: ' . ($response['error'] ?? 'Unknown');
            $orderProduct->save(false);
        }
    }
}
```

### Screen/Form Mapping

| Screen | Method/Endpoint | Description |
|--------|----------------|-------------|
| Checkout (auto) | `Order::createBtsOrderForStockGroup()` | Automatically called during order creation |
| Admin: Order Detail | BTS Info section | Shows BTS IDs, statuses, delivery costs |
| Admin: Update BTS Status | AJAX `/admin/order/update-bts-status` | Refresh courier status from BTS API |
| Admin: Tracking History | AJAX `/admin/order/get-bts-tracking` | Modal with delivery timeline |
| API: Calculate Delivery | `POST /api/order/calculate` | Pre-checkout delivery estimate |
| API: Get Regions/Cities | `GET /api/order/get-regions`, `get-cities` | BTS geo data for address selection |

---

## 7. Didox E-Invoicing

**File:** `services/DidoxOrderService.php`

### Feature Description

Didox is the Uzbekistan government e-invoicing platform. The `DidoxOrderService` automatically generates two types of legally-required documents when an order is placed:

1. **Invoice (Schyot-faktura)** -- Document type `002`. Contains seller/buyer TIN (tax ID), bank details, and a product list with IKPU codes, quantities, prices, and VAT calculations (12% standard).
2. **Arbitrary Contract (Dogovor)** -- Document type `000`. A PDF contract generated and uploaded to Didox for digital signature.

The service authenticates with Didox using either a PFX certificate (auto-auth) or a stored system token, then sends the document via the Didox API. Documents are tracked locally in `didox_document` tables and can be signed, downloaded as PDF, or retried manually from the admin panel.

### Code Snippet -- Automated Document Creation

```php
class DidoxOrderService
{
    public static function createDocuments(Order $order)
    {
        $result = ['success' => true, 'messages' => [], 'documents' => []];

        // 1. Load seller info from global Settings table
        $settings = \app\models\Settings::find()
            ->where(['type' => ['didox_seller_inn', 'didox_seller_name', ...]])->all();
        $sellerInfo = [
            'tin'          => $settingsMap['didox_seller_inn'] ?? '',
            'name'         => $settingsMap['didox_seller_name'] ?? '',
            'address'      => $settingsMap['didox_seller_address'] ?? '',
            'account'      => $settingsMap['didox_seller_account'] ?? '',
            'bank_id'      => $settingsMap['didox_seller_mfo'] ?? '',
            'vat_reg_code' => $settingsMap['didox_seller_vat_reg_code'] ?? '300000000001',
        ];

        // 2. Get buyer info from Order + User
        $buyerInfo = self::getBuyerInfo($order, $order->user);

        // 3. Authenticate: try PFX auto-auth, fallback to stored system token
        $didoxService = new DidoxService();
        $autoAuth = $didoxService->getAuthTokenFromPfx();
        if ($autoAuth['success']) {
            $userKey = $autoAuth['token'];
        } else {
            $userKey = Settings::findOne(['type' => 'didox_eimzo_token'])->content ?? '';
        }

        // 4. Create Invoice if not already exists
        if (!DidoxDocument::find()->where(['order_id' => $order->id, 'document_type' => 'invoice'])->exists()) {
            $invoiceDoc = self::createInvoiceDocument($order, $sellerInfo, $buyerInfo);
            $apiData = $invoiceDoc->generateDidoxApiStructure();
            $apiData['doctype'] = '002'; // Invoice type
            $uploadResult = $didoxService->createDocument($apiData, $userKey);
            // Save Didox ID, status, download PDF...
        }

        // 5. Create Arbitrary Contract if not already exists
        if (!DidoxDocument::find()->where(['order_id' => $order->id, 'document_type' => 'arbitrary'])->exists()) {
            $contractDoc = self::createContractDocument($order, $sellerInfo, $buyerInfo);
            // Generate PDF, upload to Didox...
        }

        return $result;
    }
}
```

### Code Snippet -- Product Line Items for Invoice

```php
protected static function createIncludedProducts(DidoxDocument $doc, Order $order)
{
    foreach ($order->orderProducts as $index => $op) {
        $product = $op->product;

        $included = new DidoxDocumentIncludedProducts();
        $included->document_id = $doc->id;
        $included->ord_no = $index + 1;
        $included->name = $product->name_ru ?: $product->name_uz ?: 'Product';

        // IKPU code (Uzbekistan product classification)
        $included->catalog_code = $product->ikpu_code ?: '10309001003000000'; // Default
        $included->catalog_name = $product->ikpu_name ?: 'Other goods';

        // Package info
        $included->package_code = $product->package_code ?: '1516231';
        $included->package_name = $product->package_name ?: 'pcs';

        $included->count = (float)($op->amount ?: 1);
        $included->summa = $op->price ?: 0;

        // VAT at 12%
        $included->vat_rate = 12;
        $included->vat_sum = $included->summa * 12 / 112;

        $included->save();
    }
}
```

### Screen/Form Mapping

| Screen | Trigger | Description |
|--------|---------|-------------|
| (Auto) Order creation | `DidoxOrderService::createDocuments()` | Called automatically from `Order::saveObject()` and `OrderController::actionSend()` |
| Admin: Order View -- Didox section | View button | Shows list of Didox documents with statuses |
| Admin: Auto Invoice | `/admin/order/create-didox-invoice?id=X` | Manually trigger invoice creation |
| Admin: Auto Contract | `/admin/order/create-didox-arbitrary?id=X` | Manually trigger contract creation |
| Admin: Didox Document View | `/admin/didox/view?id=X` | View, sign, or download PDF |
| Admin: Didox Logs | Collapsible section on Order view | Request/response logs for debugging |

---

## 8. Admin Panel

**Files:** `modules/admin/views/order/view.php` and `modules/admin/views/user/view.php`

### Feature Description

The admin panel is built with the AdminLTE template (Bootstrap 3). It provides comprehensive management screens:

**Order View (`order/view.php`):**
- Order status management (Accept, Reject, Delivering, In Transit, Delivered, Return).
- Payment and logistics status indicators.
- BTS courier integration section: real-time status updates via AJAX, delivery tracking timeline modal, per-product BTS cost display.
- Product list with per-unit pricing, quantity, delivery cost breakdown, and customer reviews inline.
- Full Didox documents section: auto-create invoice/contract, view/sign uploaded documents, see draft/signed/rejected statuses, and view API logs.
- Yandex Maps integration for delivery location visualization.

**User View (`user/view.php`):**
- User profile with personal info (name, phone, email, birthday, gender, registration date).
- Tabbed interface: Activity, Orders, Transactions, Addresses, Promocodes, Business Requisites (for `yur` type), Security.
- Crypto wallet management: visual wallet card showing ETH balance, AA address, deployment status; token asset table (USDT, USDC, HUMO, app); recent blockchain transactions; "Deploy Wallet", "Mint Token", and "Pay" actions via modals.
- Business requisites for legal entities: INN, bank account, MFO, OKED, legal address.
- Security info: user ID, Bearer token preview, IP, device ID, role.

### Code Snippet -- Order View: BTS Status Section

```php
<!-- BTS Information Row in Order Detail -->
<tr>
    <td>BTS Information:</td>
    <td id="bts-info-container">
        <?php if ($model->hasBtsIntegration()): ?>
            <div class="bts-controls" style="margin-bottom: 15px;">
                <button class="btn btn-info btn-sm" onclick="updateBtsStatus(<?= $model->id ?>)">
                    <i class="fa fa-refresh"></i> Update BTS Statuses
                </button>
                <button class="btn btn-success btn-sm" onclick="getBtsTracking(<?= $model->id ?>)">
                    <i class="fa fa-history"></i> Show Delivery History
                </button>
            </div>
            <div id="bts-status-list">
                <?php foreach ($model->orderProducts as $orderProduct): ?>
                    <?php if ($orderProduct->bts_id): ?>
                        <div class="bts-item" data-bts-id="<?= $orderProduct->bts_id ?>">
                            <strong>BTS ID:</strong> <?= $orderProduct->bts_id ?>
                            <span class="label <?= $statusClass ?>">
                                <?= $statusLabel ?: $orderProduct->bts_status_info ?>
                            </span>
                            <small>
                                Status ID: <?= $orderProduct->bts_status ?> |
                                Delivery Cost: <?= number_format($orderProduct->bts_price, 2) ?> UZS
                            </small>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <span style="color: #999;">BTS integration not used</span>
        <?php endif; ?>
    </td>
</tr>
```

### Code Snippet -- User View: Crypto Wallet Section

```php
<!-- Wallet Card in User Security Tab -->
<?php
$walletService = new \app\services\WalletService();
$addresses = $walletService->predictWallet($model->id);
$balanceData = $walletService->getBalance($model->id);

$eoaAddress = $addresses['eoa_address'];
$aaAddress  = $addresses['aa_address'];
$balance    = $balanceData['balance'] ?? 0;
$isDeployed = $balanceData['isDeployed'] ?? false;
$tokens     = $balanceData['tokens'] ?? [];
?>

<div class="wallet-card bg-blue-gradient shadow-lg">
    <div class="wallet-header">
        <i class="fa fa-wifi fa-2x opacity-50"></i>
        <span class="wallet-title">ETH Wallet</span>
    </div>
    <div class="wallet-balance">
        <small class="opacity-75">Total Balance</small>
        <div class="balance-amount"><?= $balance ?> ETH</div>
    </div>
    <div class="wallet-footer">
        <div class="wallet-address-label">AA Address</div>
        <code class="wallet-address-code"><?= $aaAddress ?: 'Not generated' ?></code>
    </div>
    <div class="wallet-status-badge">
        <?php if ($isDeployed): ?>
            <span class="label label-success"><i class="fa fa-check"></i> Active</span>
        <?php else: ?>
            <span class="label label-warning"><i class="fa fa-clock-o"></i> Undeployed</span>
        <?php endif; ?>
    </div>
</div>

<!-- Token Asset Table -->
<table class="table table-hover">
    <tr>
        <td><img src="<?= $images['ETH'] ?>" width="32"></td>
        <td><strong>Ethereum</strong><div class="text-muted small">ETH</div></td>
        <td class="text-right"><strong><?= $balance ?></strong></td>
    </tr>
    <?php foreach ($tokens as $token): ?>
    <tr>
        <td><img src="<?= $token['image'] ?>" width="32"></td>
        <td><strong><?= $token['symbol'] ?></strong></td>
        <td class="text-right"><strong><?= $token['balance'] ?></strong></td>
    </tr>
    <?php endforeach; ?>
</table>
```

### Admin Screen Summary

| Admin Screen | URL Pattern | Key Features |
|-------------|-------------|--------------|
| Order Detail | `/admin/order/view?id=X` | Status management, BTS tracking, Didox docs, product list, Yandex map |
| User Profile | `/admin/user/view?id=X` | Personal info, orders, transactions, addresses, wallet, promocodes, business requisites |
| Order List | `/admin/order/` | Filterable order grid |
| User List | `/admin/user/` | User grid with role/status filters |
| Didox Document | `/admin/didox/view?id=X` | Invoice/contract viewer with sign capability |

---

## Architecture Summary

```
Mobile/Web App (Flutter / Web)
        |
        | REST API (Bearer Token Auth)
        v
+---------------------------+
|   Yii2 API Controllers    |
|  (UserController,         |
|   OrderController,        |
|   CartController,         |
|   WalletController,       |
|   ProductController, ...) |
+---------------------------+
        |
        v
+---------------------------+     +-------------------+
|    Models / Services      |---->|   MySQL Database   |
|  (Order, Product, User,   |     +-------------------+
|   WalletService,          |
|   DidoxOrderService, ...) |     +-------------------+
|                           |---->| Wallet Service     |
+---------------------------+     | (Node.js/Ethereum) |
        |                         +-------------------+
        |
        |  +------------------+   +-------------------+
        +->| BTS API          |   | Didox API          |
        |  | (Courier/Logist) |   | (E-Invoicing)      |
        |  +------------------+   +-------------------+
        |
        |  +------------------+   +-------------------+
        +->| Payme Subscribe  |   | Sklad (Warehouse)  |
           | (Card Payments)  |   | (Stock Management) |
           +------------------+   +-------------------+
```

### Key Integration Points

| Integration | Purpose | Protocol |
|-------------|---------|----------|
| **BTS** | Courier delivery (create orders, track, calculate costs) | REST API |
| **Didox** | Government e-invoicing (invoices, contracts, digital signatures) | REST API |
| **Wallet Service** | Crypto payments (AA wallets, token transfers) | REST API (GuzzleHttp) |
| **Payme Subscribe** | Card payment processing (Uzbekistan) | REST API |
| **Sklad** | Warehouse stock management and order sync | REST API |
| **Yandex Maps** | Delivery location visualization in admin | JS SDK |
| **Binlist** | Card BIN lookup for bank identification | REST API |

---

*End of Source Code Documentation*
