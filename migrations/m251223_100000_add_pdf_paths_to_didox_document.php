<?php

use yii\db\Migration;

/**
 * Adds pdf_paths column to didox_document table
 * This column stores JSON with paths to locally saved PDFs
 * Example: {"uz": "uploads/didox/abc123_uz.pdf", "ru": "uploads/didox/abc123_ru.pdf"}
 * 
 * Manual SQL (run in phpMyAdmin or MySQL client):
 * 
 * -- UP (add column):
 * ALTER TABLE `didox_document` ADD COLUMN `pdf_paths` TEXT NULL COMMENT 'JSON with paths to locally saved PDFs' AFTER `didox_error_data`;
 * 
 * -- DOWN (remove column):
 * ALTER TABLE `didox_document` DROP COLUMN `pdf_paths`;
 * 
 * -- After running UP, also add to migration table to mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m251223_100000_add_pdf_paths_to_didox_document', UNIX_TIMESTAMP());
 * 
 */
class m251223_100000_add_pdf_paths_to_didox_document extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('didox_document', 'pdf_paths', $this->text()->null()->after('didox_error_data')->comment('JSON with paths to locally saved PDFs'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('didox_document', 'pdf_paths');
    }
}
