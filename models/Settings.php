<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

use app\models\Images;

/**
 * This is the model class for table "settings".
 *
 * @property int $id
 * @property string|null $type
 * @property string|null $content
 * @property string $date
 */
class Settings extends \yii\db\ActiveRecord
{
    public $imageFiles = [];
    public $phone = [];
    public $email = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date', 'phone', 'email'], 'safe'],
            [['type', 'content'], 'string', 'max' => 255],
            [['main'], 'integer'],
            [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, svg', 'maxSize' => 2048000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'content' => 'Content',
            'date' => 'Date',
        ];
    }

    public function saveLogo() {
        $current_image = $this->image ? $this->image : null;
        $this->type = 'logo';
        if ($this->save()) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($current_image) {
                    $current_image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'logo');
            }
        }

        return true;
    }

    public function saveContacts() {
        // save phones
        if ($this->phone) {
            Settings::deleteAll(['type'=>'phone']);

            $keys = array('type', 'content');
            $vals = array();
            foreach ($this->phone as $phone) {
                if ($phone) {
                    $vals[] = [
                        'type' => 'phone',
                        'content' => $phone
                    ];
                }
            }
            Yii::$app->db->createCommand()->batchInsert('settings', $keys, $vals)->execute();
        }

        // save emails
        if ($this->email) {
            Settings::deleteAll(['type'=>'email']);

            $keys = array('type', 'content');
            $vals = array();
            foreach ($this->email as $email) {
                if ($email) { 
                    $vals[] = [
                        'type' => 'email',
                        'content' => $email
                    ];
                }
            }
            Yii::$app->db->createCommand()->batchInsert('settings', $keys, $vals)->execute();
        }

        return true;
    }

    public function removeObject(){
        if ($this->image) {
            $this->image->removeImageSize();
        }
        
        return $this->delete();
    }

    public function getPhoto($s = 'original') {
        if ($this->image && $this->image->photo) {
            $path = Images::PHOTO_LOGO_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
            if (is_file($path)) {
                return '/'.$path;
            }
        }
        return Images::PHOTO_DEFAULT;
    }

    public function fields() {
        return [
            'id',
            'content',
            'photo'
        ];
    }

    // relations
    public function getImage() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'logo']);
    }
}
