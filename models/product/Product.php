<?php

namespace app\models\product;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\user\favorite\UserFavorite;
use app\models\product\review\ProductReview;
use app\models\product\ProductProperty;
use app\models\product\ProductFilter;
use app\models\product\ProductProductType;
use app\models\product\ProductType;
use app\models\color\Color;
use app\models\Category;
use app\models\brand\CategoryBrand;
use app\models\Images;
use app\models\Notification;
use app\models\delivery\Delivery;
use app\models\office\ProductOffice;
use app\models\shop\Shop;
use app\models\stock\Stock;
use app\models\Ikpu;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Intervention\Image\ImageManager;
/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property int|null $region_id
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property float|null $price
 * @property int $view
 * @property int $status
 * @property string $date
 * @property string|null $ikpu_code Код ИКПУ
 * @property string|null $ikpu_name Название ИКПУ (кэшированное)
 * @property string|null $package_code Код упаковки
 * @property string|null $package_name Название упаковки
 *
 * @property Category $category
 * @property Ikpu $ikpu
 * @property UserFavorite[] $productFavorites
 * @property ProductFilter[] $productFilters
 * @property ProductView[] $productViews
 * @property User $user
 */
class Product extends \yii\db\ActiveRecord
{
    public $imageFiles = [];
    public $imageGallery = [];
    public $galleryFiles = []; // Added for gallery file uploads
    public $filters = [];
    public $properties_data = [];

    public $sub_category_id = [];
    public $galleryPhoto = [];
    public $colors = [];
    public $product_types = [];
    public $product_relation_id;
    public $office_id;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru', 'description_ru', 'price', 'category_id', 'shop_id'], 'required', 'message'=>'Заполните поле'],
            [['user_id', 'category_id', 'brand_id', 'tag_id', 'region_id', 'currency_id', 'unit_id', 'color_id', 'delivery_id', 'product_relation_id', 'views', 'status', 'office_id', 'shop_id', 'stock_id', 'sklad_product_id', 'sync_status'], 'integer'],
            [['description_ru', 'description_en', 'description_uz', 'composition_ru', 'composition_en', 'composition_uz', 'recommendation_ru', 'recommendation_en', 'recommendation_uz', 'token_key', 'name_trans_ru', 'name_trans_en'], 'string'],
            [['price', 'rating', 'discount', 'discount_small_count', 'discount_big_count', 'amount', 'price_opt', 'price_small', 'min_order', 'weight', 'height', 'width', 'length'], 'number'],
            [['qty_small_wholesale', 'qty_big_wholesale'], 'integer', 'min' => 1],
            [['date', 'filters', 'sub_category_id', 'properties_data', 'colors', 'product_types', 'galleryFiles'], 'safe'],
            [['name_ru', 'name_en', 'name_uz', 'category_tree', 'credit_label'], 'string', 'max' => 255],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['shop_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shop::className(), 'targetAttribute' => ['shop_id' => 'id']],
            [['stock_id'], 'exist', 'skipOnError' => true, 'targetClass' => Stock::className(), 'targetAttribute' => ['stock_id' => 'id']],
            // [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, webp'],
            // [['imageGallery'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, webp'],
            // [['galleryPhoto'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, webp'],

            // billz
            [['billz_id', 'sku', 'barcode', 'qty'], 'string'],
            
            // IKPU fields
            [['ikpu_code'], 'string', 'max' => 17],
            [['ikpu_name'], 'string', 'max' => 500],
            [['package_code'], 'string', 'max' => 50],
            [['package_name'], 'string', 'max' => 100],
            [['ikpu_code'], 'match', 'pattern' => '/^[0-9]{17}$/', 'message' => 'Код ИКПУ должен содержать ровно 17 цифр', 'skipOnEmpty' => true],
            [['ikpu_code'], 'validateIkpuCode']
        ];
    }

    /**
     * Custom validation for IKPU code
     * Allows both existing IKPU codes from database and custom codes
     */
    public function validateIkpuCode($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            // Check if IKPU exists in database
            $ikpu = Ikpu::findOne(['code' => $this->$attribute]);
            
            if ($ikpu) {
                // If IKPU exists in database, clear custom name (will be auto-filled from relation)
                $this->ikpu_name = null;
            } else {
                // Custom IKPU code - require ikpu_name
                if (empty($this->ikpu_name)) {
                    $this->addError('ikpu_name', 'Для пользовательского кода ИКПУ необходимо указать название');
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'category_id' => 'Category ID',
            'brand_id' => 'Brand ID',
            'region_id' => 'Region ID',
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
            'name_uz' => 'Name Uz',
            'description_ru' => 'Description Ru',
            'description_en' => 'Description En',
            'description_uz' => 'Description Uz',
            'price' => 'Price',
            'price_opt' => 'Price Optom',
            'price_small' => 'Price Small',
            'min_order' => 'Min Order',
            'weight' => 'Weight',
            'height' => 'Height',
            'width' => 'Width',
            'length' => 'Length',
            'composition_ru' => 'Composition Ru',
            'composition_en' => 'Composition En',
            'composition_uz' => 'Composition Uz',
            'recommendation_ru' => 'Recommendation Ru',
            'recommendation_en' => 'Recommendation En',
            'recommendation_uz' => 'Recommendation Uz',
            'credit_label' => 'Credit Label',
            'views' => 'Views',
            'rating' => 'Rating',
            'view' => 'View',
            'status' => 'Status',
            'date' => 'Date',
            'ikpu_code' => 'Код ИКПУ',
            'ikpu_name' => 'Название ИКПУ',
            'package_code' => 'Код упаковки',
            'package_name' => 'Название упаковки',
        ];
    }

    function transliterate($st, $rotate = false) {
        $letters = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '\'', 'ы' => 'yi', 'ъ' => '\'',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '\'', 'Ы' => 'YI', 'Ъ' => '\'',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );
        $letters = $rotate ? array_flip($letters) : $letters;
        $st = strtr($st, $letters);
        return $st;
    }

    public function setCategoryDashboard($category_id = null) {
        if (!$category_id) {
            $category_id = $this->category_id;
            $this->sub_category_id[] = (int)$this->category_id;
        }

        $category = Category::find()->with('parent')->where(['id'=>$category_id])->one();
        if ($category && $category->parent) {
            $this->sub_category_id[] = $category->parent->id;
            $this->setCategory($category->parent->id);
        }

        $this->sub_category_id = array_reverse($this->sub_category_id);
        $this->category_tree = implode('/', $this->sub_category_id);

        return $this->category_tree;
    }

    public function softDelete()
    {
        $this->status = 2;
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save(false);
    }

    public function setCategory($category_id = null) {
        if (!$category_id) {
            $category_id = $this->category_id;
            $this->sub_category_id[] = (int)$this->category_id;
        }

        $category = Category::find()->with('parent')->where(['id'=>$category_id])->one();
        if ($category && $category->parent) {
            $this->sub_category_id[] = $category->parent->id;
            $this->setCategory($category->parent->id);
        }

        $this->sub_category_id = array_reverse($this->sub_category_id);
        $this->category_tree = implode('/', $this->sub_category_id);

        return $this->sub_category_id;
    }

    public function saveObject($dashboard = false, $color = null, $token_key = null, $category_tree = null, $product_types = null) {
        // Create new product instance when we have variants (color or product_types)
        if ($color || $product_types) {
            $product = new Product;
            if ($dashboard === false) {
                $product->setAttributes(Yii::$app->request->post()['Product']);
            } else {
                $product->setAttributes(Yii::$app->request->post());
                $product->token_key = $token_key;
            }
            if ($color) {
                $product->color_id = $color;
            }
        }
        // Create new product for dashboard without variants
        else if (!$color && $dashboard == true) {
            $product = new Product;
            $product->setAttributes(Yii::$app->request->post());
            $product->token_key = $token_key;
        }
        // Use existing product instance for regular updates
        else if (!$color && !$dashboard && !$product_types) {
            $product = $this;
        }
        // For other cases, create new product
        else {
            $product = new Product;
            if ($dashboard === false) {
                $product->setAttributes(Yii::$app->request->post()['Product']);
            } else {
                $product->setAttributes(Yii::$app->request->post());
                $product->token_key = $token_key;
            }
        }
        // Set token_key for linking variants
        if ($this->product_relation_id) {
            $pr = Product::findOne($this->product_relation_id);
            if ($pr) {
                $product->token_key = $pr->token_key;
            }
        } else if (($color || $product_types) && !$product->token_key) {
            // Generate token_key for linking variants if not set
            $product->token_key = $this->token_key ?: Yii::$app->security->generateRandomString();
        }
        /** @var User|null $user */
        $user = Yii::$app->user->identity;
        $product->user_id = $product->user_id ? $product->user_id : ($user ? $user->id : null);
        $product->status = 2;

        $product->name_trans_ru = $this->transliterate($product->name_ru, true);
        $product->name_trans_en = $this->transliterate($product->name_ru);

        if ($dashboard === false) {
            $tree = [$product->category_id];

            if ($product->sub_category_id) {
                foreach ($product->sub_category_id as $category) {
                    if ($category) {
                        $tree[] = $category;
                        $product->category_id = $category;
                    }
                }
            }

            $product->category_tree = implode('/', $tree);
        } else {
            if ($category_tree) {
                $product->category_tree = $category_tree;
            }
            /** @var User $user */
            $user = Yii::$app->user->identity;
            $shop = Shop::findOne(['user_id'=>$user->id]);
            if (!$shop) {
                $shop = Shop::findOne(['id'=>$user->shop_id]);
            }

            $product->shop_id = $shop->id;
        }

        if ($product->save()) {
            $admin = User::findOne(['role'=>User::ROLE_ADMIN]);
            /** @var User $user */
            $user = Yii::$app->user->identity;
            if ($user->role != User::ROLE_ADMIN && !Yii::$app->request->get('id')) {
                $notification = new Notification;
                $notification->saveObject($admin->id, $product->id, 'product_new', 'Добавлен новый товар');
            }

            ProductProperty::deleteAll(['product_id' => $product->id]);

            if ($product->properties_data) {
                $keys = ['product_id', 'key_name', 'value_name'];
                $vals = [];
                if (array_key_exists('key_name', $product->properties_data)) {
                    foreach ($product->properties_data['key_name'] as $key => $val) {
                        $vals[] = [
                            'product_id' => $product->id,
                            'key_name' => $val,
                            'value_name' => $product->properties_data['value_name'][$key],
                        ];
                    }
                }

                Yii::$app->db->createCommand()->batchInsert('product_property', $keys, $vals)->execute();
            }

            ProductFilter::deleteAll(['product_id'=>$product->id]);

            if ($product->filters) {
                $keys = ['product_id', 'filter_id', 'value_ru'];
                $vals = [];
                foreach ($product->filters as $key => $val) {
                    if ($val) {
                        if (is_array($val)) {
                            foreach ($val as $k => $v) {
                                if ($v) {
                                    $vals[] = [
                                        'product_id' => $product->id,
                                        'filter_id' => $key,
                                        'value_ru' => $v,
                                    ];
                                }
                            }
                        } else {
                            $vals[] = [
                                'product_id' => $product->id,
                                'filter_id' => $key,
                                'value_ru' => $val,
                            ];
                        }
                    }
                }

                Yii::$app->db->createCommand()->batchInsert('product_filter', $keys, $vals)->execute();
            }

            // product types
            if ($product_types) {
                $keys = ['product_id', 'product_type_id', 'product_type_value_id', 'custom_value'];
                $vals = [];
                
                foreach ($product_types as $type_id => $type_values) {
                    if ($type_values) {
                        // Get the product type to check its type
                        $productType = ProductType::findOne($type_id);
                        if (!$productType) {
                            continue; // Skip if product type doesn't exist
                        }
                        
                        if (is_array($type_values)) {
                            foreach ($type_values as $type_value) {
                                if ($type_value) {
                                    if ($productType->type == ProductType::TYPE_INPUT) {
                                        // For input types, store as custom_value
                                        $vals[] = [
                                            'product_id' => $product->id,
                                            'product_type_id' => $type_id,
                                            'product_type_value_id' => null,
                                            'custom_value' => $type_value,
                                        ];
                                    } else {
                                        // For select/checkbox/range types, store as product_type_value_id
                                        $vals[] = [
                                            'product_id' => $product->id,
                                            'product_type_id' => $type_id,
                                            'product_type_value_id' => $type_value,
                                            'custom_value' => null,
                                        ];
                                    }
                                }
                            }
                        } else {
                            if ($productType->type == ProductType::TYPE_INPUT) {
                                // For input types, store as custom_value
                                $vals[] = [
                                    'product_id' => $product->id,
                                    'product_type_id' => $type_id,
                                    'product_type_value_id' => null,
                                    'custom_value' => $type_values,
                                ];
                            } else {
                                // For select/checkbox/range types, store as product_type_value_id
                                $vals[] = [
                                    'product_id' => $product->id,
                                    'product_type_id' => $type_id,
                                    'product_type_value_id' => $type_values,
                                    'custom_value' => null,
                                ];
                            }
                        }
                    }
                }
                
                if ($vals) {
                    Yii::$app->db->createCommand()->batchInsert('product_product_type', $keys, $vals)->execute();
                }
            }

            // colors
            // if ($this->colors && $dashboard === false) {
            //     foreach ($this->colors['color'] as $k => $color) {
            //         if ($this->colors['color'][$k]) {
            //             $c = ProductColor::findOne(['product_id'=>$this->id, 'color_id'=>$color]);
            //             if (!$c) {
            //                 $c = new ProductColor;
            //             }
            //             $c->product_id = $this->id;
            //             $c->color_id = $color;
            //             $c->status = 1;
            //             if ($c->save()) {
            //                 if ($this->colors['image'][$k]) {
            //                     foreach ($this->colors['image'][$k] as $key => $image) {
            //                         $color_image = new Images;
            //                         if ($color_image->imageFiles = UploadedFile::getInstance($this, 'colors[image]['.$k.']['.$key.']')) {
            //                             $img = Images::findOne(['object_id'=>$c->id, 'type'=>'color', 'number_image'=>$key]);
            //                             if ($img) {
            //                                 $img->removeImageSize('color');
            //                             }
            //                             $path = 'uploads/color/';
            //                             $rnd = mt_rand(0, 1000000);
            //                             $name = time() + $rnd.'.'.$color_image->imageFiles->extension;
            //                             $original = $path.$name;

            //                             $color_image->imageFiles->saveAs($original);

            //                             $image = new Images;
            //                             $image->object_id = $c->id;
            //                             $image->number_image = $key;
            //                             $image->type = 'color';
            //                             $image->photo = $name;
            //                             $image->main = 1;
            //                             $image->sort = 0;
            //                             $image->save();
            //                         }
            //                     }
            //                 }
            //             }
            //         }
            //     }
            // }
            // end colors

            // Handle image uploads for the product
            // Use static variable to track the first product that received images for copying to variants
            static $firstProductWithImages = null;
            
            $image = new Images;
            $hasUploadedImages = false;
            
            // Main product image
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($product->image) {
                    $product->image->removeImageSize();
                }
                // $image->uploadPhoto($product->id, 'product');

                $image->uploadPhoto($product->token_key, 'product');

                $hasUploadedImages = true;
            }

            // Gallery images (imageGallery field)
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageGallery')) {
                $image->uploadPhoto($product->id, 'product', 2);
                $hasUploadedImages = true;
            }

            // Gallery images (galleryFiles field - alternative field name used in some views)
            if ($image->imageFiles = UploadedFile::getInstances($this, 'galleryFiles')) {
                $image->uploadPhoto($product->id, 'product', 2);
                $hasUploadedImages = true;
            }

            // Track the first product that received images
            if ($hasUploadedImages && $firstProductWithImages === null) {
                $firstProductWithImages = $product->id;
            }
            // Copy images from first product to subsequent variants
            elseif ($firstProductWithImages !== null && $product->id !== $firstProductWithImages) {
                $this->copyImagesFromProduct($firstProductWithImages, $product->id);
            }

            return $product;
        }

        return false;
    }

    /**
     * Copy images from source product to target product
     * Used when creating product variants to share the same images
     * @param int $sourceProductId The product ID to copy images from
     * @param int $targetProductId The product ID to copy images to
     * @return bool
     */
    protected function copyImagesFromProduct($sourceProductId, $targetProductId)
    {
        $sourceImages = Images::find()
            ->where(['object_id' => $sourceProductId, 'type' => 'product'])
            ->all();
        
        if (empty($sourceImages)) {
            return false;
        }

        $basePath = 'uploads/product/';
        $sizes = ['50' => '50', '100' => '100', '150' => '150', '200' => '200', '250' => '250', '300' => '300'];

        // Create directories for target product
        $targetDir = $basePath . $targetProductId;
        $targetOriginalDir = $targetDir . '/original';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        if (!is_dir($targetOriginalDir)) {
            mkdir($targetOriginalDir, 0755, true);
        }
        
        foreach ($sizes as $k => $v) {
            $sizeDir = $targetDir . '/' . $k . 'x' . $v;
            if (!is_dir($sizeDir)) {
                mkdir($sizeDir, 0755, true);
            }
        }

        foreach ($sourceImages as $sourceImage) {
            // Copy the physical files
            $sourceOriginal = $basePath . $sourceProductId . '/original/' . $sourceImage->photo;
            $targetOriginal = $basePath . $targetProductId . '/original/' . $sourceImage->photo;
            
            if (is_file($sourceOriginal)) {
                copy($sourceOriginal, $targetOriginal);
                
                // Copy sized versions
                foreach ($sizes as $k => $v) {
                    $sourceSize = $basePath . $sourceProductId . '/' . $k . 'x' . $v . '/' . $sourceImage->photo;
                    $targetSize = $basePath . $targetProductId . '/' . $k . 'x' . $v . '/' . $sourceImage->photo;
                    
                    if (is_file($sourceSize)) {
                        copy($sourceSize, $targetSize);
                    }
                }
            }

            // Create database record for the copied image
            Yii::$app->db->createCommand()->insert('image', [
                'type' => 'product',
                'object_id' => $targetProductId,
                'photo' => $sourceImage->photo,
                'main' => $sourceImage->main,
                'sort' => $sourceImage->sort,
                'web' => $sourceImage->web,
                'status' => $sourceImage->status,
                'hash' => $sourceImage->hash
            ])->execute();
        }

        return true;
    }

    public function updateObject($dashboard = false,$status = 2) {
        /** @var User|null $user */
        $user = Yii::$app->user->identity;
        $this->user_id = $this->user_id ? $this->user_id : ($user ? $user->id : null);
        $this->status = $status;

        if ($dashboard === false) {
            $tree = [$this->category_id];

            if ($this->sub_category_id) {
                foreach ($this->sub_category_id as $category) {
                    if ($category) {
                        $tree[] = $category;
                        $this->category_id = $category;
                    }
                }
            }

            $this->category_tree = implode('/', $tree);
        }

        $this->name_trans_ru = $this->transliterate($this->name_ru, true);
        $this->name_trans_en = $this->transliterate($this->name_ru);

        if ($this->save()) {
            $admin = User::findOne(['role'=>User::ROLE_ADMIN]);
            /** @var User $user */
            $user = Yii::$app->user->identity;
            if ($user->role != User::ROLE_ADMIN && !Yii::$app->request->get('id')) {
                $notification = new Notification;
                $notification->saveObject($admin->id, $this->id, 'product_new', 'Добавлен новый товар');
            }

            ProductProperty::deleteAll(['product_id' => $this->id]);

            if ($this->properties_data) {
                $keys = ['product_id', 'key_name', 'value_name'];
                $vals = [];
                if (array_key_exists('key_name', $this->properties_data)) {
                    foreach ($this->properties_data['key_name'] as $key => $val) {
                        $vals[] = [
                            'product_id' => $this->id,
                            'key_name' => $val,
                            'value_name' => $this->properties_data['value_name'][$key],
                        ];
                    }
                }

                Yii::$app->db->createCommand()->batchInsert('product_property', $keys, $vals)->execute();
            }

            ProductFilter::deleteAll(['product_id'=>$this->id]);

            if ($this->filters) {
                $keys = ['product_id', 'filter_id', 'value_ru'];
                $vals = [];
                foreach ($this->filters as $key => $val) {
                    if ($val) {
                        if (is_array($val)) {
                            foreach ($val as $k => $v) {
                                if ($v) {
                                    $vals[] = [
                                        'product_id' => $this->id,
                                        'filter_id' => $key,
                                        'value_ru' => $v,
                                    ];
                                }
                            }
                        } else {
                            $vals[] = [
                                'product_id' => $this->id,
                                'filter_id' => $key,
                                'value_ru' => $val,
                            ];
                        }
                    }
                }

                Yii::$app->db->createCommand()->batchInsert('product_filter', $keys, $vals)->execute();
            }

            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                // $image->uploadPhoto($this->id, 'product');
                $image->uploadPhoto($this->token_key, 'product');
            }

            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageGallery')) {
                $image->uploadPhoto($this->id, 'product', 2);
            }

            // Handle galleryFiles (same as imageGallery but different field name in some views)
            if ($image->imageFiles = UploadedFile::getInstances($this, 'galleryFiles')) {
                $image->uploadPhoto($this->id, 'product', 2);
            }

            return $this;
        }

        return false;
    }


    public function removeObject(){
        if ($this->image && $this->image->delete()){
            $this->image->removeImageSize();
        }

        if ($this->gallery) {
            foreach ($this->gallery as $photo) {
                $photo->removeImageSize();
            }
        }
        
        return $this->delete();
    }

    public function getPhoto($s = 'original') {
        if ($this->image) {
            if ($this->image->web == 1) {
                return $this->image->photo;
            }
            if (!$this->token_key) {
                return Images::PHOTO_DEFAULT;
            }

            $baseUrl = Yii::$app->params['minio']['publicEndpoint'];

            return $baseUrl . '/uploads/product/' . $this->token_key . '/' . $s . '/' . $this->image->photo;
            // $path = Images::PHOTO_PRODUCT_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;

            // $path = Images::PHOTO_PRODUCT_PATH.$this->token_key.'/'.$s.'/'.$this->image->photo;

            // if (is_file($path)) {
            //     return '/'.$path;
            // // $imageManager = new ImageManager();
            // // $image = $imageManager->make($path);
            // // $image->encode('webp');
            // // $image->save(Images::PHOTO_PRODUCT_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->object_id.'.webp');
            // //     return '/'.Images::PHOTO_PRODUCT_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->object_id.'.webp';
            // }
        }

        return Images::PHOTO_DEFAULT;
    }

    public function getPhotos($s = 'original') {
        $data = [];
        $baseUrl = Yii::$app->params['minio']['publicEndpoint'];  

        if ($this->image) {
            if ($this->image->web == 1) {
                $data[] = $this->image->photo;
            } elseif ($this->token_key) {
                $data[] = $baseUrl . '/uploads/product/' . $this->token_key . '/' . $s . '/' . $this->image->photo;
            }
            //  else {
            //     $path = Images::PHOTO_PRODUCT_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
            //     if (is_file($path)) {
            //         $data[] = '/'.$path;
            //     }
            // }
        }

        if ($this->gallery) {
            foreach ($this->gallery as $photo) {
                if ($photo->web == 1) {
                    $data[] = $photo->photo;
                } elseif ($this->token_key) {
                    $data[] = $baseUrl . '/uploads/product/'. $this->token_key. '/'. $s. '/'. $photo->photo;
                }
                // } else {
                //     $path = Images::PHOTO_PRODUCT_PATH.$photo->object_id.'/'.$s.'/'.$photo->photo;
                //     if (is_file($path)) {
                //         $data[] = '/'.$path;
                //     }
                // }
            }
        }

        return $data;
    }

    public function getFilter() {
        $data = [];

        if ($this->productFilters) {
            foreach ($this->productFilters as $key => $filter) {
                if ($filter->filter->type == 'checkbox') {
                    $items = ProductFilter::find()->where(['product_id'=>$filter->product_id, 'filter_id'=>$filter->filter_id])->all();
                    if ($items) {
                        $data[$key] = [
                            'id' => $filter->filter->id,
                            'type' => $filter->filter->type,
                            'name' => $filter->filter->name_ru,
                        ];
                        foreach ($items as $k => $v) {
                            $data[$key]['items'][] = [
                                'value_id' => $v->id,
                                'filter_value_id' => $v->id,
                                'value' => $v->value_ru
                            ];
                        }
                    }
                } else {
                    $data[] = [
                        'id' => $filter->filter->id,
                        'value_id' => $filter->id,
                        'filter_value_id' => $filter->id,
                        'type' => $filter->filter->type,
                        'name' => $filter->filter->name_ru,
                        'value' => $filter->value_ru
                    ];
                }
            }
        }

        return $data;
    }

    public function isFavorite() {
        $favorite = false;
        /** @var User|null $user */
        $user = Yii::$app->user->identity;
        if($user && !empty($user->id) && !empty($this->id)){
            $favorite = UserFavorite::findOne(['product_id'=>$this->id, 'user_id'=>$user->id]);
        }
        return $favorite ? true : false;
    }

    public function getCountRating() {
        $data = [];

        for ($i = 1; $i <= 5; $i++) {
            $data['rate_'.$i] = 0;
        }

        // Get only accepted or processed reviews
        $reviews = $this->getProductReviews()
            ->where(['status' => [ProductReview::STATUS_ACCEPTED, ProductReview::STATUS_PROCESSED]])
            ->all();

        if ($reviews) {
            foreach ($reviews as $review) {
                if ($review->rate >= 1 && $review->rate <= 5) {
                    $data['rate_'.$review->rate] = $data['rate_'.$review->rate] + 1;
                }
            }
        }

        return $data;
    }

    public function getCategoryFull() {
        $data = [];

        if ($this->category_tree) {
            $category_tree = explode('/', $this->category_tree);

            if ($category_tree) {
                foreach ($category_tree as $tree) {
                    $category = Category::findOne($tree);
                    if ($category) {
                        $data[] = $category->name_ru;
                    }
                }
            }
        }

        return $data ? implode('/', $data) : $data;
    }

    public function getCategoryFullArray() {
        $data = [];

        if ($this->category_tree) {
            $category_tree = explode('/', $this->category_tree);

            if ($category_tree) {
                foreach ($category_tree as $tree) {
                    $category = Category::findOne($tree);
                    if ($category) {
                        $data[] = [
                            'id' => $category->id,
                            'name' => $category->name_ru
                        ];
                    }
                }
            }
        }

        return $data;
    }

    public function getOtherProducts() {
        $data = [];
        if ($this->products) {
            foreach ($this->products as $product) {
                // Get color object with full information
                $colorData = null;
                if ($product->color) {
                    $colorData = [
                        'id' => $product->color->id,
                        'name' => $product->color->name_ru,
                        'color' => $product->color->color
                    ];
                }
                
                // Get productTypes information with product_id and color
                $productTypesData = [];
                if ($product->productProductTypes) {
                    foreach ($product->productProductTypes as $productProductType) {
                        if ($productProductType->productType && $productProductType->productTypeValue) {
                            $productTypesData[] = [
                                'product_id' => $product->id,
                                'id' => $productProductType->productTypeValue->id,
                                'name' => $productProductType->productType->name_ru,
                                'display_value' => $productProductType->productTypeValue->value_ru,
                                'color' => $product->color ? $product->color->color : null
                            ];
                        }
                    }
                }

                $data[] = [
                    'id' => $product->id,
                    'name' => $product->name_ru,
                    'color' => $colorData,
                    'photo' => $product->getPhoto(),
                    'productTypes' => $productTypesData
                ];
            }
        }

        return $data;
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';
        
        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $data = [
            'id',
            'name' => function() use($language) { return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'color' => function() {
                if ($this->color) {
                    return [
                        'id' => $this->color->id,
                        'name' => $this->color->name_ru,
                        'color' => $this->color->color
                    ];
                }
                return null;
            },
            'stock' => function () {
                if ($this->stock) {
                    return [
                        'id'   => $this->stock->id,
                        'name' => $this->stock->name_ru ?? $this->stock->name,
                        'address' => $this->stock->address,
                        'shop_id' => $this->stock->shop_id
                    ];
                }
                return null;
            },
            'user' => function() {
                return $this->user ? [
                    'id' => $this->user->id,
                    'role' => $this->user->role,
                    'type' => $this->user->type,
                    'shop' => $this->user->shop_id,
                    'name' => $this->user->name,
                    'phone' => $this->user->phone,
                ] : null;
            },
            'images' => function () {
                return \app\models\Images::find()->where(['token_key' => $this->token_key])
                ->asArray()
                ->all();
            },
            'tag',
            'name_ru',
            'name_en',
            'name_uz',
            'price',
            'price_small',
            'price_opt',
            'qty_small_wholesale',
            'qty_big_wholesale',
            'min_order',
            'delivery',
            'discount',
            'amount',
            'unit' => function() {
                        return $this->unit ? [
                            'id' => $this->unit->id,
                            'name' => $this->unit->name_ru
                        ] : null;
                    },
            'brand',
            'category',
            'category_full' => function(){return $this->getCategoryFull();},
            'category_full_array' => function(){return $this->getCategoryFullArray();},
            'weight',
            'height',
            'width',
            'length',
            'image',
            'gallery',
            'views',
            'rating' => function() {
                // Calculate average rating from accepted or processed reviews
                $reviews = $this->getProductReviews()
                    ->where(['status' => [ProductReview::STATUS_ACCEPTED, ProductReview::STATUS_PROCESSED]])
                    ->all();
                
                if (empty($reviews)) {
                    return 0;
                }
                
                $totalRating = 0;
                $reviewCount = count($reviews);
                
                foreach ($reviews as $review) {
                    $totalRating += $review->rate;
                }
                
                return round($totalRating / $reviewCount, 1);
            },
            'review_count' => function() {
                // Count of accepted or processed reviews
                return $this->getProductReviews()
                    ->where(['status' => [ProductReview::STATUS_ACCEPTED, ProductReview::STATUS_PROCESSED]])
                    ->count();
            },
            'photo',
            'isFavorite' => function(){return $this->isFavorite();},
            'status',
            'productProperties',
            'productColors',
            'productTypes' => function() {
                $productTypesData = [];
                
                // Get current product's productTypes
                if ($this->productProductTypes) {
                    foreach ($this->productProductTypes as $productProductType) {
                        if ($productProductType->productType && $productProductType->productTypeValue) {
                            $productTypesData[] = [
                                'product_id' => $this->id,
                                'id' => $productProductType->productTypeValue->id,
                                'name' => $productProductType->productType->name_ru,
                                'display_value' => $productProductType->productTypeValue->value_ru,
                                'color' => $this->color ? $this->color->color : null
                            ];
                        }
                    }
                }
                
                // Get product variants with same color and same token_key (same product, same color, different types)
                if ($this->token_key && $this->color_id) {
                    $relatedProducts = Product::find()
                        ->with([
                            'color',
                            'productProductTypes',
                            'productProductTypes.productType',
                            'productProductTypes.productTypeValue'
                        ])
                        ->where([
                            'token_key' => $this->token_key,
                            'color_id' => $this->color_id, // Same color as current product
                            'status' => 1 // Only active products
                        ])
                        ->andWhere(['!=', 'id', $this->id]) // Exclude current product
                        ->limit(20) // Increased limit for product variants
                        ->all();
                    
                    foreach ($relatedProducts as $relatedProduct) {
                        if ($relatedProduct->productProductTypes) {
                            foreach ($relatedProduct->productProductTypes as $productProductType) {
                                if ($productProductType->productType && $productProductType->productTypeValue) {
                                    $productTypesData[] = [
                                        'product_id' => $relatedProduct->id,
                                        'id' => $productProductType->productTypeValue->id,
                                        'name' => $productProductType->productType->name_ru,
                                        'display_value' => $productProductType->productTypeValue->value_ru,
                                        'color' => $relatedProduct->color ? $relatedProduct->color->color : null
                                    ];
                                }
                            }
                        }
                    }
                }
                
                return $productTypesData;
            },
            'availableProductTypes' => function() {
                return $this->category ? $this->category->productTypes : [];
            },
            'gallery' => function() {return $this->getPhotos();},
            'pricing_tiers' => function() {return $this->getPricingTiers();}
        ];

        $exception = ['product', 'detail', 'set-rate', 'set-review', 'compares'];

        if (($controller == 'product') && in_array($action, $exception)) {
            $detail = [
                'description' => function() use($language) { return $this->{'description_'.$language} ? strip_tags(html_entity_decode(htmlspecialchars_decode($this->{'description_'.$language}))) : $this->description_ru;},
                'description_ru' => function() {return strip_tags(html_entity_decode(htmlspecialchars_decode($this->description_ru)));},
                'description_en' => function() {return strip_tags(html_entity_decode(htmlspecialchars_decode($this->description_en)));},
                'description_uz' => function() {return strip_tags(html_entity_decode(htmlspecialchars_decode($this->description_uz)));},
                'filters' => function() {return $this->getFilter();},
                'reviews' => function() {return $this->productReviews;},
                'reviews_count' => function() {return count($this->productReviews);},
                'review_separate' => function() {return $this->getCountRating();},
                'products' => function() {return $this->getOtherProducts();},
                'shop'
            ];

            $data = array_merge($data, $detail);
        }

        return $data;
    }

    /**
     * Get price based on quantity (wholesale pricing)
     * @param int $quantity
     * @return float
     */
    public function getPriceByQuantity($quantity)
    {
        // Default to regular price
        $price = $this->price ? $this->price : $this->price;
        // Check for small wholesale pricing
        if ($this->qty_small_wholesale && $quantity >= $this->qty_small_wholesale && $this->price_small && $quantity < $this->qty_big_wholesale) {
            $price = $this->price_small;
        } else if ($this->qty_big_wholesale && $quantity >= $this->qty_big_wholesale && $this->price_opt) {
            $price = $this->price_opt;
        }

        return $price;
    }

    /**
     * Get pricing tiers for frontend
     * @return array
     */
    public function getPricingTiers()
    {
        $tiers = [
            [
                'min_quantity' => 1,
                'max_quantity' => $this->qty_small_wholesale ? $this->qty_small_wholesale - 1 : null,
                'price' => $this->price,
                'type' => 'regular'
            ]
        ];
        
        if ($this->qty_small_wholesale && $this->price_small) {
            $tiers[] = [
                'min_quantity' => $this->qty_small_wholesale,
                'max_quantity' => $this->qty_big_wholesale ? $this->qty_big_wholesale - 1 : null,
                'price' => $this->price_small,
                'type' => 'small_wholesale'
            ];
        }
        
        if ($this->qty_big_wholesale && $this->price_opt) {
            $tiers[] = [
                'min_quantity' => $this->qty_big_wholesale,
                'max_quantity' => null,
                'price' => $this->price_opt,
                'type' => 'big_wholesale'
            ];
        }
        
        return $tiers;
    }

    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }

    public function getTag()
    {
        return $this->hasOne(Category::className(), ['id' => 'tag_id']);
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }

    /**
     * Gets query for [[ProductFavorites]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductFavorites()
    {
        return $this->hasMany(\app\models\user\favorite\UserFavorite::className(), ['product_id' => 'id']);
    }

    /**
     * Gets query for [[ProductFilters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductFilters()
    {
        return $this->hasMany(ProductFilter::className(), ['product_id' => 'id'])
            ->select([
                'id' => 'MAX(id)', 
                'product_id', 
                'filter_id', 
                'value_ru' => 'MAX(value_ru)', 
                'value_en' => 'MAX(value_en)', 
                'value_uz' => 'MAX(value_uz)'
            ])
            ->groupBy(['product_id', 'filter_id']);
    }

    /**
     * Gets query for [[ProductViews]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductViews()
    {
        return $this->hasMany(ProductView::className(), ['product_id' => 'id']);
    }

    // images
    // public function getImage() {
    //     return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'product', 'main'=>1]);
    // }

    public function getImage() {
        return $this->hasOne(Images::className(), ['token_key'=>'token_key'])
            ->andOnCondition(['type'=>'product', 'main'=>1]);
    }


    // public function getGallery() {
    //     return $this->hasMany(Images::className(), ['object_id' => 'id'])->andOnCondition(['type'=>'product', 'main'=>2]);
    // }

    public function getGallery() {
        return $this->hasMany(Images::className(), ['token_key'=>'token_key'])
            ->andOnCondition(['type'=>'product', 'main'=>2]);
    }

    // brand
    public function getBrand()
    {
        return $this->hasOne(CategoryBrand::className(), ['id' => 'brand_id']);
    }

    // reviews
    public function getProductReviews()
    {
        return $this->hasMany(ProductReview::className(), ['product_id' => 'id']);
    }

    // properties
    public function getProductProperties() {
        return $this->hasMany(ProductProperty::className(), ['product_id' => 'id']);
    }

    // product colors
    public function getProductColors() {
        return $this->hasMany(ProductColor::className(), ['product_id' => 'id']);
    }

    public function getColor() {
        return $this->hasOne(Color::className(), ['id' => 'color_id']);
    }

    public function getProducts() {
        return $this->hasMany(Product::className(), ['token_key' => 'token_key'])->andOnCondition(['!=', 'id', $this->id]);
    }

    // delivery
    public function getDelivery()
    {
        return $this->hasOne(Delivery::className(), ['id' => 'delivery_id']);
    }

    // product office
    public function getProductOffices()
    {
        return $this->hasMany(ProductOffice::className(), ['product_id' => 'id']);
    }

    // user
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    // stock
    public function getStock()
    {
        return $this->hasOne(Stock::className(), ['id' => 'stock_id']);
    }

    // IKPU
    public function getIkpu()
    {
        return $this->hasOne(Ikpu::class, ['code' => 'ikpu_code']);
    }

    // product types
    public function getProductProductTypes() {
        return $this->hasMany(ProductProductType::className(), ['product_id' => 'id']);
    }

    public function getProductTypes() {
        return $this->hasMany(\app\models\product\ProductType::className(), ['id' => 'product_type_id'])
            ->via('productProductTypes');
    }

    public function getUnit()
    {
        return $this->hasOne(Category::className(), ['id' => 'unit_id'])
            ->andOnCondition(['type' => 'unit']);
    }

    public function getCurrency()
    {
        return $this->hasOne(Category::className(), ['id' => 'unit_id'])
            ->andOnCondition(['type' => 'currency']);
    }

    public function get()
    {
        return $this->hasOne(Category::className(), ['id' => 'unit_id'])
            ->andOnCondition(['type' => 'currency']);
    }


    /**
     * Get IKPU name with fallback to cached value
     *
     * @param string $language Language code (ru, uz, en)
     * @return string|null
     */
    public function getIkpuName($language = 'ru')
    {
        if ($this->ikpu) {
            return $this->ikpu->getName($language);
        }
        
        // Fallback to cached value if IKPU relation is not loaded
        return $this->ikpu_name;
    }

    /**
     * Get IKPU display text for forms and lists
     *
     * @param string $language Language code
     * @return string|null
     */
    public function getIkpuDisplayText($language = 'ru')
    {
        if ($this->ikpu_code) {
            $name = $this->getIkpuName($language);
            return $this->ikpu_code . ($name ? ' - ' . $name : '');
        }
        
        return null;
    }

    /**
     * Update cached IKPU name when IKPU code changes
     */
    public function updateIkpuCache()
    {
        if ($this->ikpu_code && $this->ikpu) {
            $this->ikpu_name = $this->ikpu->name_ru;
        } else {
            $this->ikpu_name = null;
        }
    }

    public function getModerationComments()
    {
        return $this->hasMany(\app\models\moderator\ModerationComment::class,
            ['entity_id' => 'id']
        )->andWhere(['entity_type' => 'product']);
    }

    /**
     * Before save event - update IKPU cache
     */
    public function beforeSave($insert)
    {
        if ($insert && empty($this->token_key)) {
            $this->token_key = Yii::$app->security->generateRandomString(32);
        }

        if (parent::beforeSave($insert)) {
            // Update IKPU cache if code changed
            if ($this->isAttributeChanged('ikpu_code')) {
                $this->updateIkpuCache();
            }

            // Reset sync status if relevant fields changed and sync_status wasn't explicitly changed
            if (!$insert && !$this->isAttributeChanged('sync_status')) {
                 // List of fields that should trigger a re-sync
                 $syncFields = ['name_ru', 'name_en', 'name_uz', 'price', 'sku', 'barcode', 'amount'];
                 foreach ($syncFields as $field) {
                     if ($this->isAttributeChanged($field)) {
                         $this->sync_status = 0; // Pending
                         break;
                     }
                 }
            }

            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        // DEPRECATED: Sync is now handled by Sklad pulling data via API to prevent UI freezing
        /*
        if ($insert) {
            $this->syncToWarehouse();
        } elseif (isset($changedAttributes['name_ru']) || isset($changedAttributes['price'])) {
            $this->syncToWarehouse();
        }
        */
    }

    private function syncToWarehouse()
    {
        $baseUrl = Yii::$app->params['warehouseApiUrl'] ?? 'http://warehouse.example.com';
        $apiUrl = $baseUrl . '/api/sync/product';

        $client = new Client(['timeout' => 5.0]);

        $dataToSend = [
            'id' => $this->id,
            'yii_product_id' => $this->id,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'name_uz' => $this->name_uz,
            'price' => $this->price,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
        ];

        $secretKey = Yii::$app->params['apiSecretKey'] ?? null;
        if (!$secretKey) {
            return;
        }
        
        $token = md5($this->id . $secretKey);

        try {
            $client->post($apiUrl, [
                'json' => $dataToSend,
                'headers' => [
                    'X-Api-Token' => $token,
                ],
            ]);
        } catch (RequestException $e) {
            Yii::error('Failed to sync product to warehouse: ' . $e->getMessage(), 'warehouse_sync');
        }
    }

    public function beforeDelete()
    {
        if(!parent::beforeDelete()) {
            return false;
        }

         Yii::$app->db->createCommand()->insert('product_sync_log', [
            'submission_id' => $this->id,
            'action' => 'delete',
            'created_at' => date('Y-m-d H:i:s'),
        ])->execute();

        return true;
    }
}
