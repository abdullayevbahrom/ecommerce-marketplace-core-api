<?php

namespace app\components\RabbitMq\Handlers;

class BrandCreatedHandler
{
    public function handle(array $message): void
    {
        (new BrandUpsertHandler())->handle($message);
    }
}
