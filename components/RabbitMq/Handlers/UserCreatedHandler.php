<?php

namespace app\components\RabbitMq\Handlers;

class UserCreatedHandler
{
    public function handle(array $message): void
    {
        (new UserUpsertHandler())->handle($message);
    }
}
