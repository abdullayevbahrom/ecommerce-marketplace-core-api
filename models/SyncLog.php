<?php

namespace app\models;

use yii\db\ActiveRecord;

class SyncLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%sync_logs}}';
    }

    public function rules(): array
    {
        return [
            [['direction', 'event_type', 'entity_type', 'status'], 'required'],
            [['entity_id', 'attempts'], 'integer'],
            [['request_payload', 'response_payload', 'logged_at', 'created_at', 'updated_at'], 'safe'],
            [['error_message'], 'string'],
            [['event_id', 'correlation_id'], 'string', 'max' => 36],
            [['direction'], 'string', 'max' => 50],
            [['exchange_name', 'queue_name', 'entity_type'], 'string', 'max' => 100],
            [['routing_key', 'event_type'], 'string', 'max' => 150],
            [['status'], 'string', 'max' => 30],
        ];
    }
}