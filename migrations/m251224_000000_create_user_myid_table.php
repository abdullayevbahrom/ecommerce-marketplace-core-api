<?php

use yii\db\Migration;

/**
 * Creates table for MyID user verification data
 * 
 * Manual SQL (run in phpMyAdmin or MySQL client):
 * 
 * -- UP (create table):
 * CREATE TABLE `user_myid` (
 *     `id` INT(11) NOT NULL AUTO_INCREMENT,
 *     `user_id` INT(11) NULL COMMENT 'Foreign key to user.id',
 *     `pinfl` VARCHAR(14) NOT NULL COMMENT 'Personal ID number (unique)',
 *     `passport_series` VARCHAR(2) NULL COMMENT 'Passport series (e.g., AA)',
 *     `passport_number` VARCHAR(7) NULL COMMENT 'Passport number',
 *     `first_name` VARCHAR(100) NULL COMMENT 'First name from passport',
 *     `last_name` VARCHAR(100) NULL COMMENT 'Last name from passport',
 *     `middle_name` VARCHAR(100) NULL COMMENT 'Middle name (patronymic)',
 *     `birth_date` DATE NULL COMMENT 'Date of birth',
 *     `gender` TINYINT(1) NULL COMMENT '1=Male, 2=Female',
 *     `nationality` VARCHAR(50) NULL COMMENT 'Nationality',
 *     `birth_place` VARCHAR(255) NULL COMMENT 'Birth place',
 *     `living_address` VARCHAR(255) NULL COMMENT 'Current address',
 *     `photo` LONGTEXT NULL COMMENT 'Base64 encoded photo',
 *     `sdk_hash` VARCHAR(64) NULL COMMENT 'SDK hash for verification',
 *     `verification_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=verified, 2=failed, 3=expired',
 *     `verified_at` DATETIME NULL COMMENT 'Verification timestamp',
 *     `myid_response` LONGTEXT NULL COMMENT 'Full JSON response for audit',
 *     `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
 *     `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *     PRIMARY KEY (`id`),
 *     UNIQUE KEY `idx_user_myid_pinfl` (`pinfl`),
 *     KEY `idx_user_myid_user_id` (`user_id`),
 *     KEY `idx_user_myid_verification_status` (`verification_status`),
 *     CONSTRAINT `fk_user_myid_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MyID verification data';
 * 
 * -- Add myid_verified column to user table:
 * ALTER TABLE `user` ADD COLUMN `myid_verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=not verified, 1=verified' AFTER `status`;
 * CREATE INDEX `idx_user_myid_verified` ON `user` (`myid_verified`);
 * 
 * -- DOWN (drop table):
 * DROP INDEX `idx_user_myid_verified` ON `user`;
 * ALTER TABLE `user` DROP COLUMN `myid_verified`;
 * DROP TABLE IF EXISTS `user_myid`;
 * 
 * -- After running UP, also add to migration table to mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m251224_000000_create_user_myid_table', UNIX_TIMESTAMP());
 * 
 */
class m251224_000000_create_user_myid_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create user_myid table
        $this->createTable('{{%user_myid}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->null()->comment('Foreign key to user.id'),
            'pinfl' => $this->string(14)->notNull()->comment('Personal ID number (unique)'),
            'passport_series' => $this->string(2)->null()->comment('Passport series (e.g., AA)'),
            'passport_number' => $this->string(7)->null()->comment('Passport number'),
            'first_name' => $this->string(100)->null()->comment('First name from passport'),
            'last_name' => $this->string(100)->null()->comment('Last name from passport'),
            'middle_name' => $this->string(100)->null()->comment('Middle name (patronymic)'),
            'birth_date' => $this->date()->null()->comment('Date of birth'),
            'gender' => $this->tinyInteger(1)->null()->comment('1=Male, 2=Female'),
            'nationality' => $this->string(50)->null()->comment('Nationality'),
            'birth_place' => $this->string(255)->null()->comment('Birth place'),
            'living_address' => $this->string(255)->null()->comment('Current address'),
            'photo' => 'LONGTEXT NULL COMMENT "Base64 encoded photo"',
            'sdk_hash' => $this->string(64)->null()->comment('SDK hash for verification'),
            'verification_status' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('0=pending, 1=verified, 2=failed, 3=expired'),
            'verified_at' => $this->dateTime()->null()->comment('Verification timestamp'),
            'myid_response' => 'LONGTEXT NULL COMMENT "Full JSON response for audit"',
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="MyID verification data"');

        // Create indexes
        $this->createIndex('idx_user_myid_pinfl', '{{%user_myid}}', 'pinfl', true);
        $this->createIndex('idx_user_myid_user_id', '{{%user_myid}}', 'user_id');
        $this->createIndex('idx_user_myid_verification_status', '{{%user_myid}}', 'verification_status');

        // Add foreign key
        $this->addForeignKey(
            'fk_user_myid_user',
            '{{%user_myid}}',
            'user_id',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Add myid_verified column to user table
        $this->addColumn('{{%user}}', 'myid_verified', $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('0=not verified, 1=verified')->after('status'));
        $this->createIndex('idx_user_myid_verified', '{{%user}}', 'myid_verified');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop index and column from user table
        $this->dropIndex('idx_user_myid_verified', '{{%user}}');
        $this->dropColumn('{{%user}}', 'myid_verified');

        // Drop foreign key and table
        $this->dropForeignKey('fk_user_myid_user', '{{%user_myid}}');
        $this->dropTable('{{%user_myid}}');
    }
}
