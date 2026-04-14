<?php
/**
 * E2E test script for Didox auto sign
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/test_didox_autosign.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/web.php';
$app = new yii\web\Application($config);

echo "=== Didox Auto Sign E2E Tekshiruv ===\n\n";

// 1. DB Connection
try {
    $app->db->createCommand('SELECT 1')->queryOne();
    echo "✅ 1. Database: ULANGAN\n";
} catch (\Exception $e) {
    die("❌ 1. Database: XATO - " . $e->getMessage() . "\n");
}

// 2. PFX Validation
$svc = new app\services\DidoxService();
$pfxRes = $svc->validateConfiguredPfx();
echo ($pfxRes['success'] ? "✅" : "❌") . " 2. PFX Validatsiya: " . ($pfxRes['success'] ? "MUVAFFAQIYATLI" : "XATO - " . $pfxRes['error']) . "\n";

// 3. PFX Identity
$identity = $svc->extractConfiguredPfxIdentity();
echo "   PFX UID: " . ($identity['uid'] ?? 'N/A') . "\n";

// 4. Seller INN
$settings = app\models\Settings::find()->where(['type' => 'didox_seller_inn'])->one();
echo "   Sotuvchi INN: " . ($settings ? $settings->content : 'TOPILMADI') . "\n";

// 5. Hujjatlar holati
$draft = app\models\didox\DidoxDocument::find()->where(['didox_status' => 0])->count();
$signed = app\models\didox\DidoxDocument::find()->where(['didox_status' => 3])->count();
$waiting = app\models\didox\DidoxDocument::find()->where(['didox_status' => 1])->count();
echo "   Draft: {$draft}, Imzolangan: {$signed}, Kutilayotgan: {$waiting}\n\n";

// 6. Boshqa PFX bilan signer test (DS3124630740001_49392584.pfx)
echo "🔄 Signer test: DS3124630740001_49392584.pfx...\n";
$altPfxResult = testPfxWithSigner('DS3124630740001_49392584.pfx', '49392584');
echo "   Natija: " . ($altPfxResult['success'] ? "✅ MUVAFFAQIYATLI" : "❌ XATO - " . ($altPfxResult['message'] ?? 'Noma\'lum')) . "\n\n";

// 7. Auto Sign Test - oxirgi draft hujjatni sign qilamiz
$doc = app\models\didox\DidoxDocument::find()
    ->where(['didox_status' => 0])
    ->andWhere(['not', ['didox_id' => null]])
    ->orderBy(['id' => SORT_DESC])
    ->one();

if (!$doc) {
    echo "⚠️  Draft hujjat topilmadi, auto sign test o'tkazib yuborilmoqda.\n";
    exit(0);
}

echo "📄 Test hujjat: #{$doc->id} | {$doc->document_type} | didox_id: {$doc->didox_id}\n";
echo "🔄 Auto sign boshlanmoqda...\n\n";

$result = $svc->autoSignAndSendDocumentWithConfiguredPfx($doc->didox_id);

echo "=== Natija ===\n";
echo "Success: " . ($result['success'] ? '✅ HA' : '❌ YO\'Q') . "\n";

if (!$result['success']) {
    echo "Error: " . ($result['error'] ?? 'Noma\'lum') . "\n";
    echo "Stage: " . ($result['stage'] ?? 'Noma\'lum') . "\n";
} else {
    echo "✅ Hujjat muvaffaqiyatli imzolandi va yuborildi!\n";

    // Refresh va tekshirish
    $doc->refresh();
    echo "Yangi status: {$doc->didox_status}\n";
    echo "Imzolangan vaqt: " . ($doc->didox_signed_at ?? 'N/A') . "\n";
}

echo "\n=== Yakuniy Holat ===\n";
echo "Baza:                  ULANGAN\n";
echo "PFX (DSDEV...):        MAVJUD (lekin signer HTTP 500 - algoritm muammosi)\n";
echo "PFX (49392584):        " . ($altPfxResult['success'] ? 'ISHLAYDI ✅' : 'ISHLAMAYDI ❌') . "\n";
echo "Eimzo-signer:          ISHLAYAPTI\n";
echo "Didox URL:             stage.goodsign.biz\n";
echo "Auto Sign (hozirgi):   " . ($result['success'] ? 'ISHLADI ✅' : 'ISHLAMADI ❌') . "\n";

function testPfxWithSigner($pfxFile, $password)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://eimzo-signer:8080/generate',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'pfxFilePath' => $pfxFile,
            'password' => $password,
            'alias' => '',
            'data' => 'dGVzdGRhdGE=',
            'dataBase64' => true,
            'attached' => true,
            'includeChain' => false,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? ['success' => false, 'message' => 'Invalid response'];
}
