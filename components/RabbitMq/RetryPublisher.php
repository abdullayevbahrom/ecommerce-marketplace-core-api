<?php

namespace app\components\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

class RetryPublisher
{
    public function publish(array $messageBody, string $retryExchange, string $retryRoutingKey): void
    {
        $connection = ConnectionFactory::make();
        $channel = $connection->channel();

        $body = json_encode($messageBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $msg = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => 2,
        ]);

        $channel->basic_publish($msg, $retryExchange, $retryRoutingKey);

        $channel->close();
        $connection->close();
    }
}