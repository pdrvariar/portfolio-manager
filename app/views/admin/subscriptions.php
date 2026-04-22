<?php
/**
 * @var array  $subscriptions Lista de assinaturas com dados do usuário
 * @var array  $stats         Estatísticas de receita e status
 */
$title = 'Gestão de Assinaturas';
$meta_robots = 'noindex, nofollow';
ob_start();

$statusBadge = function(string $status): string {
    $map = [
        'active'   => ['bg-success',   'bi-check-circle-fill',      'Ativa'],
        'canceled' => ['bg-warning',   'bi-x-circle-fill',          'Cancelada'],
        'expired'  => ['bg-secondary', 'bi-clock-history',          'Expirada'],
        'refunded' => ['bg-info',      'bi-arrow-counterclockwise', 'Reembolsada'],
        'pending'  => ['bg-warning',   'bi-hourglass-split',        'Pendente'],
        'failed'   => ['bg-danger',    'bi-exclamation-circle',     'Falhou'],
    ];
    [$bg, $icon, $label] = $map[$status] ?? ['bg-secondary','bi-question-circle', ucfirst($status)];
    return "<span class='badge {$bg} rounded-pill'><i class='bi {$icon} me-1'></i>{$label}</span>";
};
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Gestão de Assinaturas</h2>
        <p class="text-muted small mb-0">Visão financeira e controle de ciclo de vida.</p>
    </div>
    <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-2 fw-bold">ADMIN</span>
</div>

<!--  Métricas  -->
<div class="row g-3 mb-5">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-3">
            <div class="small text-muted fw-bold text-uppercase mb-1">MRR</div>
            <div class="h5 fw-bold text-primary mb-0">R$ <?= number_format($stats['mrr'] ?? 0, 2, ',', '.') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-3">
            <div class="small text-muted fw-bold text-uppercase mb-1">Ativas</div>
            <div class="h5 fw-bold text-success mb-0"><?= $stats['active_count'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-3">
            <div class="small text-muted fw-bold text-uppercase mb-1">Novas (30d)</div>
            <div class="h5 fw-bold text-info mb-0"><?= $stats['new_30d'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-3">
            <div class="small text-muted fw-bold text-uppercase mb-1">Canceladas (30d)</div>
            <div class="h5 fw-bold text-warning mb-0"><?= $stats['cancels_30d'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-3">
            <div class="small text-muted fw-bold text-uppercase mb-1">Reembolsos</div>
            <div class="h5 fw-bold text-danger mb-0">R$ <?= number_format($stats['total_refunded'] ?? 0, 2, ',', '.') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-3">
            <div class="small text-muted fw-bold text-uppercase mb-1">Receita Total</div>
            <div class="h5 fw-bold mb-0">R$ <?= number_format($stats['total_revenue'] ?? 0, 2, ',', '.') ?></div>
        </div>
    </div>
</div>

<!--  Tabela de Assinaturas  -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-credit-card me-2 text-primary"></i>Todas as Assinaturas</h5>
        <input type="text" id="subSearch" class="form-control form-control-sm w-auto" placeholder="ðŸ” Buscar usuário...">
    </div>
    <div class="card-body p-0">
        <?php if (empty($subscriptions)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                Nenhuma assinatura registrada ainda.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="subTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 small">Usuário</th>
                        <th class="small">Plano</th>
                        <th class="small">Status</th>
                        <th class="small">Valor</th>
                        <th class="small">Início</th>
                        <th class="small">Expiração</th>
                        <th class="small">Payment ID</th>
                        <th class="small text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subscriptions as $sub): ?>
                <tr class="sub-row" data-user="<?= strtolower($sub['email'] . ' ' . $sub['full_name']) ?>">
                    <td class="ps-4">
                        <div class="fw-bold small"><?= htmlspecialchars($sub['full_name']) ?></div>
                        <div class="text-muted smaller"><?= htmlspecialchars($sub['email']) ?></div>
                    </td>
                    <td class="small">PRO <?= $sub['plan_type'] === 'yearly' ? 'Anual' : 'Mensal' ?></td>
                    <td><?= $statusBadge($sub['status']) ?></td>
                    <td class="small fw-medium">R$ <?= number_format($sub['amount_paid'], 2, ',', '.') ?>
                        <?php if ($sub['status'] === 'refunded'): ?>
                            <div class="smaller text-danger">Reemb: R$ <?= number_format($sub['refund_amount'] ?? 0, 2, ',', '.') ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= date('d/m/Y', strtotime($sub['starts_at'])) ?></td>
                    <td class="small text-muted"><?= date('d/m/Y', strtotime($sub['expires_at'])) ?>
                        <?php
                        $daysLeft = (int)ceil((strtotime($sub['expires_at']) - time()) / 86400);
                        if ($sub['status'] === 'active' && $daysLeft <= 7 && $daysLeft > 0):
                        ?>
                            <span class="badge bg-warning text-dark smaller"><?= $daysLeft ?>d</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted">
                        <code><?= $sub['mp_payment_id'] ? substr($sub['mp_payment_id'], 0, 10) . '™' : '—' ?></code>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex gap-1 justify-content-end">
                            <?php if ($sub['status'] === 'active'): ?>
                            <button class="btn btn-outline-warning btn-sm rounded-pill px-2"
                                    title="Cancelar assinatura"
                                    onclick="adminAction('cancel', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['full_name']) ?>')">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            <?php endif; ?>

                            <?php if ($sub['status'] === 'active' && !empty($sub['mp_payment_id'])): ?>
                            <button class="btn btn-outline-info btn-sm rounded-pill px-2"
                                    title="Processar reembolso"
                                    onclick="adminAction('refund', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['full_name']) ?>')">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <?php endif; ?>

                            <?php if (in_array($sub['status'], ['canceled','expired','failed'])): ?>
                            <button class="btn btn-outline-success btn-sm rounded-pill px-2"
                                    title="Reativar assinatura"
                                    onclick="adminAction('reactivate', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['full_name']) ?>')">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de ação admin -->
<div class="modal fade" id="modalAdminAction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h6 class="fw-bold mb-0" id="modalActionTitle">Confirmar Ação</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adminActionForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <div class="modal-body px-4">
                    <p class="small text-muted mb-0" id="modalActionMsg"></p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill btn-sm px-3 fw-bold" id="btnAdminConfirm">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Busca na tabela
document.getElementById('subSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.sub-row').forEach(row => {
        row.style.display = row.dataset.user.includes(q) ? '' : 'none';
    });
});

// Ação admin (cancel/refund/reactivate)
function adminAction(type, id, userName) {
    const form   = document.getElementById('adminActionForm');
    const title  = document.getElementById('modalActionTitle');
    const msg    = document.getElementById('modalActionMsg');
    const btn    = document.getElementById('btnAdminConfirm');

    const baseUrl = '/index.php?url=<?= obfuscateUrl('admin/subscriptions') ?>/';
    const config  = {
        cancel:     { url: 'cancel/',     label: 'Cancelar',   color: 'btn-warning',  msg: `Cancelar a assinatura de <strong>${userName}</strong> imediatamente?` },
        refund:     { url: 'refund/',     label: 'Reembolsar', color: 'btn-info',     msg: `Processar reembolso da assinatura de <strong>${userName}</strong>?` },
        reactivate: { url: 'reactivate/', label: 'Reativar',   color: 'btn-success',  msg: `Reativar a assinatura de <strong>${userName}</strong>?` },
    };
    const c = config[type];
    form.action = '/index.php?url=<?= rawurlencode(obfuscateUrl('admin/subscriptions')) ?>/' + type + '/' + id;
    title.textContent = c.label + ' — ' + userName;
    msg.innerHTML     = c.msg;
    btn.className     = 'btn rounded-pill btn-sm px-3 fw-bold ' + c.color;
    btn.textContent   = c.label;

    new bootstrap.Modal(document.getElementById('modalAdminAction')).show();
}
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>


