<?php

namespace app\components\RabbitMq\Handlers;

class UserUpdatedHandler
{
    public function handle(array $message): void
    {
        (new UserUpsertHandler())->handle($message);
    }
}
