<?php

namespace app\components\RabbitMq\Handlers;

class TagUpdatedHandler
{
    public function handle(array $message): void
    {
        (new TagUpsertHandler())->handle($message);
    }
}
