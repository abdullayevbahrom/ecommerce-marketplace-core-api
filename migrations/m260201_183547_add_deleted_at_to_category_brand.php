<?php

use yii\db\Migration;

class m260201_183547_add_deleted_at_to_category_brand extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%category_brand}}', 'deleted_at', $this->dateTime()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260201_183547_add_deleted_at_to_category_brand cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260201_183547_add_deleted_at_to_category_brand cannot be reverted.\n";

        return false;
    }
    */
}
