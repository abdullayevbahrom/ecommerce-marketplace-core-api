<?php

namespace app\models\question;

use Yii;

/**
 * This is the model class for table "question".
 *
 * @property int $id
 * @property string|null $question
 * @property string|null $answer
 * @property int $status
 * @property string $date
 */
class Question extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['question_ru', 'answer_ru'], 'required', 'message' => 'Заполните поле'],
            [['question_ru', 'question_en', 'question_uz', 'answer_ru', 'answer_en', 'answer_uz'], 'string'],
            [['status'], 'integer'],
            [['date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question' => 'Question',
            'answer' => 'Answer',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        return [
            'id',
            'question' => function() use($language) { return $this->{'question_'.$language} ? $this->{'question_'.$language} : $this->question_ru;},
            'answer' => function() use($language) { return $this->{'answer_'.$language} ? $this->{'answer_'.$language} : $this->answer_ru;},
            'date'
        ];
    }
}
