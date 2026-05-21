<?php

namespace app\components\RabbitMq\Handlers;

use app\models\user\User;

class AuthGatewayEventHandler
{
    public function handle(array $message): void
    {
        $eventType = (string) ($message['event_type'] ?? '');
        $payload = is_array($message['payload'] ?? null) ? $message['payload'] : [];

        match ($eventType) {
            'UserRegistered' => $this->handleUserRegistered($payload),
            'SmsCodeRequested' => $this->handleSmsCodeRequested($payload),
            default => throw new \RuntimeException('Unsupported auth event: ' . $eventType),
        };
    }

    private function handleUserRegistered(array $payload): void
    {
        $phone = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? ''));
        if (strlen((string) $phone) !== 12) {
            throw new \RuntimeException('UserRegistered payload has invalid phone');
        }

        $user = $this->findUserByPhone($phone);
        if (!$user) {
            $user = new User();
            $user->phone = $phone;
            $user->password = '1';
            $user->token = '';
            $user->date = date('Y-m-d H:i:s');
        }

        if (isset($payload['name'])) {
            $user->name = (string) $payload['name'];
        }
        if (isset($payload['role'])) {
            $user->role = (int) $payload['role'];
        } elseif (empty($user->role)) {
            $user->role = User::ROLE_USER;
        }
        if (array_key_exists('inn', $payload)) {
            $user->inn = $payload['inn'] ? (string) $payload['inn'] : null;
        }
        $user->status = User::STATUS_ACTIVE;

        if (User::hasColumn('global_user_id') && !empty($payload['global_user_id'])) {
            $user->setAttribute('global_user_id', (string) $payload['global_user_id']);
        }

        if (!$user->save(false)) {
            throw new \RuntimeException('Failed to save user from UserRegistered event');
        }
    }

    private function handleSmsCodeRequested(array $payload): void
    {
        $phone = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? ''));
        if (strlen((string) $phone) !== 12) {
            throw new \RuntimeException('SmsCodeRequested payload has invalid phone');
        }

        $user = $this->findUserByPhone($phone);
        if (!$user) {
            $user = new User();
            $user->phone = $phone;
            $user->password = '1';
            $user->token = '';
            $user->date = date('Y-m-d H:i:s');
            $user->role = User::ROLE_USER;
        }

        if (isset($payload['otp']) && is_string($payload['otp']) && $payload['otp'] !== '') {
            $user->phone_code = $payload['otp'];
            $user->sms_live = strtotime('+5 minute');
        }
        $user->status = User::STATUS_ACTIVE;

        if (User::hasColumn('global_user_id') && !empty($payload['global_user_id'])) {
            $user->setAttribute('global_user_id', (string) $payload['global_user_id']);
        }

        if (!$user->save(false)) {
            throw new \RuntimeException('Failed to save user from SmsCodeRequested event');
        }
    }

    private function findUserByPhone(string $normalizedPhone): ?User
    {
        $variants = [$normalizedPhone, '+' . $normalizedPhone];

        if (str_starts_with($normalizedPhone, '998') && strlen($normalizedPhone) === 12) {
            $variants[] = substr($normalizedPhone, 3); // legacy 9-digit local format
        }

        $variants = array_values(array_unique(array_filter($variants, static fn($v) => is_string($v) && $v !== '')));

        return User::find()->where(['phone' => $variants])->one();
    }
}
