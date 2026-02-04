<?php

use yii\db\Migration;

/**
 * Class m220309_063930_shop_advertising
 */
class m220309_063930_shop_advertising extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('shop_advertising', [
            'id' => $this->primaryKey(),
            'shop_id' => $this->integer(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'content_ru' => $this->text(),
            'content_uz' => $this->text(),
            'content_en' => $this->text(),
            'link' => $this->string(),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('shop_advertising_sh_fk', 'shop_advertising', 'shop_id', 'shop', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220309_063930_shop_advertising cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220309_063930_shop_advertising cannot be reverted.\n";

        return false;
    }
    */
}
