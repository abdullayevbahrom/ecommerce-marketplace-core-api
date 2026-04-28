<?php
/**
 * Real flow test: Order uchun Didox auto sign
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_real_autosign.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

$didoxService = new \app\services\DidoxService();
$draftDocs = \app\models\didox\DidoxDocument::find()
    ->where(['didox_status' => 0])
    ->andWhere(['>', 'id', 37])
    ->andWhere(['is not', 'didox_id', null])
    ->all();

foreach ($draftDocs as $draftDoc) {
    if (!$draftDoc) {
        die("❌ Draft hujjat topilmadi!\n");
    }
    $msg = "📄 Test qilinadigan hujjat: #{$draftDoc->id} | {$draftDoc->document_type} | didox_id: {$draftDoc->didox_id} | Hujjat statusi: {$draftDoc->didox_status} | Imzolangan vaqt: " . ($draftDoc->didox_signed_at ?? 'N/A') . "\n";

    $result = $didoxService->autoSignAndSendDocumentWithConfiguredPfx($draftDoc->didox_id);

    $msg .= "=== Natija ===\n";
    $msg .= "Success: " . ($result['success'] ? '✅ HA' : '❌ YO\'Q') . "\n";

    if (!$result['success']) {
        $msg .= "Error: " . ($result['error'] ?? 'Noma\'lum') . "\n";
        $msg .= "Stage: " . ($result['stage'] ?? 'Noma\'lum') . "\n";
        $msg .= "Debug Info: " . json_encode($result) . "\n";
    } else {
        $msg .= "✅ Hujjat muvaffaqiyatli imzolandi va yuborildi!\n";

        $draftDoc->refresh();
        $msg .= "Yangi status: {$draftDoc->didox_status}\n";
        $msg .= "Imzolangan vaqt: " . ($draftDoc->didox_signed_at ?? 'N/A') . "\n";
    }

    $msg .= "\n=== Yakuniy Holat ===\n";
    $msg .= "Auto Sign Natija:   " . ($result['success'] ? 'MUVAFFAQIYATLI ✅' : 'XATO ❌') . "\n";

    logToFile($msg);
}

function logToFile($message)
{
    $logFile = __DIR__ . '/autosign_test_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}
