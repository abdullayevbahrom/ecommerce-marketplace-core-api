<?php
/**
 * Clean up old DIDOX documents that no longer exist in API
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/cleanup_old_didox_docs.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

echo "=== DIDOX Document Cleanup ===\n\n";

// Get all documents with didox_id
$docs = \app\models\didox\DidoxDocument::find()
    ->where(['not', ['didox_id' => null]])
    ->orderBy(['id' => SORT_ASC])
    ->all();

if (empty($docs)) {
    die("❌ No documents found with didox_id!\n");
}

echo "📊 Found " . count($docs) . " documents with didox_id\n\n";

// Init DIDOX service
$svc = new \app\services\DidoxService();
$authRes = $svc->getAuthTokenFromPfx();
if (empty($authRes['success']) || empty($authRes['token'])) {
    die("❌ Failed to authenticate with PFX!\n");
}

$userKey = $authRes['token'];

echo "✅ Authenticated (user-key: " . substr($userKey, 0, 8) . "...)\n\n";

// Collect IDs to delete
$toDelete = [];
$toKeep = [];

echo "🔍 Checking documents...\n\n";

foreach ($docs as $doc) {
    echo "  #{$doc->id} | {$doc->document_type} | didox_id: {$doc->didox_id} | created: {$doc->didox_created_at}\n";

    // Fetch from API
    $apiRes = $svc->getDocumentForSigning($doc->didox_id, $userKey);

    if (!$apiRes['success']) {
        echo "    ❌ NOT FOUND in API\n";
        $toDelete[] = $doc;
    } else {
        $apiDoc = $apiRes['data']['data']['document'] ?? $apiRes['data']['document'] ?? null;
        if ($apiDoc) {
            $status = $apiDoc['doc_status'] ?? $apiDoc['status'] ?? 'N/A';
            echo "    ✅ EXISTS in API (status: {$status})\n";
            $toKeep[] = $doc;
        } else {
            echo "    ❌ Invalid API response\n";
            $toDelete[] = $doc;
        }
    }

    echo "\n";
}

// Summary
echo str_repeat('=', 60) . "\n";
echo "=== CLEANUP SUMMARY ===\n";
echo str_repeat('=', 60) . "\n";
echo "Total Documents:     " . count($docs) . "\n";
echo "To KEEP:             " . count($toKeep) . " ✅\n";
echo "To DELETE:           " . count($toDelete) . " ❌\n";
echo str_repeat('=', 60) . "\n\n";

if (empty($toDelete)) {
    echo "✅ No documents to delete!\n";
    exit(0);
}

// Confirm deletion
echo "⚠️  The following documents will be DELETED:\n\n";
foreach ($toDelete as $doc) {
    echo "   #{$doc->id} | {$doc->document_type} | didox_id: {$doc->didox_id} | created: {$doc->didox_created_at}\n";
}

echo "\n";

// Delete documents
echo "🗑️  Deleting documents...\n\n";
$deletedCount = 0;

foreach ($toDelete as $doc) {
    echo "  Deleting #{$doc->id} | {$doc->document_type}... ";

    // Also delete related records
    $didoxInvoice = \app\models\didox\DidoxDocumentInvoice::find()
        ->where(['document_id' => $doc->id])
        ->one();
    if ($didoxInvoice) {
        $didoxInvoice->delete();
    }

    $didoxArbitrary = \app\models\didox\DidoxDocumentArbitrary::find()
        ->where(['document_id' => $doc->id])
        ->one();
    if ($didoxArbitrary) {
        $didoxArbitrary->delete();
    }

    $didoxProducts = \app\models\didox\DidoxDocumentIncludedProducts::findAll(['document_id' => $doc->id]);
    foreach ($didoxProducts as $product) {
        $product->delete();
    }

    // Delete main document
    if ($doc->delete()) {
        echo "✅ DELETED\n";
        $deletedCount++;
    } else {
        echo "❌ FAILED\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "=== CLEANUP COMPLETE ===\n";
echo str_repeat('=', 60) . "\n";
echo "Deleted:             {$deletedCount} documents ✅\n";
echo "Remaining:           " . (count($docs) - $deletedCount) . " documents\n";
echo str_repeat('=', 60) . "\n";
