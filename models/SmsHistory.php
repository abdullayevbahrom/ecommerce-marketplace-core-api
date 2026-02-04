<?php

namespace app\models;


/**
 * Class SmsHistory
 * @package app\models
 * @property int $id
 * @property string $text
 * @property string $phone
 * @property int $status
 * @property string $request
 * @property string $response
 * @property string $date
 */

class SmsHistory extends \yii\db\ActiveRecord {

    public static function tableName() {
        return 'sms_history';
    }

    public function rules() {
        return [
            [['text', 'phone', 'status', 'request', 'response', 'date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'text' => 'text',
            'phone' => 'phone',
            'status' => 'status',
            'request' => 'request',
            'response' => 'response',
            'date' => 'date',
        ];
    }

    public static  function create($phone,$message, $request, $response){
        $model =  new self();
        $model->text = $message;
        $model->phone = $phone;
        $model->status = 1;
        $model->request = $request;
        $model->response = $response;
        $model->date = time();
        $model->save();
    }

    public static function logger($line, $file,  $data){
        if(is_array($data)){ $data = json_encode($data); }
        $model = new self();
        $model->text = $file;
        $model->phone = $line;
        $model->status = 1;
        $model->request = $data;
        $model->response = $data;
        $model->date = time();
        $model->save();
    }

}
