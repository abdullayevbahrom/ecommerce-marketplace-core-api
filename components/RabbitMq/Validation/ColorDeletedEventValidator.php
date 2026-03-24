<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class ColorDeletedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['color.deleted']],
            [['entity_type'], 'in', 'range' => ['color']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_color_id'], 'required'],
            [['id', 'sklad_color_id', 'yii_color_id'], 'integer'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
