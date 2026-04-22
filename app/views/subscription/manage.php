<?php
/**
 * @var array|null  $activeSub       Assinatura ativa do usuário
 * @var array       $history         Histórico de assinaturas
 * @var bool        $refundEligible  Se ainda está na janela de 7 dias
 * @var int         $daysRemaining   Dias restantes
 * @var int         $usagePercent    % do período já consumido
 * @var float       $proratedCredit  Crédito proporcional para upgrade anual
 * @var array       $userData        Dados do usuário
 */
$title = 'Gerenciar Assinatura';
$meta_robots = 'noindex, nofollow';
ob_start();

// Status badge helper
$statusBadge = function(string $status): string {
    $map = [
        'active'     => ['bg-success',   'bi-check-circle-fill',      'Ativa'],
        'canceled'   => ['bg-warning',   'bi-x-circle-fill',          'Cancelada'],
        'expired'    => ['bg-secondary', 'bi-clock-history',          'Expirada'],
        'refunded'   => ['bg-info',      'bi-arrow-counterclockwise', 'Reembolsada'],
        'pending'    => ['bg-warning',   'bi-hourglass-split',        'Pendente'],
        'failed'     => ['bg-danger',    'bi-exclamation-circle-fill','Falhou'],
    ];
    [$bg, $icon, $label] = $map[$status] ?? ['bg-secondary', 'bi-question-circle', ucfirst($status)];
    return "<span class='badge {$bg}'><i class='bi {$icon} me-1'></i>{$label}</span>";
};

$planLabel = function(string $type): string {
    return $type === 'yearly' ? 'ðŸŒŸ Anual' : 'ðŸ“… Mensal';
};
?>

<div class="row justify-content-center">
<div class="col-lg-10">

    <!--  Header  -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Gerenciar Assinatura</h2>
            <p class="text-muted small mb-0">Controle total da sua assinatura PRO.</p>
        </div>
        <?php if (!$activeSub): ?>
            <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="btn btn-primary rounded-pill px-4 fw-bold">
                <i class="bi bi-gem me-2"></i>Assinar Plano PRO
            </a>
        <?php endif; ?>
    </div>

    <!--  Plano Atual  -->
    <?php if ($activeSub): ?>

    <?php
        $refundDeadline    = !empty($activeSub['refund_eligible_until']) ? strtotime($activeSub['refund_eligible_until']) : 0;
        $refundHoursLeft   = $refundEligible ? max(0, (int)(($refundDeadline - time()) / 3600)) : 0;
        $canceledEndPeriod = ($activeSub['status'] === 'canceled' && $activeSub['cancel_type'] === 'end_of_period');
    ?>

    <div class="row g-4 mb-4">
        <!-- Card plano -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Plano PRO <?= $planLabel($activeSub['plan_type']) ?></h5>
                            <p class="text-muted small mb-0">
                                ID Pagamento: <code><?= htmlspecialchars($activeSub['mp_payment_id'] ?? 'N/A') ?></code>
                            </p>
                        </div>
                        <?= $statusBadge($activeSub['status']) ?>
                    </div>

                    <?php if ($canceledEndPeriod): ?>
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Cancelamento agendado — acesso PRO até <strong><?= date('d/m/Y', strtotime($activeSub['expires_at'])) ?></strong>.
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <div class="small text-muted mb-1">Ativado em</div>
                                <div class="fw-bold"><?= date('d/m/Y', strtotime($activeSub['starts_at'])) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <div class="small text-muted mb-1">
                                    <?= $canceledEndPeriod ? 'Acesso até' : 'Renova em' ?>
                                </div>
                                <div class="fw-bold"><?= date('d/m/Y', strtotime($activeSub['expires_at'])) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <div class="small text-muted mb-1">Valor pago</div>
                                <div class="fw-bold text-primary">R$ <?= number_format($activeSub['amount_paid'], 2, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <div class="small text-muted mb-1">Dias restantes</div>
                                <div class="fw-bold <?= $daysRemaining <= 7 ? 'text-danger' : 'text-success' ?>">
                                    <?= $daysRemaining ?> dias
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de progresso do período -->
                    <div class="mb-1 d-flex justify-content-between small text-muted">
                        <span>Início</span>
                        <span><?= $daysRemaining ?> dias restantes</span>
                    </div>
                    <div class="progress mb-4" style="height: 8px;">
                        <div class="progress-bar <?= $usagePercent >= 85 ? 'bg-danger' : ($usagePercent >= 60 ? 'bg-warning' : 'bg-success') ?>"
                             style="width: <?= $usagePercent ?>%"
                             role="progressbar" title="<?= $usagePercent ?>% consumido"></div>
                    </div>

                    <!-- Ações -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($daysRemaining <= 30 || $canceledEndPeriod): ?>
                            <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>"
                               class="btn btn-primary rounded-pill btn-sm px-3 fw-bold">
                                <i class="bi bi-arrow-repeat me-1"></i>Renovar Assinatura
                            </a>
                        <?php endif; ?>

                        <?php if ($activeSub['plan_type'] === 'monthly' && $activeSub['status'] === 'active'): ?>
                            <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>"
                               class="btn btn-outline-primary rounded-pill btn-sm px-3">
                                <i class="bi bi-arrow-up-circle me-1"></i>
                                Upgrade Anual (economize R$ <?= number_format(max(0, 179.40 - $proratedCredit * 12), 2, ',', '.') ?>)
                            </a>
                        <?php endif; ?>

                        <?php if ($activeSub['status'] === 'active' && !$canceledEndPeriod): ?>
                            <button type="button"
                                    class="btn btn-outline-danger rounded-pill btn-sm px-3"
                                    data-bs-toggle="modal" data-bs-target="#modalCancel">
                                <i class="bi bi-x-circle me-1"></i>Cancelar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card garantia / reembolso -->
        <div class="col-md-5">
            <?php if ($refundEligible): ?>
            <div class="card border-warning shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <div class="bg-soft-warning rounded-circle d-inline-flex p-3 mb-2">
                            <i class="bi bi-shield-check fs-2 text-warning"></i>
                        </div>
                        <h6 class="fw-bold">Garantia de 7 Dias</h6>
                        <p class="small text-muted">Não ficou satisfeito? Reembolso total sem perguntas.</p>
                    </div>

                    <div class="text-center mb-3">
                        <div class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill" id="refundCountdown"
                             data-deadline="<?= $refundDeadline ?>">
                            <?= $refundHoursLeft ?>h restantes
                        </div>
                        <div class="small text-muted mt-1">
                            Prazo até: <?= date('d/m/Y H:i', $refundDeadline) ?>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-warning fw-bold rounded-pill"
                                data-bs-toggle="modal" data-bs-target="#modalRefund">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Solicitar Reembolso (R$ <?= number_format($activeSub['amount_paid'], 2, ',', '.') ?>)
                        </button>
                    </div>

                    <p class="text-muted smaller text-center mt-2 mb-0">
                        Reembolso via Mercado Pago em até 5 dias úteis.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0 bg-light shadow-sm rounded-4 h-100">
                <div class="card-body p-4 text-center">
                    <div class="bg-soft-success rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-gem fs-2 text-success"></i>
                    </div>
                    <h6 class="fw-bold">Plano PRO Ativo</h6>
                    <p class="small text-muted">
                        Aproveite todos os recursos avançados de análise e simulação de portfólios.
                    </p>
                    <a href="/index.php?url=<?= obfuscateUrl('dashboard') ?>" class="btn btn-success rounded-pill btn-sm px-4">
                        <i class="bi bi-house-door me-1"></i>Ir ao Dashboard
                    </a>
                    <?php if ($activeSub['status'] === 'active'): ?>
                    <div class="mt-3 pt-3 border-top">
                        <p class="smaller text-muted mb-1">
                            <i class="bi bi-info-circle me-1"></i>
                            A janela de reembolso (7 dias) expirou em
                            <?= date('d/m/Y', strtotime($activeSub['refund_eligible_until'])) ?>.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: /* sem assinatura ativa */ ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-5 text-center">
            <div class="bg-soft-secondary rounded-circle d-inline-flex p-4 mb-3">
                <i class="bi bi-star fs-1 text-secondary"></i>
            </div>
            <h4 class="fw-bold mb-2">Você está no Plano Starter</h4>
            <p class="text-muted mb-4">Desbloqueie recursos avançados assinando o Plano PRO.</p>
            <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold">
                <i class="bi bi-gem me-2"></i>Ver Planos PRO
            </a>
        </div>
    </div>

    <?php endif; ?>

    <!--  Histórico de Assinaturas  -->
    <?php if (!empty($history)): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Histórico de Assinaturas</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 small">Plano</th>
                            <th class="small">Status</th>
                            <th class="small">Valor</th>
                            <th class="small">Início</th>
                            <th class="small">Expiração</th>
                            <th class="small">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $sub): ?>
                        <tr>
                            <td class="ps-4 small fw-medium">
                                PRO <?= $sub['plan_type'] === 'yearly' ? 'Anual' : 'Mensal' ?>
                            </td>
                            <td><?= $statusBadge($sub['status']) ?></td>
                            <td class="small">R$ <?= number_format($sub['amount_paid'], 2, ',', '.') ?></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($sub['starts_at'])) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($sub['expires_at'])) ?></td>
                            <td class="small text-muted">
                                <?php if ($sub['status'] === 'refunded'): ?>
                                    Reembolso: R$ <?= number_format($sub['refund_amount'] ?? 0, 2, ',', '.') ?>
                                <?php elseif ($sub['status'] === 'canceled'): ?>
                                    <?= $sub['cancel_type'] === 'immediate' ? 'Imediato' : 'Fim do período' ?>
                                <?php elseif (!empty($sub['notes'])): ?>
                                    <?= htmlspecialchars($sub['notes']) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mb-4">
        <a href="javascript:history.back()" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>

</div><!-- /col -->
</div><!-- /row -->

<!--  Modal Cancelamento  -->
<?php if ($activeSub && $activeSub['status'] === 'active'): ?>
<div class="modal fade" id="modalCancel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-x-circle me-2"></i>Cancelar Assinatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/index.php?url=<?= obfuscateUrl('subscription/cancel') ?>">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <div class="modal-body px-4 pb-0">
                    <p class="text-muted">Tem certeza que deseja cancelar sua assinatura PRO?</p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Como deseja cancelar?</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check border rounded-3 p-3 cursor-pointer" onclick="document.getElementById('cancel_end').click()">
                                <input class="form-check-input" type="radio" name="cancel_type"
                                       id="cancel_end" value="end_of_period" checked>
                                <label class="form-check-label ms-1 cursor-pointer" for="cancel_end">
                                    <strong>No fim do período</strong> <span class="badge bg-soft-success text-success ms-1">Recomendado</span>
                                    <div class="small text-muted">
                                        Mantém acesso PRO até <?= date('d/m/Y', strtotime($activeSub['expires_at'])) ?>.
                                        Sem novas cobranças.
                                    </div>
                                </label>
                            </div>
                            <div class="form-check border rounded-3 p-3 cursor-pointer" onclick="document.getElementById('cancel_now').click()">
                                <input class="form-check-input" type="radio" name="cancel_type"
                                       id="cancel_now" value="immediate">
                                <label class="form-check-label ms-1 cursor-pointer" for="cancel_now">
                                    <strong>Imediatamente</strong>
                                    <div class="small text-muted">Acesso PRO encerrado agora. Sem reembolso.</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <?php if ($refundEligible): ?>
                    <div class="alert alert-warning small">
                        <i class="bi bi-lightbulb me-1"></i>
                        Você ainda está na janela de <strong>7 dias de garantia</strong>.
                        Considere solicitar o <strong>reembolso completo</strong> em vez de cancelar.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Manter Plano</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Confirmar Cancelamento</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!--  Modal Reembolso  -->
<?php if ($refundEligible): ?>
<div class="modal fade" id="modalRefund" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0 text-info"><i class="bi bi-arrow-counterclockwise me-2"></i>Solicitar Reembolso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/index.php?url=<?= obfuscateUrl('subscription/refund') ?>">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <div class="modal-body px-4">
                    <div class="text-center mb-4">
                        <div class="display-6 text-info fw-bold">
                            R$ <?= number_format($activeSub['amount_paid'], 2, ',', '.') ?>
                        </div>
                        <p class="text-muted small">Reembolso total da sua assinatura</p>
                    </div>

                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        O valor será estornado no cartão utilizado em até <strong>5 dias úteis</strong> após o processamento.
                        Seu acesso PRO será encerrado imediatamente após a confirmação.
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmRefund" required>
                        <label class="form-check-label small" for="confirmRefund">
                            Entendo que meu acesso PRO será encerrado imediatamente e o valor será estornado.
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white rounded-pill px-4 fw-bold" id="btnConfirmRefund" disabled>
                        Confirmar Reembolso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Countdown reembolso
(function() {
    const el = document.getElementById('refundCountdown');
    if (!el) return;
    const deadline = parseInt(el.dataset.deadline, 10) * 1000;
    function update() {
        const diff = deadline - Date.now();
        if (diff <= 0) { el.textContent = 'Expirado'; el.classList.replace('bg-warning','bg-secondary'); return; }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        el.textContent = h + 'h ' + String(m).padStart(2,'0') + 'min restantes';
    }
    update();
    setInterval(update, 60000);
})();

// Habilitar botão de reembolso apenas após checkbox
const chk = document.getElementById('confirmRefund');
const btn = document.getElementById('btnConfirmRefund');
if (chk && btn) {
    chk.addEventListener('change', function() {
        btn.disabled = !this.checked;
    });
}
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>


