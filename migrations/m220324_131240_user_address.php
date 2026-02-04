<?php

use yii\db\Migration;

/**
 * Class m220324_131240_user_address
 */
class m220324_131240_user_address extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_address', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'address' => $this->text(),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('user_address_u_fk', 'user_address', 'user_id', 'user', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220324_131240_user_address cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220324_131240_user_address cannot be reverted.\n";

        return false;
    }
    */
}
