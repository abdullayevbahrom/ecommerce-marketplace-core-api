<?php

use yii\db\Migration;

/**
 * Class m220311_151637_product_view_recently
 */
class m220311_151637_product_view_recently extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_view_recently', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(),
            'ip' => $this->string(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('product_view_recently_p_fk', 'product_view_recently', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220311_151637_product_view_recently cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220311_151637_product_view_recently cannot be reverted.\n";

        return false;
    }
    */
}
