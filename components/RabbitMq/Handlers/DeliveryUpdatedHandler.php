<?php

namespace app\components\RabbitMq\Handlers;

class DeliveryUpdatedHandler
{
    public function handle(array $message): void
    {
        (new DeliveryUpsertHandler())->handle($message);
    }
}
