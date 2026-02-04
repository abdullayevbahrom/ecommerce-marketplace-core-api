<?php

use yii\db\Migration;

/**
 * Class m220301_035302_shop_seller
 */
class m220301_035302_shop_seller extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('shop_seller', [
            'id' => $this->primaryKey(),
            'shop_id' => $this->integer(),
            'inn' => $this->string(),
            'account' => $this->string(),
            'bank' => $this->string(),
            'address_legal' => $this->string(),
            'oked' => $this->string(),
            'okohx' => $this->string(),
            'mfo' => $this->string(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('shop_seller_sh_fk', 'shop_seller', 'shop_id', 'shop', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220301_035302_shop_seller cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220301_035302_shop_seller cannot be reverted.\n";

        return false;
    }
    */
}
