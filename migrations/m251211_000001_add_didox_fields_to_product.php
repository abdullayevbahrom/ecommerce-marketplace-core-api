<?php

use yii\db\Migration;

/**
 * Class m240101_000001_add_didox_fields_to_product
 * 
 * NOTE: Run this migration manually in database as yii console might not be available.
 * 
 * SQL:
 * ALTER TABLE `product` ADD COLUMN `package_code` VARCHAR(50) NULL DEFAULT NULL AFTER `ikpu_name`;
 * ALTER TABLE `product` ADD COLUMN `package_name` VARCHAR(100) NULL DEFAULT NULL AFTER `package_code`;
 */
class m251211_000001_add_didox_fields_to_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('product', 'package_code', $this->string(50)->null()->after('ikpu_name'));
        $this->addColumn('product', 'package_name', $this->string(100)->null()->after('package_code'));
        
        echo "Please execute the SQL manually:\n";
        echo "ALTER TABLE `product` ADD COLUMN `package_code` VARCHAR(50) NULL DEFAULT NULL AFTER `ikpu_name`;\n";
        echo "ALTER TABLE `product` ADD COLUMN `package_name` VARCHAR(100) NULL DEFAULT NULL AFTER `package_code`;\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('product', 'package_name');
        $this->dropColumn('product', 'package_code');
    }
}
