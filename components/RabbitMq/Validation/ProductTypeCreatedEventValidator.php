<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class ProductTypeCreatedEventValidator extends BaseEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['product_type.created']],
            [['entity_type'], 'in', 'range' => ['product_type']],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], [
            [['sklad_product_type_id', 'name_ru', 'type'], 'required'],
            [['id', 'sklad_product_type_id', 'yii_product_type_id', 'category_id', 'status', 'sort'], 'integer'],
            [['name_ru', 'name_en', 'name_uz', 'type', 'description_ru', 'description_en', 'description_uz'], 'safe'],
            [['values'], 'safe'],
        ]);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        return $message;
    }
}
