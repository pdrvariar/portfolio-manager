<?php
/**
 * @var array $user Dados do usuÃ¡rio a ser editado
 */
$title = 'Editar UsuÃ¡rio: ' . htmlspecialchars($user['username']);`n$meta_robots = 'noindex, nofollow';

$breadcrumbs = [
    ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=' . obfuscateUrl('dashboard')],
    ['label' => 'Admin', 'url' => '/index.php?url=' . obfuscateUrl('admin')],
    ['label' => 'UsuÃ¡rios', 'url' => '/index.php?url=' . obfuscateUrl('admin/users')],
    ['label' => htmlspecialchars($user['username']), 'url' => '#'],
];

ob_start();
?>

<div class="row">
    <div class="col-lg-7 col-md-9 mx-auto">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-gear me-2 text-primary"></i>Editar UsuÃ¡rio</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('admin/users/update/' . $user['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                    
                    <!-- Dados bÃ¡sicos -->
                    <h6 class="text-uppercase text-muted small fw-bold mb-3 border-bottom pb-2">Dados da Conta</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome de UsuÃ¡rio</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <div class="form-text">O nome de usuÃ¡rio nÃ£o pode ser alterado.</div>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label fw-bold">Nome Completo</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="status" class="form-label fw-bold">Status da Conta</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active"    <?php echo $user['status'] === 'active'    ? 'selected' : ''; ?>>Ativo</option>
                                <option value="pending"   <?php echo $user['status'] === 'pending'   ? 'selected' : ''; ?>>Pendente</option>
                                <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspenso</option>
                            </select>
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="is_admin">PrivilÃ©gios de Administrador</label>
                                </div>
                                <div class="form-text text-danger">AtenÃ§Ã£o: acesso total ao sistema.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Assinatura -->
                    <h6 class="text-uppercase text-muted small fw-bold mb-3 border-bottom pb-2 mt-4">Plano &amp; Assinatura</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="plan" class="form-label fw-bold">Plano</label>
                            <select class="form-select" id="plan" name="plan">
                                <option value="starter" <?php echo ($user['plan'] ?? 'starter') === 'starter' ? 'selected' : ''; ?>>Starter (Gratuito)</option>
                                <option value="pro"     <?php echo ($user['plan'] ?? '') === 'pro'     ? 'selected' : ''; ?>>PRO</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="subscription_plan_type" class="form-label fw-bold">Tipo de Assinatura</label>
                            <select class="form-select" id="subscription_plan_type" name="subscription_plan_type">
                                <option value="">â€” Nenhum â€”</option>
                                <option value="monthly" <?php echo ($user['subscription_plan_type'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Mensal</option>
                                <option value="yearly"  <?php echo ($user['subscription_plan_type'] ?? '') === 'yearly'  ? 'selected' : ''; ?>>Anual</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="subscription_expires_at" class="form-label fw-bold">Data de ExpiraÃ§Ã£o</label>
                            <?php
                                $expiresVal = '';
                                if (!empty($user['subscription_expires_at'])) {
                                    $expiresVal = date('Y-m-d\TH:i', strtotime($user['subscription_expires_at']));
                                }
                            ?>
                            <input type="datetime-local" class="form-control" id="subscription_expires_at" name="subscription_expires_at" value="<?php echo $expiresVal; ?>">
                            <div class="form-text">Deixe em branco para remover a expiraÃ§Ã£o.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="last_payment_id" class="form-label fw-bold">ID do Ãšltimo Pagamento</label>
                            <input type="text" class="form-control" id="last_payment_id" name="last_payment_id" value="<?php echo htmlspecialchars($user['last_payment_id'] ?? ''); ?>" placeholder="Ex: MP-123456">
                        </div>
                    </div>

                    <!-- Atalhos rÃ¡pidos de extensÃ£o de assinatura -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Atalhos de AtivaÃ§Ã£o PRO</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProExpiry(1)">+1 mÃªs</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProExpiry(3)">+3 meses</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProExpiry(6)">+6 meses</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProExpiry(12)">+12 meses</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSubscription()">Cancelar / Remover PRO</button>
                        </div>
                        <div class="form-text">Os atalhos alteram os campos acima â€” ainda Ã© necessÃ¡rio salvar.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="/index.php?url=<?php echo obfuscateUrl('admin/users'); ?>" class="btn btn-light px-4">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Salvar AlteraÃ§Ãµes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function setProExpiry(months) {
    const now = new Date();
    now.setMonth(now.getMonth() + months);
    const formatted = now.toISOString().slice(0, 16); // YYYY-MM-DDTHH:mm
    document.getElementById('subscription_expires_at').value = formatted;
    document.getElementById('plan').value = 'pro';
    document.getElementById('subscription_plan_type').value = months >= 12 ? 'yearly' : 'monthly';
}
function clearSubscription() {
    document.getElementById('plan').value = 'starter';
    document.getElementById('subscription_expires_at').value = '';
    document.getElementById('subscription_plan_type').value = '';
    document.getElementById('last_payment_id').value = '';
}
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>

