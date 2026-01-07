<?php
$title = 'Registro - Portfolio Backtest';
ob_start();
?>
<div class="row justify-content-center min-vh-100 align-items-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Crie sua conta gratuita</h3>
                    <p class="text-muted">Junte-se a milhares de investidores</p>
                </div>

                <div class="d-grid mb-4">
                    <a href="/index.php?url=google-auth" class="btn btn-outline-dark py-2 rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-google me-2 text-danger"></i> Cadastrar com Google
                    </a>
                </div>

                <div class="position-relative mb-4">
                    <hr>
                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small">OU</span>
                </div>

                <form method="POST" action="/index.php?url=register">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nome Completo</label>
                            <input type="text" name="full_name" class="form-control" placeholder="Ex: João Silva" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="joao@email.com" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telefone</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="(11) 99999-9999" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Data de Nascimento</label>
                            <input type="date" name="birth_date" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Usuário (Nick)</label>
                            <input type="text" name="username" class="form-control" placeholder="joaosilva123" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Senha</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirmar Senha</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 mt-4 fw-bold rounded-3">Criar minha conta</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>