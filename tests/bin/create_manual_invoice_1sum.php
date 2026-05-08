<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/web.php';
new yii\web\Application($config);

$doc = app\models\didox\DidoxDocument::findOne(336);
if (!$doc) {
    echo "DOC_NOT_FOUND\n";
    exit(1);
}

$payload = $doc->generateDidoxApiStructure();
$payload['doctype'] = $doc->didox_doc_type ?: '002';

if (isset($payload['ProductList']['Products']) && is_array($payload['ProductList']['Products']) && count($payload['ProductList']['Products']) > 0) {
    foreach ($payload['ProductList']['Products'] as &$p) {
        $p['Summa'] = '0';
        $p['DeliverySum'] = '0.00';
        $p['VatRate'] = '0';
        $p['VatSum'] = '0.00';
        $p['DeliverySumWithVat'] = '0.00';
    }
    unset($p);

    $payload['ProductList']['Products'][0]['Count'] = '1';
    $payload['ProductList']['Products'][0]['Summa'] = '1';
    $payload['ProductList']['Products'][0]['DeliverySum'] = '1.00';
    $payload['ProductList']['Products'][0]['VatRate'] = '0';
    $payload['ProductList']['Products'][0]['VatSum'] = '0.00';
    $payload['ProductList']['Products'][0]['DeliverySumWithVat'] = '1.00';
    $payload['ProductList']['HasVat'] = false;
}

$svc = new app\services\DidoxService();
$auth = $svc->authenticateWithConfiguredPfx('312463074');
if (empty($auth['success']) || empty($auth['token'])) {
    echo 'AUTH_FAIL ' . json_encode($auth, JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

$upload = $svc->createDocument($payload, $auth['token']);
if (empty($upload['success'])) {
    echo 'UPLOAD_FAIL ' . json_encode($upload, JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

$didoxRootId = $upload['data']['_id']
    ?? $upload['data']['data']['_id']
    ?? $upload['data']['pending_document']['_id']
    ?? null;
$didoxFacturaId = $upload['data']['pending_document']['document_json']['facturaid']
    ?? $upload['data']['data']['pending_document']['document_json']['facturaid']
    ?? null;
$newDidoxId = $didoxRootId
    ?? $upload['data']['data']['document']['doc_id']
    ?? $upload['data']['data']['document']['id']
    ?? $upload['data']['data']['id']
    ?? $didoxFacturaId
    ?? null;

if (!$newDidoxId) {
    echo 'NO_DOC_ID ' . json_encode($upload, JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

$doc->didox_id = $newDidoxId;
$doc->didox_status = 0;
$doc->didox_signed_at = null;
$doc->didox_error_data = null;
$doc->setDidoxData($upload['data']);
$doc->save(false);

$check = $svc->getDocumentForSigning($newDidoxId, $auth['token']);
$summa = null;
if (!empty($check['success'])) {
    $summa = $check['data']['data']['json']['productlist']['products'][0]['summa'] ?? null;
}

echo json_encode([
    'success' => true,
    'document_id' => $doc->id,
    'order_id' => $doc->order_id,
    'didox_id' => $doc->didox_id,
    'didox_root_id' => $didoxRootId,
    'didox_factura_id' => $didoxFacturaId,
    'didox_status' => $doc->didox_status,
    'first_line_summa' => $summa,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
