<?php

namespace app\models\user\card;

use yii\base\Model;
use yii\data\ActiveDataProvider;

use app\models\user\card\UserCard;

/**
 * UserCardSearch represents the model behind the search form of `app\models\UserCard`.
 */
class UserCardSearch extends UserCard
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'card_type_id', 'status'], 'integer'],
            [['card_token', 'card_number', 'card_expire', 'card_phone_number', 'date'], 'safe'],
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
        $query = UserCard::find();

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
            'card_type_id' => $this->card_type_id,
            'status' => $this->status,
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'card_token', $this->card_token])
            ->andFilterWhere(['like', 'card_number', $this->card_number])
            ->andFilterWhere(['like', 'card_expire', $this->card_expire])
            ->andFilterWhere(['like', 'card_phone_number', $this->card_phone_number]);

        return $dataProvider;
    }
}
