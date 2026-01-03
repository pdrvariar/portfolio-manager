<?php
class Auth {
    public static function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            Session::setFlash('error', 'Você precisa fazer login para acessar esta página.');
            header('Location: /login');
            exit;
        }
    }
    
    public static function checkAdmin() {
        self::checkAuthentication();
        
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            Session::setFlash('error', 'Acesso negado. Apenas administradores.');
            header('Location: /');
            exit;
        }
    }
    
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        Session::setFlash('success', 'Login realizado com sucesso!');
    }
    
    public static function logout() {
        Session::destroy();
        header('Location: /login');
        exit;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
    
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>