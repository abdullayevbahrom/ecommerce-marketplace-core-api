<?php
/**
 * Test: User #1 TIN ni 123456789 ga o'zgartirib autosign qilish
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_user1_tin_123456789.php
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

$NEW_TIN = '123456789';

echo "=== USER #1 TIN → {$NEW_TIN} AUTOSIGN TEST ===\n\n";

// 1. User #1 ni topish
echo "1. USER #1 NI TOPISH...\n";
$buyer = \app\models\user\User::findOne(1);
if (!$buyer) {
    die("   ❌ User #1 topilmadi!\n");
}

$oldTin = $buyer->eimzo_tax_id;
echo "   Name: {$buyer->lastname} {$buyer->name}\n";
echo "   Old TIN: {$oldTin}\n";

// 2. TIN ni o'zgartirish
echo "\n2. TIN NI {$NEW_TIN} GA O'ZGARTIRISH...\n";
$buyer->eimzo_tax_id = $NEW_TIN;
if ($buyer->save(false, ['eimzo_tax_id'])) {
    echo "   ✅ TIN yangilandi: {$oldTin} → {$NEW_TIN}\n";
} else {
    echo "   ❌ Xato: " . json_encode($buyer->errors) . "\n";
    exit(1);
}

// 3. Productlar
echo "\n3. PRODUCTLAR...\n";
$products = \app\models\product\Product::find()
    ->where(['not', ['ikpu_code' => null]])
    ->limit(2)
    ->all();

if (count($products) == 0) {
    $products = \app\models\product\Product::find()->limit(2)->all();
}

echo "   Topildi: " . count($products) . " ta\n";
foreach ($products as $p) {
    $name = $p->name_uz ?: $p->name_ru ?: $p->name_en ?: 'N/A';
    $price = $p->price ?: 5000000;
    echo "   - #{$p->id} | {$name} | " . number_format($price, 0) . "\n";
}
echo "\n";

// 4. Order yaratish
echo "4. ORDER YARATISH...\n";
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
    echo "   ❌ Xato: " . json_encode($order->errors) . "\n";
    exit(1);
}

// 5. Productlarni qo'shish
echo "\n5. PRODUCTLAR QO'SHISH...\n";
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
echo "\n   💰 Jami: " . number_format($totalPrice, 0) . "\n\n";

// 6. Invoice yaratish va auto-sign
echo "6. INVOICE YARATISH VA AUTO-SIGN...\n";
echo "   " . str_repeat('─', 55) . "\n";

try {
    $result = \app\services\DidoxOrderService::createInvoice($order);

    echo "\n   ✅ Success: " . ($result['success'] ? 'HA' : 'YO\'Q') . "\n";
    echo "   📋 Messages:\n";
    foreach ($result['messages'] as $msg) {
        $prefix = strpos($msg, 'failed') !== false || strpos($msg, 'Error') !== false || strpos($msg, 'Exception') !== false ? '⚠️ ' : '✅ ';
        echo "     {$prefix} {$msg}\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// 7. Yakuniy holat
echo "\n7. YAKUNIY HOLAT:\n";
echo "   " . str_repeat('─', 55) . "\n";

$docs = \app\models\didox\DidoxDocument::find()
    ->where(['order_id' => $order->id])
    ->all();

foreach ($docs as $doc) {
    echo "\n   📄 Document #{$doc->id} | {$doc->document_type}\n";
    echo "      didox_id:    " . ($doc->didox_id ?: 'NULL') . "\n";
    echo "      status:      {$doc->didox_status}";

    $statusLabels = [
        0 => 'Draft',
        1 => 'Waiting Partner',
        2 => 'Waiting Your Signature',
        3 => 'Signed',
        4 => 'Rejected',
        110 => 'Sent',
        120 => 'Canceled',
    ];
    echo " (" . ($statusLabels[$doc->didox_status] ?? 'Unknown') . ")\n";

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

// 8. TIN ni asl holiga qaytarish
echo "\n8. TIN NI ASL HOLIGA QAYTARISH ({$NEW_TIN} → {$oldTin})...\n";
$buyer->eimzo_tax_id = $oldTin;
if ($buyer->save(false, ['eimzo_tax_id'])) {
    echo "   ✅ TIN qaytarildi: {$oldTin}\n";
} else {
    echo "   ⚠️  TIN qaytarilmadi, qo'lda tekshiring!\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "=== TEST YAKUNLANDI ===\n";
echo str_repeat('=', 60) . "\n";
