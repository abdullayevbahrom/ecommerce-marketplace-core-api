<?php

namespace app\models;

use yii\db\ActiveRecord;

class ProcessedEvent extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%processed_events}}';
    }

    public function rules(): array
    {
        return [
            [['event_id', 'source', 'event_type', 'entity_type'], 'required'],
            [['entity_id'], 'integer'],
            [['processed_at', 'result_json', 'created_at', 'updated_at'], 'safe'],
            [['event_id', 'correlation_id'], 'string', 'max' => 36],
            [['source'], 'string', 'max' => 50],
            [['event_type'], 'string', 'max' => 150],
            [['entity_type'], 'string', 'max' => 100],
            [['event_id'], 'unique'],
        ];
    }
}