<?php
$title = 'Nova Senha - Portfolio Backtest';
ob_start();
?>
<div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-key text-success fs-3"></i>
                    </div>
                    <h3 class="fw-bold">Nova Senha</h3>
                    <p class="text-muted">Crie uma senha forte para proteger os seus investimentos.</p>
                </div>

                <form method="POST" action="/index.php?url=reset-password">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">
                    
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nova Senha</label>
                        <input type="password" name="password" class="form-control bg-light" 
                               placeholder="Mínimo 6 caracteres" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirmar Nova Senha</label>
                        <input type="password" name="confirm_password" class="form-control bg-light" 
                               placeholder="Repita a senha" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm">
                        <i class="bi bi-check-lg me-2"></i> Alterar Senha
                    </button>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted small">Lembrou a senha? <a href="/index.php?url=login" class="fw-bold">Fazer Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>