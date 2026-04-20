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
                    <p class="text-muted">Entre na sua conta de investidor</p>
                </div>

                <div class="d-grid mb-4">
                    <a href="/index.php?url=google-auth" class="btn btn-google py-2 rounded-3 d-flex align-items-center justify-content-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" style="flex-shrink:0;">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.36-8.16 2.36-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            <path fill="none" d="M0 0h48v48H0z"/>
                        </svg>
                        Entrar com Google
                    </a>
                </div>

                <div class="position-relative mb-4">
                    <hr class="google-divider-hr">
                    <span class="position-absolute top-50 start-50 translate-middle px-3 text-muted small google-divider-label">OU</span>
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

$additional_css = '
<style>
/* ── Botão Google ── */
.btn-google {
    background: #fff;
    color: #3c4043;
    border: 1px solid #dadce0;
    font-weight: 600;
    font-size: .9rem;
    transition: background .2s, box-shadow .2s;
}
.btn-google:hover {
    background: #f8f9fa;
    box-shadow: 0 1px 6px rgba(0,0,0,.15);
    color: #3c4043;
}
[data-theme="dark"] .btn-google {
    background: #2d2d2d;
    color: #e8eaed;
    border-color: #5f6368;
}
[data-theme="dark"] .btn-google:hover {
    background: #3c3c3c;
    color: #e8eaed;
    box-shadow: 0 1px 6px rgba(0,0,0,.4);
}

/* ── Divisor OU ── */
.google-divider-label {
    background: var(--bg-card, #fff);
}
[data-theme="dark"] .google-divider-label {
    background: var(--bg-card);
}
</style>
';

include_once __DIR__ . '/../layouts/main.php';
?>