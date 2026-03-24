<?php

namespace app\components\RabbitMq\Handlers;

class BrandUpdatedHandler
{
    public function handle(array $message): void
    {
        (new BrandUpsertHandler())->handle($message);
    }
}
