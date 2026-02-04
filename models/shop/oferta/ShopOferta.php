<?php

namespace app\models\shop\oferta;

use Yii;
use yii\web\UploadedFile;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\File;

/**
 * This is the model class for table "shop_oferta".
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $content_ru
 * @property string|null $content_en
 * @property string|null $content_uz
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property Shop $shop
 */
class ShopOferta extends \yii\db\ActiveRecord
{
    public $files = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shop_oferta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru', 'content_ru'], 'required', 'message'=>'Заполните поле'],
            [['shop_id', 'status', 'sort'], 'integer'],
            [['content_ru', 'content_en', 'content_uz'], 'string'],
            [['date'], 'safe'],
            [['name_ru', 'name_en', 'name_uz'], 'string', 'max' => 255],
            [['shop_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shop::className(), 'targetAttribute' => ['shop_id' => 'id']],
            [['files'], 'file', 'skipOnEmpty' => true, 'extensions' => 'doc, docx, xls, xlsx, txt, pdf']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
            'name_uz' => 'Name Uz',
            'content_ru' => 'Content Ru',
            'content_en' => 'Content En',
            'content_uz' => 'Content Uz',
            'status' => 'Status',
            'sort' => 'Sort',
            'date' => 'Date',
        ];
    }

    public function saveObject() {
        $this->status = 1;

        if (Yii::$app->user->identity->role == User::ROLE_SHOP) {
            $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);
            $this->shop_id = $shop->id;
        }

        if ($this->save()) {
            $file = new File;
            if ($file->files = UploadedFile::getInstances($this, 'files')) {
                if ($this->file) {
                    $this->file->remove();
                }
                $file->upload($this->id, 'oferta');
            }

            return true;
        }

        return false;
    }

    public function removeObject(){
        if ($this->file && $this->file->delete()){
            $this->file->remove();
        }
        
        return $this->delete();
    }

    public function getPath() {
        if ($this->file) {
            $path = File::FILE_OFERTA.$this->file->object_id.'/file/'.$this->file->url;
            if (is_file($path)) {
                return '/'.$path;
            }
        }

        return false;
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $data = [
            'id',
            'name' => function() use($language) { return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'content' => function() use($language) { return $this->{'content_'.$language} ? $this->{'content_'.$language} : $this->content_ru;},
            'status',
            'date'
        ];

        return $data;
    }

    /**
     * Gets query for [[Shop]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }

    public function getFile() {
        return $this->hasOne(File::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'oferta', 'main'=>1]);
    }
}
