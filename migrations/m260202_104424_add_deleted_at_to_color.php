<?php

use yii\db\Migration;

class m260202_104424_add_deleted_at_to_color extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%color}}', 'deleted_at', $this->dateTime()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260202_104424_add_deleted_at_to_color cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260202_104424_add_deleted_at_to_color cannot be reverted.\n";

        return false;
    }
    */
}
