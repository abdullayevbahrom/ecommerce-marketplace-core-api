<?php

namespace app\models;

use Yii;
use app\components\S3Component;
use app\models\product\ProductColor;



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
    const PHOTO_PRODUCT_PATH = 'uploads/product/';
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
    const PHOTO_DEFAULT = 'https://files.example.com/uploads/no-photo.png';

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

    public function uploadPhoto($objectId, $type, $main = 1, $type_image = null, $check = true): bool
    {
        if (!array_key_exists($type, $this->object)) {
            Yii::error("Invalid image type: {$type}", __METHOD__);
            return false;
        }
        /** @var S3Component $s3 */
        $s3 = \Yii::$app->s3;

        foreach ($this->imageFiles as $index => $file) {
            $rnd = mt_rand(0, 1000000);
            // WebP formatini qo'llab-quvvatlamaslik uchun jpg ga o'zgartiramiz
            $extension = strtolower($file->extension) === 'webp' ? 'jpg' : $file->extension;
            $name = time() . '_' . $rnd . '.' . $extension;

            $localTemp = Yii::getAlias('@runtime') . '/' . $name;
            
            Yii::info("Uploading image #{$index}: {$file->name} -> {$localTemp}", __METHOD__);
            
            if (!$file->saveAs($localTemp)) {
                Yii::error("Failed to save uploaded file to: {$localTemp}", __METHOD__);
                continue;
            }

            // Tekshirish: fayl haqiqatan ham mavjudmi?
            if (!is_file($localTemp)) {
                Yii::error("Uploaded file not found after save: {$localTemp}", __METHOD__);
                continue;
            }

            $fileSize = filesize($localTemp);
            Yii::info("File saved successfully: {$localTemp} ({$fileSize} bytes)", __METHOD__);

            $ext = strtolower($extension);
            $contentType = @mime_content_type($localTemp) ?: 'application/octet-stream';

            // original
            try {
                Yii::info("Uploading original to Minio: {$type}/{$objectId}/original/{$name}", __METHOD__);
                $s3->putVariant($type, (string) $objectId, 'original', $name, $localTemp, $contentType);
                Yii::info("Original uploaded successfully to Minio", __METHOD__);
            } catch (\Throwable $e) {
                Yii::error("Failed to upload original to Minio: {$e->getMessage()}", __METHOD__);
                @unlink($localTemp);
                continue;
            }

            // thumbnails (svg/mp4/webp skip)
            $makeThumbs = !in_array($ext, ['svg', 'mp4', 'webp'], true);
            if ($makeThumbs) {
                foreach ($this->image_sizes as $w => $h) {
                    $thumbPath = Yii::getAlias('@runtime') . "/{$w}_{$name}";

                    try {
                        // Tekshirish: source fayl hali mavjudmi?
                        if (!is_file($localTemp)) {
                            Yii::error("Source file missing before thumbnail creation: {$localTemp}", __METHOD__);
                            break;
                        }

                        Yii::info("Creating thumbnail: {$w}x{$h} from {$localTemp}", __METHOD__);
                        \yii\imagine\Image::thumbnail($localTemp, (int) $w, (int) $h)
                            ->save($thumbPath, ['quality' => 80]);
                        Yii::info("Thumbnail created: {$thumbPath}", __METHOD__);

                        $s3->putVariant($type, (string) $objectId, "{$w}x{$h}", $name, $thumbPath);

                        @unlink($thumbPath);
                    } catch (\Throwable $e) {
                        Yii::error("Failed to create thumbnail {$w}x{$h}: {$e->getMessage()}", __METHOD__);
                        @unlink($thumbPath);
                        // Continue with other sizes even if one fails
                    }
                }
            }

            @unlink($localTemp);

            if ($this->photo && $check) {
                $sizes = ['original'];
                foreach ($this->image_sizes as $w => $h)
                    $sizes[] = "{$w}x{$h}";
                $s3->deleteVariants($this->type, (string) $objectId, $this->photo, $sizes);

                Yii::$app->db->createCommand()->update('image', [
                    'photo' => $name,
                    'web' => 1,
                ], ['id' => $this->id])->execute();
            } else {
                Yii::$app->db->createCommand()->insert('image', [
                    'type' => $type_image ?: $type,
                    'object_id' => (int) $objectId,
                    'photo' => $name,
                    'main' => $main,
                    'sort' => 0,
                    'web' => 1,
                    'status' => 1,
                ])->execute();
            }
        }

        return true;
    }

    public function removeImage()
    {
        return (bool) $this->delete();
    }

    public function uploadPhotoColor($object_id)
    {
        if (!$this->colors)
            return true;

        /** @var S3Component $s3 */
        $s3 = Yii::$app->s3;

        foreach ($this->colors as $k => $color) {
            $c = ProductColor::findOne(['product_id' => $object_id, 'color_id' => $color]) ?: new ProductColor();
            $c->product_id = $object_id;
            $c->color_id = (int) $color;
            $c->status = 1;

            if (!$c->save(false)) {
                continue;
            }

            $file = $this->imageFiles[$k] ?? null;
            if (!$file)
                continue;

            $old = self::findOne(['object_id' => $c->id, 'type' => 'color']);
            if ($old) {
                $old->removeImageSize();
            }

            $rnd = mt_rand(0, 1000000);
            // WebP formatini qo'llab-quvvatlamaslik uchun jpg ga o'zgartiramiz
            $extension = strtolower($file->extension) === 'webp' ? 'jpg' : $file->extension;
            $name = time() . '_' . $rnd . '.' . $extension;

            $localTemp = Yii::getAlias('@runtime') . '/' . $name;
            if (!$file->saveAs($localTemp)) {
                Yii::error("Failed to save color image to: {$localTemp}", __METHOD__);
                continue;
            }

            // Tekshirish: fayl mavjudmi?
            if (!is_file($localTemp)) {
                Yii::error("Color image file not found after save: {$localTemp}", __METHOD__);
                continue;
            }

            $ext = strtolower($extension);
            $contentType = @mime_content_type($localTemp) ?: 'application/octet-stream';

            try {
                $s3->putVariant('color', (string) $c->id, 'original', $name, $localTemp, $contentType);
            } catch (\Throwable $e) {
                Yii::error("Failed to upload color image to Minio: {$e->getMessage()}", __METHOD__);
                @unlink($localTemp);
                continue;
            }

            if (!in_array($ext, ['svg', 'mp4', 'webp'], true)) {
                foreach ($this->image_sizes as $w => $h) {
                    $thumbPath = Yii::getAlias('@runtime') . "/{$w}_{$name}";
                    try {
                        if (!is_file($localTemp)) {
                            Yii::error("Color source file missing before thumbnail: {$localTemp}", __METHOD__);
                            break;
                        }
                        \yii\imagine\Image::thumbnail($localTemp, (int) $w, (int) $h)->save($thumbPath, ['quality' => 80]);
                        $s3->putVariant('color', (string) $c->id, "{$w}x{$h}", $name, $thumbPath);
                        @unlink($thumbPath);
                    } catch (\Throwable $e) {
                        Yii::error("Failed to create color thumbnail {$w}x{$h}: {$e->getMessage()}", __METHOD__);
                        @unlink($thumbPath);
                    }
                }
            }

            @unlink($localTemp);

            $img = new self();
            $img->object_id = (int) $c->id;
            $img->type = 'color';
            $img->photo = $name;
            $img->main = 1;
            $img->sort = 0;
            $img->web = 1;
            $img->status = 1;
            $img->save(false);
        }

        return true;
    }

    public function removeImageSize()
    {
        if ((int) $this->web === 1) {
            if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
                return (bool) $this->delete();
            }

            if (!$this->object_id || !$this->type || !$this->photo) {
                return (bool) $this->delete();
            }

            /** @var S3Component $s3 */
            $s3 = Yii::$app->s3;

            $sizes = ['original'];
            foreach ($this->image_sizes as $w => $h) {
                $sizes[] = "{$w}x{$h}";
            }

            $s3->deleteVariants($this->type, (string) $this->object_id, $this->photo, $sizes);

            return (bool) $this->delete();
        }

        $path = $this->object[$this->type] ?? null;
        if (!$path) {
            return (bool) $this->delete();
        }

        $original = $path . $this->object_id . '/original/' . $this->photo;
        if (is_file($original))
            unlink($original);

        foreach ($this->image_sizes as $k => $v) {
            $image = $path . $this->object_id . '/' . $k . 'x' . $v . '/' . $this->photo;
            if (is_file($image))
                unlink($image);
        }

        return (bool) $this->delete();
    }

    public function getPhoto(string $type, string $size = 'original'): string
    {
        if (!$this->photo) {
            return self::PHOTO_DEFAULT;
        }

        if ((int) $this->web === 1) {
            /** @var S3Component $s3 */
            $s3 = Yii::$app->s3;
            $realType = $this->type ?: $type;

            return $s3->urlVariant($realType, (string) $this->object_id, $size, $this->photo);
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

        if ((int) $this->web !== 1) {
            return;
        }

        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return;
        }

        if (!$this->object_id || !$this->type || !$this->photo) {
            return;
        }

        /** @var S3Component $s3 */
        $s3 = Yii::$app->s3;

        $sizes = ['original', '50x50', '100x100', '150x150', '200x200', '250x250', '300x300'];
        $s3->deleteVariants($this->type, (string) $this->object_id, $this->photo, $sizes);
    }
}
