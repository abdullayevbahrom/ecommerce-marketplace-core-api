<?php

use yii\db\Migration;

/**
 * Expand settings.content from VARCHAR(255) to TEXT
 * so long Didox refresh error diagnostics can be persisted.
 */
class m260501_130000_expand_settings_content_to_text extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('settings', true);
        if ($table === null) {
            return;
        }

        $column = $table->getColumn('content');
        if ($column === null) {
            return;
        }

        // Use TEXT to store full request/response/error payloads.
        $this->alterColumn('settings', 'content', $this->text()->null());
    }

    public function safeDown()
    {
        // Best-effort rollback. Data longer than 255 chars would be truncated by DB.
        $this->alterColumn('settings', 'content', $this->string(255)->null());
    }
}
