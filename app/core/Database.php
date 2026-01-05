<?php
// app/core/Database.php

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = DatabaseConfig::getConfig();
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            
            // Passamos as opções configuradas no database.php
            $this->connection = new PDO(
                $dsn, 
                $config['username'], 
                $config['password'], 
                $config['options']
            );
            
        } catch (PDOException $e) {
            // Em vez de apenas die, poderíamos usar error_log para o Docker capturar
            error_log("Erro de Conexão: " . $e->getMessage());
            die("Erro crítico: Não foi possível conectar ao banco de dados.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Método auxiliar para facilitar queries rápidas
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}