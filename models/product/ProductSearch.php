<?php

namespace app\models\product;

use yii\base\Model;
use yii\data\ActiveDataProvider;

use app\models\product\Product;

/**
 * ProductSearch represents the model behind the search form of `app\models\Product`.
 */
class ProductSearch extends Product
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'category_id', 'shop_id', 'brand_id', 'region_id', 'stock_id', 'views', 'status'], 'integer'],
            [['name_ru', 'name_en', 'name_uz', 'description_ru', 'description_en', 'description_uz', 'composition_ru', 'composition_en', 'composition_uz', 'recommendation_ru', 'recommendation_en', 'recommendation_uz', 'date', 'ikpu_code', 'ikpu_name'], 'safe'],
            [['price', 'price_opt', 'price_small', 'min_order', 'discount', 'rating', 'amount'], 'number'],
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
        $query = Product::find();

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
            'user_id' => $this->user_id,
            'stock_id' => $this->stock_id,
            'category_id' => $this->category_id,
            'shop_id' => $this->shop_id,
            'brand_id' => $this->brand_id,
            'region_id' => $this->region_id,
            'price' => $this->price,
            'price_small' => $this->price_small,
            'price_opt' => $this->price_opt,
            'min_order' => $this->min_order,
            'discount' => $this->discount,
            'amount' => $this->amount,
            'views' => $this->views,
            'rating' => $this->rating,
            'status' => $this->status,
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'name_ru', $this->name_ru])
            ->andFilterWhere(['like', 'name_en', $this->name_en])
            ->andFilterWhere(['like', 'name_uz', $this->name_uz])
            ->andFilterWhere(['like', 'description_ru', $this->description_ru])
            ->andFilterWhere(['like', 'description_en', $this->description_en])
            ->andFilterWhere(['like', 'description_uz', $this->description_uz])
            ->andFilterWhere(['like', 'composition_ru', $this->composition_ru])
            ->andFilterWhere(['like', 'composition_en', $this->composition_en])
            ->andFilterWhere(['like', 'composition_uz', $this->composition_uz])
            ->andFilterWhere(['like', 'recommendation_ru', $this->recommendation_ru])
            ->andFilterWhere(['like', 'recommendation_en', $this->recommendation_en])
            ->andFilterWhere(['like', 'recommendation_uz', $this->recommendation_uz])
            ->andFilterWhere(['like', 'ikpu_code', $this->ikpu_code])
            ->andFilterWhere(['like', 'ikpu_name', $this->ikpu_name]);

        return $dataProvider;
    }
}
