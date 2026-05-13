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
            'ikpu.created' => (new PayloadEventValidator(
                ['ikpu.created'],
                ['ikpu'],
                [
                    [['id', 'yii_ikpu_id', 'status'], 'integer'],
                    [['code', 'name_ru', 'name_uz', 'name_en', 'parent_code'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['code']) || empty($payload['name_ru'])) {
                        throw new EventValidationException('Create payload must contain IKPU code and name_ru');
                    }
                }
            ))->validate($message),
            'ikpu.updated' => (new PayloadEventValidator(
                ['ikpu.updated'],
                ['ikpu'],
                [
                    [['id', 'yii_ikpu_id', 'status'], 'integer'],
                    [['code', 'name_ru', 'name_uz', 'name_en', 'parent_code'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['code']) || empty($payload['name_ru'])) {
                        throw new EventValidationException('Update payload must contain IKPU code and name_ru');
                    }
                }
            ))->validate($message),
            'ikpu.deleted' => (new PayloadEventValidator(
                ['ikpu.deleted'],
                ['ikpu'],
                [
                    [['id', 'yii_ikpu_id'], 'integer'],
                    [['code'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['code']) && empty($payload['id']) && empty($payload['yii_ikpu_id'])) {
                        throw new EventValidationException('Delete payload must contain IKPU identifier');
                    }
                }
            ))->validate($message),
            'tag.created' => (new PayloadEventValidator(
                ['tag.created'],
                ['tag'],
                [
                    [['id', 'yii_tag_id', 'status'], 'integer'],
                    [['name_ru', 'name_uz', 'name_en'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['name_ru'])) {
                        throw new EventValidationException('Create payload must contain tag name_ru');
                    }
                }
            ))->validate($message),
            'tag.updated' => (new PayloadEventValidator(
                ['tag.updated'],
                ['tag'],
                [
                    [['id', 'yii_tag_id', 'status'], 'integer'],
                    [['name_ru', 'name_uz', 'name_en'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['name_ru'])) {
                        throw new EventValidationException('Update payload must contain tag name_ru');
                    }
                }
            ))->validate($message),
            'tag.deleted' => (new PayloadEventValidator(
                ['tag.deleted'],
                ['tag'],
                [
                    [['id', 'yii_tag_id'], 'integer'],
                ],
                function (array $payload): void {
                    if (empty($payload['id']) && empty($payload['yii_tag_id'])) {
                        throw new EventValidationException('Delete payload must contain tag identifier');
                    }
                }
            ))->validate($message),
            'region.created' => (new PayloadEventValidator(
                ['region.created'],
                ['region'],
                [
                    [['id', 'yii_region_id', 'bts_id', 'status', 'selecting'], 'integer'],
                    [['name', 'name_ru', 'name_uz', 'name_en', 'img'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['name']) && empty($payload['name_ru'])) {
                        throw new EventValidationException('Create payload must contain region name');
                    }
                }
            ))->validate($message),
            'region.updated' => (new PayloadEventValidator(
                ['region.updated'],
                ['region'],
                [
                    [['id', 'yii_region_id', 'bts_id', 'status', 'selecting'], 'integer'],
                    [['name', 'name_ru', 'name_uz', 'name_en', 'img'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['name']) && empty($payload['name_ru'])) {
                        throw new EventValidationException('Update payload must contain region name');
                    }
                }
            ))->validate($message),
            'region.deleted' => (new PayloadEventValidator(
                ['region.deleted'],
                ['region'],
                [
                    [['id', 'yii_region_id', 'bts_id'], 'integer'],
                ],
                function (array $payload): void {
                    if (empty($payload['id']) && empty($payload['yii_region_id']) && empty($payload['bts_id'])) {
                        throw new EventValidationException('Delete payload must contain region identifier');
                    }
                }
            ))->validate($message),
            'delivery.created' => (new PayloadEventValidator(
                ['delivery.created'],
                ['delivery'],
                [
                    [['id', 'yii_delivery_id', 'status', 'sort'], 'integer'],
                    [['name_ru', 'name_uz', 'name_en', 'description_ru', 'description_uz', 'description_en'], 'safe'],
                    [['price'], 'number'],
                ],
                function (array $payload): void {
                    if (empty($payload['name_ru'])) {
                        throw new EventValidationException('Create payload must contain delivery name_ru');
                    }
                }
            ))->validate($message),
            'delivery.updated' => (new PayloadEventValidator(
                ['delivery.updated'],
                ['delivery'],
                [
                    [['id', 'yii_delivery_id', 'status', 'sort'], 'integer'],
                    [['name_ru', 'name_uz', 'name_en', 'description_ru', 'description_uz', 'description_en'], 'safe'],
                    [['price'], 'number'],
                ],
                function (array $payload): void {
                    if (empty($payload['name_ru'])) {
                        throw new EventValidationException('Update payload must contain delivery name_ru');
                    }
                }
            ))->validate($message),
            'delivery.deleted' => (new PayloadEventValidator(
                ['delivery.deleted'],
                ['delivery'],
                [
                    [['id', 'yii_delivery_id'], 'integer'],
                ],
                function (array $payload): void {
                    if (empty($payload['id']) && empty($payload['yii_delivery_id'])) {
                        throw new EventValidationException('Delete payload must contain delivery identifier');
                    }
                }
            ))->validate($message),
            'office.created' => (new PayloadEventValidator(
                ['office.created'],
                ['office'],
                [
                    [['id', 'yii_office_id', 'status'], 'integer'],
                    [['name', 'address'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['name'])) {
                        throw new EventValidationException('Create payload must contain office name');
                    }
                }
            ))->validate($message),
            'office.updated' => (new PayloadEventValidator(
                ['office.updated'],
                ['office'],
                [
                    [['id', 'yii_office_id', 'status'], 'integer'],
                    [['name', 'address'], 'safe'],
                ],
                function (array $payload): void {
                    if (empty($payload['name'])) {
                        throw new EventValidationException('Update payload must contain office name');
                    }
                }
            ))->validate($message),
            'office.deleted' => (new PayloadEventValidator(
                ['office.deleted'],
                ['office'],
                [
                    [['id', 'yii_office_id'], 'integer'],
                ],
                function (array $payload): void {
                    if (empty($payload['id']) && empty($payload['yii_office_id'])) {
                        throw new EventValidationException('Delete payload must contain office identifier');
                    }
                }
            ))->validate($message),
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
            'product.asl_belgisi.updated' => (new PayloadEventValidator(
                ['product.asl_belgisi.updated'],
                ['product', 'product_asl_belgisi'],
                [
                    [['yii_product_id'], 'required'],
                    [['yii_product_id', 'sklad_product_id'], 'integer'],
                    [['asl_belgisi_status', 'asl_belgisi_checked_at'], 'safe'],
                    [['asl_belgisi_data'], 'safe'],
                ]
            ))->validate($message),
            'stock.created', 'branch.created' => (new PayloadEventValidator(
                ['stock.created', 'branch.created'],
                ['stock', 'branch'],
                [
                    [['id', 'shop_id', 'yii_shop_id', 'status', 'sort'], 'integer'],
                    [['name_ru', 'name_uz', 'name_en', 'address', 'phone', 'responsible_person'], 'safe'],
                    [['for_marketplace'], 'safe'],
                ],
                function (array $payload): void {
                    if (isset($payload['for_marketplace']) && (int) $payload['for_marketplace'] === 0) {
                        return;
                    }
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
