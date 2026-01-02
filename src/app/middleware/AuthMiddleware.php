<?php
class AuthMiddleware {
    
    public static function requireLogin() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /auth/login');
            exit;
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        
        if (!$_SESSION['is_admin']) {
            $_SESSION['error'] = 'Acesso negado. Apenas administradores podem acessar esta página.';
            header('Location: /portfolio');
            exit;
        }
    }
    
    public static function requireGuest() {
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            header('Location: /portfolio');
            exit;
        }
    }
    
    public static function checkCSRFToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? '';
            
            if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
                exit;
            }
        }
    }
    
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function checkAPIKey() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
        
        if (empty($apiKey)) {
            Response::error('API key é necessária', 401);
        }
        
        // Verificar API key no banco de dados
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM api_keys WHERE api_key = ? AND active = 1");
        $stmt->execute([hash('sha256', $apiKey)]);
        $apiKeyData = $stmt->fetch();
        
        if (!$apiKeyData) {
            Response::error('API key inválida', 401);
        }
        
        return $apiKeyData['user_id'];
    }
}