<?php

namespace app\models\product\review;

use Yii;
use app\models\product\Product;
use app\models\user\User;
use app\models\order\product\OrderProduct;

/**
 * This is the model class for table "product_review".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $product_id
 * @property string|null $review
 * @property string $date
 *
 * @property Product $product
 * @property User $user
 */
class ProductReview extends \yii\db\ActiveRecord
{
    // Review status constants
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_PROCESSED = 3;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_review';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'rate', 'review'], 'required', 'message'=>'Заполните поле'],
            [['user_id', 'product_id', 'status', 'status_user_id'], 'integer'],
            [['rate'], 'number', 'max' => 5, 'min' => 1],
            [['review', 'status_comment'], 'string'],
            [['date', 'status_date'], 'safe'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_PROCESSED]],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['product_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['status_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['status_user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'product_id' => 'Product ID',
            'review' => 'Review',
            'rate' => 'Rating',
            'status' => 'Status',
            'status_date' => 'Status Date',
            'status_user_id' => 'Status Changed By',
            'status_comment' => 'Status Comment',
            'date' => 'Date',
        ];
    }

    public function saveObject($user_id, $product) {
        $this->user_id = $user_id;
        $this->review = trim(stripslashes($this->review));
        
        if ($this->save()) {
            $count = 0;
            $rates = self::find()->where(['product_id'=>$product->id])->all();
            if ($rates) {
                foreach ($rates as $value) {
                    $count += $value->rate;
                }
            }

            $product->rating = preg_replace('/(\..{1}).*/', '$1', $count/count($rates));
            $product->save(false);
            return true;
        }

        return false;
    }

    public function getBought() {
        $flag = false;

        if ($this->product) {
            if ($this->orderProduct) {
                $flag = true;
            }
        }

        return $flag;
    }

    public function fields() {
        return ['id', 'bought'=>function(){return $this->getBought();}, 'review', 'rate', 'user', 'date'];
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getOrderProduct() {
        return $this->hasOne(OrderProduct::className(), ['product_id' => 'product_id'])->andOnCondition(['user_id' => Yii::$app->user->identity->id]);
    }

    /**
     * Gets query for status user (admin who changed status)
     * @return \yii\db\ActiveQuery
     */
    public function getStatusUser()
    {
        return $this->hasOne(User::className(), ['id' => 'status_user_id']);
    }

    /**
     * Get status label
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = self::getStatusLabels();
        return isset($labels[$this->status]) ? $labels[$this->status] : 'Unknown';
    }

    /**
     * Get status color for display
     * @return string
     */
    public function getStatusColor()
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'success', 
            self::STATUS_REJECTED => 'danger',
            self::STATUS_PROCESSED => 'info'
        ];
        return isset($colors[$this->status]) ? $colors[$this->status] : 'default';
    }

    /**
     * Get all status labels
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => 'В ожидании',
            self::STATUS_ACCEPTED => 'Принят',
            self::STATUS_REJECTED => 'Отклонен', 
            self::STATUS_PROCESSED => 'Обработан'
        ];
    }

    /**
     * Change review status
     * @param int $status
     * @param int $userId Admin user ID
     * @param string $comment Optional comment
     * @return bool
     */
    public function changeStatus($status, $userId, $comment = null)
    {
        $this->status = $status;
        $this->status_date = date('Y-m-d H:i:s');
        $this->status_user_id = $userId;
        $this->status_comment = $comment;

        return $this->save(false);
    }
}
