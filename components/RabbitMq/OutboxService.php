<?php

namespace app\components\RabbitMq;

use app\models\IntegrationEvent;

class OutboxService
{
    public function queue(
        string $exchange,
        string $routingKey,
        string $eventType,
        string $entityType,
        ?int $entityId,
        string $source,
        ?int $branchId,
        array $message
    ): IntegrationEvent {
        $event = new IntegrationEvent();
        $event->event_id = $message['event_id'];
        $event->correlation_id = $message['correlation_id'] ?? null;
        $event->exchange_name = $exchange;
        $event->routing_key = $routingKey;
        $event->event_type = $eventType;
        $event->entity_type = $entityType;
        $event->entity_id = $entityId;
        $event->source = $source;
        $event->branch_id = $branchId;
        $event->payload_json = $message;
        $event->status = 'pending';
        $event->available_at = date('Y-m-d H:i:s');

        return $event->save(false) ? $event : throw new \RuntimeException('Failed to queue integration event');
    }
}