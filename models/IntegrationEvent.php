<?php

namespace app\models;

use yii\db\ActiveRecord;

class IntegrationEvent extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%integration_events}}';
    }

    public function rules(): array
    {
        return [
            [
                [
                    'event_id',
                    'exchange_name',
                    'routing_key',
                    'event_type',
                    'entity_type',
                    'source',
                    'payload_json',
                    'status'
                ],
                'required'
            ],
            [['entity_id', 'branch_id', 'attempts'], 'integer'],
            [['payload_json', 'published_at', 'available_at', 'created_at', 'updated_at'], 'safe'],
            [['last_error'], 'string'],
            [['event_id', 'correlation_id'], 'string', 'max' => 36],
            [['exchange_name'], 'string', 'max' => 100],
            [['routing_key', 'event_type'], 'string', 'max' => 150],
            [['entity_type'], 'string', 'max' => 100],
            [['source'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 30],
            [['event_id'], 'unique'],
        ];
    }

    public function markPublished(): void
    {
        $this->status = 'published';
        $this->attempts += 1;
        $this->published_at = date('Y-m-d H:i:s');
        $this->last_error = null;
        $this->save(false);
    }

    public function markFailed(string $error): void
    {
        $this->status = 'failed';
        $this->attempts += 1;
        $this->last_error = $error;
        $this->save(false);
    }
}