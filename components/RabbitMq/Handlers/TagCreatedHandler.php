<?php

namespace app\components\RabbitMq\Handlers;

class TagCreatedHandler
{
    public function handle(array $message): void
    {
        (new TagUpsertHandler())->handle($message);
    }
}
