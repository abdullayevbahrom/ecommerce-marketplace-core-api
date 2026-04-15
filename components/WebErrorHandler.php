<?php

namespace app\components;

use Throwable;
use Yii;

class WebErrorHandler extends \yii\web\ErrorHandler
{
    public function logException($exception)
    {
        parent::logException($exception);

        // Exception notifier mavjud bo'lsa, Telegramga yuborish
        if ($exception instanceof Throwable && Yii::$app->has('exceptionNotifier', true)) {
            $bodyParams = Yii::$app->request->bodyParams;
            if (empty($bodyParams)) {
                $bodyParams = Yii::$app->request->post();
            }
            $identity = Yii::$app->user->identity ?? null;
            $userLabel = 'guest';
            if ($identity) {
                $phone = $identity->phone ?? null;
                $userLabel = $phone ? 'phone:' . $phone : 'id:' . ($identity->id ?? 'auth');
            }

            Yii::$app->get('exceptionNotifier')->notify($exception, [
                'route' => Yii::$app->requestedRoute,
                'url' => Yii::$app->request->absoluteUrl ?? Yii::$app->request->url ?? null,
                'method' => Yii::$app->request->method ?? null,
                'user' => $userLabel,
                'body' => !empty($bodyParams) ? json_encode($bodyParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);
        }
    }

    /**
     * Development mode-da ham xatolar to'g'ri qaytarilishi uchun
     */
    protected function renderException($exception)
    {
        // YII_DEBUG=true bo'lsa ham, exception notifier ishlashi kerak
        // Bu metodni override qilib, parent::renderException() chaqiramiz
        parent::renderException($exception);
    }
}
