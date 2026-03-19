<?php

namespace app\components\RabbitMq;

class MessageFactory
{
    public static function make(
        string $eventType,
        string $source,
        string $entityType,
        ?int $entityId,
        ?int $branchId,
        array $payload,
        ?string $correlationId = null
    ): array {
        return [
            'event_id' => self::uuid4(),
            'correlation_id' => $correlationId ?: self::uuid4(),
            'event_type' => $eventType,
            'source' => $source,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'branch_id' => $branchId,
            'occurred_at' => date(\DateTime::ATOM),
            'schema_version' => 1,
            'payload' => $payload,
            'meta' => [
                'attempt' => 1,
            ],
        ];
    }

    public static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}