<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "logs".
 *
 * @property int $id
 * @property string $category
 * @property string|null $level
 * @property string|null $message
 * @property string|null $data
 * @property string|null $created_at
 */
class Log extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category'], 'required'],
            [['message', 'data'], 'string'],
            [['created_at'], 'safe'],
            [['category'], 'string', 'max' => 255],
            [['level'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'Category',
            'level' => 'Level',
            'message' => 'Message',
            'data' => 'Data',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Helper method to add a log entry
     * 
     * @param string $category
     * @param string $message
     * @param mixed $data Optional data to be JSON encoded
     * @param string $level 'info', 'warning', 'error', etc.
     * @return bool
     */
    public static function log($category, $message, $data = null, $level = 'info')
    {
        $log = new self();
        $log->category = $category;
        $log->message = $message;
        $log->level = $level;
        
        if ($data !== null) {
            $log->data = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        $log->created_at = date('Y-m-d H:i:s');
        
        return $log->save(false);
    }
}
