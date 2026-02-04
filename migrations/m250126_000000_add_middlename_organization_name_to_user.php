<?php

use yii\db\Migration;

/**
 * Class m250126_000000_add_middlename_organization_name_to_user
 * Adds middlename and organization_name fields to user table
 */
class m250126_000000_add_middlename_organization_name_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'middlename', $this->string(255)->null()->comment('User middle name'));
        $this->addColumn('user', 'organization_name', $this->string(255)->null()->comment('Organization name for legal entities (yur)'));
        
        /*
         * Manual SQL adjustment if needed:
         * ALTER TABLE `user` ADD `middlename` VARCHAR(255) NULL COMMENT 'User middle name';
         * ALTER TABLE `user` ADD `organization_name` VARCHAR(255) NULL COMMENT 'Organization name for legal entities (yur)';
         */
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'organization_name');
        $this->dropColumn('user', 'middlename');
        
        /*
         * Manual SQL rollback if needed:
         * ALTER TABLE `user` DROP COLUMN `organization_name`;
         * ALTER TABLE `user` DROP COLUMN `middlename`;
         */
    }
}