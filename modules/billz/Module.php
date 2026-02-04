<?php

namespace app\modules\billz;

/**
 * billz module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\billz\controllers';
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->layout = '/billz';
        parent::init();

        // custom initialization code goes here
    }
}
