<?php

use yii\db\Migration;

/**
 * Expands didox_error_data column to LONGTEXT to handle large error messages
 * This fixes: SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'didox_error_data'
 */
class m250128_170000_expand_didox_error_data_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Expand didox_error_data column to LONGTEXT
        $this->alterColumn('{{%didox_document}}', 'didox_error_data', $this->text()->comment('DIDOX API error data (JSON)'));
        
        // For MySQL, explicitly set to LONGTEXT to handle large content
        if ($this->db->driverName === 'mysql') {
            $this->execute("ALTER TABLE {{%didox_document}} MODIFY COLUMN `didox_error_data` LONGTEXT COMMENT 'DIDOX API error data (JSON)'");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert back to regular TEXT (this may cause data loss if large content exists)
        $this->alterColumn('{{%didox_document}}', 'didox_error_data', $this->text()->comment('DIDOX API error data (JSON)'));
    }
}

/*
Manual SQL commands for this migration:

-- Up migration (expand column):
ALTER TABLE `didox_document` MODIFY COLUMN `didox_error_data` LONGTEXT COMMENT 'DIDOX API error data (JSON)';

-- Down migration (revert to TEXT - may cause data loss):
ALTER TABLE `didox_document` MODIFY COLUMN `didox_error_data` TEXT COMMENT 'DIDOX API error data (JSON)';

-- Check current column definition:
SHOW COLUMNS FROM `didox_document` LIKE 'didox_error_data';

-- Check column size limits:
-- TEXT: up to 65,535 bytes (64 KB)
-- LONGTEXT: up to 4,294,967,295 bytes (4 GB)
*/ 