<?php

use yii\db\Migration;

/**
 * Class m220628_042943_user_shop_favorite
 */
class m220628_042943_user_shop_favorite extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_shop_favorite', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'shop_id' => $this->integer(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('user_shop_favorite_user_fk', 'user_shop_favorite', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('user_shop_favorite_shop_fk', 'user_shop_favorite', 'shop_id', 'shop', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220628_042943_user_shop_favorite cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220628_042943_user_shop_favorite cannot be reverted.\n";

        return false;
    }
    */
}
