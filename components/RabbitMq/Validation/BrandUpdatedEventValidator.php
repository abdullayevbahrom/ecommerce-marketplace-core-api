<?php

namespace app\components\RabbitMq\Validation;

class BrandUpdatedEventValidator extends BrandCreatedEventValidator
{
    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => ['brand.updated']],
            [['entity_type'], 'in', 'range' => ['brand']],
        ]);
    }
}
