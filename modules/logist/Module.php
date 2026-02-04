<?php

namespace app\modules\logist;

use Yii;

/**
 * logist module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\logist\controllers';
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->layout = '/logist';
        parent::init();

        // custom initialization code goes here
    }
}
