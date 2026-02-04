<?php

namespace app\models\chat;

use yii\base\Model;
use yii\data\ActiveDataProvider;

use app\models\chat\MessageRoom;

/**
 * MessageRoomSearch represents the model behind the search form of `app\models\MessageRoom`.
 */
class MessageRoomSearch extends MessageRoom
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'sender_id', 'getter_id', 'status', 'status_archive', 'status_important'], 'integer'],
            [['date'], 'safe'],
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
        $query = MessageRoom::find();

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
            'sender_id' => $this->sender_id,
            'getter_id' => $this->getter_id,
            'status' => $this->status,
            'status_archive' => $this->status_archive,
            'status_important' => $this->status_important,
            'date' => $this->date,
        ]);

        return $dataProvider;
    }
}
