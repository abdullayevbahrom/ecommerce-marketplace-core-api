<?php

namespace app\components\RabbitMq\Handlers;

class CategoryUpdatedHandler
{
    public function handle(array $message): void
    {
        (new CategoryUpsertHandler())->handle($message);
    }
}
