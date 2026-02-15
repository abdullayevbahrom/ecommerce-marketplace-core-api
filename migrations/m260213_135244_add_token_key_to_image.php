<?php

use yii\db\Migration;

class m260213_135244_add_token_key_to_image extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('image', 'token_key', $this->string(64)->after('object_id'));
        $this->createIndex('idx-image-token_key', 'image', 'token_key');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260213_135244_add_token_key_to_image cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260213_135244_add_token_key_to_image cannot be reverted.\n";

        return false;
    }
    */
}
