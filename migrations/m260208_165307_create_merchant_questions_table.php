<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%merchant_questions}}`.
 */
class m260208_165307_create_merchant_questions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%merchant_questions}}', [
            'id' => $this->primaryKey(),

            'client_id' => $this->integer()->notNull()
                ->comment('Client who asked the question'),

            'merchant_id' => $this->integer()->notNull()
                ->comment('Merchant who should answer'),

            'entity_type' => $this->string(50)->null()
                ->comment('product | order | general'),

            'entity_id' => $this->integer()->null()
                ->comment('Related entity ID'),

            'status' => $this->tinyInteger()->notNull()->defaultValue(0)
                ->comment('0=open, 1=answered, 2=closed'),

            'created_at' => $this->integer()->notNull(),
            'answered_at' => $this->integer()->null(),
            'closed_at' => $this->integer()->null(),
        ]);

        $this->createIndex(
            'idx-merchant_questions-client_id',
            '{{%merchant_questions}}',
            'client_id'
        );

        $this->createIndex(
            'idx-merchant_questions-merchant_id',
            '{{%merchant_questions}}',
            'merchant_id'
        );

        $this->createIndex(
            'idx-merchant_questions-status',
            '{{%merchant_questions}}',
            'status'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%merchant_questions}}');
    }
}
