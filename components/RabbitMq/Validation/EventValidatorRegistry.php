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
            'category.created' => (new CategoryCreatedEventValidator())->validate($message),
            'category.updated' => (new CategoryUpdatedEventValidator())->validate($message),
            'category.deleted' => (new CategoryDeletedEventValidator())->validate($message),
            'filter.created' => (new FilterCreatedEventValidator())->validate($message),
            'filter.updated' => (new FilterUpdatedEventValidator())->validate($message),
            'filter.deleted' => (new FilterDeletedEventValidator())->validate($message),
            'product_type.created' => (new ProductTypeCreatedEventValidator())->validate($message),
            'product_type.updated' => (new ProductTypeUpdatedEventValidator())->validate($message),
            'product_type.deleted' => (new ProductTypeDeletedEventValidator())->validate($message),
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
