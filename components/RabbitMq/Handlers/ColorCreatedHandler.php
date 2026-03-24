<?php

namespace app\components\RabbitMq\Handlers;

class ColorCreatedHandler
{
    public function handle(array $message): void
    {
        (new ColorUpsertHandler())->handle($message);
    }
}
