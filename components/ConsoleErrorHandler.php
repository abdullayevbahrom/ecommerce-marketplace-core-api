<?php

namespace app\components;

use Throwable;
use Yii;

class ConsoleErrorHandler extends \yii\console\ErrorHandler
{
    public function logException($exception)
    {
        parent::logException($exception);

        if ($exception instanceof Throwable && Yii::$app->has('exceptionNotifier', true)) {
            Yii::$app->get('exceptionNotifier')->notify($exception, [
                'command' => implode(' ', $_SERVER['argv'] ?? []),
            ]);
        }
    }
}
