<?php

use yii\db\Migration;

class m250126_130100_create_cities_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%cities}}', [
            'id' => $this->primaryKey(),
            'bts_id' => $this->integer()->notNull()->unique()->comment('BTS system city ID'),
            'region_id' => $this->integer()->notNull()->comment('Reference to regions table'),
            'bts_region_id' => $this->integer()->notNull()->comment('BTS system region ID'),
            'name_ru' => $this->string(255)->notNull()->comment('Russian name'),
            'name_uz' => $this->string(255)->notNull()->comment('Uzbek name'),
            'name_en' => $this->string(255)->notNull()->comment('English name'),
            'status' => $this->integer()->defaultValue(1)->comment('Status: 1=active, 0=inactive'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Create indexes
        $this->createIndex('idx-cities-bts_id', '{{%cities}}', 'bts_id');
        $this->createIndex('idx-cities-region_id', '{{%cities}}', 'region_id');
        $this->createIndex('idx-cities-bts_region_id', '{{%cities}}', 'bts_region_id');
        $this->createIndex('idx-cities-status', '{{%cities}}', 'status');

        // Add foreign key for region_id
        $this->addForeignKey(
            'fk-cities-region_id',
            '{{%cities}}',
            'region_id',
            '{{%regions}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-cities-region_id', '{{%cities}}');
        $this->dropIndex('idx-cities-bts_id', '{{%cities}}');
        $this->dropIndex('idx-cities-region_id', '{{%cities}}');
        $this->dropIndex('idx-cities-bts_region_id', '{{%cities}}');
        $this->dropIndex('idx-cities-status', '{{%cities}}');
        $this->dropTable('{{%cities}}');
    }
}