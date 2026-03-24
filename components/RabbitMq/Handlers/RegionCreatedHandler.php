<?php

namespace app\components\RabbitMq\Handlers;

class RegionCreatedHandler
{
    public function handle(array $message): void
    {
        (new RegionUpsertHandler())->handle($message);
    }
}
