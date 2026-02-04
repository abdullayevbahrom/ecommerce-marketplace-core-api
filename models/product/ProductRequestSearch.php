<?php

namespace app\models\product;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ProductRequestSearch represents the model behind the search form of `app\models\product\ProductRequest`.
 */
class ProductRequestSearch extends ProductRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'quantity', 'status', 'user_id', 'admin_id'], 'integer'],
            [['product_name', 'phone', 'email', 'product_link', 'admin_notes', 'date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
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
        $query = ProductRequest::find()->with(['user', 'admin']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'quantity' => $this->quantity,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'product_name', $this->product_name])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'product_link', $this->product_link])
            ->andFilterWhere(['like', 'admin_notes', $this->admin_notes]);

        // Date range filtering
        if ($this->date) {
            $dates = explode(' - ', $this->date);
            if (count($dates) == 2) {
                $startDate = date('Y-m-d 00:00:00', strtotime($dates[0]));
                $endDate = date('Y-m-d 23:59:59', strtotime($dates[1]));
                $query->andFilterWhere(['between', 'date', $startDate, $endDate]);
            } else {
                $query->andFilterWhere(['like', 'date', $this->date]);
            }
        }

        return $dataProvider;
    }
} 