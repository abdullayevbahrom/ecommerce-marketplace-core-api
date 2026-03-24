<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class BrandCreatedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['brand.created']],
            [['entity_type'], 'in', 'range' => ['brand']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_brand_id', 'name_ru'], 'required'],
            [['id', 'sklad_brand_id', 'yii_brand_id', 'category_id', 'status', 'sort'], 'integer'],
            [['name_ru', 'name_en', 'name_uz'], 'string', 'max' => 255],
            [['description_ru', 'description_en', 'description_uz', 'category_tree'], 'string'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
