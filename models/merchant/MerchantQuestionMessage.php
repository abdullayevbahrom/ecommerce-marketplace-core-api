<?php

namespace app\models\merchant;

use yii\db\ActiveRecord;
use app\models\user\User;
use yii\helpers\Json;
use Yii;

class MerchantQuestionMessage extends ActiveRecord
{
    public const ROLE_CLIENT = 'client';
    public const ROLE_MERCHANT = 'merchant';
    public const ROLE_MODERATOR = 'moderator';
    public const PHOTO_PATH = 'merchant_question_messages/';

    /** @var \yii\web\UploadedFile[] */
    public array $rawFiles = [];

    public static function tableName()
    {
        return '{{%merchant_question_messages}}';
    }

    public function rules()
    {
        return [
            [['question_id', 'sender_id', 'sender_role', 'message', 'created_at'], 'required'],
            [['question_id', 'sender_id', 'created_at'], 'integer'],
            [
                'sender_role',
                'in',
                'range' => [
                    self::ROLE_CLIENT,
                    self::ROLE_MERCHANT,
                    self::ROLE_MODERATOR
                ]
            ],
            ['message', 'string'],
            ['files', 'safe'],
        ];
    }

    public function getQuestion()
    {
        return $this->hasOne(MerchantQuestion::class, ['id' => 'question_id']);
    }

    public function getSender()
    {
        return $this->hasOne(User::class, ['id' => 'sender_id']);
    }

    public function afterFind()
    {
        parent::afterFind();
        if (\is_string($this->files) && !empty($this->files)) {
            try {
                $this->files = Json::decode($this->files);
            } catch (\Exception $e) {
                $this->files = [];
            }
        } else {
            $this->files = is_array($this->files) ? $this->files : [];
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (\is_string($this->files) && !empty($this->files)) {
            try {
                $this->files = Json::decode($this->files);
            } catch (\Exception $e) {
                $this->files = [];
            }
        }
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->uploadPhoto();
            if (\is_array($this->files)) {
                $this->files = !empty($this->files) ? Json::encode(array_values($this->files)) : null;
            } else {
                $this->files = null;
            }
            return true;
        }
        return false;
    }

    public function uploadPhoto(): void
    {
        if (empty($this->rawFiles)) {
            return;
        }

        /** @var \app\components\S3Component $s3 */
        $s3 = Yii::$app->s3;
        $newFiles = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        $maxFileSize = 10 * 1024 * 1024;
        foreach ($this->rawFiles as $file) {
            $ext = strtolower($file->extension);
            if (!\in_array($ext, $allowedExtensions)) {
                Yii::warning("Blocked unsafe file extension: .{$ext}", 'security_upload');
                continue;
            }
            if ($file->size > $maxFileSize) {
                Yii::warning("File size exceeds limit (10MB): {$file->name} ({$file->size} bytes)", 'upload_limit');
                continue;
            }
            $rnd = mt_rand(0, 1000000);
            $filename = time() . '-' . $rnd . '.' . $ext;

            $tmp = Yii::getAlias('@runtime') . '/merchant_question_messages_' . uniqid() . '_' . $filename;
            
            if (!$file->saveAs($tmp)) {
                continue;
            }

            try {
                $contentType = @mime_content_type($tmp) ?: 'application/octet-stream';
                $key = self::PHOTO_PATH . $this->question_id . '/' . $filename;
                $s3->putFile($key, $tmp, $contentType);
                $files[] = $s3->url($key);
            } catch (\Throwable $e) {
                Yii::error("S3 Upload error: " . $e->getMessage(), 's3_upload');
            } finally {
                @unlink($tmp);
            }
        }

        $existingFiles = \is_array($this->files) ? $this->files : [];
        $this->files = \array_merge($existingFiles, $newFiles);
    }
}
