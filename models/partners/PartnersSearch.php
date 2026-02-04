<?php

namespace app\models\partners;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\partners\Partners;

/**
 * PartnersSearch represents the model behind the search form of `app\models\partners\Partners`.
 */
class PartnersSearch extends Partners
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status', 'sort'], 'integer'],
            [['name_ru', 'name_uz', 'name_en', 'description_mini_ru', 'description_mini_uz', 'description_mini_en', 'description_ru', 'description_uz', 'description_en', 'date'], 'safe'],
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
        $query = Partners::find();

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
            'status' => $this->status,
            'sort' => $this->sort,
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'name_ru', $this->name_ru])
            ->andFilterWhere(['like', 'name_uz', $this->name_uz])
            ->andFilterWhere(['like', 'name_en', $this->name_en])
            ->andFilterWhere(['like', 'description_mini_ru', $this->description_mini_ru])
            ->andFilterWhere(['like', 'description_mini_uz', $this->description_mini_uz])
            ->andFilterWhere(['like', 'description_mini_en', $this->description_mini_en])
            ->andFilterWhere(['like', 'description_ru', $this->description_ru])
            ->andFilterWhere(['like', 'description_uz', $this->description_uz])
            ->andFilterWhere(['like', 'description_en', $this->description_en]);

        return $dataProvider;
    }
}
