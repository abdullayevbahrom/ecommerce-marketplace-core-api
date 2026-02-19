<?php

use yii\db\Migration;

/**
 * Migration: Change bts_region_id and bts_city_id from INT to VARCHAR(10)
 *
 * BTS API v1 now uses string codes instead of numeric IDs:
 *   - Region codes: "01" (Tashkent city), "10" (Tashkent region), "60" (Andijan), etc.
 *   - City codes: "0101" (Bektimir), "0110" (Yunusobod), "6001" (Xonobod), etc.
 *
 * Affected tables: user, order, stock
 *
 * IMPORTANT: After running this migration, existing numeric values (1, 2, 3...)
 * will be converted to strings ("1", "2", "3"...) but these do NOT match the new
 * BTS API codes. You must update existing records with correct BTS codes.
 * Use GET /api/bts/regions and GET /api/bts/cities?regionCode=XX to look up codes.
 */
class m260213_000000_change_bts_ids_to_string_codes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // --- Table: stock ---
        $this->alterColumn('stock', 'bts_region_id', $this->string(10)->null()->comment('BTS region code (e.g. "01", "10", "60")'));
        $this->alterColumn('stock', 'bts_city_id', $this->string(10)->null()->comment('BTS city code (e.g. "0101", "0110", "6001")'));

        // --- Table: user ---
        $this->alterColumn('user', 'bts_region_id', $this->string(10)->null()->comment('BTS region code (e.g. "01", "10", "60")'));
        $this->alterColumn('user', 'bts_city_id', $this->string(10)->null()->comment('BTS city code (e.g. "0101", "0110", "6001")'));

        // --- Table: order ---
        $this->alterColumn('order', 'bts_region_id', $this->string(10)->null()->comment('BTS region code (e.g. "01", "10", "60")'));
        $this->alterColumn('order', 'bts_city_id', $this->string(10)->null()->comment('BTS city code (e.g. "0101", "0110", "6001")'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert to INT (existing string codes will be truncated/lost)
        $this->alterColumn('stock', 'bts_region_id', $this->integer()->null()->comment('BTS region ID for delivery calculation'));
        $this->alterColumn('stock', 'bts_city_id', $this->integer()->null()->comment('BTS city ID for delivery calculation'));

        $this->alterColumn('user', 'bts_region_id', $this->integer()->null()->comment('BTS region ID for delivery calculation'));
        $this->alterColumn('user', 'bts_city_id', $this->integer()->null()->comment('BTS city ID for delivery calculation'));

        $this->alterColumn('order', 'bts_region_id', $this->integer()->null()->comment('BTS region ID for delivery calculation'));
        $this->alterColumn('order', 'bts_city_id', $this->integer()->null()->comment('BTS city ID for delivery calculation'));
    }

    /*
    -- ============================================================
    -- MANUAL SQL: Change bts_region_id and bts_city_id to VARCHAR
    -- ============================================================
    -- BTS API now uses string codes instead of numeric IDs:
    --   Regions: "01"=Tashkent city, "10"=Tashkent region, "20"=Syrdarya,
    --            "25"=Jizzakh, "30"=Samarkand, "40"=Fergana, "50"=Namangan,
    --            "60"=Andijan, "70"=Kashkadarya, "75"=Surkhandarya,
    --            "80"=Bukhara, "85"=Navoi, "90"=Khorezm, "95"=Karakalpakstan
    --   Cities:  "0101"=Bektimir, "0110"=Yunusobod, "6000"=Andijan city, etc.
    --
    -- Get full list: GET /api/bts/regions, GET /api/bts/cities?regionCode=01

    -- stock table
    ALTER TABLE `stock`
        MODIFY COLUMN `bts_region_id` VARCHAR(10) DEFAULT NULL COMMENT 'BTS region code (e.g. "01", "10", "60")',
        MODIFY COLUMN `bts_city_id` VARCHAR(10) DEFAULT NULL COMMENT 'BTS city code (e.g. "0101", "0110", "6001")';

    -- user table
    ALTER TABLE `user`
        MODIFY COLUMN `bts_region_id` VARCHAR(10) DEFAULT NULL COMMENT 'BTS region code (e.g. "01", "10", "60")',
        MODIFY COLUMN `bts_city_id` VARCHAR(10) DEFAULT NULL COMMENT 'BTS city code (e.g. "0101", "0110", "6001")';

    -- order table
    ALTER TABLE `order`
        MODIFY COLUMN `bts_region_id` VARCHAR(10) DEFAULT NULL COMMENT 'BTS region code (e.g. "01", "10", "60")',
        MODIFY COLUMN `bts_city_id` VARCHAR(10) DEFAULT NULL COMMENT 'BTS city code (e.g. "0101", "0110", "6001")';


    -- ============================================================
    -- ROLLBACK SQL: Revert to INT
    -- ============================================================
    -- WARNING: String codes that are not numeric will be truncated to 0

    ALTER TABLE `stock`
        MODIFY COLUMN `bts_region_id` INT DEFAULT NULL COMMENT 'BTS region ID for delivery calculation',
        MODIFY COLUMN `bts_city_id` INT DEFAULT NULL COMMENT 'BTS city ID for delivery calculation';

    ALTER TABLE `user`
        MODIFY COLUMN `bts_region_id` INT DEFAULT NULL COMMENT 'BTS region ID for delivery calculation',
        MODIFY COLUMN `bts_city_id` INT DEFAULT NULL COMMENT 'BTS city ID for delivery calculation';

    ALTER TABLE `order`
        MODIFY COLUMN `bts_region_id` INT DEFAULT NULL COMMENT 'BTS region ID for delivery calculation',
        MODIFY COLUMN `bts_city_id` INT DEFAULT NULL COMMENT 'BTS city ID for delivery calculation';
    */
}
