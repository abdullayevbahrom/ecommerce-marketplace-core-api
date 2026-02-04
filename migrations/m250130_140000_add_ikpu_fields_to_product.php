<?php

use yii\db\Migration;

/**
 * Class m250130_140000_add_ikpu_fields_to_product
 * Adds IKPU code and name fields to product table
 */
class m250130_140000_add_ikpu_fields_to_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add IKPU code field to product table
        $this->addColumn('product', 'ikpu_code', $this->string(17)->null()->comment('Код ИКПУ'));
        
        // Add IKPU name field to product table (for caching purposes)
        $this->addColumn('product', 'ikpu_name', $this->string(500)->null()->comment('Название ИКПУ (кэшированное)'));

        // Create index for IKPU code
        $this->createIndex('idx_product_ikpu_code', 'product', 'ikpu_code');

        // Add foreign key constraint to IKPU table
        $this->addForeignKey(
            'fk_product_ikpu',
            'product',
            'ikpu_code',
            'ikpu',
            'code',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_product_ikpu', 'product');
        $this->dropIndex('idx_product_ikpu_code', 'product');
        $this->dropColumn('product', 'ikpu_name');
        $this->dropColumn('product', 'ikpu_code');
    }

    /*
    // Manual SQL for adding IKPU fields to product table:
    ALTER TABLE `product` 
    ADD COLUMN `ikpu_code` varchar(17) DEFAULT NULL COMMENT 'Код ИКПУ',
    ADD COLUMN `ikpu_name` varchar(500) DEFAULT NULL COMMENT 'Название ИКПУ (кэшированное)';

    CREATE INDEX `idx_product_ikpu_code` ON `product` (`ikpu_code`);

    ALTER TABLE `product` 
    ADD CONSTRAINT `fk_product_ikpu` FOREIGN KEY (`ikpu_code`) REFERENCES `ikpu` (`code`) ON DELETE SET NULL ON UPDATE CASCADE;

    // To rollback (remove IKPU fields):
    ALTER TABLE `product` 
    DROP FOREIGN KEY `fk_product_ikpu`,
    DROP INDEX `idx_product_ikpu_code`,
    DROP COLUMN `ikpu_name`,
    DROP COLUMN `ikpu_code`;
    */
}
