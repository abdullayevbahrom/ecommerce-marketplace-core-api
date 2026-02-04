<?php

use yii\db\Migration;

/**
 * Add E-IMZO authentication fields to user table
 * 
 * -- Add E-IMZO authentication fields
 * ALTER TABLE `user` ADD `eimzo_tax_id` varchar(20) NULL COMMENT 'E-IMZO Tax ID (INN)';
 * ALTER TABLE `user` ADD `eimzo_didox_token` varchar(255) NULL COMMENT 'Current Didox token from E-IMZO auth';
 * ALTER TABLE `user` ADD `eimzo_last_login` datetime NULL COMMENT 'Last E-IMZO login timestamp';
 * ALTER TABLE `user` ADD `eimzo_certificate_info` text NULL COMMENT 'JSON encoded E-IMZO certificate information';
 * 
 * -- Create indexes for better performance
 * CREATE INDEX `idx-user-eimzo_tax_id` ON `user` (`eimzo_tax_id`);
 * CREATE INDEX `idx-user-eimzo_didox_token` ON `user` (`eimzo_didox_token`);
 * CREATE INDEX `idx-user-eimzo_last_login` ON `user` (`eimzo_last_login`);
 */
class m240101_000000_add_didox_fields_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add E-IMZO authentication fields
        $this->addColumn('{{%user}}', 'eimzo_tax_id', $this->string(20)->null()->comment('E-IMZO Tax ID (INN)'));
        $this->addColumn('{{%user}}', 'eimzo_didox_token', $this->string(255)->null()->comment('Current Didox token from E-IMZO auth'));
        $this->addColumn('{{%user}}', 'eimzo_last_login', $this->dateTime()->null()->comment('Last E-IMZO login timestamp'));
        $this->addColumn('{{%user}}', 'eimzo_certificate_info', $this->text()->null()->comment('JSON encoded E-IMZO certificate information'));
        
        // Create indexes for better performance
        $this->createIndex('idx-user-eimzo_tax_id', '{{%user}}', 'eimzo_tax_id');
        $this->createIndex('idx-user-eimzo_didox_token', '{{%user}}', 'eimzo_didox_token');
        $this->createIndex('idx-user-eimzo_last_login', '{{%user}}', 'eimzo_last_login');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop indexes
        $this->dropIndex('idx-user-eimzo_last_login', '{{%user}}');
        $this->dropIndex('idx-user-eimzo_didox_token', '{{%user}}');
        $this->dropIndex('idx-user-eimzo_tax_id', '{{%user}}');
        
        // Drop columns
        $this->dropColumn('{{%user}}', 'eimzo_certificate_info');
        $this->dropColumn('{{%user}}', 'eimzo_last_login');
        $this->dropColumn('{{%user}}', 'eimzo_didox_token');
        $this->dropColumn('{{%user}}', 'eimzo_tax_id');
    }
} 