<?php

namespace app\models\shop\seller;

use Yii;
use app\models\shop\Shop;

/**
 * This is the model class for table "shop_seller".
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $inn
 * @property string|null $account
 * @property string|null $bank
 * @property string|null $address_legal
 * @property string|null $oked
 * @property string|null $okohx
 * @property string|null $mfo
 * @property string|null $vat_reg_code
 * @property int $status
 * @property string $date
 *
 * @property Shop $shop
 */
class ShopSeller extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shop_seller';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['shop_id', 'status'], 'integer'],
            [['date'], 'safe'],
            [['inn', 'account', 'bank', 'address_legal', 'oked', 'okohx', 'mfo', 'organization', 'vat_reg_code'], 'string', 'max' => 255],
            [['shop_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shop::className(), 'targetAttribute' => ['shop_id' => 'id']],
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
            'inn' => 'Inn',
            'account' => 'Account',
            'bank' => 'Bank',
            'address_legal' => 'Address Legal',
            'oked' => 'Oked',
            'okohx' => 'Okohx',
            'mfo' => 'Mfo',
            'vat_reg_code' => 'VAT Reg Code',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function fields() {
        return ['id', 'name'=>function(){return $this->shop->name_ru;}, 'inn', 'account', 'bank', 'address_legal', 'oked', 'okohx', 'mfo', 'vat_reg_code', 'organization'];
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
}
