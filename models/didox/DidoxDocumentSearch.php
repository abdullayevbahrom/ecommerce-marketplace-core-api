<?php

namespace app\models\didox;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\didox\DidoxDocument;

/**
 * DidoxDocumentSearch represents the model behind the search form of `app\models\didox\DidoxDocument`.
 */
class DidoxDocumentSearch extends DidoxDocument
{
    // Add virtual fields for searching by invoice fields
    public $invoice_number;
    public $buyer_tin;
    public $seller_tin;
    public $contract_number;
    public $total_sum;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'didox_status', 'created_by', 'to_user_id', 'status'], 'integer'],
            [['name', 'document_type', 'didox_id', 'didox_data', 'didox_error_data', 'didox_last_attempt', 'didox_created_at', 'didox_signed_at', 'created_at', 'updated_at'], 'safe'],
            // Virtual invoice fields for search
            [['invoice_number', 'buyer_tin', 'seller_tin', 'contract_number'], 'safe'],
            [['total_sum'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = DidoxDocument::find();

        // Join with invoice table for searching by invoice fields
        $query->joinWith(['invoice'], false);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions for core document fields
        $query->andFilterWhere([
            'didox_document.id' => $this->id,
            'didox_document.didox_status' => $this->didox_status,
            'didox_document.created_by' => $this->created_by,
            'didox_document.to_user_id' => $this->to_user_id,
            'didox_document.status' => $this->status,
            'didox_document.document_type' => $this->document_type,
            'didox_document.didox_created_at' => $this->didox_created_at,
            'didox_document.didox_signed_at' => $this->didox_signed_at,
            'didox_document.created_at' => $this->created_at,
            'didox_document.updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'didox_document.name', $this->name])
            ->andFilterWhere(['like', 'didox_document.didox_id', $this->didox_id]);

        // Filter by invoice fields if they are provided
        if (!empty($this->invoice_number)) {
            $query->andFilterWhere(['like', 'didox_document_invoice.invoice_number', $this->invoice_number]);
        }
        if (!empty($this->buyer_tin)) {
            $query->andFilterWhere(['like', 'didox_document_invoice.buyer_tin', $this->buyer_tin]);
        }
        if (!empty($this->seller_tin)) {
            $query->andFilterWhere(['like', 'didox_document_invoice.seller_tin', $this->seller_tin]);
        }
        if (!empty($this->contract_number)) {
            $query->andFilterWhere(['like', 'didox_document_invoice.contract_number', $this->contract_number]);
        }
        if (!empty($this->total_sum)) {
            $query->andFilterWhere(['didox_document_invoice.total_sum' => $this->total_sum]);
        }

        return $dataProvider;
    }
} 