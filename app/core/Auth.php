<?php
// app/core/Auth.php

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
        
        // Usando a classe Session para padronização
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('user_email', $user['email']);
        Session::set('is_admin', (bool)$user['is_admin']);
        Session::set('user_plan', $user['plan'] ?? 'starter');
        
        // Opcional: Registrar data do último login no banco aqui
        
        Session::setFlash('success', 'Bem-vindo de volta, ' . $user['username'] . '!');
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
        return self::isLoggedIn() && (bool)Session::get('is_admin') === true;
    }
    
    /**
     * Retorna os dados do usuário logado na sessão
     */
    public static function getUser() {
        if (!self::isLoggedIn()) return null;
        
        return [
            'id' => Session::get('user_id'),
            'username' => Session::get('username'),
            'email' => Session::get('user_email'),
            'is_admin' => (bool)Session::get('is_admin'),
            'plan' => Session::get('user_plan') ?? 'starter'
        ];
    }

    public static function getUserId() {
        return self::getCurrentUserId();
    }
    
    public static function getCurrentUserId() {
        return Session::get('user_id');
    }

    public static function getUserPlan() {
        return Session::get('user_plan') ?? 'starter';
    }

    /**
     * Atualiza o plano do usuário na sessão ativa.
     */
    public static function updateSessionPlan($plan) {
        Session::set('user_plan', $plan);
    }

    public static function isPro() {
        return self::getUserPlan() === 'pro' || self::isAdmin();
    }
}