<?php
$title = 'Entrar - Portfolio Backtest';
ob_start();
?>
<div class="row justify-content-center min-vh-100 align-items-center">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Bem-vindo de volta</h3>
                    <p class="text-muted">Aceda à sua conta de investidor</p>
                </div>

                <div class="d-grid mb-4">
                    <a href="/index.php?url=google-auth" class="btn btn-outline-dark py-2 rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-google me-2 text-danger"></i>
                        Entrar com Google
                    </a>
                </div>

                <div class="position-relative mb-4">
                    <hr>
                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small">OU</span>
                </div>

                <form method="POST" action="/index.php?url=login">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Usuário ou E-mail</label>
                        <input type="text" name="username" class="form-control" placeholder="Seu nick ou e-mail" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label fw-semibold">Senha</label>
                            <a href="/index.php?url=forgot-password" class="small text-decoration-none">Esqueceu a senha?</a>
                        </div>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 mt-2 fw-bold rounded-3">Entrar</button>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted small">Não tem uma conta? <a href="/index.php?url=register" class="fw-bold">Registre-se</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>