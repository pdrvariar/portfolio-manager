<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Portfolio Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <div class="logo">
                        <h1><i class="bi bi-graph-up"></i> Portfolio Manager</h1>
                        <p class="text-muted">Crie sua conta para começar</p>
                    </div>
                    
                    <?php if (!empty($_SESSION['errors'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="/auth/register" id="registerForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome Completo *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                                           required minlength="3">
                                    <div class="form-text">Mínimo 3 caracteres</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                           required>
                                    <div class="form-text">Usaremos para login e notificações</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Senha *</label>
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
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Senha *</label>
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
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Aceito os <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Termos de Uso</a> 
                                e <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Política de Privacidade</a>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '' ?>"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="bi bi-person-plus"></i> Criar Conta
                        </button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Já tem uma conta? <a href="/auth/login">Faça login aqui</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Termos de Uso -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Termos de Uso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Aceitação dos Termos</h6>
                    <p>Ao acessar e usar o Portfolio Manager, você concorda com estes termos.</p>
                    
                    <h6>2. Uso do Serviço</h6>
                    <p>Você concorda em usar o serviço apenas para fins legais e de acordo com estes termos.</p>
                    
                    <h6>3. Conta do Usuário</h6>
                    <p>Você é responsável por manter a confidencialidade de sua conta e senha.</p>
                    
                    <h6>4. Dados Financeiros</h6>
                    <p>As simulações são para fins educacionais e não constituem aconselhamento financeiro.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Política de Privacidade -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Política de Privacidade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Coleta de Dados</h6>
                    <p>Coletamos apenas dados necessários para o funcionamento do serviço.</p>
                    
                    <h6>2. Uso dos Dados</h6>
                    <p>Seus dados são usados apenas para fornecer e melhorar o serviço.</p>
                    
                    <h6>3. Segurança</h6>
                    <p>Implementamos medidas de segurança para proteger seus dados.</p>
                    
                    <h6>4. Cookies</h6>
                    <p>Usamos cookies para melhorar sua experiência de usuário.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
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
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Você deve aceitar os Termos de Uso e Política de Privacidade');
                return;
            }
            
            // Desabilitar botão para evitar múltiplos envios
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Criando conta...';
        });
    </script>
</body>
</html>