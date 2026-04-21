<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "file".
 *
 * @property int $id
 * @property int $object_id
 * @property string $type
 * @property string $url
 * @property int $main
 * @property int $sort
 */
class File extends \yii\db\ActiveRecord
{
    public $files = [];

    const FILE_DEFAULT = 'https://files.example.com/uploads/file.png';
    const FILE_OFERTA = 'uploads/shop_oferta/';
    const FILE_DOCUMENT = 'uploads/shop_document/';

    public $object = array(
        'oferta' => self::FILE_OFERTA,
        'document' => self::FILE_DOCUMENT
    );

    public static function tableName()
    {
        return 'file';
    }

    public function rules()
    {
        return [
            [['object_id', 'main', 'sort'], 'integer'],
            [['type', 'url'], 'string', 'max' => 255],
            [['files'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip, rar, doc, docx, xls, xlsx, pdf, txt, psd', 'maxSize' => 2048000],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'object_id' => 'Object ID',
            'type' => 'Type',
            'url' => 'Url',
            'main' => 'Main',
            'sort' => 'Sort',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'type',
            'object_id',
            'main',
            'sort',
            'url' => fn(): string => $this->getPublicUrl(),
        ];
    }

    public function upload($object_id, $type, $main = 1)
    {
        if (!array_key_exists($type, $this->object)) {
            return false;
        }

        /** @var \app\components\S3Component $s3 */
        $s3 = Yii::$app->s3;

        foreach ($this->files as $v) {
            $rnd = mt_rand(0, 1000000);
            $name = time() . '_' . $rnd . '.' . $v->extension;

            $localTemp = Yii::getAlias('@runtime') . '/' . $name;
            $v->saveAs($localTemp);

            $contentType = @mime_content_type($localTemp) ?: 'application/octet-stream';

            // upload to minio: {type}/{object_id}/file/{name}
            $s3->putFileVariant((string)$type, (string)$object_id, $name, $localTemp, $contentType);

            @unlink($localTemp);

            if ($this->url) {
                $old = (string)$this->url;
                $s3->deleteFileVariant((string)$type, (string)$object_id, $old);

                Yii::$app->db->createCommand()
                    ->update(self::tableName(), ['url' => $name, 'web' => 1], ['id' => $this->id])
                    ->execute();
            } else {
                Yii::$app->db->createCommand()->insert(self::tableName(), [
                    'type' => $type,
                    'object_id' => (int)$object_id,
                    'url' => $name,
                    'main' => (int)$main,
                    'sort' => 0,
                ])->execute();
            }
        }

        return true;
    }

    public function getPublicUrl(): string
    {
        /** @var \app\components\S3Component $s3 */
        $s3 = Yii::$app->s3;

        if (!$this->url) {
            return self::FILE_DEFAULT;
        }

        return $s3->urlFileVariant((string)$this->type, (string)$this->object_id, (string)$this->url);
    }

    public function remove()
    {
        /** @var \app\components\S3Component $s3 */
        $s3 = Yii::$app->s3;

        $s3->deleteFileVariant((string)$this->type, (string)$this->object_id, (string)$this->url);

        return (bool)$this->delete();
    }
}
