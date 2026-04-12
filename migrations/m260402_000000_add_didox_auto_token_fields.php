<?php

use yii\db\Migration;

/**
 * Add fields for automatic Didox token refresh functionality
 * - didox_token_expires_at: token expiration datetime
 * - didox_auto_refresh_status: active/failed/manual/disabled
 * - didox_auto_refresh_error: last error message
 * - didox_auto_refresh_last_attempt: last attempt timestamp
 */
class m260402_000000_add_didox_auto_token_fields extends Migration
{
    public function safeUp()
    {
        // Add token expiry tracking to settings table (using existing settings infrastructure)
        // We'll use new setting types for this
        $settingsTypes = [
            'didox_token_expires_at',
            'didox_auto_refresh_status', 
            'didox_auto_refresh_error',
            'didox_auto_refresh_last_attempt'
        ];
        
        // Insert default settings if they don't exist
        $existing = \app\models\Settings::find()
            ->where(['type' => $settingsTypes])
            ->column();
        
        $now = date('Y-m-d H:i:s');
        $missingTypes = array_diff($settingsTypes, $existing);
        
        foreach ($missingTypes as $type) {
            $defaultContent = '';
            if ($type === 'didox_auto_refresh_status') {
                $defaultContent = 'manual'; // manual, active, failed, disabled
            }
            
            $this->insert('settings', [
                'type' => $type,
                'content' => $defaultContent,
                'date' => $now,
            ]);
        }
        
        // Also add column to user table as backup tracking (if not exists from previous migration)
        $tableSchema = $this->db->schema->getTableSchema('user');
        if (!$tableSchema->getColumn('eimzo_didox_token_expires_at')) {
            $this->addColumn('user', 'eimzo_didox_token_expires_at', $this->dateTime()->null()->after('eimzo_didox_token'));
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->schema->getTableSchema('user');
        if ($tableSchema->getColumn('eimzo_didox_token_expires_at')) {
            $this->dropColumn('user', 'eimzo_didox_token_expires_at');
        }
        
        // Remove auto refresh settings
        $this->delete('settings', ['type' => [
            'didox_token_expires_at',
            'didox_auto_refresh_status',
            'didox_auto_refresh_error', 
            'didox_auto_refresh_last_attempt'
        ]]);
    }
}
