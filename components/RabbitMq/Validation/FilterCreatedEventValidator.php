<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class FilterCreatedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['filter.created']],
            [['entity_type'], 'in', 'range' => ['filter']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_filter_id', 'category_id', 'name_ru', 'type'], 'required'],
            [['id', 'sklad_filter_id', 'yii_filter_id', 'category_id', 'status'], 'integer'],
            [['name_ru', 'name_uz', 'name_en', 'type', 'code'], 'safe'],
            [['values'], 'safe'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
