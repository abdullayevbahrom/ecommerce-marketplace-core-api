<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class CategoryCreatedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['category.created']],
            [['entity_type'], 'in', 'range' => ['category']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_category_id', 'name_ru'], 'required'],
            [['id', 'sklad_category_id', 'yii_category_id', 'parent_id', 'sort', 'status', 'main', 'is_filter', 'popular'], 'integer'],
            [['name_mini', 'name_ru', 'name_uz', 'name_en', 'description_ru', 'description_uz', 'description_en', 'option_ru', 'option_uz', 'option_en', 'type'], 'safe'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
