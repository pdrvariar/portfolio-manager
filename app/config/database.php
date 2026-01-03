<?php
class DatabaseConfig {
    public static function getConfig() {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? 'portfolio_db',
            'username' => $_ENV['DB_USER'] ?? 'portfolio_user',
            'password' => $_ENV['DB_PASS'] ?? '123456',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    }
}
?>