<?php

namespace app\components\Jwt;

use Yii;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;
use yii\base\Component;
use yii\web\UnauthorizedHttpException;
use yii\web\ForbiddenHttpException;

class JwtService extends Component
{
    public function issueAccessToken($user, array $roles, array $permissions, array $audiences): string
    {
        $now = time();

        $payload = [
            'iss' => Yii::$app->params['jwt']['issuer'],
            'aud' => $audiences,
            'sub' => $user->id,
            'roles' => $roles,
            'permissions' => $permissions,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + Yii::$app->params['jwt']['accessTtl'],
            'jti' => Uuid::uuid4()->toString(),
        ];

        $privateKey = file_get_contents(Yii::getAlias(Yii::$app->params['jwt']['privateKeyPath']));

        return JWT::encode($payload, $privateKey, 'RS256');
    }

    public function decodeAndVerify(string $token, string $requiredAudience): object
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new UnauthorizedHttpException('Invalid JWT format');
            }

            $payloadRaw = JWT::jsonDecode(JWT::urlsafeB64Decode($parts[1]));
            $issuer = $payloadRaw->iss ?? null;

            if (!$issuer) {
                throw new UnauthorizedHttpException('JWT issuer missing');
            }

            $publicKeys = Yii::$app->params['jwt']['publicKeys'];

            if (!isset($publicKeys[$issuer])) {
                throw new UnauthorizedHttpException('Unknown JWT issuer');
            }

            $publicKey = file_get_contents(Yii::getAlias($publicKeys[$issuer]));
            $payload = JWT::decode($token, new Key($publicKey, 'RS256'));

            $aud = $payload->aud ?? [];

            if (is_string($aud)) {
                $aud = [$aud];
            }

            if (!in_array($requiredAudience, $aud, true)) {
                throw new ForbiddenHttpException('Invalid token audience');
            }

            return $payload;
        } catch (\Throwable $e) {
            throw new UnauthorizedHttpException('Invalid token: ' . $e->getMessage());
        }
    }
}