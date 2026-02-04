<?php

namespace app\models\news;

use Yii;

/**
 * This is the model class for table "news_view".
 *
 * @property int $id
 * @property int|null $news_id
 * @property string|null $ip
 * @property string $date
 *
 * @property News $news
 */
class NewsView extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'news_view';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['news_id'], 'integer'],
            [['date'], 'safe'],
            [['ip'], 'string', 'max' => 255],
            [['news_id'], 'exist', 'skipOnError' => true, 'targetClass' => News::className(), 'targetAttribute' => ['news_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'news_id' => 'News ID',
            'ip' => 'Ip',
            'date' => 'Date',
        ];
    }

    public function saveObject($news) {
        $this->news_id = $news->id;
        $this->ip = Yii::$app->request->userIP;
        if ($this->save()) {
            $news->views += 1;
            return $news->save(false);
        }

        return false;
    }

    /**
     * Gets query for [[News]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNews()
    {
        return $this->hasOne(News::className(), ['id' => 'news_id']);
    }
}
