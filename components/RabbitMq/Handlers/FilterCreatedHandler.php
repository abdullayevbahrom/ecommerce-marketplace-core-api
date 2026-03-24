<?php

namespace app\components\RabbitMq\Handlers;

class FilterCreatedHandler
{
    public function handle(array $message): void
    {
        (new FilterUpsertHandler())->handle($message);
    }
}
