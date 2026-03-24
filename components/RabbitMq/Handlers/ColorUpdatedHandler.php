<?php

namespace app\components\RabbitMq\Handlers;

class ColorUpdatedHandler
{
    public function handle(array $message): void
    {
        (new ColorUpsertHandler())->handle($message);
    }
}
