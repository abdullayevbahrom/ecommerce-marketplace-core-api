<?php

use yii\db\Migration;

/**
 * Class m220111_062726_product_view
 */
class m220111_062726_product_view extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_view', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(),
            'ip' => $this->string(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('product_view_product_fk', 'product_view', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220111_062726_product_view cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220111_062726_product_view cannot be reverted.\n";

        return false;
    }
    */
}
