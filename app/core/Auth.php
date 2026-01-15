<?php
namespace App\Core;

use App\Core\Session;

class Auth {
    /**
     * Protege rotas que exigem login
     */
    public static function checkAuthentication() {
        if (!self::isLoggedIn()) {
            Session::setFlash('error', 'Você precisa fazer login para acessar esta página.');
            header('Location: /index.php?url=' . obfuscateUrl('login'));
            exit;
        }
    }
    
    /**
     * Protege rotas administrativas
     */
    public static function checkAdmin() {
        self::checkAuthentication();
        
        if (!self::isAdmin()) {
            Session::setFlash('error', 'Acesso negado. Esta área é restrita a administradores.');
            header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
            exit;
        }
    }
    
    /**
     * Processa a autenticação do usuário
     */
    public static function login($user) {
        // SEGURANÇA: Regenera o ID da sessão para evitar Session Fixation
        session_regenerate_id(true);
        
        // Suporte tanto para array (legado) quanto para Entidade
        if ($user instanceof \App\Entities\User) {
            Session::set('user_id', $user->getId());
            Session::set('username', $user->getUsername());
            Session::set('user_email', $user->getEmail());
            Session::set('is_admin', $user->isAdmin());
        } else {
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('user_email', $user['email']);
            Session::set('is_admin', (bool)$user['is_admin']);
        }
        
        Session::setFlash('success', 'Bem-vindo de volta, ' . Session::get('username') . '!');
    }
    
    public static function logout() {
        Session::destroy();
        header('Location: /index.php?url=' . obfuscateUrl('login'));
        exit;
    }
    
    public static function isLoggedIn() {
        return Session::has('user_id');
    }
    
    public static function isAdmin() {
        return self::isLoggedIn() && Session::get('is_admin') === true;
    }
    
    public static function getCurrentUserId() {
        return Session::get('user_id');
    }
}