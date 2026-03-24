<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class BrandDeletedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['brand.deleted']],
            [['entity_type'], 'in', 'range' => ['brand']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_brand_id'], 'required'],
            [['id', 'sklad_brand_id', 'yii_brand_id'], 'integer'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
