<?php
// app/config/database.php

class DatabaseConfig {
    public static function getConfig() {
        // Usamos getenv() para buscar as variáveis carregadas pelo seu novo Env.php
        return [
            'host'     => getenv('DB_HOST') ?: 'localhost',
            'database' => getenv('DB_NAME'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
            'charset'  => 'utf8mb4',
            'options'  => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Padroniza a conexão para UTF8MB4 diretamente no driver
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];
    }
}