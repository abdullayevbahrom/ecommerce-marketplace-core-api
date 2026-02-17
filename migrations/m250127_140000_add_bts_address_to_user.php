<?php

use yii\db\Migration;

/**
 * Class m250127_140000_add_bts_address_to_user
 */
class m250127_140000_add_bts_address_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'bts_region_id', $this->integer()->null()->comment('BTS region ID for delivery calculation'));
        $this->addColumn('user', 'bts_city_id', $this->integer()->null()->comment('BTS city ID for delivery calculation'));

        // Create indexes for better performance
        $this->createIndex('idx-user-bts_region_id', 'user', 'bts_region_id');
        $this->createIndex('idx-user-bts_city_id', 'user', 'bts_city_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-user-bts_region_id', 'user');
        $this->dropIndex('idx-user-bts_city_id', 'user');
        
        $this->dropColumn('user', 'bts_region_id');
        $this->dropColumn('user', 'bts_city_id');
    }

    /*
    // Manual SQL for adjustments if needed:
    ALTER TABLE `user` 
    ADD COLUMN `bts_region_id` INT DEFAULT NULL COMMENT 'BTS region ID for delivery calculation',
    ADD COLUMN `bts_city_id` INT DEFAULT NULL COMMENT 'BTS city ID for delivery calculation';
    
    CREATE INDEX `idx-user-bts_region_id` ON `user` (`bts_region_id`);
    CREATE INDEX `idx-user-bts_city_id` ON `user` (`bts_city_id`);
    
    // To rollback:
    ALTER TABLE `user` 
    DROP INDEX `idx-user-bts_region_id`,
    DROP INDEX `idx-user-bts_city_id`,
    DROP COLUMN `bts_region_id`,
    DROP COLUMN `bts_city_id`;
    */
}