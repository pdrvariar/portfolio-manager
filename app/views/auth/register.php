<?php
$title = 'Registro - Portfolio Backtest';
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Criar Conta</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/register">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Registrar</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p class="mb-0">Já tem uma conta? <a href="/login">Faça login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>