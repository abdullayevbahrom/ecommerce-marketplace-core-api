<?php

namespace app\components\RabbitMq\Validation;

class FilterUpdatedEventValidator extends FilterCreatedEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['filter.updated']],
            [['entity_type'], 'in', 'range' => ['filter']],
        ]);
    }
}
