<?php

use yii\db\Migration;

/**
 * Class m221013_054737_product_office
 */
class m221013_054737_product_office extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_office', [
            'id' => $this->primaryKey(),
            'office_id' => $this->integer(),
            'product_id' => $this->integer(),
            'price' => $this->string(),
            // 'price_usd' => $this->string(),
            'discount' => $this->string(),
            'qty' => $this->string()
        ]);

        $this->addForeignKey('product_office_o_fk', 'product_office', 'office_id', 'office', 'id', 'CASCADE');
        $this->addForeignKey('product_office_p_fk', 'product_office', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221013_054737_product_office cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221013_054737_product_office cannot be reverted.\n";

        return false;
    }
    */
}
