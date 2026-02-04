<?php

namespace app\components;

use yii\base\Component;

class TelegramComponent extends Component
{
    public $botToken;
    public $chatId;

     public function sendMessage($message, $buttons = false)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        if ($buttons) {
            $data['reply_markup'] = json_encode([
                'inline_keyboard' => $buttons
            ]);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            \Yii::error("Failed to send message to Telegram: " . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        return true;
    }
}