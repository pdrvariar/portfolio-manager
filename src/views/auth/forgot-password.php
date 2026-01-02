<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Portfolio Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .forgot-card {
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
        .back-link {
            text-decoration: none;
            color: #666;
        }
        .back-link:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="forgot-card">
                    <div class="logo">
                        <h1><i class="bi bi-graph-up"></i></h1>
                        <h2>Recuperar Senha</h2>
                        <p class="text-muted">Digite seu email para receber instruções</p>
                    </div>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="/auth/forgot-password" id="forgotForm">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Cadastrado</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                       required placeholder="seu@email.com">
                            </div>
                            <div class="form-text">
                                Enviaremos um link para redefinir sua senha
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '' ?>"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                            <i class="bi bi-send"></i> Enviar Instruções
                        </button>
                        
                        <div class="text-center">
                            <a href="/auth/login" class="back-link">
                                <i class="bi bi-arrow-left"></i> Voltar para o login
                            </a>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="text-muted small">
                            <i class="bi bi-info-circle"></i> O link de recuperação expira em 1 hora
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        // Prevenir múltiplos envios
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const email = document.getElementById('email').value;
            
            // Validação básica de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Por favor, insira um email válido');
                return;
            }
            
            // Desabilitar botão
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
        });
    </script>
</body>
</html>