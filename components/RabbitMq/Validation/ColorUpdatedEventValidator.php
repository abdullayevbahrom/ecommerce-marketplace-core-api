<?php

namespace app\components\RabbitMq\Validation;

class ColorUpdatedEventValidator extends ColorCreatedEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['color.updated']],
            [['entity_type'], 'in', 'range' => ['color']],
        ]);
    }
}
