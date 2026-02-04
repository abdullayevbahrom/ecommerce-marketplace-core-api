<?php

namespace app\models\question;

use yii\base\Model;
use yii\data\ActiveDataProvider;

use app\models\question\Question;

/**
 * QuestionSearch represents the model behind the search form of `app\models\Question`.
 */
class QuestionSearch extends Question
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['question_ru', 'question_en', 'question_uz', 'answer_ru', 'answer_en', 'answer_uz', 'date'], 'safe'],
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
        $query = Question::find();

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
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'question_ru', $this->question_ru])
            ->andFilterWhere(['like', 'question_en', $this->question_en])
            ->andFilterWhere(['like', 'question_uz', $this->question_uz])
            ->andFilterWhere(['like', 'answer_ru', $this->answer_ru])
            ->andFilterWhere(['like', 'answer_en', $this->answer_en])
            ->andFilterWhere(['like', 'answer_uz', $this->answer_uz]);

        return $dataProvider;
    }
}
