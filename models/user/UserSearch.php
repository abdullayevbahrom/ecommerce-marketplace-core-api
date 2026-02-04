<?php

namespace app\models\user;

use yii\base\Model;
use yii\data\ActiveDataProvider;

use app\models\user\User;

/**
 * UserSearch represents the model behind the search form of `app\models\User`.
 */
class UserSearch extends User
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'role', 'status'], 'integer'],
            [['type', 'token', 'device_id', 'name', 'lastname', 'balance', 'phone', 'email', 'login', 'password', 'date', 'ip', 'eimzo_tax_id'], 'safe'],
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
        $query = User::find();

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
            'role' => $this->role,
            'balance' => $this->balance,
            'status' => $this->status,
            'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'token', $this->token])
            ->andFilterWhere(['like', 'device_id', $this->device_id])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'lastname', $this->lastname])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'login', $this->login])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'eimzo_tax_id', $this->eimzo_tax_id]);

        if ($this->date) {
            $date = explode('-', $this->date);
            $date_start = date('Y-m-d', strtotime($date[0]));
            $date_finish = date('Y-m-d', strtotime($date[1]));

            $query->andFilterWhere(['between', 'date', $date_start, $date_finish]);
        }

        return $dataProvider;
    }
}
