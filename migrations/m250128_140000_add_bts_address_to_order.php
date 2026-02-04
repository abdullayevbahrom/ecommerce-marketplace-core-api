<?php

use yii\db\Migration;

/**
 * Class m250126_140000_add_location_fields_to_stock
 */
class m250126_140000_add_location_fields_to_stock extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('order', 'bts_region_id', $this->integer()->null()->comment('BTS region ID for delivery calculation'));
        $this->addColumn('order', 'bts_city_id', $this->integer()->null()->comment('BTS city ID for delivery calculation'));

        // Create indexes for better performance
        $this->createIndex('idx-order-bts_region_id', 'order', 'bts_region_id');
        $this->createIndex('idx-order-bts_city_id', 'order', 'bts_city_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-order-bts_region_id', 'order');
        $this->dropIndex('idx-order-bts_city_id', 'order');
        
        $this->dropColumn('order', 'bts_region_id');
        $this->dropColumn('order', 'bts_city_id');
    }

    /*
    // Manual SQL for adjustments if needed:
    ALTER TABLE `order` 
    ADD COLUMN `bts_region_id` INT DEFAULT NULL COMMENT 'BTS region ID for delivery calculation',
    ADD COLUMN `bts_city_id` INT DEFAULT NULL COMMENT 'BTS city ID for delivery calculation';
    
    CREATE INDEX `idx-order-bts_region_id` ON `order` (`bts_region_id`);
    CREATE INDEX `idx-order-bts_city_id` ON `order` (`bts_city_id`);
    
    // To rollback:
    ALTER TABLE `order` 
    DROP INDEX `idx-order-bts_region_id`,
    DROP INDEX `idx-order-bts_city_id`,
    DROP COLUMN `bts_region_id`,
    DROP COLUMN `bts_city_id`;
    */
}