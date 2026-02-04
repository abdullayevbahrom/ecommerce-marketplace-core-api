<?php

use yii\db\Migration;

/**
 * Class m220111_062317_product
 */
class m220111_062317_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'category_id' => $this->integer(),
            'shop_id' => $this->integer(),
            'brand_id' => $this->integer(),
            'region_id' => $this->integer(),
            'name_ru' => $this->string(),
            'name_en' => $this->string(),
            'name_uz' => $this->string(),
            'description_ru' => $this->text(),
            'description_en' => $this->text(),
            'description_uz' => $this->text(),
            'composition_ru' => $this->text(),
            'composition_en' => $this->text(),
            'composition_uz' => $this->text(),
            'recommendation_ru' => $this->text(),
            'recommendation_en' => $this->text(),
            'recommendation_uz' => $this->text(),
            'price' => $this->double(),
            // 'price_old' => $this->double(),
            'discount' => $this->double(),
            'amount' => $this->double(),
            'credit_label' => $this->string(),
            'views' => $this->integer()->notNull()->defaultValue(0),
            'rating' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('product_user_fk', 'product', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('product_category_fk', 'product', 'category_id', 'category', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220111_062317_product cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220111_062317_product cannot be reverted.\n";

        return false;
    }
    */
}
