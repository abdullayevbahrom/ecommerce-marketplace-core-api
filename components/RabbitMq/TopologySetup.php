<?php

namespace app\components\RabbitMq;

class TopologySetup
{
    public function run(): void
    {
        $rabbitMq = \Yii::$app->params['rabbitmq'] ?? null;
        if (!$rabbitMq) {
            throw new \RuntimeException('RabbitMQ konfiguratsiyasi topilmadi');
        }

        $connection = ConnectionFactory::make();
        $channel = $connection->channel();

        $channel->exchange_declare($rabbitMq['exchange_sklad_to_market'], 'topic', false, true, false);
        $channel->exchange_declare($rabbitMq['exchange_market_to_sklad'], 'topic', false, true, false);
        $channel->exchange_declare($rabbitMq['exchange_sklad_to_market_retry'], 'topic', false, true, false);
        $channel->exchange_declare($rabbitMq['exchange_market_to_sklad_retry'], 'topic', false, true, false);
        $channel->exchange_declare($rabbitMq['exchange_auth_outbox'], 'topic', false, true, false);

        $channel->queue_declare($rabbitMq['queue_market_sync_main'], false, true, false, false);
        $channel->queue_bind($rabbitMq['queue_market_sync_main'], $rabbitMq['exchange_sklad_to_market'], '#');

        $channel->queue_declare(
            'market.sync.retry.30s',
            false,
            true,
            false,
            false,
            false,
            [
                'x-message-ttl' => ['I', 30000],
                'x-dead-letter-exchange' => ['S', $rabbitMq['exchange_sklad_to_market']],
                'x-dead-letter-routing-key' => ['S', '#'],
            ]
        );

        $channel->queue_declare(
            'market.sync.retry.5m',
            false,
            true,
            false,
            false,
            false,
            [
                'x-message-ttl' => ['I', 300000],
                'x-dead-letter-exchange' => ['S', $rabbitMq['exchange_sklad_to_market']],
                'x-dead-letter-routing-key' => ['S', '#'],
            ]
        );

        $channel->queue_declare('market.sync.dlq', false, true, false, false);

        $channel->queue_bind('market.sync.retry.30s', $rabbitMq['exchange_sklad_to_market_retry'], 'retry.30s');
        $channel->queue_bind('market.sync.retry.5m', $rabbitMq['exchange_sklad_to_market_retry'], 'retry.5m');

        $channel->queue_declare($rabbitMq['queue_sklad_sync_main'], false, true, false, false);
        $channel->queue_bind($rabbitMq['queue_sklad_sync_main'], $rabbitMq['exchange_market_to_sklad'], '#');

        $channel->queue_declare(
            'sklad.sync.retry.30s',
            false,
            true,
            false,
            false,
            false,
            [
                'x-message-ttl' => ['I', 30000],
                'x-dead-letter-exchange' => ['S', $rabbitMq['exchange_market_to_sklad']],
                'x-dead-letter-routing-key' => ['S', '#'],
            ]
        );

        $channel->queue_declare(
            'sklad.sync.retry.5m',
            false,
            true,
            false,
            false,
            false,
            [
                'x-message-ttl' => ['I', 300000],
                'x-dead-letter-exchange' => ['S', $rabbitMq['exchange_market_to_sklad']],
                'x-dead-letter-routing-key' => ['S', '#'],
            ]
        );

        $channel->queue_declare('sklad.sync.dlq', false, true, false, false);

        $channel->queue_bind('sklad.sync.retry.30s', $rabbitMq['exchange_market_to_sklad_retry'], 'retry.30s');
        $channel->queue_bind('sklad.sync.retry.5m', $rabbitMq['exchange_market_to_sklad_retry'], 'retry.5m');

        $channel->queue_declare($rabbitMq['queue_auth_outbox_shop'], false, true, false, false);
        $channel->queue_bind($rabbitMq['queue_auth_outbox_shop'], $rabbitMq['exchange_auth_outbox'], 'auth.event');

        $channel->close();
        $connection->close();
    }
}
