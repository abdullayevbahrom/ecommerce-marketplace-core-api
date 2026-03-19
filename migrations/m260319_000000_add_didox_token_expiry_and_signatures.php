<?php

use yii\db\Migration;

/**
 * 1. Add eimzo_didox_token_expires_at to user table (token expiry tracking)
 * 2. Create didox_document_signatures table (store signatures for app-originated docs)
 * 3. Add unique index on eimzo_tax_id (prevent duplicate users)
 *
 * Manual SQL:
 *
 * -- UP:
 * ALTER TABLE `user` ADD COLUMN `eimzo_didox_token_expires_at` DATETIME NULL AFTER `eimzo_didox_token`;
 *
 * -- Unique index (allows NULLs)
 * ALTER TABLE `user` ADD UNIQUE INDEX `idx_user_eimzo_tax_id_unique` (`eimzo_tax_id`);
 *
 * CREATE TABLE `didox_document_signatures` (
 *   `id` INT AUTO_INCREMENT PRIMARY KEY,
 *   `didox_document_id` INT NOT NULL,
 *   `user_id` INT NOT NULL,
 *   `signer_tax_id` VARCHAR(20) NOT NULL,
 *   `signer_name` VARCHAR(255) NULL,
 *   `signature_data` LONGTEXT NOT NULL,
 *   `signature_type` ENUM('sign','accept','reject') DEFAULT 'sign',
 *   `comment` TEXT NULL,
 *   `signed_at` DATETIME NOT NULL,
 *   `certificate_serial` VARCHAR(100) NULL,
 *   `certificate_valid_from` DATETIME NULL,
 *   `certificate_valid_to` DATETIME NULL,
 *   `didox_response` TEXT NULL,
 *   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   INDEX `idx_didox_sig_doc` (`didox_document_id`),
 *   INDEX `idx_didox_sig_user` (`user_id`),
 *   CONSTRAINT `fk_didox_sig_doc` FOREIGN KEY (`didox_document_id`) REFERENCES `didox_document`(`id`) ON DELETE CASCADE,
 *   CONSTRAINT `fk_didox_sig_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * -- DOWN:
 * DROP TABLE IF EXISTS `didox_document_signatures`;
 * ALTER TABLE `user` DROP INDEX `idx_user_eimzo_tax_id_unique`;
 * ALTER TABLE `user` DROP COLUMN `eimzo_didox_token_expires_at`;
 *
 * -- After running UP, mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m260319_000000_add_didox_token_expiry_and_signatures', UNIX_TIMESTAMP());
 */
class m260319_000000_add_didox_token_expiry_and_signatures extends Migration
{
    public function safeUp()
    {
        // 1. Token expiry tracking
        $this->addColumn('user', 'eimzo_didox_token_expires_at', $this->dateTime()->null()->after('eimzo_didox_token'));

        // 2. Unique index on eimzo_tax_id (MySQL allows multiple NULLs in unique index)
        $this->createIndex('idx_user_eimzo_tax_id_unique', 'user', 'eimzo_tax_id', true);

        // 3. Signatures table
        $this->createTable('didox_document_signatures', [
            'id' => $this->primaryKey(),
            'didox_document_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'signer_tax_id' => $this->string(20)->notNull(),
            'signer_name' => $this->string(255)->null(),
            'signature_data' => 'LONGTEXT NOT NULL',
            'signature_type' => "ENUM('sign','accept','reject') DEFAULT 'sign'",
            'comment' => $this->text()->null(),
            'signed_at' => $this->dateTime()->notNull(),
            'certificate_serial' => $this->string(100)->null(),
            'certificate_valid_from' => $this->dateTime()->null(),
            'certificate_valid_to' => $this->dateTime()->null(),
            'didox_response' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('idx_didox_sig_doc', 'didox_document_signatures', 'didox_document_id');
        $this->createIndex('idx_didox_sig_user', 'didox_document_signatures', 'user_id');

        $this->addForeignKey(
            'fk_didox_sig_doc',
            'didox_document_signatures', 'didox_document_id',
            'didox_document', 'id',
            'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            'fk_didox_sig_user',
            'didox_document_signatures', 'user_id',
            'user', 'id',
            'CASCADE', 'CASCADE'
        );

        // Backfill: set token expiry for users that have a didox token
        $this->update('user', [
            'eimzo_didox_token_expires_at' => date('Y-m-d H:i:s', strtotime('+350 minutes')),
        ], ['IS NOT', 'eimzo_didox_token', null]);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_didox_sig_user', 'didox_document_signatures');
        $this->dropForeignKey('fk_didox_sig_doc', 'didox_document_signatures');
        $this->dropTable('didox_document_signatures');
        $this->dropIndex('idx_user_eimzo_tax_id_unique', 'user');
        $this->dropColumn('user', 'eimzo_didox_token_expires_at');
    }
}
