<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->loadEnvironment();
        
        $host = $_ENV['DB_HOST'] ?? 'mysql';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? 'portfolio_db';
        $username = $_ENV['DB_USER'] ?? 'portfolio_user';
        $password = $_ENV['DB_PASSWORD'] ?? 'portfolio_pass';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        try {
            $this->connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function loadEnvironment() {
        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
    
    public static function beginTransaction() {
        return self::getInstance()->beginTransaction();
    }
    
    public static function commit() {
        return self::getInstance()->commit();
    }
    
    public static function rollBack() {
        return self::getInstance()->rollBack();
    }
    
    public static function lastInsertId() {
        return self::getInstance()->lastInsertId();
    }
}