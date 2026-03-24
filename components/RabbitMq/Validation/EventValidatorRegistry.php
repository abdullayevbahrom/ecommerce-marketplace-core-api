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
            'moderation.created' => (new PayloadEventValidator(
                ['moderation.created'],
                ['moderation'],
                [
                    [['id', 'entity_id', 'moderator_id'], 'required'],
                    [['id', 'entity_id', 'moderator_id'], 'integer'],
                    [['entity_type', 'action', 'status_after'], 'required'],
                    [['entity_type'], 'in', 'range' => ['product', 'category', 'brand', 'color', 'filter', 'product-type']],
                    [['action'], 'in', 'range' => ['approve', 'reject', 'block']],
                    [['status_after'], 'in', 'range' => ['approved', 'rejected', 'pending']],
                    [['comment'], 'safe'],
                    [['metadata'], 'safe'],
                ]
            ))->validate($message),
            'stock.created', 'branch.created' => (new PayloadEventValidator(
                ['stock.created', 'branch.created'],
                ['stock', 'branch'],
                [
                    [['id', 'shop_id', 'yii_shop_id', 'status', 'sort'], 'integer'],
                    [['name_ru', 'name_uz', 'name_en', 'address', 'phone', 'responsible_person'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['shop_id']) && empty($payload['yii_shop_id'])) {
                        throw new EventValidationException('Create payload must contain shop identifier');
                    }
                }
            ))->validate($message),
            'stock.updated', 'branch.updated' => (new PayloadEventValidator(
                ['stock.updated', 'branch.updated'],
                ['stock', 'branch'],
                [
                    [['id', 'shop_id', 'yii_shop_id', 'status', 'sort'], 'integer'],
                    [['name_ru', 'name_uz', 'name_en', 'address', 'phone', 'responsible_person'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['id']) && empty($payload['yii_stock_id'])) {
                        throw new EventValidationException('Update payload must contain stock identifier');
                    }
                }
            ))->validate($message),
            'stock.deleted', 'branch.deleted' => (new PayloadEventValidator(
                ['stock.deleted', 'branch.deleted'],
                ['stock', 'branch'],
                [
                    [['id'], 'integer'],
                ],
                function (array $payload): void {
                    if (empty($payload['id']) && empty($payload['yii_stock_id'])) {
                        throw new EventValidationException('Delete payload must contain stock identifier');
                    }
                }
            ))->validate($message),
            'user.created' => (new PayloadEventValidator(
                ['user.created'],
                ['user'],
                [
                    [['id', 'yii_shop_id', 'status', 'role'], 'integer'],
                    [['name', 'phone', 'source'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['yii_shop_id']) || empty($payload['phone'])) {
                        throw new EventValidationException('Create payload must contain yii_shop_id and phone');
                    }
                }
            ))->validate($message),
            'user.updated' => (new PayloadEventValidator(
                ['user.updated'],
                ['user'],
                [
                    [['id', 'yii_shop_id', 'status', 'role'], 'integer'],
                    [['name', 'phone', 'source'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['id']) && empty($payload['phone'])) {
                        throw new EventValidationException('Update payload must contain user identifier');
                    }
                }
            ))->validate($message),
            'user.deleted' => (new PayloadEventValidator(
                ['user.deleted'],
                ['user'],
                [
                    [['id'], 'integer'],
                ],
                function (array $payload): void {
                    if (empty($payload['id'])) {
                        throw new EventValidationException('Delete payload must contain user id');
                    }
                }
            ))->validate($message),
            default => throw new EventValidationException("Validator topilmadi: {$eventType}"),
        };
    }
}
