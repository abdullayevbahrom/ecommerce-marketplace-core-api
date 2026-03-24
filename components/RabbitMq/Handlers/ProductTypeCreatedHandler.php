<?php

namespace app\components\RabbitMq\Handlers;

class ProductTypeCreatedHandler
{
    public function handle(array $message): void
    {
        (new ProductTypeUpsertHandler())->handle($message);
    }
}
