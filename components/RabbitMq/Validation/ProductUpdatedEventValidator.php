<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class ProductUpdatedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['product.updated']],
            [['entity_type'], 'in', 'range' => ['product']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payload = $message['payload'] ?? [];

        $payloadModel = DynamicModel::validateData($payload, [
            [['sklad_product_id', 'shop_id', 'user_id', 'token_key', 'status', 'price', 'amount'], 'required'],
            [['sklad_product_id', 'yii_product_id', 'shop_id', 'user_id', 'stock_id', 'status', 'category_id', 'brand_id', 'color_id'], 'integer'],
            [['price', 'amount', 'discount'], 'number', 'min' => 0],
            [['token_key', 'sku', 'barcode', 'name_uz', 'name_ru', 'name_en'], 'string', 'max' => 255],
            [['description_uz', 'description_ru', 'description_en'], 'string'],
            [['colors', 'filters', 'product_types', 'images'], 'safe'],
            [['status'], 'in', 'range' => [1, 2]],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
