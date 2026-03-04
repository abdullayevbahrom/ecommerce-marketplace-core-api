<?php

use yii\db\Migration;

/**
 * Adds new columns to user_myid for MyID SDK New Flow support.
 *
 * Manual SQL (run in phpMyAdmin or MySQL client):
 *
 * -- UP:
 * ALTER TABLE `user_myid`
 *     ADD COLUMN `first_name_en` VARCHAR(100) NULL COMMENT 'First name (Latin)' AFTER `middle_name`,
 *     ADD COLUMN `last_name_en` VARCHAR(100) NULL COMMENT 'Last name (Latin)' AFTER `first_name_en`,
 *     ADD COLUMN `citizenship` VARCHAR(50) NULL COMMENT 'Citizenship' AFTER `nationality`,
 *     ADD COLUMN `passport_issued_by` VARCHAR(500) NULL COMMENT 'Passport issued by' AFTER `passport_number`,
 *     ADD COLUMN `passport_issued_date` VARCHAR(20) NULL COMMENT 'Passport issued date' AFTER `passport_issued_by`,
 *     ADD COLUMN `passport_expiry_date` VARCHAR(20) NULL COMMENT 'Passport expiry date' AFTER `passport_issued_date`,
 *     ADD COLUMN `doc_type` VARCHAR(50) NULL COMMENT 'Document type' AFTER `passport_expiry_date`,
 *     ADD COLUMN `permanent_address` VARCHAR(500) NULL COMMENT 'Permanent address' AFTER `living_address`,
 *     ADD COLUMN `temporary_address` VARCHAR(500) NULL COMMENT 'Temporary address' AFTER `permanent_address`,
 *     ADD COLUMN `phone` VARCHAR(20) NULL COMMENT 'Phone from MyID' AFTER `temporary_address`,
 *     ADD COLUMN `email` VARCHAR(100) NULL COMMENT 'Email from MyID' AFTER `phone`,
 *     ADD COLUMN `comparison_value` DECIMAL(4,3) NULL COMMENT 'Face comparison score (0.000-1.000)' AFTER `email`,
 *     ADD COLUMN `job_id` VARCHAR(255) NULL COMMENT 'MyID job UUID' AFTER `comparison_value`,
 *     ADD COLUMN `reuid` VARCHAR(255) NULL COMMENT 'Reusable unique ID for secondary requests' AFTER `sdk_hash`,
 *     ADD COLUMN `reuid_expires_at` INT(11) NULL COMMENT 'Reuid expiration timestamp' AFTER `reuid`,
 *     MODIFY COLUMN `birth_place` VARCHAR(500) NULL COMMENT 'Birth place',
 *     MODIFY COLUMN `sdk_hash` VARCHAR(255) NULL COMMENT 'SDK tracking hash';
 *
 * -- DOWN:
 * ALTER TABLE `user_myid`
 *     DROP COLUMN `first_name_en`,
 *     DROP COLUMN `last_name_en`,
 *     DROP COLUMN `citizenship`,
 *     DROP COLUMN `passport_issued_by`,
 *     DROP COLUMN `passport_issued_date`,
 *     DROP COLUMN `passport_expiry_date`,
 *     DROP COLUMN `doc_type`,
 *     DROP COLUMN `permanent_address`,
 *     DROP COLUMN `temporary_address`,
 *     DROP COLUMN `phone`,
 *     DROP COLUMN `email`,
 *     DROP COLUMN `comparison_value`,
 *     DROP COLUMN `job_id`,
 *     DROP COLUMN `reuid`,
 *     DROP COLUMN `reuid_expires_at`;
 *
 * -- After running UP, mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m260303_000000_update_user_myid_for_sdk_new_flow', UNIX_TIMESTAMP());
 */
class m260303_000000_update_user_myid_for_sdk_new_flow extends Migration
{
    public function safeUp()
    {
        // Latin name fields
        $this->addColumn('{{%user_myid}}', 'first_name_en', $this->string(100)->null()->comment('First name (Latin)')->after('middle_name'));
        $this->addColumn('{{%user_myid}}', 'last_name_en', $this->string(100)->null()->comment('Last name (Latin)')->after('first_name_en'));

        // Citizenship
        $this->addColumn('{{%user_myid}}', 'citizenship', $this->string(50)->null()->comment('Citizenship')->after('nationality'));

        // Extended passport fields
        $this->addColumn('{{%user_myid}}', 'passport_issued_by', $this->string(500)->null()->comment('Passport issued by')->after('passport_number'));
        $this->addColumn('{{%user_myid}}', 'passport_issued_date', $this->string(20)->null()->comment('Passport issued date')->after('passport_issued_by'));
        $this->addColumn('{{%user_myid}}', 'passport_expiry_date', $this->string(20)->null()->comment('Passport expiry date')->after('passport_issued_date'));
        $this->addColumn('{{%user_myid}}', 'doc_type', $this->string(50)->null()->comment('Document type')->after('passport_expiry_date'));

        // Address fields (replace living_address with more specific fields)
        $this->addColumn('{{%user_myid}}', 'permanent_address', $this->string(500)->null()->comment('Permanent address')->after('birth_place'));
        $this->addColumn('{{%user_myid}}', 'temporary_address', $this->string(500)->null()->comment('Temporary address')->after('permanent_address'));

        // Contact fields
        $this->addColumn('{{%user_myid}}', 'phone', $this->string(20)->null()->comment('Phone from MyID')->after('temporary_address'));
        $this->addColumn('{{%user_myid}}', 'email', $this->string(100)->null()->comment('Email from MyID')->after('phone'));

        // Comparison and job tracking
        $this->addColumn('{{%user_myid}}', 'comparison_value', $this->decimal(4, 3)->null()->comment('Face comparison score')->after('email'));
        $this->addColumn('{{%user_myid}}', 'job_id', $this->string(255)->null()->comment('MyID job UUID')->after('comparison_value'));

        // Reuid for secondary flow
        $this->addColumn('{{%user_myid}}', 'reuid', $this->string(255)->null()->comment('Reusable unique ID for secondary requests')->after('sdk_hash'));
        $this->addColumn('{{%user_myid}}', 'reuid_expires_at', $this->integer()->null()->comment('Reuid expiration timestamp')->after('reuid'));

        // Index on reuid for lookups
        $this->createIndex('idx_user_myid_reuid', '{{%user_myid}}', 'reuid');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_user_myid_reuid', '{{%user_myid}}');
        $this->dropColumn('{{%user_myid}}', 'reuid_expires_at');
        $this->dropColumn('{{%user_myid}}', 'reuid');
        $this->dropColumn('{{%user_myid}}', 'job_id');
        $this->dropColumn('{{%user_myid}}', 'comparison_value');
        $this->dropColumn('{{%user_myid}}', 'email');
        $this->dropColumn('{{%user_myid}}', 'phone');
        $this->dropColumn('{{%user_myid}}', 'temporary_address');
        $this->dropColumn('{{%user_myid}}', 'permanent_address');
        $this->dropColumn('{{%user_myid}}', 'doc_type');
        $this->dropColumn('{{%user_myid}}', 'passport_expiry_date');
        $this->dropColumn('{{%user_myid}}', 'passport_issued_date');
        $this->dropColumn('{{%user_myid}}', 'passport_issued_by');
        $this->dropColumn('{{%user_myid}}', 'citizenship');
        $this->dropColumn('{{%user_myid}}', 'last_name_en');
        $this->dropColumn('{{%user_myid}}', 'first_name_en');
    }
}
