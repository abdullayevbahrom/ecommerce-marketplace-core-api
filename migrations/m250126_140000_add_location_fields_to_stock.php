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
        $this->addColumn('stock', 'bts_region_id', 'INT DEFAULT NULL COMMENT "BTS region ID for delivery calculation"');
        $this->addColumn('stock', 'bts_city_id', 'INT DEFAULT NULL COMMENT "BTS city ID for delivery calculation"');
        $this->addColumn('stock', 'address', 'TEXT DEFAULT NULL COMMENT "Stock warehouse address"');

        // Create indexes for better performance
        $this->createIndex('idx-stock-bts_region_id', 'stock', 'bts_region_id');
        $this->createIndex('idx-stock-bts_city_id', 'stock', 'bts_city_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-stock-bts_region_id', 'stock');
        $this->dropIndex('idx-stock-bts_city_id', 'stock');
        
        $this->dropColumn('stock', 'bts_region_id');
        $this->dropColumn('stock', 'bts_city_id');
        $this->dropColumn('stock', 'address');
    }

    /*
    // Manual SQL for adjustments if needed:
    ALTER TABLE `stock` 
    ADD COLUMN `bts_region_id` INT DEFAULT NULL COMMENT 'BTS region ID for delivery calculation',
    ADD COLUMN `bts_city_id` INT DEFAULT NULL COMMENT 'BTS city ID for delivery calculation',
    ADD COLUMN `address` TEXT DEFAULT NULL COMMENT 'Stock warehouse address';
    
    CREATE INDEX `idx-stock-bts_region_id` ON `stock` (`bts_region_id`);
    CREATE INDEX `idx-stock-bts_city_id` ON `stock` (`bts_city_id`);
    
    // To rollback:
    ALTER TABLE `stock` 
    DROP INDEX `idx-stock-bts_region_id`,
    DROP INDEX `idx-stock-bts_city_id`,
    DROP COLUMN `bts_region_id`,
    DROP COLUMN `bts_city_id`,
    DROP COLUMN `address`;
    */
}