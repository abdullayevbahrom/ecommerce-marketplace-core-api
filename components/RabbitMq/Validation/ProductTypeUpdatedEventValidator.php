<?php

namespace app\components\RabbitMq\Validation;

class ProductTypeUpdatedEventValidator extends ProductTypeCreatedEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['product_type.updated']],
            [['entity_type'], 'in', 'range' => ['product_type']],
        ]);
    }
}
