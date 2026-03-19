<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

abstract class BaseEventValidator
{
    abstract protected function rules(): array;

    public function validate(array $message): array
    {
        $model = DynamicModel::validateData($message, $this->rules());

        if ($model->hasErrors()) {
            throw new EventValidationException('Event validation failed', $model->getErrors());
        }

        return $message;
    }

    protected function baseRules(): array
    {
        return [
            [['event_id', 'event_type', 'source', 'entity_type', 'occurred_at', 'schema_version', 'payload'], 'required'],
            [['event_id', 'correlation_id'], 'string', 'max' => 36],
            [['event_type'], 'string', 'max' => 150],
            [['source'], 'in', 'range' => ['sklad', 'market']],
            [['entity_type'], 'string', 'max' => 100],
            [['entity_id', 'branch_id', 'schema_version'], 'integer'],
            [['payload', 'meta'], 'safe'],
            [['occurred_at'], 'datetime', 'format' => 'php:Y-m-d\TH:i:sP'],
        ];
    }

    protected function mergeRules(array $rules): array
    {
        return array_merge($this->baseRules(), $rules);
    }
}