<?php

namespace app\components\RabbitMq;

use app\components\RabbitMq\Validation\EventValidatorRegistry;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer
{
    public function consumeMarketSyncQueue(): void
    {
        $connection = ConnectionFactory::make();
        $channel = $connection->channel();
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(
            'market.sync.main',
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) {
                $body = json_decode($msg->getBody(), true);
                $inbox = new InboxService();
                $logger = new SyncLogger();

                try {
                    if (!is_array($body)) {
                        throw new \RuntimeException('Invalid JSON message body');
                    }

                    $validated = (new EventValidatorRegistry())
                        ->validate($body);

                    if ($inbox->alreadyProcessed($validated['event_id'])) {
                        $msg->ack();
                        return;
                    }

                    match ($validated['event_type']) {
                        'product.created' => (new \app\components\RabbitMq\Handlers\ProductCreatedHandler())->handle($validated),
                        'product.updated' => (new \app\components\RabbitMq\Handlers\ProductUpdatedHandler())->handle($validated),
                        'product.deleted' => (new \app\components\RabbitMq\Handlers\ProductDeletedHandler())->handle($validated),
                        'color.created' => (new \app\components\RabbitMq\Handlers\ColorCreatedHandler())->handle($validated),
                        'color.updated' => (new \app\components\RabbitMq\Handlers\ColorUpdatedHandler())->handle($validated),
                        'color.deleted' => (new \app\components\RabbitMq\Handlers\ColorDeletedHandler())->handle($validated),
                        'brand.created' => (new \app\components\RabbitMq\Handlers\BrandCreatedHandler())->handle($validated),
                        'brand.updated' => (new \app\components\RabbitMq\Handlers\BrandUpdatedHandler())->handle($validated),
                        'brand.deleted' => (new \app\components\RabbitMq\Handlers\BrandDeletedHandler())->handle($validated),
                        'branch.created' => (new \app\components\RabbitMq\Handlers\StockCreatedHandler())->handle($validated),
                        'branch.updated' => (new \app\components\RabbitMq\Handlers\StockUpdatedHandler())->handle($validated),
                        'branch.deleted' => (new \app\components\RabbitMq\Handlers\StockDeletedHandler())->handle($validated),
                        default => throw new \RuntimeException('Unsupported event: ' . $body['event_type']),
                    };

                    $inbox->markProcessed($validated, ['status' => 'ok']);
                    $logger->processed($validated, 'market_to_sklad', 'sklad_sync_main');
                    $msg->ack();
                } catch (\Throwable $e) {
                    $attempt = (int) ($body['meta']['attempt'] ?? 1);

                    $logger->failed(
                        is_array($body) ? $body : ['event_type' => 'unknown', 'entity_type' => 'unknown'],
                        'market_to_sklad',
                        'sklad_sync_main',
                        $e->getMessage(),
                        $attempt
                    );

                    if ($attempt >= 5) {
                        (new RetryPublisher())->publish($body, '', 'market.sync.dlq');
                        $msg->ack();
                        return;
                    }

                    $body['meta']['attempt'] = $attempt + 1;
                    $retryKey = $attempt < 3 ? 'retry.30s' : 'retry.5m';

                    (new RetryPublisher())->publish($body, 'sklad_to_market.retry', $retryKey);
                    $msg->ack();
                }
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
