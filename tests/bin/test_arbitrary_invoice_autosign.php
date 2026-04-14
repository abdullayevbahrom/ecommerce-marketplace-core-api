<?php
/**
 * Test: Arbitrary va Invoice auto sign flow
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_arbitrary_invoice_autosign.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

echo "=== Arbitrary va Invoice Auto Sign Test ===\n\n";

// 1. Eng yangi arbitrary document ni topamiz
$arbitraryDoc = \app\models\didox\DidoxDocument::find()
    ->where(['document_type' => 'arbitrary'])
    ->andWhere(['not', ['didox_id' => null]])
    ->orderBy(['id' => SORT_DESC])
    ->one();

echo "📄 ARBITRARY DOCUMENT:\n";
if (!$arbitraryDoc) {
    echo "   ❌ Arbitrary document topilmadi!\n\n";
} else {
    echo "   ID:         #{$arbitraryDoc->id}\n";
    echo "   Order ID:   #{$arbitraryDoc->order_id}\n";
    echo "   didox_id:   {$arbitraryDoc->didox_id}\n";
    echo "   Status:     {$arbitraryDoc->didox_status}";

    $statusLabels = [
        0 => 'Draft',
        1 => 'Waiting Partner',
        2 => 'Waiting Your Signature',
        3 => 'Signed',
        4 => 'Rejected',
    ];
    echo " (" . ($statusLabels[$arbitraryDoc->didox_status] ?? 'Unknown') . ")\n";
    echo "   Created:    {$arbitraryDoc->didox_created_at}\n";
    echo "   Signed:     " . ($arbitraryDoc->didox_signed_at ?? 'N/A') . "\n";

    // Didox data ni ko'ramiz
    if ($arbitraryDoc->didox_data) {
        $data = json_decode($arbitraryDoc->didox_data, true);
        $docInfo = $data['data']['document'] ?? ($data['document'] ?? null);
        if ($docInfo) {
            echo "\n   📊 Didox API State:\n";
            echo "      doc_status: " . ($docInfo['doc_status'] ?? 'N/A') . "\n";
            echo "      doc_status_label: " . ($docInfo['doc_status_label'] ?? 'N/A') . "\n";
            echo "      sender: " . ($docInfo['sender'] ?? 'N/A') . "\n";
            echo "      recipient: " . ($docInfo['recipient'] ?? 'N/A') . "\n";
        }
    }
    echo "\n";
}

// 2. Eng yangi invoice document ni topamiz
$invoiceDoc = \app\models\didox\DidoxDocument::find()
    ->where(['document_type' => 'invoice'])
    ->andWhere(['not', ['didox_id' => null]])
    ->orderBy(['id' => SORT_DESC])
    ->one();

echo "\n📄 INVOICE DOCUMENT:\n";
if (!$invoiceDoc) {
    echo "   ❌ Invoice document topilmadi!\n\n";
} else {
    echo "   ID:         #{$invoiceDoc->id}\n";
    echo "   Order ID:   #{$invoiceDoc->order_id}\n";
    echo "   didox_id:   {$invoiceDoc->didox_id}\n";
    echo "   Status:     {$invoiceDoc->didox_status}";
    echo " (" . ($statusLabels[$invoiceDoc->didox_status] ?? 'Unknown') . ")\n";
    echo "   Created:    {$invoiceDoc->didox_created_at}\n";
    echo "   Signed:     " . ($invoiceDoc->didox_signed_at ?? 'N/A') . "\n";

    // Didox data ni ko'ramiz
    if ($invoiceDoc->didox_data) {
        $data = json_decode($invoiceDoc->didox_data, true);
        $docInfo = $data['data']['document'] ?? ($data['document'] ?? null);
        if ($docInfo) {
            echo "\n   📊 Didox API State:\n";
            echo "      doc_status: " . ($docInfo['doc_status'] ?? 'N/A') . "\n";
            echo "      doc_status_label: " . ($docInfo['doc_status_label'] ?? 'N/A') . "\n";
            echo "      sender: " . ($docInfo['sender'] ?? 'N/A') . "\n";
            echo "      recipient: " . ($docInfo['recipient'] ?? 'N/A') . "\n";
        }
    }
    echo "\n";
}

// 3. Auto sign test - arbitrary
if ($arbitraryDoc) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "🔄 ARBITRARY AUTO SIGN BOSHLANMOQDA...\n";
    echo str_repeat('=', 60) . "\n\n";

    $didoxService = new \app\services\DidoxService();
    $result = $didoxService->autoSignAndSendDocumentWithConfiguredPfx($arbitraryDoc->didox_id);

    echo "📝 NATIJA:\n";
    echo "   Success: " . ($result['success'] ? '✅ HA' : '❌ YO\'Q') . "\n";
    echo "   Stage: " . ($result['stage'] ?? 'N/A') . "\n";

    if (!$result['success']) {
        echo "   Error: " . ($result['error'] ?? 'Noma\'lum') . "\n";

        // Debug info
        if (!empty($result['didox_sign_result'])) {
            echo "   DIDOX Sign Result Debug:\n";
            $signRes = $result['didox_sign_result'];
            if (!empty($signRes['debug'])) {
                echo "      " . json_encode($signRes['debug'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
            }
            echo "      httpCode: " . ($signRes['httpCode'] ?? 'N/A') . "\n";
            echo "      data: " . json_encode($signRes['data'] ?? 'N/A', JSON_UNESCAPED_SLASHES) . "\n";
        }

        if (!empty($result['debug'])) {
            echo "   Debug: " . json_encode($result['debug'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "   ✅ Arbitrary muvaffaqiyatli imzolandi va yuborildi!\n";
        if (!empty($result['document_state'])) {
            $finalDoc = $result['document_state']['data']['data']['document']
                ?? ($result['document_state']['data']['document'] ?? null);
            if ($finalDoc) {
                echo "   Final Status: " . ($finalDoc['doc_status'] ?? 'N/A') . "\n";
                echo "   Final Label: " . ($finalDoc['doc_status_label'] ?? 'N/A') . "\n";
            }
        }
    }

    // Refresh va tekshirish
    $arbitraryDoc->refresh();
    echo "\n📊 DATABASE YANGILANDI:\n";
    echo "   didox_status: {$arbitraryDoc->didox_status}";
    echo " (" . ($statusLabels[$arbitraryDoc->didox_status] ?? 'Unknown') . ")\n";
    echo "   didox_signed_at: " . ($arbitraryDoc->didox_signed_at ?? 'N/A') . "\n";
    if ($arbitraryDoc->didox_error_data) {
        echo "   didox_error_data: " . $arbitraryDoc->didox_error_data . "\n";
    }
}

// 4. Auto sign test - invoice
if ($invoiceDoc) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "🔄 INVOICE AUTO SIGN BOSHLANMOQDA...\n";
    echo str_repeat('=', 60) . "\n\n";

    $didoxService = new \app\services\DidoxService();
    $result = $didoxService->autoSignAndSendDocumentWithConfiguredPfx($invoiceDoc->didox_id);

    echo "📝 NATIJA:\n";
    echo "   Success: " . ($result['success'] ? '✅ HA' : '❌ YO\'Q') . "\n";
    echo "   Stage: " . ($result['stage'] ?? 'N/A') . "\n";

    if (!$result['success']) {
        echo "   Error: " . ($result['error'] ?? 'Noma\'lum') . "\n";

        // Debug info
        if (!empty($result['didox_sign_result'])) {
            echo "   DIDOX Sign Result Debug:\n";
            $signRes = $result['didox_sign_result'];
            if (!empty($signRes['debug'])) {
                echo "      " . json_encode($signRes['debug'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
            }
            echo "      httpCode: " . ($signRes['httpCode'] ?? 'N/A') . "\n";
            echo "      data: " . json_encode($signRes['data'] ?? 'N/A', JSON_UNESCAPED_SLASHES) . "\n";
        }

        if (!empty($result['debug'])) {
            echo "   Debug: " . json_encode($result['debug'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "   ✅ Invoice muvaffaqiyatli imzolandi va yuborildi!\n";
        if (!empty($result['document_state'])) {
            $finalDoc = $result['document_state']['data']['data']['document']
                ?? ($result['document_state']['data']['document'] ?? null);
            if ($finalDoc) {
                echo "   Final Status: " . ($finalDoc['doc_status'] ?? 'N/A') . "\n";
                echo "   Final Label: " . ($finalDoc['doc_status_label'] ?? 'N/A') . "\n";
            }
        }
    }

    // Refresh va tekshirish
    $invoiceDoc->refresh();
    echo "\n📊 DATABASE YANGILANDI:\n";
    echo "   didox_status: {$invoiceDoc->didox_status}";
    echo " (" . ($statusLabels[$invoiceDoc->didox_status] ?? 'Unknown') . ")\n";
    echo "   didox_signed_at: " . ($invoiceDoc->didox_signed_at ?? 'N/A') . "\n";
    if ($invoiceDoc->didox_error_data) {
        echo "   didox_error_data: " . $invoiceDoc->didox_error_data . "\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "=== TEST YAKUNLANDI ===\n";
echo str_repeat('=', 60) . "\n";
