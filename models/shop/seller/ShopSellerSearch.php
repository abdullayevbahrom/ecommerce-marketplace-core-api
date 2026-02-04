<?php

namespace app\models\shop\seller;

use yii\base\Model;
use yii\data\ActiveDataProvider;

use app\models\shop\seller\ShopSeller;

/**
 * ShopSellerSearch represents the model behind the search form of `app\models\ShopSeller`.
 */
class ShopSellerSearch extends ShopSeller
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'shop_id', 'status'], 'integer'],
            [['inn', 'account', 'bank', 'address_legal', 'oked', 'okohx', 'mfo', 'date'], 'safe'],
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
        $query = ShopSeller::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'status' => $this->status,
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'inn', $this->inn])
            ->andFilterWhere(['like', 'account', $this->account])
            ->andFilterWhere(['like', 'bank', $this->bank])
            ->andFilterWhere(['like', 'address_legal', $this->address_legal])
            ->andFilterWhere(['like', 'oked', $this->oked])
            ->andFilterWhere(['like', 'okohx', $this->okohx])
            ->andFilterWhere(['like', 'mfo', $this->mfo]);

        return $dataProvider;
    }
}
