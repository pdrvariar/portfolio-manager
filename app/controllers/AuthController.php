<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleServiceOauth2;

class AuthController {
    private $userModel;
    private $params;

    public function __construct($params = []) {
        $this->userModel = new User();
        $this->params = $params;
    }

    /**
     * Exibe e processa o formulário de Login
     */
    public function login() {
        if (Auth::isLoggedIn()) {
            header('Location: /index.php?url=' . obfuscateUrl('dashboard')); //
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.'); //
                redirectBack('/index.php?url=login');
            }            
            
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            $user = $this->userModel->findByCredentials($username);
            
            if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
                // Bloqueia login se o e-mail não estiver verificado
                if ($user['status'] === 'pending') {
                    // Esta mensagem só aparece se a Session::start() e o main.php estiverem 100%
                    Session::setFlash('warning', 'Sua conta ainda não foi ativada. Verifique seu e-mail.');
                    header('Location: /index.php?url=' . obfuscateUrl('login'));
                    exit;
                }
                Auth::login($user); //
                header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
                exit;
            } else {
                Session::setFlash('error', 'Usuário ou senha inválidos.'); //
                header('Location: /index.php?url=' . obfuscateUrl('login'));
                exit;
            }
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Processa o registro de novos utilizadores
     * Corrigido para sincronizar o token de verificação entre o Banco e o E-mail
     */
    public function register() {
        // Se o utilizador já estiver logado, redireciona para a home
        if (Auth::isLoggedIn()) {
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validação de Segurança: CSRF Token
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=register');
            }

            // Captura e sanitização dos campos do formulário
            $data = [
                'full_name'       => sanitize($_POST['full_name'] ?? ''),
                'username'        => sanitize($_POST['username'] ?? ''),
                'email'           => sanitize($_POST['email'] ?? ''),
                'phone'           => sanitize($_POST['phone'] ?? ''),
                'birth_date'      => $_POST['birth_date'] ?? '',
                'password'        => $_POST['password'] ?? '',
                'confirm_password'=> $_POST['confirm_password'] ?? ''
            ];

            // 1. Validação de Campos Obrigatórios
            foreach ($data as $key => $value) {
                if (empty($value)) {
                    Session::setFlash('error', 'Todos os campos são obrigatórios.');
                    redirectBack('/index.php?url=' . obfuscateUrl('register'));
                }
            }

            // 2. Validação de Idade (Mínimo 18 anos)
            $birthDate = new DateTime($data['birth_date']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            if ($age < 18) {
                Session::setFlash('error', 'Você precisa ter pelo menos 18 anos para se cadastrar.');
                redirectBack('/index.php?url=' . obfuscateUrl('register'));
            }

            // 3. Validação de Senha
            if ($data['password'] !== $data['confirm_password']) {
                Session::setFlash('error', 'As senhas não coincidem.');
                redirectBack('/index.php?url=' . obfuscateUrl('register'));
            }
            
            if (strlen($data['password']) < 6) {
                Session::setFlash('error', 'A senha deve ter no mínimo 6 caracteres.');
                redirectBack('/index.php?url=' . obfuscateUrl('register'));
            }

            // 4. Verificação de Duplicidade de Usuário
            if ($this->userModel->findByUsername($data['username'])) {
                Session::setFlash('error', 'Este nome de usuário já existe.');
                redirectBack('/index.php?url=' . obfuscateUrl('register'));
            }

            // 5. Criação do Usuário no Banco de Dados
            // O método create() agora retorna o token gerado internamente ou false
            $verificationToken = $this->userModel->create($data);

            if ($verificationToken) {
                // 6. Envio de E-mail de Verificação usando o token correto retornado pelo banco
                $this->sendEmailVerification($data['email'], $data['full_name'], $verificationToken);
                
                // Exibe a view informando que o e-mail foi enviado
                require_once __DIR__ . '/../views/auth/verify_notice.php';
                exit;
            } else {
                Session::setFlash('error', 'Falha no cadastro. Tente novamente.');
                redirectBack('/index.php?url=' . obfuscateUrl('register'));
            }
        }

        // Carrega a view do formulário de registro (GET)
        require_once __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Confirmação do Token de E-mail
     */
    public function verify() {
        

        // Sanitizamos o token que vem da URL
        $token = $_GET['token'] ;

        error_log("DEBUG TOKEN RECEBIDO: " . $token);
        
        if (empty($token)) {
            Session::setFlash('error', 'Token de verificação não fornecido.');
            header('Location: /index.php?url=' . obfuscateUrl('login'));
            exit;
        }

        // 1. Primeiro buscamos se o token existe no banco
        $user = $this->userModel->findByToken($token);
        
        if ($user) {
            // 2. Se o usuário existe, tentamos ativar a conta
            if ($this->userModel->activate($user['id'])) {
                Session::setFlash('success', 'E-mail confirmado com sucesso! Você já pode entrar.');
                header('Location: /index.php?url=' . obfuscateUrl('login'));
            } else {
                // Caso o banco falhe no UPDATE
                Session::setFlash('error', 'Erro ao processar ativação no banco de dados.');
                header('Location: /index.php?url=' . obfuscateUrl('register'));
            }
        } else {
            // Token não encontrado ou já expirado/removido
            Session::setFlash('error', 'Link de confirmação inválido ou já utilizado.');
            header('Location: /index.php?url=' . obfuscateUrl('register'));
        }
        exit;
    }
/**
     * Integração SMTP Brevo (PHPMailer) - Versão Sênior Hostinger
     */
    private function sendEmailVerification($email, $name, $token) {
        $mail = new PHPMailer(true);
        try {
            // 1. Recupera e limpa as credenciais de forma robusta
            $smtpUser = trim(getenv('BREVO_USER') ?: ($_ENV['BREVO_USER'] ?? ''), "\"' ");
            $smtpPass = trim(getenv('BREVO_PASS') ?: ($_ENV['BREVO_PASS'] ?? ''), "\"' ");
            $fromEmail = trim(getenv('MAIL_FROM_ADDRESS') ?: ($_ENV['MAIL_FROM_ADDRESS'] ?? ''), "\"' ");
            $fromName  = trim(getenv('MAIL_FROM_NAME') ?: ($_ENV['MAIL_FROM_NAME'] ?? 'Portfolio Backtest'), "\"' ");
            $appUrl    = trim(getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'https://smartreturns.com.br'), "\"' ");

            // Configurações do servidor Brevo
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.brevo.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser; 
            $mail->Password   = $smtpPass; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Define o remetente e destinatário
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $name);

            // Monta a URL absoluta de verificação
            $url = rtrim($appUrl, '/') . "/index.php?url=" . obfuscateUrl('verify') . "&token=" . $token;

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8'; // Garante acentuação correta
            $mail->Subject = 'Confirme sua conta - Portfolio Backtest';
            $mail->Body    = "<h2>Olá, $name!</h2>
                              <p>Obrigado por se cadastrar. Clique no botão abaixo para ativar sua conta:</p>
                              <a href='$url' style='display:inline-block; padding:10px 20px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:5px;'>Confirmar E-mail</a>
                              <p>Se o botão não funcionar, copie este link: $url</p>";

            $mail->send();
            error_log("E-mail enviado com sucesso para: $email");
        } catch (Exception $e) {
            error_log("Erro Brevo/PHPMailer: {$mail->ErrorInfo}");
            // Se estiver em modo dev, você pode ver o erro na tela temporariamente para debug
            if (getenv('APP_ENV') === 'development') {
                echo "Erro no envio: " . $mail->ErrorInfo;
            }
        }
    }

    /**
     * Ponto de Entrada para Login com Google
     */
/**
     * Ponto de Entrada para Login com Google
     */
    public function googleLogin() {
        // 1. Busca a URL e LIMPA aspas e espaços (Essencial para Hostinger)
        $baseUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'https://smartreturns.com.br');
        $baseUrl = trim($baseUrl, "\"' "); 
        
        $client = new GoogleClient(); 
        $client->setClientId(trim(getenv('GOOGLE_CLIENT_ID') ?: $_ENV['GOOGLE_CLIENT_ID'], "\"' "));
        $client->setClientSecret(trim(getenv('GOOGLE_CLIENT_SECRET') ?: $_ENV['GOOGLE_CLIENT_SECRET'], "\"' "));
        
        // 2. Monta a URI absoluta garantindo o formato correto
        $redirectUri = rtrim($baseUrl, '/') . "/index.php?url=google/callback";
        $client->setRedirectUri($redirectUri);
        
        $client->addScope("email");
        $client->addScope("profile");

        // Agora a chamada na linha 233 (conforme seu log) será bem-sucedida
        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    public function logout() {
        Auth::logout(); //
    }

    /**
     * GET: Exibe formulário de pedido | POST: Gera token e envia e-mail
     */
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email']);
            $user = $this->userModel->findByEmail($email);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $this->userModel->setResetToken($email, $token);
                EmailService::sendPasswordReset($email, $user['full_name'], $token);
            }
            
            // Prática Sênior: Sempre mostramos sucesso para evitar "Enumeração de E-mail" por hackers
            Session::setFlash('success', 'Se o e-mail existir em nossa base, um link de recuperação foi enviado.');
            header('Location: /index.php?url=' . obfuscateUrl('login'));
            exit;
        }
        require_once __DIR__ . '/../views/auth/forgot.php';
    }

    /**
     * GET: Valida token e mostra form | POST: Grava nova senha
     */
    public function resetPassword() {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        $user = $this->userModel->validateResetToken($token);

        if (!$user) {
            Session::setFlash('error', 'O link de recuperação é inválido ou expirou.');
            header('Location: /index.php?url=' . obfuscateUrl('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm = $_POST['confirm_password'];

            if ($password === $confirm && strlen($password) >= 6) {
                $this->userModel->updatePassword($user['id'], $password);
                Session::setFlash('success', 'Senha alterada com sucesso! Faça login.');
                header('Location: /index.php?url=' . obfuscateUrl('login'));
                exit;
            }
            Session::setFlash('error', 'As senhas devem coincidir e ter no mínimo 6 caracteres.');
        }
        require_once __DIR__ . '/../views/auth/reset_form.php';
    }    

/**
     * Callback do Google - Processa o retorno
     */
    public function googleCallback() {
        $baseUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'https://smartreturns.com.br');
        $baseUrl = trim($baseUrl, "\"' ");

        $client = new GoogleClient();
        $client->setClientId(trim(getenv('GOOGLE_CLIENT_ID') ?: $_ENV['GOOGLE_CLIENT_ID'], "\"' "));
        $client->setClientSecret(trim(getenv('GOOGLE_CLIENT_SECRET') ?: $_ENV['GOOGLE_CLIENT_SECRET'], "\"' "));
        
        // A URI aqui deve ser IDÊNTICA à do googleLogin
        $client->setRedirectUri(rtrim($baseUrl, '/') . "/index.php?url=google/callback");

        if (isset($_GET['code'])) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                $client->setAccessToken($token);

                $googleService = new GoogleServiceOauth2($client);
                $googleUser = $googleService->userinfo->get();

                $data = [
                    'email' => $googleUser->email,
                    'name'  => $googleUser->name,
                    'google_id' => $googleUser->id 
                ];

                $user = $this->userModel->findOrCreateGoogleUser($data);

                if ($user) {
                    Auth::login($user); 
                    Session::setFlash('success', "Bem-vindo, " . $user['full_name']);
                    header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
                    exit;
                }
            } catch (Exception $e) {
                error_log("Google Login Error: " . $e->getMessage());
                Session::setFlash('error', 'Falha ao autenticar com o Google.');
            }
        }
        header('Location: /index.php?url=login');
        exit;
    } 
}