<?php

namespace app\components\RabbitMq\Handlers;

class ProductTypeUpdatedHandler
{
    public function handle(array $message): void
    {
        (new ProductTypeUpsertHandler())->handle($message);
    }
}
