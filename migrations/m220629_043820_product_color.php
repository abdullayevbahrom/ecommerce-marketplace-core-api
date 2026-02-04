<?php

use yii\db\Migration;

/**
 * Class m220629_043820_product_color
 */
class m220629_043820_product_color extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_color', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(),
            'color_id' => $this->integer(),
            'date' => $this->timestamp(),
            'status' => $this->integer()
        ]);

        $this->addForeignKey('product_color_product_fk', 'product_color', 'product_id', 'product', 'id', 'CASCADE');
        $this->addForeignKey('product_color_color_fk', 'product_color', 'color_id', 'color', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220629_043820_product_color cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220629_043820_product_color cannot be reverted.\n";

        return false;
    }
    */
}
