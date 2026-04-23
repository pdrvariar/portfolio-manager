<?php

/**
 * Modelo de configurações globais da aplicação (chave → valor).
 * Tabela: app_settings (key VARCHAR, value TEXT, updated_at TIMESTAMP)
 */
class Settings {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtém o valor de uma chave. Retorna $default se não existir.
     */
    public function get(string $key, $default = null) {
        try {
            $stmt = $this->db->prepare("SELECT value FROM app_settings WHERE `key` = ? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            return $row ? $row['value'] : $default;
        } catch (Exception $e) {
            error_log("Settings::get error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Define ou atualiza o valor de uma chave.
     */
    public function set(string $key, $value): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO app_settings (`key`, value, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
            ");
            return $stmt->execute([$key, (string)$value]);
        } catch (Exception $e) {
            error_log("Settings::set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna true/false para uma chave booleana ('1'/'0' / 'true'/'false').
     */
    public function getBool(string $key, bool $default = false): bool {
        $val = $this->get($key);
        if ($val === null) return $default;
        return in_array(strtolower((string)$val), ['1', 'true', 'yes', 'on'], true);
    }
}

