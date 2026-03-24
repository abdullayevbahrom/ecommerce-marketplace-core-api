<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class ColorCreatedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['color.created']],
            [['entity_type'], 'in', 'range' => ['color']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_color_id', 'name_ru', 'color'], 'required'],
            [['id', 'sklad_color_id', 'yii_color_id', 'status'], 'integer'],
            [['name_ru', 'name_en', 'name_uz'], 'string', 'max' => 255],
            [['color'], 'string', 'max' => 50],
            [['deleted_at'], 'safe'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
