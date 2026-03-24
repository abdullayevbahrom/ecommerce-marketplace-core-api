<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class FilterDeletedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['filter.deleted']],
            [['entity_type'], 'in', 'range' => ['filter']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_filter_id'], 'required'],
            [['id', 'sklad_filter_id', 'yii_filter_id'], 'integer'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
