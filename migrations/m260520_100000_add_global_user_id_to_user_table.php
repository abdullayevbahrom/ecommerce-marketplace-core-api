<?php

use yii\db\Migration;

class m260520_100000_add_global_user_id_to_user_table extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('user', true);
        if ($table === null) {
            return;
        }

        if (!isset($table->columns['global_user_id'])) {
            $this->addColumn('user', 'global_user_id', $this->string(64)->null()->after('token'));
            $this->createIndex('idx_user_global_user_id', 'user', 'global_user_id', true);
        }
    }

    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('user', true);
        if ($table === null) {
            return;
        }

        if (isset($table->columns['global_user_id'])) {
            $this->dropIndex('idx_user_global_user_id', 'user');
            $this->dropColumn('user', 'global_user_id');
        }
    }
}

