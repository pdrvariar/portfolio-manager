<?php

class AuthController {
    private $userModel;
    
    public function __construct() {
        // O Autoload no index.php deve carregar a classe User automaticamente
        $this->userModel = new User();
    }
    
    public function login() {
        // Se já estiver logado, manda para a home
        if (Auth::isLoggedIn()) {
            header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // VALIDAÇÃO CSRF CRUCIAL
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=login');
            }            
            
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $user = $this->userModel->findByUsername($username);
            
            if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
                Auth::login($user);
                header('Location: /');
                exit;
            } else {
                Session::setFlash('error', 'Usuário ou senha inválidos.');
                header('Location: /index.php?url=' . obfuscateUrl('login'));
                exit;
            }
        }
        
        // Caminho absoluto para a view
        require_once __DIR__ . '/../views/auth/login.php';
    }
    
    public function register() {
        if (Auth::isLoggedIn()) {
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // VALIDAÇÃO CSRF CRUCIAL
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=login');
            }           

            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validações básicas
            if (empty($username) || empty($email) || empty($password)) {
                Session::setFlash('error', 'Todos os campos são obrigatórios.');
                header('Location: /index.php?url=' . obfuscateUrl('register'));
                exit;
            }
            
            if ($password !== $confirmPassword) {
                Session::setFlash('error', 'As senhas não coincidem.');
                header('Location: /index.php?url=' . obfuscateUrl('register'));
                exit;
            }
            
            if (strlen($password) < 6) {
                Session::setFlash('error', 'A senha deve ter no mínimo 6 caracteres.');
                header('Location: /index.php?url=' . obfuscateUrl('register'));
                exit;
            }
            
            // Verificar se usuário já existe
            $existingUser = $this->userModel->findByUsername($username);
            if ($existingUser) {
                Session::setFlash('error', 'Nome de usuário já está em uso.');
                header('Location: /index.php?url=' . obfuscateUrl('register'));
                exit;
            }
            
            // Criar usuário
            $success = $this->userModel->create($username, $email, $password);
            
            if ($success) {
                Session::setFlash('success', 'Conta criada com sucesso! Faça login.');
                header('Location: /index.php?url=' . obfuscateUrl('login'));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao criar conta. Tente novamente.');
                header('Location: /index.php?url=' . obfuscateUrl('register'));
                exit;
            }
        }
        
        // Caminho absoluto para a view
        require_once __DIR__ . '/../views/auth/register.php';
    }
    
    public function logout() {
        Auth::logout();
    }
}