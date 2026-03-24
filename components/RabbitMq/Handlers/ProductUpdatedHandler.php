<?php

namespace app\components\RabbitMq\Handlers;

class ProductUpdatedHandler
{
    public function handle(array $message): void
    {
        (new ProductUpsertHandler())->handle($message);
    }
}
