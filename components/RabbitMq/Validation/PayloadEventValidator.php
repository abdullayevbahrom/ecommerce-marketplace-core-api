<?php

namespace app\components\RabbitMq\Validation;

use yii\base\DynamicModel;

class PayloadEventValidator extends BaseEventValidator
{
    public function __construct(
        private readonly array $eventTypes,
        private readonly array $entityTypes,
        private readonly array $payloadRules = [],
        private readonly ?\Closure $payloadAssertion = null,
    ) {
    }

    protected function rules(): array
    {
        return $this->mergeRules([
            [['event_type'], 'in', 'range' => $this->eventTypes],
            [['entity_type'], 'in', 'range' => $this->entityTypes],
        ]);
    }

    public function validate(array $message): array
    {
        parent::validate($message);

        $payloadModel = DynamicModel::validateData($message['payload'] ?? [], $this->payloadRules);

        if ($payloadModel->hasErrors()) {
            throw new EventValidationException('Payload validation failed', $payloadModel->getErrors());
        }

        if ($this->payloadAssertion) {
            ($this->payloadAssertion)($message['payload'] ?? []);
        }

        return $message;
    }
}
