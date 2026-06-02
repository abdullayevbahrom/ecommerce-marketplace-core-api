<?php

use yii\db\Migration;

/**
 * Handles adding `entity_type` and `entity_id` to table `{{%merchant_questions}}`.
 */
class m260602_100000_add_entity_fields_to_merchant_questions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%merchant_questions}}', 'entity_type', $this->string(50)->null()->after('merchant_id'));
        $this->addColumn('{{%merchant_questions}}', 'entity_id', $this->integer()->null()->after('entity_type'));

        $this->createIndex(
            'idx-merchant_questions-entity_id',
            '{{%merchant_questions}}',
            'entity_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-merchant_questions-entity_id', '{{%merchant_questions}}');
        $this->dropColumn('{{%merchant_questions}}', 'entity_id');
        $this->dropColumn('{{%merchant_questions}}', 'entity_type');
    }
}
