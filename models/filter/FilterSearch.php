<?php

namespace app\models\filter;

use yii\base\Model;
use yii\data\ActiveDataProvider;

use app\models\filter\Filter;

/**
 * FilterSearch represents the model behind the search form of `app\models\Filter`.
 */
class FilterSearch extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'category_id', 'sub_category_id', 'status', 'is_filter'], 'integer'],
            [['type', 'name_ru', 'name_uz', 'name_en', 'value_ru', 'value_uz', 'value_en', 'date'], 'safe'],
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
        $query = Filter::find();

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
            'parent_id' => $this->parent_id,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'is_filter' => $this->is_filter,
            'status' => $this->status,
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'name_ru', $this->name_ru])
            ->andFilterWhere(['like', 'name_uz', $this->name_uz])
            ->andFilterWhere(['like', 'name_en', $this->name_en])
            ->andFilterWhere(['like', 'value_ru', $this->value_ru])
            ->andFilterWhere(['like', 'value_uz', $this->value_uz])
            ->andFilterWhere(['like', 'value_en', $this->value_en]);

        return $dataProvider;
    }
}
