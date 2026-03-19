<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class ProductCreatedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['product.created']],
            [['entity_type'], 'in', 'range' => ['product']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payload = $message['payload'] ?? [];

        $payloadModel = DynamicModel::validateData($payload, [
            [['id', 'shop_id', 'user_id', 'token_key', 'status', 'price', 'amount', 'name_uz'], 'required'],
            [['id', 'shop_id', 'user_id', 'status', 'price', 'amount', 'category_id', 'brand_id'], 'integer'],
            [['token_key', 'sku', 'barcode', 'name_uz', 'name_ru', 'name_en'], 'string', 'max' => 255],
            [['description_uz', 'description_ru'], 'string'],
            [['colors', 'filters', 'product_types'], 'safe'],
            [['status'], 'in', 'range' => [2]],
            [['price', 'amount'], 'integer', 'min' => 0],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        if (isset($payload['colors']) && !is_array($payload['colors'])) {
            throw new EventValidationException('payload.colors array bo‘lishi kerak');
        }

        if (isset($payload['filters']) && !is_array($payload['filters'])) {
            throw new EventValidationException('payload.filters array bo‘lishi kerak');
        }

        if (isset($payload['product_types']) && !is_array($payload['product_types'])) {
            throw new EventValidationException('payload.product_types array bo‘lishi kerak');
        }

        return $message;
    }
}