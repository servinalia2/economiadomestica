<?php
return [
    'app_name' => 'Economía Doméstica',
    'base_path' => '',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'economia_domestica',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'auth' => [
        'username' => getenv('APP_USERNAME') ?: 'admin',
        'password' => getenv('APP_PASSWORD') ?: 'admin123',
    ],
];
