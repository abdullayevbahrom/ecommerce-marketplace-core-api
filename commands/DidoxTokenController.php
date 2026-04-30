<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use app\services\DidoxService;
use yii\helpers\Console;

/**
 * Didox Token Management Console Commands
 * 
 * Usage:
 *   php yii didox-token/refresh     - Refresh token immediately
 *   php yii didox-token/status      - Check token status
 *   php yii didox-token/enable      - Enable auto-refresh
 *   php yii didox-token/disable     - Disable auto-refresh
 *   php yii didox-token/cleanup     - Cleanup invalid auto-refresh state
 */
class DidoxTokenController extends Controller
{
    /**
     * @var bool Enable verbose output
     */
    public $verbose = false;
    
    /**
     * @var bool Force refresh even if token not expired
     */
    public $force = false;
    
    public function options($actionID)
    {
        return ['verbose', 'force'];
    }
    
    public function optionAliases()
    {
        return [
            'v' => 'verbose',
            'f' => 'force',
        ];
    }

    /**
     * Refresh Didox token using configured PFX
     * 
     * Examples:
     *   php yii didox-token/refresh
     *   php yii didox-token/refresh --verbose
     *   php yii didox-token/refresh --force
     * 
     * @return int Exit code (0 = success, 1 = error)
     */
    public function actionRefresh()
    {
        $this->stdout("Didox Token Refresh - " . date('Y-m-d H:i:s') . "\n", $this->verbose ? Console::FG_GREY : null);
        
        $service = new DidoxService();
        
        // Check if token needs refresh (unless forced)
        if (!$this->force) {
            $status = $service->getTokenStatus();
            
            if ($this->verbose) {
                $this->stdout("Current status:\n");
                $this->stdout("  Token exists: " . ($status['has_token'] ? 'Yes' : 'No') . "\n");
                $this->stdout("  Expires at: " . ($status['expires_at'] ?? 'N/A') . "\n");
                $this->stdout("  Expired: " . ($status['is_expired'] ? 'Yes' : 'No') . "\n");
                $this->stdout("  Expiring soon: " . ($status['expiring_soon'] ? 'Yes' : 'No') . "\n");
            }
            
            if (!$status['is_expired'] && !$status['expiring_soon']) {
                $this->stdout("Token is still valid (expires in {$status['expires_in']}). Use --force to refresh anyway.\n", Console::FG_YELLOW);
                return 0;
            }
            
            $this->stdout("Token needs refresh. Proceeding...\n", Console::FG_BLUE);
        } else {
            $this->stdout("Force refresh enabled.\n", Console::FG_YELLOW);
        }
        
        // Perform refresh
        $result = $service->refreshAndStoreToken(true);
        
        if ($result['success']) {
            $this->stdout("✓ Token refreshed successfully!\n", Console::FG_GREEN);
            $this->stdout("  Token: " . substr($result['token'], 0, 20) . "...\n");
            $this->stdout("  Expires at: {$result['expires_at']}\n");
            $this->stdout("  Tax ID: {$result['tax_id']}\n");
            
            // Log success
            Yii::info('Token refreshed successfully via console command', __METHOD__);
            
            // Send notification if configured
            $this->notifySuccess($result);
            
            return 0;
        } else {
            if (!empty($result['skipped'])) {
                $this->stdout("! Token refresh skipped.\n", Console::FG_YELLOW);
                $this->stdout("  Message: {$result['error']}\n", Console::FG_YELLOW);
                return 0;
            }

            $this->stderr("✗ Token refresh failed!\n", Console::FG_RED);
            $this->stderr("  Error: {$result['error']}\n");

            // Force-persist diagnostics to settings even if service-level write failed.
            $this->persistAutoRefreshErrorFallback($result);
            
            // Log error
            Yii::error('Token refresh failed: ' . $result['error'], __METHOD__);
            
            // Send alert
            $this->notifyError($result['error']);
            
            return 1;
        }
    }
    
    /**
     * Check current token status
     * 
     * Example:
     *   php yii didox-token/status
     * 
     * @return int Exit code (0 = valid, 1 = expired/invalid, 2 = error)
     */
    public function actionStatus()
    {
        $service = new DidoxService();
        $status = $service->getTokenStatus();
        
        $this->stdout("Didox Token Status\n");
        $this->stdout("==================\n");
        $this->stdout("Has Token:        " . ($status['has_token'] ? 'Yes' : 'No') . "\n");
        $this->stdout("Status:           {$status['status']}\n");
        $this->stdout("Seller INN:       " . ($status['seller_inn'] ?: 'Not set') . "\n");
        $this->stdout("Last Login:       " . ($status['last_login'] ?: 'Never') . "\n");
        $this->stdout("Expires At:       " . ($status['expires_at'] ?: 'N/A') . "\n");
        $this->stdout("Time Remaining:   " . ($status['expires_in'] ?: 'N/A') . "\n");
        $this->stdout("Is Expired:       " . ($status['is_expired'] ? 'Yes' : 'No') . "\n");
        $this->stdout("Expiring Soon:    " . ($status['expiring_soon'] ? 'Yes' : 'No') . "\n");
        
        if ($status['last_attempt']) {
            $this->stdout("Last Attempt:     {$status['last_attempt']}\n");
        }
        
        if ($status['last_error']) {
            $this->stderr("Last Error:       {$status['last_error']}\n", Console::FG_RED);
        }
        
        $this->stdout("\n");
        
        // Return appropriate exit code
        if ($status['is_expired']) {
            return 1;
        }
        if ($status['expiring_soon']) {
            return 2; // Warning - expiring soon
        }
        return 0;
    }
    
    /**
     * Enable automatic token refresh
     * 
     * Example:
     *   php yii didox-token/enable
     * 
     * @return int Exit code
     */
    public function actionEnable()
    {
        $service = new DidoxService();
        $pfxValidation = $service->validateConfiguredPfx();
        if (!$pfxValidation['success']) {
            $message = $service->cleanupInvalidAutoRefreshState($pfxValidation['error']);
            $this->stdout("! {$message}\n", Console::FG_YELLOW);
            return 0;
        }

        $this->updateSetting('didox_auto_refresh_status', 'active');
        
        $this->stdout("✓ Auto-refresh enabled.\n", Console::FG_GREEN);
        $this->stdout("Token will be automatically refreshed every 3 hours.\n");
        $this->stdout("\nCRON setup:\n");
        $this->stdout("  0 */3 * * * php " . \Yii::getAlias('@app') . "/yii didox-token/refresh\n\n");
        
        return 0;
    }
    
    /**
     * Disable automatic token refresh
     * 
     * Example:
     *   php yii didox-token/disable
     * 
     * @return int Exit code
     */
    public function actionDisable()
    {
        $service = new DidoxService();
        $this->updateSetting('didox_auto_refresh_status', 'disabled');
        
        $this->stdout("✓ Auto-refresh disabled.\n", Console::FG_YELLOW);
        $this->stdout("Token will NOT be automatically refreshed.\n");
        $this->stdout("You must refresh manually using: php yii didox-token/refresh\n");
        
        return 0;
    }

    /**
     * Cleanup stale auto-refresh state when PFX is missing or invalid
     *
     * @return int
     */
    public function actionCleanup()
    {
        $service = new DidoxService();
        $status = $service->getTokenStatus();
        $pfxValidation = $service->validateConfiguredPfx();

        if ($pfxValidation['success']) {
            $this->stdout("PFX configuration is valid. Cleanup not required.\n", Console::FG_GREEN);
            return 0;
        }

        if (!in_array(($status['status'] ?? 'manual'), ['active', 'failed'], true)) {
            $this->stdout("No stale auto-refresh state found.\n", Console::FG_YELLOW);
            $this->stdout("Message: {$pfxValidation['error']}\n", Console::FG_YELLOW);
            return 0;
        }

        $message = $service->cleanupInvalidAutoRefreshState($pfxValidation['error']);
        $this->stdout("Cleanup completed.\n", Console::FG_GREEN);
        $this->stdout("Message: {$message}\n", Console::FG_YELLOW);

        return 0;
    }
    
    /**
     * Manual token refresh (alias for actionRefresh with verbose output)
     * 
     * Example:
     *   php yii didox-token/manual
     * 
     * @return int
     */
    public function actionManual()
    {
        $this->verbose = true;
        return $this->actionRefresh();
    }
    
    /**
     * Test PFX authentication without saving
     * 
     * Example:
     *   php yii didox-token/test
     * 
     * @return int
     */
    public function actionTest()
    {
        $this->stdout("Testing PFX authentication...\n");
        $this->stdout("" . date('Y-m-d H:i:s') . "\n\n");
        
        $service = new DidoxService();
        $settings = $service->getTokenStatus();
        
        $this->stdout("Seller INN: {$settings['seller_inn']}\n\n");
        
        // Try to get token (without saving)
        $result = $service->getAuthTokenFromPfx();
        
        if ($result['success']) {
            $this->stdout("✓ PFX authentication test PASSED!\n", Console::FG_GREEN);
            $this->stdout("  Token: " . substr($result['token'], 0, 30) . "...\n");
            $this->stdout("  Tax ID: {$result['taxId']}\n");
            return 0;
        } else {
            $this->stderr("✗ PFX authentication test FAILED!\n", Console::FG_RED);
            $this->stderr("  Error: {$result['error']}\n");
            return 1;
        }
    }
    
    /**
     * Update a setting value
     * 
     * @param string $type
     * @param string $content
     * @return bool
     */
    private function updateSetting(string $type, string $content): bool
    {
        try {
            $model = \app\models\Settings::findOne(['type' => $type]);
            if (!$model) {
                $model = new \app\models\Settings();
                $model->type = $type;
            }

            $model->content = $content;
            $model->date = date('Y-m-d H:i:s');
            if ($model->save(false)) {
                return true;
            }

            $now = date('Y-m-d H:i:s');
            Yii::$app->db->createCommand()->upsert('settings', [
                'type' => $type,
                'content' => $content,
                'date' => $now,
            ], [
                'content' => $content,
                'date' => $now,
            ])->execute();
            return true;
        } catch (\Throwable $e) {
            Yii::error("DidoxTokenController updateSetting failed for {$type}: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private function persistAutoRefreshErrorFallback(array $result): void
    {
        try {
            $payload = (string)($result['error'] ?? 'Unknown token refresh error');
            if (!empty($result['refresh_debug']) && is_array($result['refresh_debug'])) {
                $payload .= "\n\n--- stage ---\n" . ($result['refresh_debug']['stage'] ?? 'unknown');
                $payload .= "\n\n--- request_url ---\n" . ($result['refresh_debug']['request_url'] ?? 'N/A');
                $payload .= "\n\n--- request_body ---\n" . ($result['refresh_debug']['request_body'] ?? 'N/A');
                $payload .= "\n\n--- response ---\n" . ($result['refresh_debug']['response'] ?? 'N/A');
            }
            $payload .= "\n\n--- full_result ---\n" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $this->updateSetting('didox_auto_refresh_status', 'failed');
            $this->updateSetting('didox_auto_refresh_last_attempt', date('Y-m-d H:i:s'));
            $this->updateSetting('didox_auto_refresh_error', $payload);
        } catch (\Throwable $e) {
            Yii::error('Failed to persist auto-refresh error fallback: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Send success notification (if telegram configured)
     * 
     * @param array $result
     */
    private function notifySuccess(array $result): void
    {
        try {
            if (Yii::$app->has('telegram')) {
                $message = "✅ Didox Token Refreshed\n\n";
                $message .= "Tax ID: {$result['tax_id']}\n";
                $message .= "Expires: {$result['expires_at']}\n";
                $message .= "Time: " . date('Y-m-d H:i:s');
                
                Yii::$app->telegram->sendMessage($message);
            }
        } catch (\Throwable $e) {
            // Silently fail notifications
            Yii::warning('Failed to send telegram notification: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Send error notification
     * 
     * @param string $error
     */
    private function notifyError(string $error): void
    {
        try {
            if (Yii::$app->has('telegram')) {
                $message = "⚠️ Didox Token Refresh Failed!\n\n";
                $message .= "Error: {$error}\n";
                $message .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
                $message .= "Please check the system immediately.";
                
                Yii::$app->telegram->sendMessage($message);
            }
        } catch (\Throwable $e) {
            // Silently fail notifications
        }
    }
}
