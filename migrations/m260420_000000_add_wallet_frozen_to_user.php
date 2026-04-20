<?php

use yii\db\Migration;

class m260420_000000_add_wallet_frozen_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'wallet_frozen', $this->tinyInteger()->defaultValue(0)->after('myid_verified'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'wallet_frozen');
    }
}
