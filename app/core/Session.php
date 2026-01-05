<?php
// app/core/Session.php - CORRIGIDO
class Session {
    public static function start() {
        // VERIFICA apenas, não inicia
        if (session_status() == PHP_SESSION_NONE) {
            // Sessão não foi iniciada - erro de configuração
            error_log("AVISO: Sessão não iniciada. Configure session_start() no index.php");
            return false;
        }
        return true;
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        if (self::has($key)) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
            $_SESSION = [];
        }
    }
    
    // Flash Messages
    public static function setFlash($type, $message) {
        self::set('flash_' . $type, $message);
    }
    
    public static function getFlash($type) {
        if (isset($_SESSION['flash_' . $type])) {
            $message = $_SESSION['flash_' . $type];
            unset($_SESSION['flash_' . $type]); // Remove imediatamente
            return $message;
        }
        return null;
    }
    
    public static function hasFlash($type) {
        return self::has('flash_' . $type);
    }
    
    /**
     * Gera um token CSRF único para a sessão, se não existir
     */
    public static function generateCsrfToken() {
        if (!self::has('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            self::set('csrf_token', $token);
        }
        return self::get('csrf_token');
    }

    /**
     * Retorna o token atual da sessão
     */
    public static function getCsrfToken() {
        return self::get('csrf_token') ?? self::generateCsrfToken();
    }

    /**
     * Valida se o token enviado é igual ao da sessão
     */
    public static function validateCsrfToken($token) {
        $storedToken = self::get('csrf_token');
        // hash_equals evita ataques de tempo (timing attacks)
        if ($storedToken && hash_equals($storedToken, (string)$token)) {
            return true;
        }
        return false;
    }
}
?>