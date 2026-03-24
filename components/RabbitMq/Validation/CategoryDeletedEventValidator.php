<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class CategoryDeletedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['category.deleted']],
            [['entity_type'], 'in', 'range' => ['category']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_category_id'], 'required'],
            [['id', 'sklad_category_id', 'yii_category_id'], 'integer'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
