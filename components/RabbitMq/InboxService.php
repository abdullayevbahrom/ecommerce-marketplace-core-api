<?php

namespace app\components\RabbitMq;

use app\models\ProcessedEvent;

class InboxService
{
    public function alreadyProcessed(string $eventId): bool
    {
        return ProcessedEvent::find()->where(['event_id' => $eventId])->exists();
    }

    public function markProcessed(array $message, array $result = []): void
    {
        $model = new ProcessedEvent();
        $model->event_id = $message['event_id'];
        $model->correlation_id = $message['correlation_id'] ?? null;
        $model->source = $message['source'] ?? 'unknown';
        $model->event_type = $message['event_type'] ?? 'unknown';
        $model->entity_type = $message['entity_type'] ?? 'unknown';
        $model->entity_id = $message['entity_id'] ?? null;
        $model->processed_at = date('Y-m-d H:i:s');
        $model->result_json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!$model->save()) {
            throw new \RuntimeException('ProcessedEvent save failed: ' . json_encode($model->errors, JSON_UNESCAPED_UNICODE));
        }
    }
}