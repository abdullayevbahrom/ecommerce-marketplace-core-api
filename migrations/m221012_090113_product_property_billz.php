<?php

use yii\db\Migration;

/**
 * Class m221012_090113_product_property_billz
 */
class m221012_090113_product_property_billz extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_property_billz', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(),
            'brand' => $this->string(),
            'category' => $this->string(),
            'collection' => $this->string(),
            'color' => $this->string(),
            'description' => $this->text(),
            'gender' => $this->string(),
            'model_name' => $this->string(),
            'season' => $this->string(),
            'size' => $this->string(),
            'sub_category' => $this->string()
        ]);

        $this->addForeignKey('product_property_billz_product_fk', 'product_property_billz', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221012_090113_product_property_billz cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221012_090113_product_property_billz cannot be reverted.\n";

        return false;
    }
    */
}
