<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=' . ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1') . ';dbname=' . ($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'db_CMS'),
    'username' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];
