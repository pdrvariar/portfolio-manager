<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../helpers/Validation.php';

class AuthController {
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
            return;
        }
        
        include __DIR__ . '/../../views/auth/login.php';
    }
    
    private function handleLogin() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validação
        if (!Validation::validateEmail($email)) {
            $_SESSION['error'] = 'Email inválido';
            header('Location: /auth/login');
            exit;
        }
        
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Email ou senha incorretos';
            header('Location: /auth/login');
            exit;
        }
        
        if (!$user['email_verified_at']) {
            $_SESSION['error'] = 'Por favor, verifique seu email antes de fazer login';
            header('Location: /auth/login');
            exit;
        }
        
        // Criar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        
        // Cookie "lembrar-me"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 60*60*24*30, '/');
            // Salvar token no banco
        }
        
        $_SESSION['success'] = 'Login realizado com sucesso!';
        header('Location: /portfolio');
        exit;
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
            return;
        }
        
        include __DIR__ . '/../../views/auth/register.php';
    }
    
    private function handleRegister() {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validação
        $errors = [];
        
        if (strlen($name) < 3) {
            $errors[] = 'Nome deve ter pelo menos 3 caracteres';
        }
        
        if (!Validation::validateEmail($email)) {
            $errors[] = 'Email inválido';
        }
        
        if (!Validation::validatePassword($password)) {
            $errors[] = 'Senha deve ter pelo menos 8 caracteres';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'As senhas não coincidem';
        }
        
        // Verificar se email já existe
        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            $errors[] = 'Email já cadastrado';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /auth/register');
            exit;
        }
        
        // Criar usuário
        $success = $userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]);
        
        if ($success) {
            $user = $userModel->findByEmail($email);
            
            // Enviar email de verificação
            $emailService = new EmailService();
            $verificationLink = "http://{$_SERVER['HTTP_HOST']}/auth/verify?token={$user['verification_token']}";
            $emailService->sendVerificationEmail($email, $name, $verificationLink);
            
            $_SESSION['success'] = 'Conta criada com sucesso! Verifique seu email para ativar sua conta.';
            header('Location: /auth/login');
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao criar conta. Tente novamente.';
            header('Location: /auth/register');
            exit;
        }
    }
    
    public function verify() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $_SESSION['error'] = 'Token de verificação inválido';
            header('Location: /auth/login');
            exit;
        }
        
        $userModel = new User();
        $success = $userModel->verifyEmail($token);
        
        if ($success) {
            $_SESSION['success'] = 'Email verificado com sucesso! Agora você pode fazer login.';
        } else {
            $_SESSION['error'] = 'Token de verificação inválido ou expirado';
        }
        
        header('Location: /auth/login');
        exit;
    }
    
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleForgotPassword();
            return;
        }
        
        include __DIR__ . '/../../views/auth/forgot-password.php';
    }
    
    private function handleForgotPassword() {
        $email = $_POST['email'] ?? '';
        
        if (!Validation::validateEmail($email)) {
            $_SESSION['error'] = 'Email inválido';
            header('Location: /auth/forgot-password');
            exit;
        }
        
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $userModel->setResetToken($email, $token);
            
            // Enviar email de recuperação
            $emailService = new EmailService();
            $resetLink = "http://{$_SERVER['HTTP_HOST']}/auth/reset-password?token=$token";
            $emailService->sendPasswordResetEmail($email, $user['name'], $resetLink);
        }
        
        // Sempre mostrar sucesso por segurança
        $_SESSION['success'] = 'Se o email estiver cadastrado, você receberá instruções para redefinir sua senha.';
        header('Location: /auth/login');
        exit;
    }
    
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $_SESSION['error'] = 'Token inválido';
            header('Location: /auth/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleResetPassword($token);
            return;
        }
        
        include __DIR__ . '/../../views/auth/reset-password.php';
    }
    
    private function handleResetPassword($token) {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!Validation::validatePassword($password)) {
            $_SESSION['error'] = 'Senha deve ter pelo menos 8 caracteres';
            header("Location: /auth/reset-password?token=$token");
            exit;
        }
        
        if ($password !== $confirmPassword) {
            $_SESSION['error'] = 'As senhas não coincidem';
            header("Location: /auth/reset-password?token=$token");
            exit;
        }
        
        $userModel = new User();
        $success = $userModel->resetPassword($token, $password);
        
        if ($success) {
            $_SESSION['success'] = 'Senha redefinida com sucesso! Agora você pode fazer login.';
            header('Location: /auth/login');
            exit;
        } else {
            $_SESSION['error'] = 'Token inválido ou expirado';
            header("Location: /auth/reset-password?token=$token");
            exit;
        }
    }
    
    public function logout() {
        // Destruir sessão
        session_destroy();
        
        // Limpar cookie "lembrar-me"
        setcookie('remember_token', '', time() - 3600, '/');
        
        header('Location: /auth/login');
        exit;
    }
}