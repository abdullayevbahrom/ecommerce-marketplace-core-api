<?php

use yii\db\Migration;

/**
 * Class m220119_200744_user_favorite
 */
class m220119_200744_user_favorite extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_favorite', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'product_id' => $this->integer(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('user_favorite_user_fk', 'user_favorite', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('user_favorite_product_fk', 'user_favorite', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220119_200744_user_favorite cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220119_200744_user_favorite cannot be reverted.\n";

        return false;
    }
    */
}
