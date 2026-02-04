<?php

use yii\db\Migration;

/**
 * Class m220113_205432_product_review
 */
class m220113_205432_product_review extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_review', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'product_id' => $this->integer(),
            'review' => $this->text(),
            'rate' => $this->integer(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('product_review_u_fk', 'product_review', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('product_review_p_fk', 'product_review', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220113_205432_product_review cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220113_205432_product_review cannot be reverted.\n";

        return false;
    }
    */
}
