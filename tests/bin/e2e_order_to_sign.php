<?php
/**
 * E2E Test: Order -> Didox Document Creation -> Auto Sign
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/e2e_order_to_sign.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';

// Console application'da session ishlamasligi uchun
// session componentni soxta (dummy) qilamiz
class DummySession extends \yii\web\Session
{
    public function open()
    { /* do nothing */
    }
    public function get($key, $default = null)
    {
        return $default;
    }
    public function set($key, $value)
    { /* do nothing */
    }
    public function has($key)
    {
        return false;
    }
    public function remove($key)
    { /* do nothing */
    }
}

$config['components']['session'] = [
    'class' => 'DummySession',
];

// Dummy user component - console muhitda user kerak (DidoxDocument::beforeSave)
$config['components']['user'] = [
    'class' => 'yii\web\User',
    'identityClass' => 'app\models\user\User',
    'enableAutoLogin' => false,
    'enableSession' => false,
];

$app = new yii\console\Application($config);

echo "=== E2E TEST: Order -> Didox Auto Sign ===\n\n";

// 1. Check existing data
echo "1. CHECKING DATA...\n";
$user = \app\models\user\User::find()->one();
if (!$user) {
    die("   ❌ No users!\n");
}
echo "   User: #{$user->id}\n";

$products = \app\models\product\Product::find()->where(['not', ['ikpu_code' => null]])->limit(2)->all();
echo "   Products: " . count($products) . "\n";
foreach ($products as $p) {
    $name = $p->name_uz ?: $p->name_ru ?: $p->name_en ?: 'N/A';
    $price = $p->price ?: 5000000;
    echo "   - #{$p->id} | {$name} | " . number_format($price, 0) . " | IKPU: {$p->ikpu_code}\n";
}

if (count($products) == 0) {
    echo "   ⚠️  No products with IKPU, using all products\n";
    $products = \app\models\product\Product::find()->limit(2)->all();
}
echo "\n";

// 2. Create Order
echo "2. CREATING ORDER...\n";
$order = new \app\models\order\Order();
$order->user_id = $user->id;
$order->price = 0;
$order->status = 1;
$order->date = date('Y-m-d H:i:s');
$order->address = 'Test Address Toshkent';
$order->delivery_id = 1; // Default delivery

if ($order->save()) {
    echo "   ✅ Order created: #{$order->id}\n";
} else {
    echo "   ❌ Failed: " . json_encode($order->errors) . "\n";
    exit(1);
}

// 3. Add Products
echo "\n3. ADDING PRODUCTS...\n";
$totalPrice = 0;
foreach ($products as $product) {
    $op = new \app\models\order\product\OrderProduct();
    $op->order_id = $order->id;
    $op->product_id = $product->id;
    $op->amount = 1;
    $op->price = $product->price ?? 5000000;
    $op->product_price = $product->price ?? 5000000;
    $op->date = date('Y-m-d H:i:s');

    if ($op->save()) {
        $totalPrice += $op->price;
        $name = $product->name_uz ?: $product->name_ru ?: 'Product';
        echo "   ✅ {$name} | " . number_format($op->price, 0) . "\n";
    }
}

$order->price = $totalPrice;
$order->save(false);
echo "\n   Order Total: " . number_format($totalPrice, 0) . "\n\n";

// 4. Get user address
$address = \app\models\user\address\UserAddress::find()->where(['user_id' => $user->id])->one();
if ($address) {
    echo "   Address: {$address->address}\n";
} else {
    echo "   ⚠️  No address, using default\n";
}
echo "\n";

// 5. Create Didox Documents (Invoice only for E2E test)
echo "4. CREATING DIDOX INVOICE DOCUMENT...\n";
try {
    $result = \app\services\DidoxOrderService::createInvoice($order);

    echo "\n   Success: " . ($result['success'] ? '✅ YES' : '❌ NO') . "\n";
    echo "   Messages:\n";
    foreach ($result['messages'] as $msg) {
        echo "     - {$msg}\n";
    }
} catch (Exception $e) {
    echo "   Exception: " . $e->getMessage() . "\n";
}

// 6. Check created documents
echo "\n5. CREATED DOCUMENTS:\n";
$docs = \app\models\didox\DidoxDocument::find()
    ->where(['order_id' => $order->id])
    ->all();

foreach ($docs as $doc) {
    echo "   #{$doc->id} | {$doc->document_type} | didox_id: " . ($doc->didox_id ?: 'NULL') . " | status: {$doc->didox_status}\n";
    if ($doc->didox_signed_at) {
        echo "      Signed: {$doc->didox_signed_at}\n";
    }
    if ($doc->didox_error_data) {
        $err = json_decode($doc->didox_error_data, true);
        echo "      Error: " . ($err['error'] ?? 'N/A') . "\n";
    }
}

echo "\n=== E2E TEST COMPLETED ===\n";
