<?php
/**
 * Real flow test: Order uchun Didox auto sign + DB update
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

$start = date('Y-m-d H:i:s');
logToFile("=== START autosign batch @ {$start} | docs: " . count($draftDocs) . " ===");

foreach ($draftDocs as $draftDoc) {
    if (!$draftDoc) {
        die("❌ Draft hujjat topilmadi!\n");
    }
    $msg = "📄 Test qilinadigan hujjat: #{$draftDoc->id} | {$draftDoc->document_type} | didox_id: {$draftDoc->didox_id} | Hujjat statusi: {$draftDoc->didox_status} | Imzolangan vaqt: " . ($draftDoc->didox_signed_at ?? 'N/A') . "\n";

    $result = $didoxService->autoSignAndSendDocumentWithConfiguredPfx($draftDoc->didox_id);
    $dbApply = applyAutoSignResultToDocument($draftDoc, $result);

    $msg .= "=== Natija ===\n";
    $msg .= "Success: " . ($result['success'] ? '✅ HA' : '❌ YO\'Q') . "\n";
    $msg .= "DB Update: " . ($dbApply['success'] ? '✅ HA' : '❌ YO\'Q') . "\n";
    if (!$dbApply['success']) {
        $msg .= "DB Error: " . ($dbApply['error'] ?? 'Noma\'lum') . "\n";
    }

    if (!$result['success']) {
        $msg .= "Error: " . ($result['error'] ?? 'Noma\'lum') . "\n";
        $msg .= "Stage: " . ($result['stage'] ?? 'Noma\'lum') . "\n";
        $msg .= "Debug Info: " . json_encode(
            sanitizeForLog($result),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . "\n";
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

logToFile("=== END autosign batch @ " . date('Y-m-d H:i:s') . " ===");

function applyAutoSignResultToDocument(\app\models\didox\DidoxDocument $doc, array $result): array
{
    try {
        if (!empty($result['success'])) {
            $existingData = $doc->getDidoxDataArray();

            // Merge best-effort response fragments into didox_data.
            if (!empty($result['sign_result']['data']) && is_array($result['sign_result']['data'])) {
                $existingData = array_merge($existingData, $result['sign_result']['data']);
            }
            if (!empty($result['send_result']['data']) && is_array($result['send_result']['data'])) {
                $existingData = array_merge($existingData, $result['send_result']['data']);
            }

            $statePayload = $result['document_state']['data'] ?? null;
            if (is_array($statePayload)) {
                $existingData = array_merge($existingData, $statePayload);
            }

            $doc->setDidoxData($existingData);

            $resolvedStatus = null;
            $stateDocument = extractDidoxDocumentState($result);
            if (is_array($stateDocument)) {
                if (isset($stateDocument['doc_status'])) {
                    $resolvedStatus = (int)$stateDocument['doc_status'];
                } elseif (isset($stateDocument['status'])) {
                    $resolvedStatus = (int)$stateDocument['status'];
                }
            }

            if ($resolvedStatus === null) {
                $resolvedStatus = \app\models\didox\DidoxDocument::STATUS_WAITING_PARTNER_SIGNATURE;
            }

            $doc->didox_status = $resolvedStatus;
            $doc->didox_signed_at = date('Y-m-d H:i:s');
            $doc->didox_last_attempt = date('Y-m-d H:i:s');
            $doc->clearDidoxErrors();
        } else {
            $errorPayload = [
                'operation' => 'auto_sign_send_test',
                'error_message' => $result['error'] ?? 'Unknown error',
                'stage' => $result['stage'] ?? null,
                'didox_id' => $doc->didox_id,
                'result' => sanitizeForLog($result),
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            // Keep Cyrillic readable in DB too.
            $doc->didox_error_data = json_encode(
                $errorPayload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $doc->didox_last_attempt = date('Y-m-d H:i:s');
        }

        if (!$doc->save(false)) {
            return ['success' => false, 'error' => 'save(false) failed'];
        }

        return ['success' => true];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function extractDidoxDocumentState(array $result): ?array
{
    $documentState = $result['document_state']['data'] ?? null;
    if (!is_array($documentState)) {
        return null;
    }

    if (isset($documentState['data']['document']) && is_array($documentState['data']['document'])) {
        return $documentState['data']['document'];
    }

    if (isset($documentState['document']) && is_array($documentState['document'])) {
        return $documentState['document'];
    }

    return $documentState;
}

function sanitizeForLog($value)
{
    if (is_array($value)) {
        $sanitized = [];
        foreach ($value as $k => $v) {
            if (in_array((string)$k, ['signature', 'request_body', 'pkcs7', 'pkcs7b64'], true) && is_string($v)) {
                $sanitized[$k] = shorten($v, 220);
                continue;
            }
            $sanitized[$k] = sanitizeForLog($v);
        }
        return $sanitized;
    }

    if (is_string($value)) {
        $decoded = decodeEscapedUnicode($value);
        $trimmed = trim($decoded);
        if ($trimmed !== '' && (($trimmed[0] === '{') || ($trimmed[0] === '['))) {
            $parsed = json_decode($trimmed, true);
            if (is_array($parsed)) {
                return sanitizeForLog($parsed);
            }
        }

        return shorten($decoded, 1200);
    }

    return $value;
}

function decodeEscapedUnicode(string $text): string
{
    if (strpos($text, '\\u') === false) {
        return $text;
    }

    return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function ($matches) {
        $decoded = json_decode('"\\u' . $matches[1] . '"');
        return is_string($decoded) ? $decoded : $matches[0];
    }, $text) ?? $text;
}

function shorten(string $text, int $maxLen): string
{
    if (strlen($text) <= $maxLen) {
        return $text;
    }

    return substr($text, 0, $maxLen) . '... [truncated]';
}

function logToFile($message)
{
    $logFile = __DIR__ . '/autosign_test_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}
