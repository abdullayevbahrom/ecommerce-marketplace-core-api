<?php

use yii\db\Migration;

class m260319_120000_create_integration_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%integration_events}}', [
            'id' => $this->primaryKey(),
            'event_id' => $this->string(36)->notNull()->unique(),
            'correlation_id' => $this->string(36)->null(),
            'exchange_name' => $this->string(100)->notNull(),
            'routing_key' => $this->string(150)->notNull(),
            'event_type' => $this->string(150)->notNull(),
            'entity_type' => $this->string(100)->notNull(),
            'entity_id' => $this->bigInteger()->null(),
            'source' => $this->string(50)->notNull(),
            'branch_id' => $this->bigInteger()->null(),
            'payload_json' => $this->json()->notNull(),
            'status' => $this->string(30)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'last_error' => $this->text()->null(),
            'published_at' => $this->timestamp()->null(),
            'available_at' => $this->timestamp()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null()->append('DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_integration_events_status', '{{%integration_events}}', 'status');
        $this->createIndex('idx_integration_events_event_type', '{{%integration_events}}', 'event_type');
        $this->createIndex('idx_integration_events_entity_type', '{{%integration_events}}', 'entity_type');

        $this->createTable('{{%processed_events}}', [
            'id' => $this->primaryKey(),
            'event_id' => $this->string(36)->notNull()->unique(),
            'correlation_id' => $this->string(36)->null(),
            'source' => $this->string(50)->notNull(),
            'event_type' => $this->string(150)->notNull(),
            'entity_type' => $this->string(100)->notNull(),
            'entity_id' => $this->bigInteger()->null(),
            'processed_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'result_json' => $this->json()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null()->append('DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createTable('{{%sync_logs}}', [
            'id' => $this->primaryKey(),
            'event_id' => $this->string(36)->null(),
            'correlation_id' => $this->string(36)->null(),
            'direction' => $this->string(50)->notNull(),
            'exchange_name' => $this->string(100)->null(),
            'queue_name' => $this->string(100)->null(),
            'routing_key' => $this->string(150)->null(),
            'event_type' => $this->string(150)->notNull(),
            'entity_type' => $this->string(100)->notNull(),
            'entity_id' => $this->bigInteger()->null(),
            'status' => $this->string(30)->notNull(),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'request_payload' => $this->json()->null(),
            'response_payload' => $this->json()->null(),
            'error_message' => $this->text()->null(),
            'logged_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null()->append('DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP'),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%sync_logs}}');
        $this->dropTable('{{%processed_events}}');
        $this->dropTable('{{%integration_events}}');
    }
}