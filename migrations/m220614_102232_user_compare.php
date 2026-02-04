<?php

use yii\db\Migration;

/**
 * Class m220614_102232_user_compare
 */
class m220614_102232_user_compare extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_compare', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'product_id' => $this->integer(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('user_compare_user_fk', 'user_compare', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('user_compare_product_fk', 'user_compare', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220614_102232_user_compare cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220614_102232_user_compare cannot be reverted.\n";

        return false;
    }
    */
}
