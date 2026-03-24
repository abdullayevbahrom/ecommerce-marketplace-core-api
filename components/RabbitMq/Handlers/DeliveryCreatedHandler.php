<?php

namespace app\components\RabbitMq\Handlers;

class DeliveryCreatedHandler
{
    public function handle(array $message): void
    {
        (new DeliveryUpsertHandler())->handle($message);
    }
}
