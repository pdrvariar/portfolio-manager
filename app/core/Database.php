<?php

class Database {
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct() {
        // Lógica Sênior: Prioriza $_ENV (carregado pelo seu Env::load) depois getenv
        $host = $_ENV['DB_HOST'] ?? (getenv('DB_HOST') ?: '127.0.0.1');
        $db   = $_ENV['DB_NAME'] ?? (getenv('DB_NAME') ?: 'portfolio_db');
        $user = $_ENV['DB_USER'] ?? (getenv('DB_USER') ?: 'root');
        $pass = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') ?: '');
        $port = $_ENV['DB_PORT'] ?? (getenv('DB_PORT') ?: '3306');

        // Limpeza de aspas de segurança para o .env da Hostinger
        $pass = trim($pass, "'\"");

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false, // Desativado para evitar limites de conexão da Hostinger
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->connection = new PDO($dsn, $user, $pass, $options);
            
        } catch (PDOException $e) {
            error_log("Erro Crítico de Banco de Dados: " . $e->getMessage());
            
            $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
            $isDev = $appEnv === 'development';
            
            die($isDev ? "Erro de Conexão: " . $e->getMessage() : "Erro crítico: O serviço de dados está temporariamente indisponível.");
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Mantida a sua lógica original de try/catch para logs de query
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro na Query: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e; 
        }
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Não é permitido desserializar um Singleton.");
    }
}