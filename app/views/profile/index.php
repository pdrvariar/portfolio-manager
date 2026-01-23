<?php
/**
 * @var array $user Dados formatados do utilizador para exibição no perfil
 */
$title = 'Meu Perfil';
ob_start();
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 text-center p-4 h-100">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <div class="bg-soft-primary rounded-circle p-1">
                    <i class="bi bi-person-circle text-primary" style="font-size: 5rem;"></i>
                </div>
                <?php if ($user['verified']): ?>
                    <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success border border-white border-3">
                        <i class="bi bi-check-lg"></i>
                    </span>
                <?php endif; ?>
            </div>
            
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h4>
            <p class="text-muted small mb-3">@<?= htmlspecialchars($user['username']) ?></p>
            
            <div class="d-flex justify-content-center gap-2 mb-4">
                <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill">
                    <?= htmlspecialchars($user['role']) ?>
                </span>
                <span class="badge <?= $user['verified'] ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' ?> px-3 py-2 rounded-pill">
                    <i class="bi <?= $user['verified'] ? 'bi-patch-check-fill' : 'bi-exclamation-triangle' ?> me-1"></i>
                    <?= $user['verified'] ? 'Conta Verificada' : 'E-mail Pendente' ?>
                </span>
            </div>

            <hr class="opacity-10">

            <div class="text-start">
                <label class="small text-muted text-uppercase fw-bold">E-mail Principal</label>
                <p class="text-dark fw-medium"><?= htmlspecialchars($user['email']) ?></p>
                
                <label class="small text-muted text-uppercase fw-bold">Membro desde</label>
                <p class="text-dark fw-medium mb-0">
                    <?= !empty($user['created_at']) ? formatFullDate($user['created_at']) : 'N/A' ?>
                </p>
            </div>    
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>Informações de Segurança e Perfil</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?url=profile/update" id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Nome Completo</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Telefone (Brasil)</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="(11) 99999-9999">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Data de Nascimento</label>
                            <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($user['birth_date']) ?>" required>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-soft-secondary d-flex align-items-center mb-0" style="background: #f8f9fa;">
                                <i class="bi bi-info-circle-fill me-2 text-primary"></i>
                                <small class="text-muted">O seu endereço de e-mail é utilizado para login e segurança, por isso não pode ser alterado diretamente.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm fw-bold">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div>
                    <h6 class="fw-bold mb-1">Segurança da Conta</h6>
                    <p class="text-muted small mb-0">Deseja alterar sua senha de acesso?</p>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPassword">
                    Alterar Senha
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/imask"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        IMask(document.getElementById('phone'), {
            mask: '(00) 00000-0000'
        });
    });
</script>

<div class="modal fade" id="modalPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Alterar Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/index.php?url=profile/change-password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Senha Atual</label>
                        <input type="password" name="current_password" class="form-control bg-light border-0" required>
                    </div>
                    <hr class="my-4 opacity-10">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nova Senha</label>
                        <input type="password" name="new_password" class="form-control bg-light border-0" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Confirmar Nova Senha</label>
                        <input type="password" name="confirm_password" class="form-control bg-light border-0" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Atualizar Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>