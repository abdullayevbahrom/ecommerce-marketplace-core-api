<?php

use yii\db\Migration;

class m260201_175454_add_deleted_at_to_category extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%category}}', 'deleted_at', $this->dateTime()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260201_175454_add_deleted_at_to_category cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260201_175454_add_deleted_at_to_category cannot be reverted.\n";

        return false;
    }
    */
}
