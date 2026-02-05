<?php

use yii\db\Migration;

class m260205_060632_add_status_to_color_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%color}}',
            'status',
            $this->integer()->notNull()->defaultValue(1)->comment('1 = active, 0 = inactive')
        );

        $this->createIndex('idx-color-status','{{%color}}','status');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260205_060632_add_status_to_color_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260205_060632_add_status_to_color_table cannot be reverted.\n";

        return false;
    }
    */
}
