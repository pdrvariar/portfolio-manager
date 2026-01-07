<?php
// app/core/Session.php - CORRIGIDO
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Inicia a sessão com parâmetros de segurança profissionais
            session_start([
                'cookie_httponly' => true,
                'cookie_secure'   => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Lax'
            ]);
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
        // Mudamos para salvar diretamente em $_SESSION para o main.php ler
        $_SESSION['flash_' . $type] = $message;
    }

    public static function getFlash($type) {
        $key = 'flash_' . $type;
        if (isset($_SESSION[$key])) {
            $message = $_SESSION[$key];
            unset($_SESSION[$key]); // Remove após o primeiro uso (Padrão Flash)
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