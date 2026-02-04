<?php

use yii\db\Migration;

/**
 * Class m220111_063733_product_filter
 */
class m220111_063733_product_filter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_filter', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(),
            'filter_id' => $this->integer(),
            'value_ru' => $this->string(),
            'value_en' => $this->string(),
            'value_uz' => $this->string()
        ]);

        $this->addForeignKey('product_filter_product_fk', 'product_filter', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220111_063733_product_filter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220111_063733_product_filter cannot be reverted.\n";

        return false;
    }
    */
}
