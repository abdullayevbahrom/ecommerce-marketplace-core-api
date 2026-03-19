<?php

namespace app\components\RabbitMq\Validation;

class EventValidationException extends \RuntimeException
{
    private array $errors = [];

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}