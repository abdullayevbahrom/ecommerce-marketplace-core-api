<?php

namespace app\components\RabbitMq\Handlers;

class CategoryCreatedHandler
{
    public function handle(array $message): void
    {
        (new CategoryUpsertHandler())->handle($message);
    }
}
