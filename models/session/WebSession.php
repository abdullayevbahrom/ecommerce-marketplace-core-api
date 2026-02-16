<?php 

namespace app\models\session;

use app\models\user\User;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Class WebSession
 *
 * @property int $id
 * @property int $user_id
 *
 * @property string $access_token
 * @property string $refresh_token
 *
 * @property string|null $device_name
 * @property string|null $ip
 * @property string|null $user_agent
 *
 * @property int $expires_at
 * @property int $refresh_expires_at
 *
 * @property bool $is_revoked
 * @property int|null $revoked_at
 *
 * @property int $created_at
 * @property int|null $updated_at
 *
 * @property User $user
 */

class WebSession extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%web_sessions}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'access_token', 'refresh_token', 'expires_at', 'created_at'], 'required'],
            [['user_id', 'expires_at', 'created_at', 'updated_at', 'is_revoked'], 'integer'],
            [['user_agent'], 'string'],
            [['device_name', 'platform', 'browser', 'ip'], 'string', 'max' => 255],
            [['access_token', 'refresh_token'], 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User',
            'token' => 'Token',
            'ip' => 'IP Address',
            'user_agent' => 'User Agent',
            'expires_at' => 'Expires At',
            'created_at' => 'Created At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'user_id',
            'access_token',
            'refresh_token',
            'device_name',
            'platform',
            'browser',
            'ip',
            'user_agent',
            'is_revoked',            
            'created_at' => function () {
                return Yii::$app->formatter->asDatetime($this->created_at, 'php:Y-m-d H:i:s');
            },
            'expired_at' => function () {
                return $this->expires_at
                    ? Yii::$app->formatter->asDatetime($this->expires_at, 'php:Y-m-d H:i:s')
                    : null;
            },
            'updated_at' => function () {
                return $this->updated_at
                    ? Yii::$app->formatter->asDatetime($this->updated_at, 'php:Y-m-d H:i:s')
                    : null;
            },
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function createSession(User $user): self
    {
        $session = new self();

        $session->user_id = $user->id;
        $session->access_token = Yii::$app->security->generateRandomString(64);
        $session->refresh_token = Yii::$app->security->generateRandomString(64);

        $session->device_name = Yii::$app->request->headers->get('X-Device-Name');
        $session->platform = Yii::$app->request->headers->get('X-Platform');
        $session->browser = Yii::$app->request->headers->get('X-Browser');

        $session->ip = Yii::$app->request->userIP;
        $session->user_agent = Yii::$app->request->userAgent;

        $session->expires_at = time() + (60 * 60 * 24 * 7); // 7 дней
        $session->created_at = time();
        $session->updated_at = time();

        $session->save(false);

        return $session;
    }

    public static function findValidByToken(string $token): ?self
    {
        return self::find()
            ->where(['token' => $token])
            ->andWhere(['>', 'expires_at', time()])
            ->one();
    }

    public function isExpired(): bool
    {
        return $this->expires_at < time();
    }
}