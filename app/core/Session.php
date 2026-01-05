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
}
?>