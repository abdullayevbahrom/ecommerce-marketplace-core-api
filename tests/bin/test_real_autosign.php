<?php
/**
 * Real flow test: Order uchun Didox auto sign
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_real_autosign.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

// Console application - session kerak emas
$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

echo "=== Real Flow: Order → Didox Auto Sign Test ===\n\n";

// Eng yangi order ni olamiz
$order = \app\models\order\Order::find()->orderBy(['id' => SORT_DESC])->one();

if (!$order) {
    die("❌ Order topilmadi!\n");
}

echo "📦 Order ma'lumotlari:\n";
echo "   ID:        #{$order->id}\n";
echo "   User ID:   {$order->user_id}\n";
echo "   Narx:      " . number_format($order->price) . " so'm\n";
echo "   Date:      {$order->date}\n\n";

// Bu order uchun mavjud Didox hujjatlarni ko'ramiz
$existingDocs = \app\models\didox\DidoxDocument::find()
    ->where(['order_id' => $order->id])
    ->all();

echo "📋 Mavjud Didox hujjatlar:\n";
if (empty($existingDocs)) {
    echo "   (yo'q)\n\n";
} else {
    foreach ($existingDocs as $doc) {
        echo "   #{$doc->id} | {$doc->document_type} | didox_id: {$doc->didox_id} | status: {$doc->didox_status}\n";
    }
    echo "\n";
}

// Auto sign test - mavjud draft hujjatni sign qilamiz
$draftDoc = \app\models\didox\DidoxDocument::find()
    ->where(['didox_status' => 0])
    ->andWhere(['not', ['didox_id' => null]])
    ->orderBy(['id' => SORT_DESC])
    ->one();

if (!$draftDoc) {
    die("❌ Draft hujjat topilmadi!\n");
}

echo "📄 Test qilinadigan hujjat: #{$draftDoc->id} | {$draftDoc->document_type} | didox_id: {$draftDoc->didox_id}\n";
echo "🔄 Auto sign boshlanmoqda...\n\n";

// To'g'ridan-to'g'ri autoSignAndSendDocumentWithConfiguredPfx chaqiramiz
$didoxService = new \app\services\DidoxService();
$result = $didoxService->autoSignAndSendDocumentWithConfiguredPfx($draftDoc->didox_id);

echo "=== Natija ===\n";
echo "Success: " . ($result['success'] ? '✅ HA' : '❌ YO\'Q') . "\n";

if (!$result['success']) {
    echo "Error: " . ($result['error'] ?? 'Noma\'lum') . "\n";
    echo "Stage: " . ($result['stage'] ?? 'Noma\'lum') . "\n";
} else {
    echo "✅ Hujjat muvaffaqiyatli imzolandi va yuborildi!\n";

    // Refresh va tekshirish
    $draftDoc->refresh();
    echo "Yangi status: {$draftDoc->didox_status}\n";
    echo "Imzolangan vaqt: " . ($draftDoc->didox_signed_at ?? 'N/A') . "\n";
}

echo "\n=== Yakuniy Holat ===\n";
echo "Order:              #{$order->id}\n";
echo "Didox Documents:    " . count($existingDocs) . " ta\n";
echo "Auto Sign Natija:   " . ($result['success'] ? 'MUVAFFAQIYATLI ✅' : 'XATO ❌') . "\n";
