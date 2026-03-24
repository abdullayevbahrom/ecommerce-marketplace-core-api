<?php

namespace app\components\RabbitMq\Validation;

class CategoryUpdatedEventValidator extends CategoryCreatedEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['category.updated']],
            [['entity_type'], 'in', 'range' => ['category']],
        ]);
    }
}
