<?php

namespace app\models\product;

use Yii;
use yii\web\UploadedFile;
use app\models\Images;

/**
 * This is the model class for table "product_request".
 *
 * @property int $id
 * @property string $product_name Product name
 * @property string|null $product_photo Product photo filename
 * @property int $quantity Requested quantity
 * @property string|null $product_link Link to product
 * @property string $phone Applicant phone number
 * @property string|null $email Applicant email
 * @property int $status Request status: 1=pending, 2=approved, 3=rejected
 * @property string|null $admin_notes Admin notes about the request
 * @property string $date Request submission date
 * @property int|null $user_id User who sent the request
 * @property int|null $admin_id Admin who responded to the request
 *
 * @property \app\models\user\User $user
 * @property \app\models\user\User $admin
 */
class ProductRequest extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    const PHOTO_PATH = 'uploads/product_request/';
    const PHOTO_DEFAULT = '/assets_files/images/no-photo.png'; // Default image when no photo attached

    public $product_photo_file;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_name', 'quantity', 'phone'], 'required', 'message' => 'Заполните поле'],
            [['product_name'], 'string', 'max' => 255],
            [['quantity'], 'integer', 'min' => 1],
            [['product_link', 'admin_notes'], 'string'],
            [['phone'], 'string', 'max' => 255],
            ['phone', 'validatePhone'],
            [['email'], 'email', 'message' => 'Неверный формат email'],
            [['email'], 'string', 'max' => 255],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED]],
            [['date'], 'safe'],
            [['user_id', 'admin_id'], 'integer'],
            [['user_id', 'admin_id'], 'safe'],
            [['product_photo_file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg', 'maxSize' => 5120000, 'maxFiles' => 1, 'wrongExtension' => 'Недопустимый формат файла. Разрешены: {extensions}', 'tooBig' => 'Размер файла слишком большой'],
            [['product_photo_file'], 'safe'],
        ];
    }

    /**
     * Custom phone validation
     */
    public function validatePhone($attribute, $params)
    {
        if (!$this->hasErrors()) {
            // Clean phone number (remove all non-digits)
            $cleanPhone = preg_replace('/\D/', '', $this->phone);
            
            // Check if exactly 12 digits
            if (strlen($cleanPhone) != 12) {
                $this->addError($attribute, 'Номер телефона должен содержать ровно 12 цифр');
                return;
            }
            
            // Update phone with cleaned version
            $this->phone = $cleanPhone;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_name' => 'Наименование товара',
            'product_photo' => 'Фотография товара',
            'product_photo_file' => 'Фотография товара (файл)',
            'quantity' => 'Количество',
            'product_link' => 'Ссылка на товар',
            'phone' => 'Номер телефона',
            'email' => 'Электронная почта',
            'status' => 'Статус',
            'admin_notes' => 'Заметки администратора',
            'date' => 'Дата подачи заявки',
            'user_id' => 'Пользователь',
            'admin_id' => 'Администратор',
        ];
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        $statuses = [
            self::STATUS_PENDING => 'На рассмотрении',
            self::STATUS_APPROVED => 'Одобрено',
            self::STATUS_REJECTED => 'Отклонено',
        ];

        return isset($statuses[$this->status]) ? $statuses[$this->status] : 'Неизвестно';
    }

    /**
     * Get all status options
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'На рассмотрении',
            self::STATUS_APPROVED => 'Одобрено',
            self::STATUS_REJECTED => 'Отклонено',
        ];
    }

    /**
     * Save request from API
     */
    public function saveRequest()
    {
        $this->status = self::STATUS_PENDING;
        
        if ($this->validate()) {
            if ($this->save(false)) {
                // Handle photo upload
                if ($this->product_photo_file) {
                    $this->uploadPhoto();
                }
                return true;
            }
        }
        
        return false;
    }

    /**
     * Upload product photo
     */
    public function uploadPhoto()
    {
        if ($this->product_photo_file) {
            $uploadPath = self::PHOTO_PATH . $this->id . '/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $file = $this->product_photo_file;
            $filename = time() . '_' . $file->baseName . '.' . $file->extension;
            $filePath = $uploadPath . $filename;
            
            if ($file->saveAs($filePath)) {
                // Remove old photo if exists
                if ($this->product_photo && file_exists(self::PHOTO_PATH . $this->id . '/' . $this->product_photo)) {
                    unlink(self::PHOTO_PATH . $this->id . '/' . $this->product_photo);
                }
                
                $this->product_photo = $filename;
                $this->save(false);
                return true;
            }
        }
        return false;
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrl()
    {
        if ($this->product_photo && file_exists(self::PHOTO_PATH . $this->id . '/' . $this->product_photo)) {
            return '/' . self::PHOTO_PATH . $this->id . '/' . $this->product_photo;
        }
        
        return self::PHOTO_DEFAULT;
    }

    /**
     * Check if photo exists
     */
    public function existPhoto()
    {
        return $this->product_photo && file_exists(self::PHOTO_PATH . $this->id . '/' . $this->product_photo);
    }

    /**
     * Remove photo
     */
    public function removePhoto()
    {
        if ($this->product_photo) {
            $filePath = self::PHOTO_PATH . $this->id . '/' . $this->product_photo;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $this->product_photo = null;
            $this->save(false);
        }
    }

    /**
     * Remove entire request with photo
     */
    public function removeRequest()
    {
        // Remove photo first
        $this->removePhoto();
        
        // Remove directory if empty
        $uploadPath = self::PHOTO_PATH . $this->id . '/';
        if (is_dir($uploadPath) && count(scandir($uploadPath)) == 2) { // only . and ..
            rmdir($uploadPath);
        }
        
        return $this->delete();
    }

    /**
     * Fields for API response
     */
    public function fields()
    {
        return [
            'id',
            'product_name',
            'quantity',
            'product_link',
            'phone',
            'email',
            'status',
            'statusLabel' => function() {
                return $this->getStatusLabel();
            },
            'photo_url' => function() {
                return $this->getPhotoUrl();
            },
            'date',
        ];
    }

    /**
     * Get the user who sent this request
     */
    public function getUser()
    {
        return $this->hasOne(\app\models\user\User::class, ['id' => 'user_id']);
    }

    /**
     * Get the admin who responded to this request
     */
    public function getAdmin()
    {
        return $this->hasOne(\app\models\user\User::class, ['id' => 'admin_id']);
    }
} 