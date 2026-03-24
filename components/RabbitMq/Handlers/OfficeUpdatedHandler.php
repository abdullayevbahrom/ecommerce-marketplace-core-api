<?php

namespace app\components\RabbitMq\Handlers;

class OfficeUpdatedHandler
{
    public function handle(array $message): void
    {
        (new OfficeUpsertHandler())->handle($message);
    }
}
