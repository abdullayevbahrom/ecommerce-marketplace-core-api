<?php

use yii\db\Migration;

/**
 * Class m251215_000001_add_banking_details_to_order
 */
    
// Add columns to database manually:
/*
ALTER TABLE `order` ADD COLUMN `inn` VARCHAR(255) NULL AFTER `user_id`;
ALTER TABLE `order` ADD COLUMN `account` VARCHAR(255) NULL AFTER `inn`;
ALTER TABLE `order` ADD COLUMN `bank_id` VARCHAR(255) NULL AFTER `account`;
*/

class m251215_000001_add_banking_details_to_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'inn', $this->string(20)->null()->comment('Buyer TIN/INN'));
        $this->addColumn('{{%order}}', 'account', $this->string(50)->null()->comment('Buyer Account Number'));
        $this->addColumn('{{%order}}', 'bank_id', $this->string(20)->null()->comment('Buyer Bank ID (MFO)'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'bank_id');
        $this->dropColumn('{{%order}}', 'account');
        $this->dropColumn('{{%order}}', 'inn');
    }
}
