<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class ProductDeletedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['product.deleted']],
            [['entity_type'], 'in', 'range' => ['product']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payload = $message['payload'] ?? [];

        $payloadModel = DynamicModel::validateData($payload, [
            [['sklad_product_id', 'shop_id', 'token_key'], 'safe'],
            [['sklad_product_id', 'yii_product_id', 'shop_id'], 'integer'],
            [['token_key'], 'string', 'max' => 255],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        if (empty($payload['sklad_product_id']) && empty($payload['yii_product_id']) && empty($payload['token_key'])) {
            throw new EventValidationException('Delete payload must contain an identifier');
        }

        return $message;
    }
}
