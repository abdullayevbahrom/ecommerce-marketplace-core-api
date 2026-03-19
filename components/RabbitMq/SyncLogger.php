<?php

namespace app\components\RabbitMq;

use app\models\SyncLog;

class SyncLogger
{
    public function processed(array $message, string $direction, string $queueName): void
    {
        $log = new SyncLog();
        $log->event_id = $message['event_id'] ?? null;
        $log->correlation_id = $message['correlation_id'] ?? null;
        $log->direction = $direction;
        $log->queue_name = $queueName;
        $log->routing_key = $message['event_type'] ?? null;
        $log->event_type = $message['event_type'] ?? 'unknown';
        $log->entity_type = $message['entity_type'] ?? 'unknown';
        $log->entity_id = $message['entity_id'] ?? null;
        $log->status = 'processed';
        $log->attempts = $message['meta']['attempt'] ?? 1;
        $log->request_payload = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $log->response_payload = json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $log->logged_at = date('Y-m-d H:i:s');
        $log->save(false);
    }

    public function failed(array $message, string $direction, string $queueName, string $error, int $attempt): void
    {
        $log = new SyncLog();
        $log->event_id = $message['event_id'] ?? null;
        $log->correlation_id = $message['correlation_id'] ?? null;
        $log->direction = $direction;
        $log->queue_name = $queueName;
        $log->routing_key = $message['event_type'] ?? null;
        $log->event_type = $message['event_type'] ?? 'unknown';
        $log->entity_type = $message['entity_type'] ?? 'unknown';
        $log->entity_id = $message['entity_id'] ?? null;
        $log->status = 'failed';
        $log->attempts = $attempt;
        $log->request_payload = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $log->error_message = $error;
        $log->logged_at = date('Y-m-d H:i:s');
        $log->save(false);
    }
}