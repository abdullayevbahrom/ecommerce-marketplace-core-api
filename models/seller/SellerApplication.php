<?php

namespace app\models\seller;

use Yii;

/**
 * This is the model class for table "seller_application".
 *
 * @property int $id
 * @property string $name Applicant name
 * @property string $phone Applicant phone number
 * @property int $status Application status: 1=pending, 2=approved, 3=rejected
 * @property string|null $admin_notes Admin notes about the application
 * @property string $date Application submission date
 */
class SellerApplication extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seller_application';
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $digits = preg_replace('/\D/', '', (string) $this->phone);
        if ($digits !== '') {
            $this->phone = $digits;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'phone'], 'required', 'message' => 'Заполните поле'],
            [['name'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 255],
            ['phone', 'validatePhone'],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED]],
            [['admin_notes'], 'string'],
            [['date'], 'safe'],
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
            
            if (!preg_match('/^998\d{9}$/', $cleanPhone)) {
                $this->addError($attribute, 'Формат телефона должен быть 998XXXXXXXXX');
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
            'name' => 'Имя',
            'phone' => 'Номер телефона',
            'status' => 'Статус',
            'admin_notes' => 'Заметки администратора',
            'date' => 'Дата подачи заявки',
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
     * Save application from API
     */
    public function saveApplication()
    {
        $this->status = self::STATUS_PENDING;
        
        // Set date if not already set (for new applications)
        if (!$this->date) {
            $this->date = date('Y-m-d H:i:s');
        }
        
        if ($this->validate()) {
            return $this->save(false);
        }
        
        return false;
    }

    /**
     * Fields for API response
     */
    public function fields()
    {
        return [
            'id',
            'name', 
            'phone',
            'status',
            'statusLabel' => function() {
                return $this->getStatusLabel();
            },
            'date',
        ];
    }
} 
