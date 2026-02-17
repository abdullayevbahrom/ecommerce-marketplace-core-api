<?php

$host = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'shop';
$port = getenv('DB_PORT') ?: 3306;
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'password';

return [
    'class' => 'yii\db\Connection',
    'dsn' => "mysql:host=$host;dbname=$dbName;port=$port",
    'username' => $user,
    'password' => $password,
    'charset' => 'utf8',
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];