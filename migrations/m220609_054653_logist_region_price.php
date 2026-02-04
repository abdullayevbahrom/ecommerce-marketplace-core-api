<?php

use yii\db\Migration;

/**
 * Class m220609_054653_logist_region_price
 */
class m220609_054653_logist_region_price extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('logist_region_price', [
            'id' => $this->primaryKey(),
            'logist_region_id' => $this->integer(),
            'unit_id' => $this->integer(),
            'unit_amount' => $this->double(),
            'price' => $this->double(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('logist_region_price_region_fk', 'logist_region_price', 'logist_region_id', 'logist_region', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220609_054653_logist_region_price cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220609_054653_logist_region_price cannot be reverted.\n";

        return false;
    }
    */
}
