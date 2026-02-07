<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=app;port=3307',
    'username' => 'root',
    'password' => 'password',
    'charset' => 'utf8',
    'enableSchemaCache' => true,
    // Schema cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];