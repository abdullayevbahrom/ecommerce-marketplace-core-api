<?php
/**
 * Test: User #511 bilan autosign (TIN o'zgartirmasdan)
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_user511_autosign_no_tin_change.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';

class DummySession extends \yii\web\Session {
    public function open() {}
    public function get($key, $default = null) { return $default; }
    public function set($key, $value) {}
    public function has($key) { return false; }
    public function remove($key) {}
}

$config['components']['session'] = ['class' => 'DummySession'];
$config['components']['user'] = [
    'class' => 'yii\web\User',
    'identityClass' => 'app\models\user\User',
    'enableAutoLogin' => false,
    'enableSession' => false,
];

$app = new yii\console\Application($config);

$USER_ID = 511;

echo "=== USER #{$USER_ID} AUTOSIGN TEST (NO TIN CHANGE) ===\n\n";

// 1. User ni topish
echo "1. USER NI TOPISH...\n";
$buyer = \app\models\user\User::findOne($USER_ID);
if (!$buyer) {
    die("   ❌ User #{$USER_ID} topilmadi!\n");
}

echo "   ID: {$buyer->id}\n";
echo "   Name: {$buyer->lastname} {$buyer->name}\n";
echo "   Phone: {$buyer->phone}\n";
echo "   Current TIN: " . ($buyer->eimzo_tax_id ?: 'NULL') . "\n\n";

// 2. Productlar
echo "2. PRODUCTLAR...\n";
$products = \app\models\product\Product::find()
    ->where(['not', ['ikpu_code' => null]])
    ->limit(2)
    ->all();

if (count($products) === 0) {
    $products = \app\models\product\Product::find()->limit(2)->all();
}

if (count($products) === 0) {
    die("   ❌ Product topilmadi!\n");
}

echo "   Topildi: " . count($products) . " ta\n";
foreach ($products as $p) {
    $name = $p->name_uz ?: $p->name_ru ?: $p->name_en ?: 'N/A';
    $price = $p->price ?: 5000000;
    echo "   - #{$p->id} | {$name} | " . number_format($price, 0) . "\n";
}
echo "\n";

// 3. Order yaratish
echo "3. ORDER YARATISH...\n";
$order = new \app\models\order\Order();
$order->user_id = $buyer->id;
$order->price = 0;
$order->status = 1;
$order->date = date('Y-m-d H:i:s');
$order->address = 'Test Address Toshkent';
$order->delivery_id = 1;

if ($order->save()) {
    echo "   ✅ Order #{$order->id} yaratildi\n";
} else {
    echo "   ❌ Xato: " . json_encode($order->errors, JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

// 4. Productlarni qo'shish
echo "\n4. PRODUCTLAR QO'SHISH...\n";
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
    } else {
        echo "   ❌ OrderProduct error: " . json_encode($op->errors, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

$order->price = $totalPrice;
$order->save(false);
echo "\n   💰 Jami: " . number_format($totalPrice, 0) . "\n\n";

// 5. Invoice yaratish va auto-sign
echo "5. INVOICE YARATISH VA AUTO-SIGN...\n";
echo "   " . str_repeat('─', 55) . "\n";

try {
    $result = \app\services\DidoxOrderService::createInvoice($order);

    echo "\n   ✅ Success: " . (!empty($result['success']) ? 'HA' : 'YO\'Q') . "\n";
    echo "   📋 Messages:\n";
    foreach (($result['messages'] ?? []) as $msg) {
        $prefix = (strpos($msg, 'failed') !== false || strpos($msg, 'Error') !== false || strpos($msg, 'Exception') !== false) ? '⚠️' : '✅';
        echo "     {$prefix} {$msg}\n";
    }
} catch (\Throwable $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// 6. Yakuniy holat
echo "\n6. YAKUNIY HOLAT:\n";
echo "   " . str_repeat('─', 55) . "\n";

$docs = \app\models\didox\DidoxDocument::find()
    ->where(['order_id' => $order->id])
    ->all();

$statusLabels = [
    0 => 'Draft',
    1 => 'Waiting Partner',
    2 => 'Waiting Your Signature',
    3 => 'Signed',
    4 => 'Rejected',
    110 => 'Sent',
    120 => 'Canceled',
];

foreach ($docs as $doc) {
    echo "\n   📄 Document #{$doc->id} | {$doc->document_type}\n";
    echo "      didox_id:    " . ($doc->didox_id ?: 'NULL') . "\n";
    echo "      status:      {$doc->didox_status} (" . ($statusLabels[$doc->didox_status] ?? 'Unknown') . ")\n";

    if ($doc->didox_signed_at) {
        echo "      signed_at:   {$doc->didox_signed_at} ✅\n";
    }
    if ($doc->didox_error_data) {
        $err = json_decode($doc->didox_error_data, true);
        $errorMsg = $err['details']['didox_sign_result']['data']['data']['message']
            ?? ($err['error'] ?? 'N/A');
        echo "      error:       {$errorMsg} ⚠️\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "=== TEST YAKUNLANDI ===\n";
echo str_repeat('=', 60) . "\n";

