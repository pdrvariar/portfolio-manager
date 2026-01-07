<?php
$title = 'Recuperar Senha - Portfolio Backtest';
ob_start();
?>
<div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-shield-lock text-primary fs-3"></i>
                    </div>
                    <h3 class="fw-bold">Recuperar Senha</h3>
                    <p class="text-muted">Insira seu e-mail abaixo e enviaremos um link para você redefinir sua senha.</p>
                </div>

                <form method="POST" action="/index.php?url=forgot-password">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Seu E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>
                            <input type="email" name="email" class="form-control bg-light border-start-0" 
                                   placeholder="seu@email.com" required autofocus>
                        </div>
                        <div class="form-text mt-2 small">
                            Verifique sua caixa de entrada e a pasta de spam após o envio.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm">
                        <i class="bi bi-send-fill me-2"></i> Enviar Link de Recuperação
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="/index.php?url=login" class="text-decoration-none small fw-bold">
                        <i class="bi bi-arrow-left me-1"></i> Voltar para o Login
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-muted small">Portfolio Backtest &copy; <?= date('Y'); ?></p>
        </div>
    </div>
</div>
<?php
// Captura o conteúdo acima e o injeta no layout principal
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>