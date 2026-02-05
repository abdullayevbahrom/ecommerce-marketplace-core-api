<?php

use yii\db\Migration;

class m260129_221035_add_source_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user','source',"ENUM('yii','sklad') NOT NULL DEFAULT 'yii' AFTER role");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'source');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260129_221035_add_source_to_user_table cannot be reverted.\n";

        return false;
    }
    */
}
