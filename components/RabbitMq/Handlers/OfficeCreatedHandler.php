<?php

namespace app\components\RabbitMq\Handlers;

class OfficeCreatedHandler
{
    public function handle(array $message): void
    {
        (new OfficeUpsertHandler())->handle($message);
    }
}
