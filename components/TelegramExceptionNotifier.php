<?php

namespace app\components;

use Throwable;
use Yii;
use yii\base\Component;
use yii\base\UserException;
use yii\web\HttpException;

class TelegramExceptionNotifier extends Component
{
    public $appName = 'shop';
    public $botToken;
    public $chatId;
    public $timeout = 3;

    public function notify(Throwable $exception, array $context = []): void
    {
        if (!$this->shouldNotify($exception)) {
            Yii::info("Telegram notification skipped (shouldNotify=false): " . get_class($exception), __METHOD__);
            return;
        }

        [$botToken, $chatId] = $this->resolveCredentials();
        if (!$botToken || !$chatId) {
            Yii::error("Telegram notification skipped: missing credentials (botToken=" . ($botToken ? 'set' : 'empty') . ", chatId=" . ($chatId ? 'set' : 'empty') . ")", __METHOD__);
            return;
        }

        $message = $this->buildMessage($exception, $context);

        try {
            Yii::info("Sending Telegram notification for: " . get_class($exception), __METHOD__);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.telegram.org/bot{$botToken}/sendMessage",
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->timeout,
                CURLOPT_TIMEOUT => $this->timeout,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                Yii::error("Telegram API error: HTTP {$httpCode}, curl error: {$curlError}, response: {$response}", __METHOD__);
            } else {
                Yii::info("Telegram notification sent successfully", __METHOD__);
            }
        } catch (Throwable $notifyException) {
            Yii::error("Telegram notification exception: " . $notifyException->getMessage(), __METHOD__);
        }
    }

    protected function shouldNotify(Throwable $exception): bool
    {
        // UserException - foydalanuvchi xatosi (masalan, 400 Bad Request)
        if ($exception instanceof UserException) {
            return false;
        }

        // HttpException < 500 - client xatolari (404, 403, va h.k.)
        if ($exception instanceof HttpException && $exception->statusCode < 500) {
            return false;
        }

        // YII_DEBUG=false bo'lsa, barcha xatolarni yuborish
        // YII_DEBUG=true bo'lsa, faqat 500+ xatolarini yuborish
        if (!YII_DEBUG) {
            return true;
        }

        // Development mode-da ham 500 xatolarini yuborish
        if ($exception instanceof HttpException && $exception->statusCode >= 500) {
            return true;
        }

        // Development mode-da boshqa exceptionlarni ham yuborish (Imagine xatolari va h.k.)
        // Faqat HttpException bo'lmaganlarini ham yuboramiz
        if (!$exception instanceof HttpException) {
            return true;
        }

        return false;
    }

    protected function resolveCredentials(): array
    {
        $botToken = $this->botToken ?: getenv('TELEGRAM_BOT_TOKEN') ?: null;
        $chatId = $this->chatId ?: getenv('TELEGRAM_CHAT_ID') ?: null;

        if ((!$botToken || !$chatId) && Yii::$app->has('telegram', true)) {
            try {
                $telegram = Yii::$app->get('telegram');
                $botToken = $botToken ?: ($telegram->botToken ?? null);
                $chatId = $chatId ?: ($telegram->chatId ?? null);
            } catch (Throwable $e) {
            }
        }

        return [$botToken, $chatId];
    }

    protected function buildMessage(Throwable $exception, array $context): string
    {
        $lines = [
            '🚨 <b>' . $this->escape($this->appName) . ' exception</b>',
            '<b>Type:</b> ' . $this->escape(get_class($exception)),
            '<b>Message:</b> ' . $this->escape($this->truncate($exception->getMessage(), 1000)),
            '<b>File:</b> ' . $this->escape($exception->getFile() . ':' . $exception->getLine()),
        ];

        if (!empty($context['route'])) {
            $lines[] = '<b>Route:</b> ' . $this->escape((string) $context['route']);
        }

        if (!empty($context['url'])) {
            $lines[] = '<b>URL:</b> ' . $this->escape((string) $context['url']);
        }

        if (!empty($context['method'])) {
            $lines[] = '<b>Method:</b> ' . $this->escape((string) $context['method']);
        }

        if (!empty($context['user'])) {
            $lines[] = '<b>User:</b> ' . $this->escape((string) $context['user']);
        }

        if (!empty($context['command'])) {
            $lines[] = '<b>Command:</b> ' . $this->escape((string) $context['command']);
        }

        if (!empty($context['body'])) {
            $lines[] = '<b>Body:</b>' . "\n<pre>" . $this->escape($this->truncate((string) $context['body'], 1500)) . '</pre>';
        }

        return implode("\n", $lines);
    }

    protected function truncate(string $value, int $limit): string
    {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 3) . '...' : $value;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
