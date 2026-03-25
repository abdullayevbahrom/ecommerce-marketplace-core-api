<?php

use yii\db\Migration;

class m260325_120000_add_status_to_color_table extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('color');

        if (!$tableSchema) {
            return;
        }

        if (!$tableSchema->getColumn('status')) {
            $this->addColumn(
                'color',
                'status',
                $this->integer()->notNull()->defaultValue(1)->comment('1=active, 2=inactive')
            );

            $this->update('color', ['status' => 1], ['status' => null]);
        }

        $indexes = $this->db->createCommand('SHOW INDEX FROM `color`')->queryAll();
        $hasStatusIndex = false;

        foreach ($indexes as $index) {
            if (($index['Column_name'] ?? null) === 'status') {
                $hasStatusIndex = true;
                break;
            }
        }

        if (!$hasStatusIndex) {
            $this->createIndex('idx-color-status', 'color', 'status');
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('color');

        if (!$tableSchema) {
            return;
        }

        if ($tableSchema->getColumn('status')) {
            $indexes = $this->db->createCommand('SHOW INDEX FROM `color`')->queryAll();

            foreach ($indexes as $index) {
                if (($index['Column_name'] ?? null) === 'status') {
                    $this->dropIndex($index['Key_name'], 'color');
                    break;
                }
            }

            $this->dropColumn('color', 'status');
        }
    }
}
