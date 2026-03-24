<?php

namespace app\components\RabbitMq\Handlers;

class ProductCreatedHandler
{
    public function handle(array $message): void
    {
        (new ProductUpsertHandler())->handle($message);
    }
}
