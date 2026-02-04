<?php

use yii\db\Migration;

/**
 * Class m250126_120000_add_wholesale_quantities_to_product
 */
class m250126_120000_add_wholesale_quantities_to_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('product', 'qty_small_wholesale', 'INT DEFAULT NULL COMMENT "Количество для малого опта"');
        $this->addColumn('product', 'qty_big_wholesale', 'INT DEFAULT NULL COMMENT "Количество для крупного опта"');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('product', 'qty_small_wholesale');
        $this->dropColumn('product', 'qty_big_wholesale');
    }

    /*
    // Manual SQL for adjustments if needed:
    ALTER TABLE `product` ADD COLUMN `qty_small_wholesale` INT DEFAULT NULL COMMENT 'Количество для малого опта';
    ALTER TABLE `product` ADD COLUMN `qty_big_wholesale` INT DEFAULT NULL COMMENT 'Количество для крупного опта';
    
    // To rollback:
    ALTER TABLE `product` DROP COLUMN `qty_small_wholesale`;
    ALTER TABLE `product` DROP COLUMN `qty_big_wholesale`;
    */
}