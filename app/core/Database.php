<?php

/**
 * Classe Database - Padrão Singleton de Alta Performance
 * Responsável pela gestão da persistência e comunicação com o MySQL no Docker.
 */
class Database {
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct() {
        // Recupera as configurações do .env (injetadas pelo Docker)
        $host = $_ENV['DB_HOST'] ?? 'db';
        $db   = $_ENV['DB_NAME'] ?? 'portfolio_db';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $port = $_ENV['DB_PORT'] ?? '3306';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            
            // Opções de Conexão Sênior para Produção
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em erros de SQL
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arrays associativos por padrão
                PDO::ATTR_EMULATE_PREPARES   => false,                 // Usa prepared statements reais (Segurança)
                PDO::ATTR_PERSISTENT         => true,                  // Mantém a conexão viva entre requisições
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"    // Garante suporte total a emojis e caracteres especiais
            ];

            $this->connection = new PDO($dsn, $user, $pass, $options);
            
        } catch (PDOException $e) {
            // Log de erro profissional para o Docker/Nginx capturarem
            error_log("Erro Crítico de Banco de Dados: " . $e->getMessage());
            
            // Em ambiente de desenvolvimento, mostra o erro; em produção, uma mensagem genérica
            $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
            die($isDev ? "Erro de Conexão: " . $e->getMessage() : "Erro crítico: O serviço de dados está temporariamente indisponível.");
        }
    }

    /**
     * Retorna a instância única da conexão (Singleton)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna o objeto PDO para operações complexas
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Atalho para Prepared Statements - Proteção contra SQL Injection
     * @param string $sql A query SQL com placeholders (?)
     * @param array $params Os valores para substituir os placeholders
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro na Query: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e; // Re-lança para o Controller tratar se necessário
        }
    }

    /**
     * Previne a clonagem da instância
     */
    private function __clone() {}

    /**
     * Previne a desserialização da instância
     */
    public function __wakeup() {
        throw new \Exception("Não é permitido desserializar um Singleton.");
    }
}