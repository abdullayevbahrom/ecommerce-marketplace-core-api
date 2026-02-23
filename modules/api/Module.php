<?php

namespace app\modules\api;

use yii\web\Response;
use yii\filters\ContentNegotiator;

/**
 * api module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\api\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->layout = '/api';
        parent::init();

        // custom initialization code goes here
    }

    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                    'text/json' => Response::FORMAT_JSON,
                    '*/*' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }
}
