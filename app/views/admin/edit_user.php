<?php
/**
 * @var array $user Dados do usuário a ser editado
 */
$title = 'Editar Usuário: ' . htmlspecialchars($user['username']);
ob_start();
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-gear me-2 text-primary"></i>Editar Usuário</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('admin/users/update/' . $user['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome de Usuário</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <div class="form-text">O nome de usuário não pode ser alterado.</div>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label fw-bold">Nome Completo</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-bold">Status da Conta</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="is_admin">Privilégios de Administrador</label>
                        </div>
                        <div class="form-text text-danger">Atenção: Usuários administradores têm acesso total ao sistema.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <a href="/index.php?url=<?php echo obfuscateUrl('admin/users'); ?>" class="btn btn-light px-4">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
