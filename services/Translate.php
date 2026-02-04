<?php

namespace yii\services;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Cookie;

use app\models\Category;
use app\models\Words;

class Translate {
    public static function get() {
        $languages = ArrayHelper::map(Category::find()->where(['type'=>'language'])->all(), 'id', 'name_mini');
        $data_translate = [];

        $cookies = Yii::$app->request->cookies;
        $model = ($language = $cookies->getValue('language')) ? Category::findOne($language) : Category::findOne(['type'=>'language', 'main'=>1]);

        $data_translate['language'] = $model;

        $model_words = Words::find()->all();

        foreach ($model_words as $k => $v) {
            $name = $v->{'name_'.$model->name_mini};
            $data_translate['words'][$v->name_ru] = $name ? $name : $v->name_ru;
        }

        return $data_translate;
    }

    public static function check($key, $words) {
        return array_key_exists($key, $words['words']) ? $words['words'][$key] : $key;
    } 
}