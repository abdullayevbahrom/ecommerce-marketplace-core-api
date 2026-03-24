<?php

namespace app\components\RabbitMq\Handlers;

class RegionUpdatedHandler
{
    public function handle(array $message): void
    {
        (new RegionUpsertHandler())->handle($message);
    }
}
