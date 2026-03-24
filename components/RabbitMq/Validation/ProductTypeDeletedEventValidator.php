<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class ProductTypeDeletedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['product_type.deleted']],
            [['entity_type'], 'in', 'range' => ['product_type']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_product_type_id'], 'required'],
            [['id', 'sklad_product_type_id', 'yii_product_type_id'], 'integer'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
