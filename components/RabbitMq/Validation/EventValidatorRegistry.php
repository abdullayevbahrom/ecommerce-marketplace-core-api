<?php

namespace app\components\RabbitMq\Validation;

class EventValidatorRegistry
{
    public function validate(array $message): array
    {
        $eventType = $message['event_type'] ?? null;

        return match ($eventType) {
            'product.created' => (new ProductCreatedEventValidator())->validate($message),
            'product.updated' => (new ProductUpdatedEventValidator())->validate($message),
            'product.deleted' => (new ProductDeletedEventValidator())->validate($message),
            'color.created' => (new ColorCreatedEventValidator())->validate($message),
            'color.updated' => (new ColorUpdatedEventValidator())->validate($message),
            'color.deleted' => (new ColorDeletedEventValidator())->validate($message),
            'brand.created' => (new BrandCreatedEventValidator())->validate($message),
            'brand.updated' => (new BrandUpdatedEventValidator())->validate($message),
            'brand.deleted' => (new BrandDeletedEventValidator())->validate($message),
            default => $message,
            // default => throw new EventValidationException("Validator topilmadi: {$eventType}"),
        };
    }
}
