<?php

use yii\db\Migration;

/**
 * Adds SMS-related fields to user table
 */
class m250107_000000_add_sms_fields_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'phone_code', $this->string(10)->comment('SMS verification code'));
        $this->addColumn('user', 'sms_live', $this->integer()->comment('SMS code expiration timestamp'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'phone_code');
        $this->dropColumn('user', 'sms_live');
    }
} 