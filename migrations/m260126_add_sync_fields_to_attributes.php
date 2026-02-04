<?php

use yii\db\Migration;

/**
 * Class m260126_add_sync_fields_to_attributes
 */
class m260126_add_sync_fields_to_attributes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tables = [
            'color',
            'category',
            'category_brand',
            'product_type',
            'filter',
            'ikpu',
            'user',
            'shop',
            'stock',
            'delivery',
            'office'
        ];

        foreach ($tables as $table) {
            $this->addColumnsToTable($table);
        }

        // regions table already has updated_at
        $this->addColumnsToTable('regions', ['deactivated_at', 'sklad_synced_at']);
    }

    private function addColumnsToTable($table, $columns = ['updated_at', 'deactivated_at', 'sklad_synced_at'])
    {
        $tableSchema = $this->db->getTableSchema($table);
        if (!$tableSchema) {
            return;
        }

        if (in_array('updated_at', $columns) && !$tableSchema->getColumn('updated_at')) {
            $this->addColumn($table, 'updated_at', $this->dateTime()->null());
            $this->createIndex("idx-{$table}-updated_at", $table, 'updated_at');
        }

        if (in_array('deactivated_at', $columns) && !$tableSchema->getColumn('deactivated_at')) {
            $this->addColumn($table, 'deactivated_at', $this->dateTime()->null());
            $this->createIndex("idx-{$table}-deactivated_at", $table, 'deactivated_at');
        }

        if (in_array('sklad_synced_at', $columns) && !$tableSchema->getColumn('sklad_synced_at')) {
            $this->addColumn($table, 'sklad_synced_at', $this->dateTime()->null());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $tables = [
            'color',
            'category',
            'category_brand',
            'product_type',
            'filter',
            'ikpu',
            'user',
            'shop',
            'stock',
            'delivery',
            'office',
            'regions'
        ];

        foreach ($tables as $table) {
            $this->dropColumnsFromTable($table);
        }
    }

    private function dropColumnsFromTable($table)
    {
        $tableSchema = $this->db->getTableSchema($table);
        if (!$tableSchema) {
            return;
        }

        if ($tableSchema->getColumn('sklad_synced_at')) {
            $this->dropColumn($table, 'sklad_synced_at');
        }

        if ($tableSchema->getColumn('deactivated_at')) {
            $this->dropIndex("idx-{$table}-deactivated_at", $table);
            $this->dropColumn($table, 'deactivated_at');
        }

        if ($table !== 'regions' && $tableSchema->getColumn('updated_at')) {
            $this->dropIndex("idx-{$table}-updated_at", $table);
            $this->dropColumn($table, 'updated_at');
        }
    }
}
