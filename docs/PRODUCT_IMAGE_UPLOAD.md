# Product Image Upload System - Backend Developer Guide

This document explains how the product image upload system works in the Yii2 admin panel. It covers the complete flow from frontend to backend, including file storage, database structure, and all related components.

## Table of Contents

1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [File Storage Structure](#file-storage-structure)
4. [Core Components](#core-components)
5. [Upload Flow](#upload-flow)
6. [Delete Flow](#delete-flow)
7. [Retrieving Images](#retrieving-images)
8. [Code Examples](#code-examples)
9. [Creating Similar Upload for New Entity](#creating-similar-upload-for-new-entity)

---

## Overview

The image upload system handles:
- **Main product photo** (single image, `main=1`)
- **Product gallery** (multiple images, `main=2`)
- **Automatic thumbnail generation** in multiple sizes
- **Image hash generation** for duplicate detection
- **CRUD operations** for images

### Key Technologies Used:
- Yii2 Framework
- jQuery FileUploader Plugin
- Yii2 Imagine Extension (for image processing)
- ImageHash library (for duplicate detection)

---

## Database Structure

### Table: `image`

```sql
CREATE TABLE `image` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `object_id` INT(11) NOT NULL COMMENT 'ID of the related entity (product_id, user_id, etc.)',
    `type` VARCHAR(255) NOT NULL COMMENT 'Entity type: product, user, category, etc.',
    `photo` VARCHAR(255) NOT NULL COMMENT 'Filename of the image',
    `main` TINYINT(1) DEFAULT 1 COMMENT '1=main photo, 2=gallery photo',
    `sort` INT(11) DEFAULT 0 COMMENT 'Sort order for gallery',
    `status` TINYINT(1) DEFAULT 1 COMMENT '0=pending, 1=approved',
    `web` TINYINT(1) DEFAULT 0 COMMENT '0=local file, 1=external URL',
    `hash` VARCHAR(255) DEFAULT NULL COMMENT 'Image hash for duplicate detection',
    `number_image` VARCHAR(255) DEFAULT NULL COMMENT 'Additional identifier',
    PRIMARY KEY (`id`),
    KEY `idx_object_type` (`object_id`, `type`),
    KEY `idx_type_main` (`type`, `main`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Field Descriptions:

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Primary key |
| `object_id` | INT | Foreign key to the parent entity (e.g., product.id) |
| `type` | VARCHAR | Entity type identifier ('product', 'user', 'category', etc.) |
| `photo` | VARCHAR | Filename only (e.g., '1723857270.png') |
| `main` | TINYINT | 1 = Main/primary photo, 2 = Gallery/secondary photo |
| `sort` | INT | Display order for gallery images |
| `status` | TINYINT | 0 = Pending approval, 1 = Approved/Active |
| `web` | TINYINT | 0 = Local file, 1 = External URL (stored in `photo` field) |
| `hash` | VARCHAR | Perceptual hash for duplicate image detection |

---

## File Storage Structure

Images are stored in the `web/uploads/` directory with the following structure:

```
web/uploads/
├── product/
│   └── {product_id}/
│       ├── original/
│       │   └── {timestamp}.{ext}      # Original uploaded file
│       ├── 50x50/
│       │   └── {timestamp}.{ext}      # Thumbnail 50x50
│       ├── 100x100/
│       │   └── {timestamp}.{ext}      # Thumbnail 100x100
│       ├── 150x150/
│       │   └── {timestamp}.{ext}      # Thumbnail 150x150
│       ├── 200x200/
│       │   └── {timestamp}.{ext}      # Thumbnail 200x200
│       ├── 250x250/
│       │   └── {timestamp}.{ext}      # Thumbnail 250x250
│       └── 300x300/
│           └── {timestamp}.{ext}      # Thumbnail 300x300
├── user/
│   └── {user_id}/
│       └── ... (same structure)
├── category/
│   └── {category_id}/
│       └── ... (same structure)
└── ... (other entity types)
```

### Supported Image Types by Entity:

| Type | Path Constant | Directory |
|------|--------------|-----------|
| product | `PHOTO_PRODUCT_PATH` | `uploads/product/` |
| user | `PHOTO_USER_PATH` | `uploads/user/` |
| category | `PHOTO_CATEGORY_PATH` | `uploads/category/` |
| news | `PHOTO_NEWS_PATH` | `uploads/news/` |
| brand | `PHOTO_BRAND_PATH` | `uploads/brand/` |
| shop | `PHOTO_SHOP_PATH` | `uploads/shop/` |
| slider | `PHOTO_SLIDER_PATH` | `uploads/slider/` |
| banner | `PHOTO_BANNER_PATH` | `uploads/banner/` |

---

## Core Components

### 1. Images Model (`models/Images.php`)

This is the core model that handles all image operations.

```php
<?php
namespace app\models;

use Yii;
use yii\imagine\Image;
use app\models\user\User;
use yii\helpers\FileHelper;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

class Images extends \yii\db\ActiveRecord
{
    // Path constants for different entity types
    const PHOTO_USER_PATH = 'uploads/user/';
    const PHOTO_CATEGORY_PATH = 'uploads/category/';
    const PHOTO_PRODUCT_PATH = 'uploads/product/';
    const PHOTO_NEWS_PATH = 'uploads/news/';
    const PHOTO_DELIVERY_PATH = 'uploads/delivery/';
    const PHOTO_SLIDER_PATH = 'uploads/slider/';
    const PHOTO_SHOP_PATH = 'uploads/shop/';
    const PHOTO_BRAND_PATH = 'uploads/brand/';
    const PHOTO_BANNER_PATH = 'uploads/banner/';
    const PHOTO_DEFAULT = '/assets_files/images/no-photo.png';

    // Virtual property for file upload
    public $imageFiles = [];
    public $colors = [];

    // Mapping of type names to paths
    public $object = [
        'user' => self::PHOTO_USER_PATH,
        'category' => self::PHOTO_CATEGORY_PATH,
        'product' => self::PHOTO_PRODUCT_PATH,
        'news' => self::PHOTO_NEWS_PATH,
        'delivery' => self::PHOTO_DELIVERY_PATH,
        'slider' => self::PHOTO_SLIDER_PATH,
        'shop' => self::PHOTO_SHOP_PATH,
        'brand' => self::PHOTO_BRAND_PATH,
        'banner' => self::PHOTO_BANNER_PATH
    ];

    // Thumbnail sizes to generate
    public $image_sizes = [
        '50' => '50',
        '100' => '100',
        '150' => '150',
        '200' => '200',
        '250' => '250',
        '300' => '300'
    ];

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
}
```

### 2. Upload Method

The core upload method in `Images` model:

```php
/**
 * Upload photo(s) for an entity
 * 
 * @param int|array $object_id - ID of the entity (or array of IDs for batch upload)
 * @param string $type - Entity type ('product', 'user', etc.)
 * @param int $main - 1 for main photo, 2 for gallery
 * @param string|null $type_image - Override type in database (optional)
 * @param bool $check - Whether to replace existing image (true) or add new (false)
 * @return bool
 */
public function uploadPhoto($object_id, $type, $main = 1, $type_image = null, $check = true) 
{
    // Validate type exists in our mapping
    if (!array_key_exists($type, $this->object)) {
        return false;
    }

    $path = $this->object[$type];

    // Helper function to create directory
    $createDir = function($dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }
    };

    // Create directories for object
    if (is_array($object_id)) {
        foreach ($object_id as $v) {
            $createDir($path . $v);
            $createDir($path . $v . '/original');
        }
    } else {
        $createDir($path . $object_id);
        $createDir($path . $object_id . '/original');
    }
    
    // Process each uploaded file
    foreach ($this->imageFiles as $key => $file) {
        // Handle array of files
        $file = is_array($file) ? $file[$key] : $file;

        // Determine object ID for this file
        if (is_array($object_id)) {
            $id = $object_id[$key];
        } else {
            $id = $object_id;
        }

        // Generate unique filename
        $rnd = mt_rand(0, 1000000);
        $name = time() + $rnd . '.' . $file->extension;
        $original = $path . $id . '/original/' . $name;

        // Save original file
        if ($file->saveAs($original)) {
            // Generate image hash for duplicate detection
            $hasher = new ImageHash(new DifferenceHash());
            $hash = $hasher->hash(Yii::$app->params['baseUrl'] . '/' . $original);

            // Create thumbnails (skip for video and SVG)
            if ($file->extension != 'mp4' && $file->extension != 'svg') {
                foreach ($this->image_sizes as $sizeKey => $img) {
                    $sizePath = $path . $id . '/' . $sizeKey . 'x' . $img;
                    $createDir($sizePath);
                    Image::thumbnail($original, $sizeKey, $img)
                        ->save(Yii::getAlias($sizePath . '/' . $name), ['quality' => 80]);
                }
            }

            // Determine status based on user role
            $status = (Yii::$app->user->identity->role == User::ROLE_ADMIN) ? 1 : 0;

            // Update existing or insert new record
            if ($this->photo && $check) {
                // Delete old files
                $oldOriginal = $path . $id . '/original/' . $this->photo;
                if (is_file($oldOriginal)) {
                    unlink($oldOriginal);
                }
                if ($file->extension != 'mp4' && $file->extension != 'svg') {
                    foreach ($this->image_sizes as $sizeKey => $img) {
                        $photo = $path . $id . '/' . $sizeKey . 'x' . $img . '/' . $this->photo;
                        if (is_file($photo)) {
                            unlink($photo);
                        }
                    }
                }

                // Update database record
                Yii::$app->db->createCommand()->update('image', 
                    ['photo' => $name, 'hash' => $hash], 
                    ['id' => $this->id]
                )->execute();
            } else {
                // Insert new record
                Yii::$app->db->createCommand()->insert('image', [
                    'type' => $type_image ? $type_image : $type,
                    'object_id' => $id,
                    'photo' => $name,
                    'main' => $main,
                    'sort' => 0,
                    'web' => 0,
                    'status' => $status,
                    'hash' => $hash
                ])->execute();
            }
        } else {
            return false;
        }
    }

    return true;
}
```

### 3. Delete Method

```php
/**
 * Remove image with all its thumbnails
 * @return bool
 */
public function removeImageSize()
{
    $path = $this->object[$this->type];

    // Delete original file
    $original = $path . $this->object_id . '/original/' . $this->photo;
    if (is_file($original)) {
        unlink($original);
    }

    $dir_empty = false;

    // Delete all thumbnails
    foreach ($this->image_sizes as $k => $v) {
        $image = $path . $this->object_id . '/' . $k . 'x' . $v . '/' . $this->photo;
        if (is_file($image)) {
            unlink($image);
        }
        $dir_empty = (glob($image . '*')) ? false : true;
    }

    // Clean up empty directories (optional)
    if ($dir_empty === true) {
        if (!is_file($path . $this->object_id . '/original/' . $this->photo)) {
            foreach ($this->image_sizes as $k => $v) {
                @rmdir($path . $this->object_id . '/' . $k . 'x' . $v);
            }
            @rmdir($path . $this->object_id . '/original');
            @rmdir($path . $this->object_id);
        }
    }

    // Delete database record
    return $this->delete() ? true : false;
}
```

### 4. Get Photo Method

```php
/**
 * Get photo URL
 * @param string $type - Entity type
 * @param string $size - Size folder name ('original', '200x200', etc.)
 * @return string - URL to the image
 */
public function getPhoto($type, $size = 'original')
{
    // If it's an external URL, return as-is
    if ($this->web == 1) {
        return $this->photo;
    }
    
    $path = 'uploads/' . $type . '/' . $this->object_id . '/' . $size . '/' . $this->photo;

    if (is_file($path)) {
        return '/' . $path;
    }

    return self::PHOTO_DEFAULT;
}
```

---

## Upload Flow

### Step 1: View Layer (Form)

```php
<?php
use yii\bootstrap4\ActiveForm;

// Form must have enctype for file uploads
$form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);
?>

<!-- Main Photo (single) -->
<?= $form->field($model, 'imageFiles[]', ['template' => '{input}{error}'])->fileInput([
    'class' => 'file-upload-ajax',  // jQuery plugin class
    'accept' => 'image/jpeg, image/jpg, image/png, image/gif, image/svg+xml, image/webp'
]); ?>

<!-- Gallery Photos (multiple) -->
<?= $form->field($model, 'imageGallery[]')->fileInput([
    'multiple' => true, 
    'accept' => 'image/jpeg, image/jpg, image/png, image/gif, image/svg+xml, image/webp', 
    'class' => 'file-upload-ajax-gallery'
])->label('Gallery Photos') ?>

<!-- Display current photo -->
<img src="<?= $model->getPhoto('200x200') ?>" width="200" class="img-thumbnail"/>

<!-- Delete photo button (if photo exists) -->
<?php if ($model->image): ?>
    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id' => $model->image->id]) ?>" 
       class="btn btn-danger btn-sm">
        <i class="fa fa-trash"></i> Delete Photo
    </a>
<?php endif; ?>

<?php ActiveForm::end(); ?>
```

### Step 2: Entity Model (Product.php)

Add virtual properties and relations:

```php
<?php
namespace app\models\product;

use Yii;
use yii\web\UploadedFile;
use app\models\Images;

class Product extends \yii\db\ActiveRecord
{
    // Virtual properties for file uploads
    public $imageFiles = [];      // Main photo
    public $imageGallery = [];    // Gallery photos

    public function rules()
    {
        return [
            // ... other rules
            // Note: File validation is handled by Images model
        ];
    }

    /**
     * Get main product image relation
     */
    public function getImage()
    {
        return $this->hasOne(Images::class, ['object_id' => 'id'])
            ->andOnCondition(['type' => 'product', 'main' => 1]);
    }

    /**
     * Get gallery images relation
     */
    public function getGallery()
    {
        return $this->hasMany(Images::class, ['object_id' => 'id'])
            ->andOnCondition(['type' => 'product', 'main' => 2]);
    }

    /**
     * Get photo URL with fallback
     * @param string $size - Size folder ('original', '200x200', etc.)
     * @return string
     */
    public function getPhoto($size = 'original')
    {
        if ($this->image) {
            if ($this->image->web == 1) {
                return $this->image->photo;
            }
            $path = Images::PHOTO_PRODUCT_PATH . $this->image->object_id . '/' . $size . '/' . $this->image->photo;
            if (is_file($path)) {
                return '/' . $path;
            }
        }
        return Images::PHOTO_DEFAULT;
    }

    /**
     * Update product with image handling
     */
    public function updateObject()
    {
        if ($this->save()) {
            // Handle main photo upload
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                // Remove old main photo if exists
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                // Upload new main photo (main=1)
                $image->uploadPhoto($this->id, 'product', 1);
            }

            // Handle gallery upload
            $galleryImage = new Images;
            if ($galleryImage->imageFiles = UploadedFile::getInstances($this, 'imageGallery')) {
                // Upload gallery photos (main=2)
                $galleryImage->uploadPhoto($this->id, 'product', 2);
            }

            return $this;
        }
        return false;
    }

    /**
     * Delete product with all images
     */
    public function removeObject()
    {
        // Remove main photo
        if ($this->image) {
            $this->image->removeImageSize();
        }

        // Remove gallery photos
        if ($this->gallery) {
            foreach ($this->gallery as $photo) {
                $photo->removeImageSize();
            }
        }
        
        return $this->delete();
    }
}
```

### Step 3: Controller

```php
<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use app\models\product\Product;
use app\models\Images;

class ProductController extends Controller
{
    /**
     * Create/Update product
     */
    public function actionCreate($id = null)
    {
        $model = $id ? Product::find()
            ->with('image', 'gallery')
            ->where(['id' => $id])
            ->one() : new Product;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($id) {
                $product = $model->updateObject();
            } else {
                $product = $model->saveObject();
            }
            
            if ($product) {
                Yii::$app->session->setFlash('success', 'Product saved');
                return $this->redirect(['/admin/product/view', 'id' => $product->id]);
            }
        }

        return $this->render($id ? 'update' : 'create', [
            'model' => $model,
        ]);
    }
}
```

### Step 4: Delete Photo Controller Action

```php
<?php
// In DefaultController.php or any base controller

/**
 * Delete a photo by ID
 * @param int $id - Image ID
 */
public function actionRemovePhoto($id)
{
    $model = Images::findOne($id);

    if ($model && $model->removeImageSize()) {
        Yii::$app->session->setFlash('success', 'Photo deleted');
    }

    return $this->redirect(Yii::$app->request->referrer);
}
```

---

## Delete Flow

```
User clicks "Delete Photo" button
        ↓
Request: /admin/default/remove-photo?id=123
        ↓
DefaultController::actionRemovePhoto($id)
        ↓
Images::findOne($id)
        ↓
$model->removeImageSize()
    ├── Delete: uploads/product/{id}/original/{filename}
    ├── Delete: uploads/product/{id}/50x50/{filename}
    ├── Delete: uploads/product/{id}/100x100/{filename}
    ├── Delete: uploads/product/{id}/150x150/{filename}
    ├── Delete: uploads/product/{id}/200x200/{filename}
    ├── Delete: uploads/product/{id}/250x250/{filename}
    ├── Delete: uploads/product/{id}/300x300/{filename}
    └── DELETE FROM image WHERE id = {image_id}
        ↓
Redirect back to previous page
```

---

## Retrieving Images

### In Controller (with eager loading):

```php
$product = Product::find()
    ->with('image', 'gallery')  // Eager load images
    ->where(['id' => $id])
    ->one();
```

### In View:

```php
<!-- Main photo with specific size -->
<img src="<?= $model->getPhoto('200x200') ?>" alt="Product">

<!-- Original size -->
<img src="<?= $model->getPhoto('original') ?>" alt="Product">

<!-- Gallery loop -->
<?php foreach ($model->gallery as $photo): ?>
    <img src="<?= $photo->getPhoto('product', '250x250') ?>" alt="Gallery">
<?php endforeach; ?>
```

### In API Response:

```php
public function fields()
{
    return [
        'id',
        'name',
        'image',  // Returns Image model
        'gallery', // Returns array of Image models
        'photo' => function() {
            return $this->getPhoto('200x200');
        },
        'photos' => function() {
            return $this->getPhotos('original');
        },
    ];
}
```

---

## Code Examples

### Example 1: Adding Image Upload to a New Entity (e.g., News)

**Step 1: Add to Images model (if new type):**

```php
// In models/Images.php
const PHOTO_NEWS_PATH = 'uploads/news/';

public $object = [
    // ... existing types
    'news' => self::PHOTO_NEWS_PATH,
];
```

**Step 2: Create News model:**

```php
<?php
namespace app\models;

use Yii;
use yii\web\UploadedFile;

class News extends \yii\db\ActiveRecord
{
    public $imageFiles = [];

    public static function tableName()
    {
        return 'news';
    }

    public function getImage()
    {
        return $this->hasOne(Images::class, ['object_id' => 'id'])
            ->andOnCondition(['type' => 'news', 'main' => 1]);
    }

    public function getPhoto($size = 'original')
    {
        if ($this->image) {
            $path = Images::PHOTO_NEWS_PATH . $this->image->object_id . '/' . $size . '/' . $this->image->photo;
            if (is_file($path)) {
                return '/' . $path;
            }
        }
        return Images::PHOTO_DEFAULT;
    }

    public function saveWithImage()
    {
        if ($this->save()) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'news');
            }
            return $this;
        }
        return false;
    }
}
```

### Example 2: Upload from URL (External Image)

```php
// Save external URL as image
$image = new Images;
$image->object_id = $product->id;
$image->type = 'product';
$image->photo = 'https://example.com/image.jpg';  // Full URL
$image->web = 1;  // Mark as external URL
$image->main = 1;
$image->status = 1;
$image->save();
```

### Example 3: Batch Upload for Multiple Products

```php
// Upload same image to multiple products
$image = new Images;
$image->imageFiles = UploadedFile::getInstances($model, 'imageFiles');
$productIds = [1, 2, 3, 4, 5];
$image->uploadPhoto($productIds, 'product');
```

---

## Creating Similar Upload for New Entity

### Checklist:

1. **Add path constant to `Images` model** (if not exists)
   ```php
   const PHOTO_NEWENTITY_PATH = 'uploads/newentity/';
   ```

2. **Add to `$object` array in `Images` model**
   ```php
   'newentity' => self::PHOTO_NEWENTITY_PATH,
   ```

3. **Add virtual property to entity model**
   ```php
   public $imageFiles = [];
   ```

4. **Add `getImage()` relation to entity model**
   ```php
   public function getImage() {
       return $this->hasOne(Images::class, ['object_id' => 'id'])
           ->andOnCondition(['type' => 'newentity', 'main' => 1]);
   }
   ```

5. **Add `getPhoto()` method to entity model**
   ```php
   public function getPhoto($size = 'original') { ... }
   ```

6. **Handle upload in save/update method**
   ```php
   $image = new Images;
   if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
       if ($this->image) {
           $this->image->removeImageSize();
       }
       $image->uploadPhoto($this->id, 'newentity');
   }
   ```

7. **Add file input to view form**
   ```php
   <?= $form->field($model, 'imageFiles[]')->fileInput([
       'class' => 'file-upload-ajax',
       'accept' => 'image/*'
   ]); ?>
   ```

8. **Create uploads directory** (will be auto-created, but ensure write permissions)
   ```
   web/uploads/newentity/
   ```

---

## JavaScript Plugin Configuration

The frontend uses jQuery FileUploader plugin. Configuration is in `web/plugins/file-uploader/js/custom.js`:

```javascript
// Single file upload
$('.file-upload-ajax').fileuploader({
    limit: 1,
    extensions: ['jpg', 'jpeg', 'png', 'webp', 'svg'],
    changeInput: ' ',
    theme: 'thumbnails',
    enableApi: true,
    addMore: true,
    // ... thumbnails configuration
});

// Multiple file upload (gallery)
$('.file-upload-ajax-gallery').fileuploader({
    extensions: ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'svg'],
    changeInput: ' ',
    theme: 'thumbnails',
    enableApi: true,
    addMore: true,
    // ... thumbnails configuration
});
```

---

## Troubleshooting

### Common Issues:

1. **Images not uploading**
   - Check `enctype="multipart/form-data"` on form
   - Check file permissions on `uploads/` directory
   - Check PHP `upload_max_filesize` and `post_max_size`

2. **Thumbnails not generating**
   - Ensure Yii2 Imagine extension is installed: `composer require yiisoft/yii2-imagine`
   - Check GD or Imagick PHP extension is installed

3. **Hash not working**
   - Install ImageHash library: `composer require jenssegers/imagehash`

4. **Delete not working**
   - Check file permissions
   - Ensure `removeImageSize()` is called, not just `delete()`

---

## Dependencies

Add to `composer.json`:

```json
{
    "require": {
        "yiisoft/yii2-imagine": "~2.3.0",
        "jenssegers/imagehash": "^0.8.0"
    }
}
```

Run: `composer install`

---

## Security Considerations

1. **File type validation** - Only allow specific extensions
2. **File size limits** - Set in model rules and PHP config
3. **Filename sanitization** - Use timestamp-based names (already implemented)
4. **Directory traversal prevention** - Never use user input directly in paths
5. **Status approval** - Non-admin uploads have `status=0` by default

---

## Summary

| Component | File | Purpose |
|-----------|------|---------|
| Images Model | `models/Images.php` | Core image handling |
| Entity Model | `models/product/Product.php` | Entity-specific relations |
| Controller | `modules/admin/controllers/ProductController.php` | HTTP handling |
| View | `modules/admin/views/product/create.php` | Form UI |
| JavaScript | `web/plugins/file-uploader/js/custom.js` | Frontend upload UI |
| Delete Action | `modules/admin/controllers/DefaultController.php` | Photo deletion |

---

*Last Updated: January 2026*
*Version: 1.0*

