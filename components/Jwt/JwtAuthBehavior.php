<?php

namespace app\components\Jwt;

use Yii;
use yii\base\ActionFilter;
use yii\web\UnauthorizedHttpException;
use yii\web\ForbiddenHttpException;
use app\models\User;

class JwtAuthBehavior extends ActionFilter
{
    public string $audience;
    public array $requiredPermissions = [];

    public function beforeAction($action)
    {
        $header = Yii::$app->request->headers->get('Authorization');

        if (!$header || !preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            throw new UnauthorizedHttpException('Bearer token required');
        }

        $token = $matches[1];

        $payload = Yii::$app->jwtService->decodeAndVerify($token, $this->audience);

        $user = User::findOne(['id' => $payload->sub]);

        if (!$user || (int) $user->status !== 1) {
            throw new UnauthorizedHttpException('User not found or disabled');
        }

        $tokenPermissions = $payload->permissions ?? [];

        foreach ($this->requiredPermissions as $permission) {
            if (!in_array($permission, $tokenPermissions, true)) {
                throw new ForbiddenHttpException('Permission denied');
            }
        }

        Yii::$app->params['authUser'] = $user;
        Yii::$app->params['jwtPayload'] = $payload;

        return parent::beforeAction($action);
    }
}