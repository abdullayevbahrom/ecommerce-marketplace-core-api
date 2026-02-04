<?php

namespace app\models\didox;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "didox_document_included_products".
 *
 * @property int $id
 * @property int $document_id FK to didox_document
 * @property int|null $ord_no Порядковый номер (OrdNo)
 * @property string|null $name Наименование товара (Name)
 * @property string|null $catalog_code Код ИКПУ (CatalogCode)
 * @property string|null $catalog_name Название ИКПУ (CatalogName)
 * @property string|null $marks Маркировки (Marks)
 * @property string|null $barcode Штрих-код (Barcode)
 * @property string|null $package_code Код упаковки (PackageCode)
 * @property string|null $package_name Название упаковки (PackageName)
 * @property float|null $count Количество (Count)
 * @property float|null $summa Цена за единицу (Summa)
 * @property float|null $delivery_sum Стоимость поставки (DeliverySum)
 * @property float|null $delivery_sum_with_vat Стоимость с НДС (DeliverySumWithVat)
 * @property float|null $vat_rate Ставка НДС (VatRate)
 * @property float|null $vat_sum Сумма НДС (VatSum)
 * @property bool|null $without_vat Без НДС (WithoutVat)
 * @property float|null $excise_rate Ставка акциза (ExciseRate)
 * @property float|null $excise_sum Сумма акциза (ExciseSum)
 * @property bool|null $without_excise Без акциза (WithoutExcise)
 * @property string|null $lgota_id Код льготы (LgotaId)
 * @property int|null $lgota_type Тип льготы: 1-НДС, 2-налог с оборота (LgotaType)
 * @property string|null $lgota_name Название льготы (LgotaName)
 * @property float|null $lgota_vat_sum Льготная сумма НДС (LgotaVatSum)
 * @property string|null $committent_name Наименование комитента (CommittentName)
 * @property string|null $committent_tin ИНН комитента (CommittentTin)
 * @property string|null $committent_vat_reg_code Рег.код НДС комитента (CommittentVatRegCode)
 * @property string|null $committent_vat_reg_status Статус рег.кода НДС комитента (CommittentVatRegStatus)
 * @property string|null $warehouse_id ID склада (WarehouseId)
 * @property int|null $origin Происхождение товара (Origin)
 * @property string|null $measure_id НЕ ИСПОЛЬЗУЕТСЯ (MeasureId)
 * @property string $created_at
 * @property string $updated_at
 *
 * @property DidoxDocument $document
 */
class DidoxDocumentIncludedProducts extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'didox_document_included_products';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['document_id'], 'required'],
            [['document_id', 'ord_no', 'lgota_type', 'origin'], 'integer'],
            [['count', 'summa', 'delivery_sum', 'delivery_sum_with_vat', 'vat_rate', 'vat_sum', 'excise_rate', 'excise_sum', 'lgota_vat_sum'], 'number'],
            [['without_vat', 'without_excise'], 'boolean'],
            [['marks'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'catalog_name', 'lgota_name', 'committent_name'], 'string', 'max' => 255],
            [['catalog_code'], 'string', 'max' => 17],
            [['barcode'], 'string', 'max' => 100],
            [['package_code', 'lgota_id', 'committent_tin', 'measure_id'], 'string', 'max' => 20],
            [['package_name'], 'string', 'max' => 50],
            [['warehouse_id', 'committent_vat_reg_code'], 'string', 'max' => 50],
            [['committent_vat_reg_status'], 'string', 'max' => 20],
            [['document_id'], 'exist', 'skipOnError' => true, 'targetClass' => DidoxDocument::class, 'targetAttribute' => ['document_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_id' => 'ID документа',
            'ord_no' => 'Порядковый номер',
            'name' => 'Наименование товара',
            'catalog_code' => 'Код ИКПУ',
            'catalog_name' => 'Название ИКПУ',
            'marks' => 'Маркировки',
            'barcode' => 'Штрих-код',
            'package_code' => 'Код упаковки',
            'package_name' => 'Единица измерения',
            'count' => 'Количество',
            'summa' => 'Цена за единицу',
            'delivery_sum' => 'Стоимость поставки',
            'delivery_sum_with_vat' => 'Стоимость с НДС',
            'vat_rate' => 'Ставка НДС (%)',
            'vat_sum' => 'Сумма НДС',
            'without_vat' => 'Без НДС',
            'excise_rate' => 'Ставка акциза (%)',
            'excise_sum' => 'Сумма акциза',
            'without_excise' => 'Без акциза',
            'lgota_id' => 'Код льготы',
            'lgota_type' => 'Тип льготы',
            'lgota_name' => 'Название льготы',
            'lgota_vat_sum' => 'Льготная сумма НДС',
            'committent_name' => 'Наименование комитента',
            'committent_tin' => 'ИНН комитента',
            'committent_vat_reg_code' => 'Рег. код НДС комитента',
            'committent_vat_reg_status' => 'Статус рег. кода НДС комитента',
            'warehouse_id' => 'ID склада',
            'origin' => 'Происхождение товара',
            'measure_id' => 'ID единицы измерения',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Gets query for the associated document.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocument()
    {
        return $this->hasOne(DidoxDocument::class, ['id' => 'document_id']);
    }

    /**
     * Get available origin types
     *
     * @return array
     */
    public static function getOriginTypes()
    {
        return [
            1 => 'Импорт',
            2 => 'Экспорт',
            3 => 'Транзит',
            4 => 'Местное производство',
            5 => 'Другое',
        ];
    }

    /**
     * Get origin type label
     *
     * @return string
     */
    public function getOriginLabel()
    {
        $types = self::getOriginTypes();
        return $types[$this->origin] ?? 'Неизвестно';
    }

    /**
     * Get available lgota types
     *
     * @return array
     */
    public static function getLgotaTypes()
    {
        return [
            1 => 'Льгота по НДС',
            2 => 'Льгота по налогу с оборота',
        ];
    }

    /**
     * Get lgota type label
     *
     * @return string
     */
    public function getLgotaTypeLabel()
    {
        if (!$this->lgota_type) {
            return 'Нет льготы';
        }
        $types = self::getLgotaTypes();
        return $types[$this->lgota_type] ?? 'Неизвестно';
    }

    /**
     * Calculate totals based on count, summa, and vat_rate
     */
    public function calculateTotals()
    {
        if (!$this->count || !$this->summa) {
            return;
        }

        // Calculate delivery sum (count * summa)
        $this->delivery_sum = $this->count * $this->summa;

        // Calculate VAT sum
        if ($this->without_vat || !$this->vat_rate) {
            $this->vat_sum = 0;
        } else {
            $this->vat_sum = $this->delivery_sum * ($this->vat_rate / 100);
        }

        // Calculate delivery sum with VAT
        $this->delivery_sum_with_vat = $this->delivery_sum + $this->vat_sum;

        // Calculate excise sum if needed
        if ($this->without_excise || !$this->excise_rate) {
            $this->excise_sum = 0;
        } else {
            $this->excise_sum = $this->delivery_sum * ($this->excise_rate / 100);
        }
    }

    /**
     * Before save event
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->calculateTotals();
            return true;
        }
        return false;
    }
} 