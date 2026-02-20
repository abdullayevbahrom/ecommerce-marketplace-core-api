<?php

namespace app\models;

use Yii;
use yii\imagine\Image;
use app\models\user\User;
use app\models\product\ProductColor;
use yii\helpers\FileHelper;


// use yii\services\ImageHash;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

/**
 * @property int $id
 * @property int|null $object_id
 * @property string|null $type
 * @property string|null $photo
 * @property int|null $main
 * @property int|null $sort
 * @property int|null $status
 * @property string|null $hash
 * @property string|null $token_key
 */

class Images extends \yii\db\ActiveRecord
{
    const PHOTO_USER_PATH = 'uploads/user/';
    const PHOTO_CATEGORY_PATH = 'uploads/category/';

    const PHOTO_PRODUCT_PATH = 'product/';

    //const PHOTO_PRODUCT_PATH = '/var/www/shared_storage/uploads/product/';

    const PHOTO_NEWS_PATH = 'uploads/news/';
    const PHOTO_DELIVERY_PATH = 'uploads/delivery/';
    const PHOTO_SLIDER_PATH = 'uploads/slider/';
    const PHOTO_SHOP_PATH = 'uploads/shop/';
    const PHOTO_SHOP_ADVERTISING_PATH = 'uploads/shop_advertising/';
    const PHOTO_CHAT_PATH = 'uploads/chat/';
    const PHOTO_BRAND_PATH = 'uploads/brand/';
    const PHOTO_STOCK_PATH = 'uploads/stock/';
    const PHOTO_LOGIST_PATH = 'uploads/logist/';
    const PHOTO_LOGO_PATH = 'uploads/logo/';
    const PHOTO_COLOR_PATH = 'uploads/color/';
    const PHOTO_PARTNER_PATH = 'uploads/partners/';
    const PHOTO_ADVANTAGE_PATH = 'uploads/advantages/';
    const PHOTO_BANNER_PATH = 'uploads/banner/';
    const PHOTO_DEFAULT = '/assets_files/images/no-photo.png';

    public $imageFiles = [];
    public $colors = [];

    public $object = array(
        'user' => self::PHOTO_USER_PATH,
        'category' => self::PHOTO_CATEGORY_PATH,
        'product' => self::PHOTO_PRODUCT_PATH,
        'news' => self::PHOTO_NEWS_PATH,
        'delivery' => self::PHOTO_DELIVERY_PATH,
        'slider' => self::PHOTO_SLIDER_PATH,
        'shop' => self::PHOTO_SHOP_PATH,
        'shop_advertising' => self::PHOTO_SHOP_ADVERTISING_PATH,
        'chat' => self::PHOTO_CHAT_PATH,
        'brand' => self::PHOTO_BRAND_PATH,
        'stock' => self::PHOTO_STOCK_PATH,
        'logist' => self::PHOTO_LOGIST_PATH,
        'logo' => self::PHOTO_LOGO_PATH,
        'color' => self::PHOTO_COLOR_PATH,
        'partner' => self::PHOTO_PARTNER_PATH,
        'advantage' => self::PHOTO_ADVANTAGE_PATH,
        'banner' => self::PHOTO_BANNER_PATH
    );

    public $image_sizes = array(
        '50' => '50',
        '100' => '100',
        '150' => '150',
        '200' => '200',
        '250' => '250',
        '300' => '300'
    );

    public static function tableName()
    {
        return 'image';
    }

    public function rules()
    {
        return [
            [['object_id', 'main', 'sort', 'status'], 'integer'],
            [['type', 'photo', 'number_image'], 'string', 'max' => 255],
            [['colors'], 'safe'],
            [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, svg', 'maxSize' => 2048000],
            [['token_key'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'object_id' => 'Object ID',
            'type' => 'Type',
            'photo' => 'Photo',
            'main' => 'Main',
            'sort' => 'Sort',
        ];
    }

    // public function uploadPhoto($object_id, $type, $main = 1, $type_image = null, $check = true) 
    // {
    //     if (!array_key_exists($type, $this->object)) {
    //         return false;
    //     }

    //     $path = $this->object[$type];

    //     // Функция для создания директории, если она не существует
    //     $createDir = function($dir) {
    //         if (!is_dir($dir)) {
    //             if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
    //                 throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
    //             }
    //         }
    //     };


    //     if (is_array($object_id)) {
    //         foreach ($object_id as $v) {
    //             $createDir($path.$v);
    //             $createDir($path.$v.'/original');
    //         }
    //     } else {
    //         $createDir($path.$object_id);
    //         $createDir($path.$object_id.'/original');
    //     }

    //     foreach ($this->imageFiles as $key => $file) {
    //         // Если $file это массив, то берём файл по ключу
    //         $file = is_array($file) ? $file[$key] : $file;

    //         if (is_array($object_id)) {
    //             $id = $object_id[$key];
    //         } else {
    //             $id = $object_id;
    //         }

    //         $rnd = mt_rand(0, 1000000);
    //         $name = time() + $rnd.'.'.$file->extension;
    //         $original = $path.$id.'/original/'.$name;

    //         if ($file->saveAs($original)) {
    //             $hasher = new ImageHash(new DifferenceHash());
    //             // $hash = $hasher->hash(Yii::$app->params['baseUrl'].'/'.$original);

    //             $absolutePath = Yii::getAlias('@webroot') . '/' . $original;
    //             if (!file_exists($absolutePath)) {
    //                 throw new \Exception('File not found: ' . $absolutePath);
    //             }
    //             $hash = $hasher->hash($absolutePath);

    //             // Если файл не является видео или SVG, создаем миниатюры
    //             if ($file->extension != 'mp4' && $file->extension != 'svg') {
    //                 foreach ($this->image_sizes as $sizeKey => $img) {
    //                     $sizePath = $path.$id.'/'.$sizeKey.'x'.$img;
    //                     $createDir($sizePath); // Создаем директорию для миниатюр, если её нет
    //                     Image::thumbnail($original, $sizeKey, $img)->save(Yii::getAlias($sizePath.'/'.$name), ['quality' => 80]);
    //                 }
    //             }

    //             $status = (Yii::$app->user->identity->role == User::ROLE_ADMIN) ? 1 : 0;

    //             if ($this->photo && $check) {
    //                 $original = $path.$id.'/original/'.$this->photo;
    //                 if (is_file($original)) {
    //                     unlink($original);
    //                 }
    //                 if ($file->extension != 'mp4' && $file->extension != 'svg') {
    //                     foreach ($this->image_sizes as $sizeKey => $img) {
    //                         $photo = $path.$id.'/'.$sizeKey.'x'.$img.'/'.$this->photo;
    //                         if (is_file($photo)) {
    //                             unlink($photo);
    //                         }
    //                     }
    //                 }

    //                 Yii::$app->db->createCommand()->update('image', ['photo' => $name], ['id' => $this->id, 'hash' => $hash])->execute();
    //             } else {
    //                 Yii::$app->db->createCommand()->insert('image', [
    //                     'type' => $type_image ? $type_image : $type,
    //                     'object_id' => $id,
    //                     'photo' => $name,
    //                     'main' => $main,
    //                     'sort' => 0,
    //                     'web' => 0,
    //                     'status' => $status,
    //                     'hash' => $hash
    //                 ])->execute();
    //             }
    //         } else {
    //             // Обработка ошибки сохранения файла
    //             return false;
    //         }
    //     }

    //     return true;
    // }

    public function uploadPhoto($tokenKey, $type, $main = 1, $type_image = null, $check = true)
    {
        if (!array_key_exists($type, $this->object)) {
            return false;
        }

        $basePath = $this->object[$type];

        foreach ($this->imageFiles as $file) {

            $rnd = mt_rand(0, 1000000);
            $name = time() . '_' . $rnd . '.' . $file->extension;

            $localTemp = Yii::getAlias('@runtime') . '/' . $name;
            $file->saveAs($localTemp);

            $originalKey = $basePath . $tokenKey . '/original/' . $name;
            Yii::$app->s3->upload($originalKey, $localTemp);

            // Миниатюры
            foreach ($this->image_sizes as $sizeKey => $img) {

                $thumbPath = Yii::getAlias('@runtime') . "/{$sizeKey}_{$name}";

                \yii\imagine\Image::thumbnail($localTemp, $sizeKey, $img)->save($thumbPath, ['quality' => 80]);

                $thumbKey = $basePath . $tokenKey . "/{$sizeKey}x{$img}/" . $name;

                Yii::$app->s3->upload($thumbKey, $thumbPath);

                unlink($thumbPath);
            }

            unlink($localTemp);

            Yii::$app->db->createCommand()->insert('image', [
                'type' => $type_image ?: $type,
                'token_key' => $tokenKey,
                'photo' => $name,
                'main' => $main,
                'sort' => 0,
                'web' => 0,
                'status' => 1,
                // 'hash' => (string)$hash
            ])->execute();
        }

        return true;
    }




    public function removeImage()
    {
        $path = $this->object[$this->type];

        $image = $path . $this->object_id . '/' . $this->photo;

        if (is_file($image)) {
            unlink($image);
        }

        return $this->delete();
    }

    // color
    public function uploadPhotoColor($object_id)
    {
        if ($this->colors) {
            foreach ($this->colors as $k => $color) {
                $c = ProductColor::findOne(['product_id' => $object_id, 'color_id' => $color]);
                if (!$c) {
                    $c = new ProductColor;
                }
                $c->product_id = $object_id;
                $c->color_id = (int)$color;
                $c->status = 1;
                if ($c->save(false)) {
                    $image = $this->imageFiles[$k];

                    $img = self::findOne(['object_id' => $c->id, 'type' => 'color']);
                    if ($img) {
                        $img->removeImageSize('color');
                    }
                    $path = 'uploads/color/';
                    $rnd = mt_rand(0, 1000000);
                    $name = time() + $rnd . '.' . $image->extension;
                    $original = $path . $name;

                    $image->saveAs($original);

                    $image = new self;
                    $image->object_id = $c->id;
                    $image->type = 'color';
                    $image->photo = $name;
                    $image->main = 1;
                    $image->sort = 0;
                    $image->save();
                }
            }
        }

        return true;
    }
    // end color

    public function removeImageSize()
    {
        $path = $this->object[$this->type];

        $original = $path . $this->object_id . '/original/' . $this->photo;

        if (is_file($original)) {
            unlink($original);
        }

        $dir_empty = false;

        foreach ($this->image_sizes as $k => $v) {
            $image = $path . $this->object_id . '/' . $k . 'x' . $v . '/' . $this->photo;
            if (is_file($image)) {
                unlink($image);
            }

            $dir_empty = (glob($image . '*')) ? false : true;
        }

        $dir_empty = false;

        if ($dir_empty === true) {
            if (!is_file($path . $this->object_id . '/original/' . $this->photo)) {
                foreach ($this->image_sizes as $k => $v) {
                    rmdir($path . $this->object_id . '/' . $k . 'x' . $v);
                }
                rmdir($path . $this->object_id . '/original');
                rmdir($path . $this->object_id);
            }
        }

        return $this->delete() ? true : false;
    }

    public function getPhoto($type, $size = 'original')
    {
        if ($this->web == 1) {
            return $this->photo;
            // $baseUrl = Yii::$app->params['minio']['publicEndpoint'];

            // return $baseUrl . "/uploads/$type/" . $this->token_key . '/' . $size . '/' . $this->photo;
        }
        $path = 'uploads/' . $type . '/' . $this->object_id . '/' . $size . '/' . $this->photo;

        if (is_file($path)) {
            return '/' . $path;
        }



        return self::PHOTO_DEFAULT;
    }

    public function fields()
    {
        return ['id', 'photo'];
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if (!$this->token_key || !$this->photo) {
            return;
        }

        $sizes = ['original', '50x50', '100x100', '200x200', '300x300'];

        foreach ($sizes as $size) {

            $key = "{$this->type}/{$this->token_key}/{$size}/{$this->photo}";

            Yii::$app->s3->deleteObject($key);
        }
    }
}
