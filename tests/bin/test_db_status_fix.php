<?php
/**
 * Test: Auto sign flow database status fix
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_db_status_fix.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

echo "=== Auto Sign Database Status Fix Test ===\n\n";

// 1. Eng yangi draft document ni topamiz
$draftDoc = \app\models\didox\DidoxDocument::find()
    ->where(['didox_status' => 0])
    ->andWhere(['not', ['didox_id' => null]])
    ->orderBy(['id' => SORT_DESC])
    ->one();

if (!$draftDoc) {
    die("❌ Draft document topilmadi!\n");
}

echo "📄 DOCUMENT BEFORE SIGN:\n";
echo "   ID:         #{$draftDoc->id}\n";
echo "   Type:       {$draftDoc->document_type}\n";
echo "   didox_id:   {$draftDoc->didox_id}\n";
echo "   DB Status:  {$draftDoc->didox_status}\n";
echo "   Created:    {$draftDoc->didox_created_at}\n\n";

// 2. API status ni tekshiramiz
$svc = new \app\services\DidoxService();
$authRes = $svc->getAuthTokenFromPfx();
$userKey = $authRes['token'];

$apiRes = $svc->getDocumentForSigning($draftDoc->didox_id, $userKey);
$apiDoc = $apiRes['data']['data']['document'] ?? $apiRes['data']['document'] ?? null;
$apiStatus = $apiDoc['doc_status'] ?? 'N/A';

echo "📡 API STATUS:\n";
echo "   API Status: {$apiStatus}";
if ($apiStatus == 0) echo " (DRAFT)";
elseif ($apiStatus == 1) echo " (WAITING PARTNER)";
elseif ($apiStatus == 3) echo " (SIGNED)";
echo "\n\n";

if ($apiStatus != 0) {
    echo "⚠️  Document API da allaqachon imzolangan! Boshqa draft qidiramiz...\n\n";
    
    // Boshqa draft qidirish
    $draftDoc = \app\models\didox\DidoxDocument::find()
        ->where(['didox_status' => 0])
        ->andWhere(['not', ['didox_id' => null]])
        ->andWhere(['!=', 'id', $draftDoc->id])
        ->orderBy(['id' => SORT_DESC])
        ->one();
    
    if (!$draftDoc) {
        die("❌ Boshqa draft document topilmadi!\n");
    }
    
    echo "📄 YANGI DOCUMENT:\n";
    echo "   ID:         #{$draftDoc->id}\n";
    echo "   Type:       {$draftDoc->document_type}\n";
    echo "   didox_id:   {$draftDoc->didox_id}\n";
    echo "   DB Status:  {$draftDoc->didox_status}\n\n";
    
    $apiRes = $svc->getDocumentForSigning($draftDoc->didox_id, $userKey);
    $apiDoc = $apiRes['data']['data']['document'] ?? $apiRes['data']['document'] ?? null;
    $apiStatus = $apiDoc['doc_status'] ?? 'N/A';
    
    echo "📡 API STATUS:\n";
    echo "   API Status: {$apiStatus}";
    if ($apiStatus == 0) echo " (DRAFT)";
    elseif ($apiStatus == 1) echo " (WAITING PARTNER)";
    elseif ($apiStatus == 3) echo " (SIGNED)";
    echo "\n\n";
}

if ($apiStatus != 0) {
    die("❌ Ikkala document ham API da draft emas! Test o'tkazib yuborildi.\n");
}

// 3. Auto sign
echo "🔄 AUTO SIGN BOSHLANMOQDA...\n\n";
$result = $svc->autoSignAndSendDocumentWithConfiguredPfx($draftDoc->didox_id);

echo "📝 AUTO SIGN RESULT:\n";
echo "   Success: " . ($result['success'] ? '✅ YES' : '❌ NO') . "\n";
echo "   Stage: " . ($result['stage'] ?? 'N/A') . "\n";

if (!$result['success']) {
    echo "   Error: " . ($result['error'] ?? 'N/A') . "\n";
    exit(1);
}

// 4. Database yangilanganmi?
$draftDoc->refresh();

echo "\n📊 DATABASE AFTER SIGN:\n";
echo "   DB Status:  {$draftDoc->didox_status}";
if ($draftDoc->didox_status == 0) echo " (DRAFT)";
elseif ($draftDoc->didox_status == 1) echo " (WAITING PARTNER)";
elseif ($draftDoc->didox_status == 3) echo " (SIGNED)";
echo "\n";
echo "   Signed At:  " . ($draftDoc->didox_signed_at ?? 'N/A') . "\n";

// 5. Verify
echo "\n=== VERIFY ===\n";
if ($draftDoc->didox_status == $apiStatus) {
    echo "✅ SUCCESS! Database status ({$draftDoc->didox_status}) = API status ({$apiStatus})\n";
} else {
    echo "❌ FAILED! Database status ({$draftDoc->didox_status}) != API status ({$apiStatus})\n";
}

echo "\n=== TEST YAKUNLANDI ===\n";
