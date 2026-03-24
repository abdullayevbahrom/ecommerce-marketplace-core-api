<?php

namespace app\components\RabbitMq\Handlers;

class FilterUpdatedHandler
{
    public function handle(array $message): void
    {
        (new FilterUpsertHandler())->handle($message);
    }
}
