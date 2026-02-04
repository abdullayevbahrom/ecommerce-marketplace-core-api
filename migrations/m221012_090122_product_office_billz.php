<?php

use yii\db\Migration;

/**
 * Class m221012_090122_product_office_billz
 */
class m221012_090122_product_office_billz extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_office_billz', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(),
            'office_id' => $this->string(),
            'office_name' => $this->string(),
            'price' => $this->string(),
            // 'price_usd' => $this->string(),
            'discountAmount' => $this->string(),
            'qty' => $this->string()
        ]);

        $this->addForeignKey('product_office_billz_product_fk', 'product_office_billz', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221012_090122_product_office_billz cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221012_090122_product_office_billz cannot be reverted.\n";

        return false;
    }
    */
}
