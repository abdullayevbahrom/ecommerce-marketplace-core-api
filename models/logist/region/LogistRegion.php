<?php

namespace app\models\logist\region;

use Yii;
use app\models\user\User;
use app\models\logist\Logist;
use app\models\Category;

/**
 * This is the model class for table "logist_region".
 *
 * @property int $id
 * @property int|null $logist_id
 * @property int|null $region_id
 * @property string $date
 *
 * @property Logist $logist
 * @property LogistRegionPrice[] $logistRegionPrices
 * @property Category $region
 */
class LogistRegion extends \yii\db\ActiveRecord
{
    public $prices = [];
    public $sub_region_id = [];
    public $sub_region_a_id = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logist_region';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['logist_id', 'region_id', 'region_a_id'], 'integer'],
            [['date', 'prices', 'sub_region_id', 'sub_region_a_id'], 'safe'],
            [['region_tree', 'region_a_tree'], 'string'],
            [['logist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Logist::className(), 'targetAttribute' => ['logist_id' => 'id']],
            [['region_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['region_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'logist_id' => 'Logist ID',
            'region_id' => 'Region ID',
            'region_a_id' => 'Region A ID',
            'date' => 'Date',
        ];
    }

    public function saveObject() {
        // region a tree
        $tree_a = [$this->region_a_id];

        if ($this->sub_region_a_id) {
            foreach ($this->sub_region_a_id as $region_a) {
                if ($region_a) {
                    $tree_a[] = $region_a;
                    $this->region_a_id = $region_a;
                }
            }
        }

        $this->region_a_tree = implode('/', $tree_a);

        // region tree
        $tree = [$this->region_id];

        if ($this->sub_region_id) {
            foreach ($this->sub_region_id as $region) {
                if ($region) {
                    $tree[] = $region;
                    $this->region_id = $region;
                }
            }
        }

        $this->region_tree = implode('/', $tree);

        $user = Yii::$app->user->identity;
        if ($user->role == User::ROLE_LOGIST) {
            $logist = Logist::findOne(['user_id'=>$user->id]);
            $this->logist_id = $logist->id;
        } else {
            $this->logist_id = Yii::$app->request->get('id');
        }

        if ($this->save()) {
            LogistRegionPrice::deleteAll(['logist_region_id'=>$this->id]);
            if ($this->prices) {
                $keys = ['logist_region_id', 'unit_id', 'unit_amount', 'price'];
                $vals = [];
                foreach ($this->prices['unit_id'] as $key => $val) {
                    $vals[] = [
                        'logist_region_id' => $this->id,
                        'unit_id' => $val,
                        'unit_amount' => $this->prices['unit_amount'][$key],
                        'price' => $this->prices['price'][$key],
                    ];
                }

                Yii::$app->db->createCommand()->batchInsert('logist_region_price', $keys, $vals)->execute();
            }

            return true;
        }
        return false;
    }

    public function getTariffs() {
        $data = [];

        if ($this->logistRegionPrices) {
            foreach ($this->logistRegionPrices as $key => $item) {
                $data[] = [
                    'id' => $item->id,
                    'unit_id' => $item->unit->id,
                    'unit' => $item->unit->name_ru,
                    'unit_amount' => $item->unit_amount,
                    'price' => $item->price,
                ];
            }
        }

        return $data;
    }

    public function fields() {
        return [
            'id',
            'regionA',
            'region' => function() {return $this->region->name_ru;},
            'tariffs' => function() {return $this->getTariffs();}
        ];
    }

    /**
     * Gets query for [[Logist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogist()
    {
        return $this->hasOne(Logist::className(), ['id' => 'logist_id']);
    }

    /**
     * Gets query for [[LogistRegionPrices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogistRegionPrices()
    {
        return $this->hasMany(LogistRegionPrice::className(), ['logist_region_id' => 'id']);
    }

    /**
     * Gets query for [[Region]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Category::className(), ['id' => 'region_id']);
    }

    public function getRegionA()
    {
        return $this->hasOne(Category::className(), ['id' => 'region_a_id']);
    }
}
