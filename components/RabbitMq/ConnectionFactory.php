<?php

namespace app\components\RabbitMq;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Yii;

class ConnectionFactory
{
    public static function make(): AMQPStreamConnection
    {
        $cfg = Yii::$app->params['rabbitmq'];

        return new AMQPStreamConnection(
            $cfg['host'],
            (int) $cfg['port'],
            $cfg['user'],
            $cfg['password'],
            $cfg['vhost']
        );
    }
}