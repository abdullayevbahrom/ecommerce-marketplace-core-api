<?php
/**
 * Sync all database DIDOX documents with API status
 * Run: docker exec shop-app-1 php /var/www/html/tests/bin/sync_didox_documents.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

echo "=== DIDOX Document Database Sync ===\n\n";

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
$userKey = $authRes['token'] ?? '';

if (!$userKey) {
    die("❌ Failed to authenticate with PFX!\n");
}

echo "✅ Authenticated (user-key: " . substr($userKey, 0, 8) . "...)\n\n";

$statusLabels = [
    0 => 'DRAFT',
    1 => 'WAITING PARTNER',
    2 => 'WAITING YOUR SIGNATURE',
    3 => 'SIGNED',
    4 => 'REJECTED',
    5 => 'DELETED',
    6 => 'WAITING AGENT',
    8 => 'SIGNED BY AGENT',
    40 => 'INVALID',
    55 => 'DRAFT DELETED',
    60 => 'WAITING AGENT 2',
    110 => 'SENT',
    120 => 'CANCELED',
    130 => 'REJECTED BY RESPONSIBLE',
    140 => 'ACCEPTED BY RESPONSIBLE',
    150 => 'RETURNED BY RESPONSIBLE',
    160 => 'DELIVERED',
    190 => 'RETURNED BY RESPONSIBLE 2',
];

$stats = [
    'total' => count($docs),
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
    'api_not_found' => 0,
];

echo "🔄 Syncing documents...\n\n";

foreach ($docs as $doc) {
    $oldStatus = $doc->didox_status;
    $oldStatusLabel = $statusLabels[$oldStatus] ?? 'UNKNOWN';
    
    echo "  #{$doc->id} | {$doc->document_type} | didox_id: {$doc->didox_id}\n";
    echo "    DB Status: {$oldStatus} ({$oldStatusLabel})\n";
    
    // Fetch from API
    $apiRes = $svc->getDocumentForSigning($doc->didox_id, $userKey);
    
    if (!$apiRes['success']) {
        echo "    ❌ API Error: Document not found or error\n";
        $stats['api_not_found']++;
        echo "\n";
        continue;
    }
    
    $apiDoc = $apiRes['data']['data']['document'] ?? $apiRes['data']['document'] ?? null;
    
    if (!$apiDoc) {
        echo "    ❌ API Error: Invalid response structure\n";
        $stats['errors']++;
        echo "\n";
        continue;
    }
    
    $apiStatus = $apiDoc['doc_status'] ?? $apiDoc['status'] ?? null;
    
    if ($apiStatus === null) {
        echo "    ❌ API Error: No status found\n";
        $stats['errors']++;
        echo "\n";
        continue;
    }
    
    $apiStatusLabel = $statusLabels[$apiStatus] ?? 'UNKNOWN';
    echo "    API Status: {$apiStatus} ({$apiStatusLabel})\n";
    
    // Check if status changed
    if ($oldStatus == $apiStatus) {
        echo "    ✅ Up to date\n";
        $stats['skipped']++;
    } else {
        // Update database
        $doc->didox_status = (int)$apiStatus;
        
        // If signed, set signed_at
        if ($apiStatus >= 3 && empty($doc->didox_signed_at)) {
            $doc->didox_signed_at = date('Y-m-d H:i:s');
        }
        
        // Update didox_data with latest API response
        $doc->setDidoxData($apiRes['data']);
        $doc->didox_error_data = null;
        $doc->save(false);
        
        echo "    🔄 UPDATED: {$oldStatus} → {$apiStatus}\n";
        $stats['updated']++;
    }
    
    echo "\n";
}

// Summary
echo str_repeat('=', 60) . "\n";
echo "=== SYNC SUMMARY ===\n";
echo str_repeat('=', 60) . "\n";
echo "Total Documents:     {$stats['total']}\n";
echo "Updated:             {$stats['updated']} ✅\n";
echo "Skipped (up to date): {$stats['skipped']}\n";
echo "API Errors:          {$stats['api_not_found']} ❌\n";
echo "Other Errors:        {$stats['errors']} ❌\n";
echo str_repeat('=', 60) . "\n";

if ($stats['updated'] > 0) {
    echo "\n✅ Successfully synced {$stats['updated']} document(s)!\n";
} else {
    echo "\n✅ All documents are already in sync!\n";
}
