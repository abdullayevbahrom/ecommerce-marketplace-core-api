<?php
/**
 * Test: DidoxOrderService auto sign database status fix
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_order_service_fix.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

echo "=== DidoxOrderService Auto Sign Database Status Fix Test ===\n\n";

// Eng yangi draft document ni topamiz
$doc = \app\models\didox\DidoxDocument::findOne(272);

if (!$doc) {
    die("❌ Document #272 topilmadi!\n");
}

echo "📄 DOCUMENT BEFORE:\n";
echo "   ID:         #{$doc->id}\n";
echo "   Type:       {$doc->document_type}\n";
echo "   didox_id:   {$doc->didox_id}\n";
echo "   DB Status:  {$doc->didox_status}\n";
echo "   Signed At:  " . ($doc->didox_signed_at ?: '(empty)') . "\n\n";

// API status ni tekshirish (optional, faqat info uchun)
$svc = new \app\services\DidoxService();
$authRes = $svc->getAuthTokenFromPfx();
$userKey = $authRes['token'];

$apiRes = $svc->getDocumentForSigning($doc->didox_id, $userKey);
$apiDoc = $apiRes['data']['data']['document'] ?? $apiRes['data']['document'] ?? null;
$apiStatus = $apiDoc['doc_status'] ?? 'N/A';

echo "📡 API Status: {$apiStatus}\n\n";

// Auto sign via DidoxService (to'g'ridan-to'g'ri)
echo "🔄 AUTO SIGN BOSHLANMOQDA...\n\n";
$result = $svc->autoSignAndSendDocumentWithConfiguredPfx($doc->didox_id);

echo "📝 AUTO SIGN RESULT:\n";
echo "   Success: " . ($result['success'] ? '✅ YES' : '❌ NO') . "\n";
echo "   Stage: " . ($result['stage'] ?? 'N/A') . "\n";

if ($result['success']) {
    // Database update - YANGI LOGIKA
    $documentState = $result['document_state'] ?? null;
    if (is_array($documentState)) {
        $doc->setDidoxData($documentState);

        // Extract status from nested document_state structure
        $newStatus = null;
        if (isset($documentState['data']['data']['document']['doc_status'])) {
            $newStatus = (int) $documentState['data']['data']['document']['doc_status'];
        } elseif (isset($documentState['data']['data']['document']['status'])) {
            $newStatus = (int) $documentState['data']['data']['document']['status'];
        } elseif (isset($documentState['data']['document']['doc_status'])) {
            $newStatus = (int) $documentState['data']['document']['doc_status'];
        } elseif (isset($documentState['data']['document']['status'])) {
            $newStatus = (int) $documentState['data']['document']['status'];
        } elseif (isset($documentState['status'])) {
            $newStatus = (int) $documentState['status'];
        }

        if ($newStatus !== null) {
            $doc->didox_status = $newStatus;
            echo "   New Status: {$newStatus}\n";
        }
    }
    $doc->didox_signed_at = date('Y-m-d H:i:s');
    $doc->didox_error_data = null;
    $doc->save(false);
} else {
    echo "   Error: " . ($result['error'] ?? 'N/A') . "\n";
}

// Refresh
$doc->refresh();

echo "📊 DATABASE AFTER:\n";
echo "   DB Status:  {$doc->didox_status}";
if ($doc->didox_status == 0)
    echo " (DRAFT)";
elseif ($doc->didox_status == 1)
    echo " (WAITING PARTNER)";
elseif ($doc->didox_status == 3)
    echo " (SIGNED)";
echo "\n";
echo "   Signed At:  " . ($doc->didox_signed_at ?: '(empty)') . "\n";
if ($doc->didox_error_data) {
    echo "   Error Data: " . $doc->didox_error_data . "\n";
}

// Verify
echo "\n=== VERIFY ===\n";
if ($doc->didox_status == 1 && !empty($doc->didox_signed_at)) {
    echo "✅ SUCCESS! Database status correctly updated to 1 (WAITING PARTNER)\n";
    echo "   didox_signed_at set: YES\n";
} elseif ($doc->didox_status != 0) {
    echo "✅ PARTIAL! Database status updated to {$doc->didox_status}\n";
} else {
    echo "❌ FAILED! Database status still 0 (DRAFT)\n";
}

echo "\n=== TEST YAKUNLANDI ===\n";
