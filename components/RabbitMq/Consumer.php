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

                    // Ensure DB connection is alive
                    try {
                        \Yii::$app->db->createCommand('SELECT 1')->queryScalar();
                    } catch (\Throwable $e) {
                        \Yii::info("Reconnecting to DB in consumer: " . $e->getMessage(), __METHOD__);
                        \Yii::$app->db->close();
                        \Yii::$app->db->open();
                    }

                    match ($validated['event_type']) {
                        'moderation.created' => (new \app\components\RabbitMq\Handlers\ModerationCreatedHandler())->handle($validated),
                        'product.asl_belgisi.updated' => (new \app\components\RabbitMq\Handlers\AslBelgisiUpdatedHandler())->handle($validated),
                        'product.created' => (new \app\components\RabbitMq\Handlers\ProductCreatedHandler())->handle($validated),
                        'product.updated' => (new \app\components\RabbitMq\Handlers\ProductUpdatedHandler())->handle($validated),
                        'product.deleted' => (new \app\components\RabbitMq\Handlers\ProductDeletedHandler())->handle($validated),
                        'category.created' => (new \app\components\RabbitMq\Handlers\CategoryCreatedHandler())->handle($validated),
                        'category.updated' => (new \app\components\RabbitMq\Handlers\CategoryUpdatedHandler())->handle($validated),
                        'category.deleted' => (new \app\components\RabbitMq\Handlers\CategoryDeletedHandler())->handle($validated),
                        'filter.created' => (new \app\components\RabbitMq\Handlers\FilterCreatedHandler())->handle($validated),
                        'filter.updated' => (new \app\components\RabbitMq\Handlers\FilterUpdatedHandler())->handle($validated),
                        'filter.deleted' => (new \app\components\RabbitMq\Handlers\FilterDeletedHandler())->handle($validated),
                        'ikpu.created' => (new \app\components\RabbitMq\Handlers\IkpuCreatedHandler())->handle($validated),
                        'ikpu.updated' => (new \app\components\RabbitMq\Handlers\IkpuUpdatedHandler())->handle($validated),
                        'ikpu.deleted' => (new \app\components\RabbitMq\Handlers\IkpuDeletedHandler())->handle($validated),
                        'tag.created' => (new \app\components\RabbitMq\Handlers\TagCreatedHandler())->handle($validated),
                        'tag.updated' => (new \app\components\RabbitMq\Handlers\TagUpdatedHandler())->handle($validated),
                        'tag.deleted' => (new \app\components\RabbitMq\Handlers\TagDeletedHandler())->handle($validated),
                        'region.created' => (new \app\components\RabbitMq\Handlers\RegionCreatedHandler())->handle($validated),
                        'region.updated' => (new \app\components\RabbitMq\Handlers\RegionUpdatedHandler())->handle($validated),
                        'region.deleted' => (new \app\components\RabbitMq\Handlers\RegionDeletedHandler())->handle($validated),
                        'delivery.created' => (new \app\components\RabbitMq\Handlers\DeliveryCreatedHandler())->handle($validated),
                        'delivery.updated' => (new \app\components\RabbitMq\Handlers\DeliveryUpdatedHandler())->handle($validated),
                        'delivery.deleted' => (new \app\components\RabbitMq\Handlers\DeliveryDeletedHandler())->handle($validated),
                        'office.created' => (new \app\components\RabbitMq\Handlers\OfficeCreatedHandler())->handle($validated),
                        'office.updated' => (new \app\components\RabbitMq\Handlers\OfficeUpdatedHandler())->handle($validated),
                        'office.deleted' => (new \app\components\RabbitMq\Handlers\OfficeDeletedHandler())->handle($validated),
                        'product_type.created' => (new \app\components\RabbitMq\Handlers\ProductTypeCreatedHandler())->handle($validated),
                        'product_type.updated' => (new \app\components\RabbitMq\Handlers\ProductTypeUpdatedHandler())->handle($validated),
                        'product_type.deleted' => (new \app\components\RabbitMq\Handlers\ProductTypeDeletedHandler())->handle($validated),
                        'color.created' => (new \app\components\RabbitMq\Handlers\ColorCreatedHandler())->handle($validated),
                        'color.updated' => (new \app\components\RabbitMq\Handlers\ColorUpdatedHandler())->handle($validated),
                        'color.deleted' => (new \app\components\RabbitMq\Handlers\ColorDeletedHandler())->handle($validated),
                        'brand.created' => (new \app\components\RabbitMq\Handlers\BrandCreatedHandler())->handle($validated),
                        'brand.updated' => (new \app\components\RabbitMq\Handlers\BrandUpdatedHandler())->handle($validated),
                        'brand.deleted' => (new \app\components\RabbitMq\Handlers\BrandDeletedHandler())->handle($validated),
                        'stock.created', 'branch.created' => (new \app\components\RabbitMq\Handlers\StockCreatedHandler())->handle($validated),
                        'stock.updated', 'branch.updated' => (new \app\components\RabbitMq\Handlers\StockUpdatedHandler())->handle($validated),
                        'stock.deleted', 'branch.deleted' => (new \app\components\RabbitMq\Handlers\StockDeletedHandler())->handle($validated),
                        'user.created' => (new \app\components\RabbitMq\Handlers\UserCreatedHandler())->handle($validated),
                        'user.updated' => (new \app\components\RabbitMq\Handlers\UserUpdatedHandler())->handle($validated),
                        'user.deleted' => (new \app\components\RabbitMq\Handlers\UserDeletedHandler())->handle($validated),
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

    public function consumeAuthOutboxQueue(): void
    {
        $rabbitMq = \Yii::$app->params['rabbitmq'];
        $queue = (string) ($rabbitMq['queue_auth_outbox_shop'] ?? 'auth.outbox.shop');

        $connection = ConnectionFactory::make();
        $channel = $connection->channel();
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) {
                $body = json_decode($msg->getBody(), true);

                try {
                    if (!is_array($body) || empty($body['event_type']) || !isset($body['payload']) || !is_array($body['payload'])) {
                        throw new \RuntimeException('Invalid auth outbox message body');
                    }

                    (new \app\components\RabbitMq\Handlers\AuthGatewayEventHandler())->handle($body);
                    $msg->ack();
                } catch (\Throwable $e) {
                    \Yii::error([
                        'message' => 'Auth outbox consume failed',
                        'error' => $e->getMessage(),
                        'payload' => $body,
                    ], __METHOD__);
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
