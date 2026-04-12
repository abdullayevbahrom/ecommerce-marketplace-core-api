<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\IntegrationEvent;
use app\components\RabbitMq\TopologySetup;
use app\components\RabbitMq\Publisher;
use app\components\RabbitMq\Consumer;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPRuntimeException;

class RabbitController extends Controller
{
    private function ensureTopologySetup(): void
    {
        $attempt = 0;

        while (true) {
            try {
                (new TopologySetup())->run();
                return;
            } catch (AMQPIOException|AMQPConnectionClosedException|AMQPRuntimeException $e) {
                $attempt++;
                $delay = min(30, max(5, $attempt * 5));

                $this->stderr("RabbitMQ setup unavailable, retrying in {$delay}s: {$e->getMessage()}\n");
                Yii::warning([
                    'message' => 'RabbitMQ topology setup failed, retrying',
                    'attempt' => $attempt,
                    'delay_seconds' => $delay,
                    'error' => $e->getMessage(),
                ], __METHOD__);

                sleep($delay);
            }
        }
    }

    public function actionSetup(): int
    {
        try {
            (new TopologySetup())->run();
            $this->stdout("RabbitMQ topology created successfully.\n");
            return ExitCode::OK;
        } catch (\Throwable $e) {
            $this->stderr("Setup failed: {$e->getMessage()}\n");
            Yii::error($e, __METHOD__);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    public function actionPublishOutbox(int $limit = 100): int
    {
        $publisher = new Publisher();

        $events = IntegrationEvent::find()
            ->where(['status' => 'pending'])
            ->andWhere([
                'or',
                ['available_at' => null],
                ['<=', 'available_at', date('Y-m-d H:i:s')],
            ])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->all();

        foreach ($events as $event) {
            try {
                $publisher->publish($event);
                $event->markPublished();
                $this->stdout("Published: {$event->event_id}\n");
                Yii::info([
                    'message' => 'Event published',
                    'event_id' => $event->event_id,
                ], __METHOD__);
            } catch (\Throwable $e) {
                $event->markFailed($e->getMessage());

                $this->stderr("Failed: {$event->event_id} - {$e->getMessage()}\n");
                Yii::error($e, __METHOD__);
            }
        }

        return ExitCode::OK;
    }

    public function actionConsumeMarketSync(): int
    {
        $attempt = 0;
        $this->ensureTopologySetup();

        while (true) {
            try {
                (new Consumer())->consumeMarketSyncQueue();

                return ExitCode::OK;
            } catch (AMQPIOException|AMQPConnectionClosedException|AMQPRuntimeException $e) {
                $attempt++;
                $delay = min(30, max(5, $attempt * 5));

                $this->stderr("RabbitMQ unavailable, retrying in {$delay}s: {$e->getMessage()}\n");
                Yii::warning([
                    'message' => 'RabbitMQ consumer connection failed, retrying',
                    'attempt' => $attempt,
                    'delay_seconds' => $delay,
                    'error' => $e->getMessage(),
                ], __METHOD__);

                sleep($delay);
            } catch (\Throwable $e) {
                $this->stderr("Consumer failed: {$e->getMessage()}\n");
                Yii::error($e, __METHOD__);

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }
    }
}
