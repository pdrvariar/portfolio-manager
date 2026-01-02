<?php
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: /auth/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Portfolio Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            margin: 0 auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #333;
            font-weight: 700;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 2px;
        }
        .strength-0 { width: 0%; background-color: #dc3545; }
        .strength-1 { width: 25%; background-color: #dc3545; }
        .strength-2 { width: 50%; background-color: #ffc107; }
        .strength-3 { width: 75%; background-color: #28a745; }
        .strength-4 { width: 100%; background-color: #28a745; }
        .token-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="reset-card">
                    <div class="logo">
                        <h1><i class="bi bi-graph-up"></i></h1>
                        <h2>Redefinir Senha</h2>
                        <p class="text-muted">Crie uma nova senha para sua conta</p>
                    </div>
                    
                    <div class="token-info">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-key me-2"></i>
                            <small>Token de redefinição: <?= substr($token, 0, 8) ?>...<?= substr($token, -8) ?></small>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-clock"></i> Este link expira em 1 hora
                        </small>
                    </div>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="/auth/reset-password?token=<?= urlencode($token) ?>" id="resetForm">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Nova Senha *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" 
                                       name="password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" 
                                        id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2" id="passwordStrength"></div>
                            <div class="form-text" id="passwordFeedback"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" 
                                        id="toggleConfirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text" id="passwordMatch"></div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '' ?>"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                            <i class="bi bi-key"></i> Redefinir Senha
                        </button>
                        
                        <div class="text-center">
                            <a href="/auth/login" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Voltar para o login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        // Alternar visibilidade da senha
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPassword = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (confirmPassword.type === 'password') {
                confirmPassword.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                confirmPassword.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Validar força da senha
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const feedback = document.getElementById('passwordFeedback');
            const confirmPassword = document.getElementById('confirm_password');
            const matchDiv = document.getElementById('passwordMatch');
            
            // Calcular força
            let strength = 0;
            let feedbackText = '';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Atualizar barra
            strengthBar.className = 'password-strength strength-' + strength;
            
            // Feedback
            switch(strength) {
                case 0:
                case 1:
                    feedbackText = '<span class="text-danger">Senha muito fraca</span>';
                    break;
                case 2:
                    feedbackText = '<span class="text-warning">Senha fraca</span>';
                    break;
                case 3:
                    feedbackText = '<span class="text-info">Senha média</span>';
                    break;
                case 4:
                    feedbackText = '<span class="text-success">Senha forte</span>';
                    break;
                case 5:
                    feedbackText = '<span class="text-success">Senha muito forte</span>';
                    break;
            }
            
            feedback.innerHTML = feedbackText;
            
            // Verificar se as senhas coincidem
            if (confirmPassword.value) {
                if (password === confirmPassword.value) {
                    matchDiv.innerHTML = '<span class="text-success">Senhas coincidem</span>';
                } else {
                    matchDiv.innerHTML = '<span class="text-danger">Senhas não coincidem</span>';
                }
            }
        });

        // Validar confirmação de senha
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (this.value === password) {
                matchDiv.innerHTML = '<span class="text-success">Senhas coincidem</span>';
            } else {
                matchDiv.innerHTML = '<span class="text-danger">Senhas não coincidem</span>';
            }
        });

        // Validar formulário antes de enviar
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 8 caracteres!');
                return;
            }
            
            // Verificar força mínima
            const strength = this.calculatePasswordStrength(password);
            if (strength < 2) {
                e.preventDefault();
                alert('A senha é muito fraca. Use letras maiúsculas, minúsculas e números.');
                return;
            }
            
            // Desabilitar botão para evitar múltiplos envios
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Redefinindo...';
        });

        // Função para calcular força da senha
        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }
        
        // Verificar token na URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        if (!token) {
            alert('Token inválido. Redirecionando para a página de login...');
            window.location.href = '/auth/login';
        }
    </script>
</body>
</html>