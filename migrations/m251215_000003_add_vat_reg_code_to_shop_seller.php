<?php

use yii\db\Migration;

/**
 * Class m251215_000003_add_vat_reg_code_to_shop_seller
 * 
 * Manual SQL to add column:
 * ALTER TABLE `shop_seller` ADD COLUMN `vat_reg_code` VARCHAR(32) NULL AFTER `mfo`;
 */
class m251215_000003_add_vat_reg_code_to_shop_seller extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%shop_seller}}', 'vat_reg_code', $this->string(32)->null()->after('mfo'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%shop_seller}}', 'vat_reg_code');
    }
}
