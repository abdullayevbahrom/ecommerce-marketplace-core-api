<?php

use yii\db\Migration;

/**
 * Add user_id and admin_id fields to product_request table
 */
class m250107_000003_add_user_admin_ids_to_product_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%product_request}}', 'user_id', $this->integer()->null()->comment('ID пользователя, отправившего запрос'));
        $this->addColumn('{{%product_request}}', 'admin_id', $this->integer()->null()->comment('ID администратора, ответившего на запрос'));
        
        // Add foreign keys
        $this->addForeignKey(
            'fk-product_request-user_id',
            '{{%product_request}}',
            'user_id',
            '{{%user}}',
            'id',
            'SET NULL'
        );
        
        $this->addForeignKey(
            'fk-product_request-admin_id',
            '{{%product_request}}',
            'admin_id',
            '{{%user}}',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-product_request-admin_id', '{{%product_request}}');
        $this->dropForeignKey('fk-product_request-user_id', '{{%product_request}}');
        $this->dropColumn('{{%product_request}}', 'admin_id');
        $this->dropColumn('{{%product_request}}', 'user_id');
    }
} 