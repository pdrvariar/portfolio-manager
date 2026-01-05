<?php
$title = 'Meu Perfil';
ob_start(); // Inicia a captura do conteúdo
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 text-center p-4">
            <div class="mb-3">
                <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
            </div>
            <h4 class="mb-0"><?= htmlspecialchars($user['name'] ?? '') ?></h4>
            <p class="text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            <span class="badge bg-primary px-3"><?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?></span>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Editar Dados</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <form method="POST" action="/index.php?url=profile/update">
                    <!-- TODO: Implementar Token CSRF aqui para segurança -->
                    <!-- <input type="hidden" name="csrf_token" value="<?php // echo $_SESSION['csrf_token']; ?>"> -->
                    <!-- TODO: Implementar Token CSRF -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean(); // Fecha a captura
include_once __DIR__ . '/../layouts/main.php'; // Injeta no layout que tem o menu
?>