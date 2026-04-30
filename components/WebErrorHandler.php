<?php

namespace app\components;

use Throwable;
use Yii;

class WebErrorHandler extends \yii\web\ErrorHandler
{
    public function logException($exception)
    {
        Yii::error("WebErrorHandler::logException called for: " . get_class($exception), __METHOD__);
        Yii::error("Exception message: " . $exception->getMessage(), __METHOD__);
        
        parent::logException($exception);

        // Exception notifier mavjud bo'lsa, Telegramga yuborish
        // has() ni false bilan ishlatamiz - component ro'yxatda bo'lsa yetarli
        if ($exception instanceof Throwable && Yii::$app->has('exceptionNotifier', false)) {
            Yii::info("exceptionNotifier component found, calling notify...", __METHOD__);
            
            try {
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

                Yii::info("Calling exceptionNotifier->notify() with context...", __METHOD__);
                Yii::$app->get('exceptionNotifier')->notify($exception, [
                    'route' => Yii::$app->requestedRoute,
                    'url' => Yii::$app->request->absoluteUrl ?? Yii::$app->request->url ?? null,
                    'method' => Yii::$app->request->method ?? null,
                    'user' => $userLabel,
                    'body' => !empty($bodyParams) ? json_encode($bodyParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ]);
                Yii::info("exceptionNotifier->notify() completed", __METHOD__);
            } catch (\Throwable $e) {
                Yii::error("Failed to send Telegram notification: " . $e->getMessage(), __METHOD__);
            }
        } else {
            Yii::warning("exceptionNotifier component NOT found or exception is not Throwable", __METHOD__);
        }
    }

    /**
     * Development mode-da ham xatolar to'g'ri qaytarilishi uchun
     */
    protected function renderException($exception)
    {
        // API endpointlarda xatolik javoblari ham CORS header bilan qaytsin
        // (aks holda brauzer real xatoni yashirib, faqat CORS xatosini ko'rsatadi)
        try {
            $pathInfo = Yii::$app->request->pathInfo ?? '';
            if (strpos($pathInfo, 'api/') === 0 || $pathInfo === 'api') {
                $headers = Yii::$app->response->getHeaders();
                $headers->set('Access-Control-Allow-Origin', '*');
                $headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
                $headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Auth-Token, Origin, language, Language, Content-Language, Accept-Language');
                $headers->set('Access-Control-Allow-Credentials', 'false');
            }
        } catch (\Throwable $e) {
            Yii::warning('Failed to attach CORS headers in error handler: ' . $e->getMessage(), __METHOD__);
        }

        // YII_DEBUG=true bo'lsa ham, exception notifier ishlashi kerak
        // Bu metodni override qilib, parent::renderException() chaqiramiz
        parent::renderException($exception);
    }
}
