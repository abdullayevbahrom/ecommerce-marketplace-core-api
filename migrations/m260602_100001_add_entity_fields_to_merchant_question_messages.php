<?php

use yii\db\Migration;

/**
 * Handles adding `files` to table `{{%merchant_question_messages}}`.
 */
class m260602_100001_add_entity_fields_to_merchant_question_messages extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%merchant_question_messages}}', 'files', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%merchant_question_messages}}', 'files');
    }
}
