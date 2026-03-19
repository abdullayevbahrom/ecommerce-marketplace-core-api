<?php

namespace app\components\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use app\models\IntegrationEvent;

class Publisher
{
    public function publish(IntegrationEvent $event): void
    {
        $connection = ConnectionFactory::make();
        $channel = $connection->channel();

        $payload = json_encode($event->payload_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $message = new AMQPMessage($payload, [
            'content_type' => 'application/json',
            'delivery_mode' => 2,
            'application_headers' => new AMQPTable([
                'x-event-id' => $event->event_id,
                'x-correlation-id' => $event->correlation_id,
                'x-event-type' => $event->event_type,
                'x-source' => $event->source,
                'x-attempt' => $event->attempts + 1,
            ]),
        ]);

        $channel->basic_publish($message, $event->exchange_name, $event->routing_key);

        $event->markPublished();

        $channel->close();
        $connection->close();
    }
}