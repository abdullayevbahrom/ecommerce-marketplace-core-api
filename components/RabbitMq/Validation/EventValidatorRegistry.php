<?php

namespace app\components\RabbitMq\Validation;

class EventValidatorRegistry
{
    public function validate(array $message): array
    {
        $eventType = $message['event_type'] ?? null;

        return match ($eventType) {
            'product.created' => (new ProductCreatedEventValidator())->validate($message),
            default => $message,
            // default => throw new EventValidationException("Validator topilmadi: {$eventType}"),
        };
    }
}