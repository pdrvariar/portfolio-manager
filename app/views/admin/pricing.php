<?php
/**
 * @var array $activePrices  Preços ativos por plan_type
 * @var array $history       Histórico de preços por plan_type
 * @var array $installments  Config de parcelamento por plan_type
 */
$title = 'Gestão de Preços';
$meta_robots = 'noindex, nofollow';
ob_start();

$plans = [
    'monthly' => ['label' => 'Mensal',  'icon' => 'bi-calendar-month', 'color' => 'primary'],
    'yearly'  => ['label' => 'Anual',   'icon' => 'bi-calendar-year',  'color' => 'success'],
];

$monthlyInstall = SubscriptionPlan::calculateInstallments(
    (float)($activePrices['monthly']['price'] ?? 29.90),
    $installments['monthly']
);
$yearlyInstall = SubscriptionPlan::calculateInstallments(
    (float)($activePrices['yearly']['price'] ?? 179.40),
    $installments['yearly']
);
$installmentPreviews = ['monthly' => $monthlyInstall, 'yearly' => $yearlyInstall];
?>

<style>
.pricing-card { border: 2px solid transparent; transition: border-color .2s; }
.pricing-card:hover { border-color: var(--bs-primary); }
.history-row-new { animation: fadeInRow .5s ease; }
@keyframes fadeInRow { from {opacity:0; transform:translateY(-4px);} to {opacity:1; transform:translateY(0);} }
.badge-plan-type { font-size: .72rem; letter-spacing:.04em; }
.install-bar { height: 6px; border-radius: 3px; background: #e9ecef; }
.install-bar-fill { height: 6px; border-radius: 3px; background: linear-gradient(90deg, #0d6efd, #6ea8fe); transition: width .4s; }
.promo-chip { display:inline-flex; align-items:center; gap:.35rem; background:#fff7e6; border:1px solid #ffc107; color:#856404; border-radius:100px; padding:.2rem .7rem; font-size:.8rem; font-weight:600; }
[data-theme="dark"] .promo-chip { background:#2e2300; border-color:#ffc107; color:#ffc107; }
.section-divider { border-top: 2px dashed #dee2e6; margin: 2rem 0; }
[data-theme="dark"] .section-divider { border-color: #3d3d3d; }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-tags-fill me-2 text-primary"></i>Gestão de Preços</h2>
        <p class="text-muted small mb-0">Configure preços, parcelamentos e promoções com prazo. Cada alteração de preço gera histórico completo.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?url=<?= obfuscateUrl('admin/coupons') ?>" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-ticket-perforated me-1"></i>Cupons
        </a>
        <a href="/index.php?url=<?= obfuscateUrl('admin/subscriptions') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-credit-card me-1"></i>Assinaturas
        </a>
    </div>
</div>

<?php foreach (['monthly','yearly'] as $planType): ?>
<?php
    $plan       = $activePrices[$planType] ?? [];
    $cfg        = $installments[$planType] ?? [];
    $hist       = $history[$planType]      ?? [];
    $pi         = $installmentPreviews[$planType];
    $pInfo      = $plans[$planType];
    $price      = (float)($plan['price'] ?? 0);
    $origPrice  = !empty($plan['original_price']) ? (float)$plan['original_price'] : null;
    $label      = $plan['label'] ?? '';
    $isPromo    = !empty($plan['effective_until']);
    $color      = $pInfo['color'];
    $maxInst    = (int)($cfg['max_installments'] ?? 1);
    $freeInst   = (int)($cfg['interest_free_up_to'] ?? 1);
    $rate       = (float)($cfg['monthly_interest_rate'] ?? 0);
?>
<div class="card border-0 shadow-sm rounded-4 mb-4 pricing-card">
    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-<?= $color ?> bg-opacity-10 rounded-3 p-2">
                <i class="bi <?= $pInfo['icon'] ?> text-<?= $color ?> fs-5"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">Plano <?= $pInfo['label'] ?></h5>
                <small class="text-muted">
                    <?php if ($isPromo): ?>
                        <span class="text-warning fw-bold"><i class="bi bi-clock-history me-1"></i>Promoção ativa até <?= date('d/m/Y H:i', strtotime($plan['effective_until'])) ?></span>
                    <?php else: ?>
                        Preço padrão ativo desde <?= date('d/m/Y', strtotime($plan['effective_from'] ?? 'now')) ?>
                    <?php endif; ?>
                </small>
            </div>
        </div>
        <div class="text-end">
            <?php if ($label): ?><span class="promo-chip me-2"><i class="bi bi-star-fill"></i><?= htmlspecialchars($label) ?></span><?php endif; ?>
            <span class="display-6 fw-bold text-<?= $color ?>">R$ <?= number_format($price, 2, ',', '.') ?></span>
            <?php if ($origPrice): ?><div class="text-muted small text-decoration-line-through">R$ <?= number_format($origPrice, 2, ',', '.') ?></div><?php endif; ?>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="row g-4">

            <!-- ■ Form: Alterar Preço -->
            <div class="col-lg-6">
                <h6 class="fw-bold mb-3 text-uppercase small text-muted"><i class="bi bi-pencil-square me-1"></i>Alterar Preço</h6>
                <form method="POST" action="/index.php?url=<?= obfuscateUrl('admin/pricing/update') ?>" id="form-price-<?= $planType ?>">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="price">
                    <input type="hidden" name="plan_type" value="<?= $planType ?>">

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Novo Preço (R$) *</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">R$</span>
                                <input type="number" name="price" class="form-control" step="0.01" min="0.01"
                                       value="<?= number_format($price, 2, '.', '') ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Preço "De" (riscado)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">R$</span>
                                <input type="number" name="original_price" class="form-control" step="0.01" min="0"
                                       value="<?= $origPrice ? number_format($origPrice, 2, '.', '') : '' ?>" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Apelido / Label <span class="text-muted">(ex: Black Friday 🔥)</span></label>
                        <input type="text" name="label" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($label) ?>" placeholder="Ex: Promoção de Lançamento, Cyber Monday...">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold"><i class="bi bi-calendar-event me-1"></i>Válido a partir de</label>
                            <input type="datetime-local" name="effective_from" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold"><i class="bi bi-calendar-x me-1"></i>Expira em <span class="text-muted">(prazo)</span></label>
                            <input type="datetime-local" name="effective_until" class="form-control form-control-sm"
                                   placeholder="Deixe vazio para sem prazo">
                            <div class="form-text">Vazio = sem prazo de validade.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Notas internas</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Ex: Black Friday 2025 — aprovado em reunião">
                    </div>

                    <!-- Preview live -->
                    <div id="preview-<?= $planType ?>" class="alert alert-info p-2 small mb-3" style="display:none;">
                        <i class="bi bi-eye me-1"></i> <span id="preview-text-<?= $planType ?>"></span>
                    </div>

                    <button type="submit" class="btn btn-<?= $color ?> btn-sm w-100">
                        <i class="bi bi-check-lg me-1"></i>Salvar Preço
                    </button>
                </form>
            </div>

            <!-- ■ Form: Parcelamento -->
            <div class="col-lg-6">
                <h6 class="fw-bold mb-3 text-uppercase small text-muted"><i class="bi bi-credit-card-2-front me-1"></i>Parcelamento</h6>
                <form method="POST" action="/index.php?url=<?= obfuscateUrl('admin/pricing/update') ?>" id="form-inst-<?= $planType ?>">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="installment">
                    <input type="hidden" name="plan_type" value="<?= $planType ?>">

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold">Máx. Parcelas</label>
                            <select name="max_installments" class="form-select form-select-sm" id="maxInst-<?= $planType ?>"
                                    onchange="renderInstallPreview('<?= $planType ?>')">
                                <?php for ($i=1; $i<=12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $maxInst == $i ? 'selected' : '' ?>><?= $i ?>x</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Sem Juros até</label>
                            <select name="interest_free_up_to" class="form-select form-select-sm" id="freeInst-<?= $planType ?>"
                                    onchange="renderInstallPreview('<?= $planType ?>')">
                                <?php for ($i=1; $i<=12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $freeInst == $i ? 'selected' : '' ?>><?= $i ?>x</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Taxa Mensal (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="monthly_interest_rate_pct" class="form-control" step="0.01" min="0" max="30"
                                       id="rateInput-<?= $planType ?>"
                                       value="<?= number_format($rate * 100, 2, '.', '') ?>"
                                       onchange="renderInstallPreview('<?= $planType ?>')">
                                <span class="input-group-text">%</span>
                            </div>
                            <input type="hidden" name="monthly_interest_rate" id="rateHidden-<?= $planType ?>" value="<?= $rate ?>">
                        </div>
                    </div>

                    <!-- Preview de parcelas -->
                    <div class="bg-light-subtle rounded-3 p-3 mb-3" style="max-height:180px; overflow-y:auto;">
                        <div id="install-preview-<?= $planType ?>">
                        <?php foreach ($pi as $row): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span class="small fw-bold"><?= $row['installments'] ?>x</span>
                                <span class="small">R$ <?= number_format($row['installment_value'], 2, ',', '.') ?></span>
                                <span class="small text-muted">Total: R$ <?= number_format($row['total_value'], 2, ',', '.') ?></span>
                                <?php if ($row['has_interest']): ?>
                                    <span class="badge bg-warning text-dark" style="font-size:.65rem;">+juros</span>
                                <?php else: ?>
                                    <span class="badge bg-success" style="font-size:.65rem;">sem juros</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-<?= $color ?> btn-sm w-100">
                        <i class="bi bi-save me-1"></i>Salvar Parcelamento
                    </button>
                </form>
            </div>
        </div>

        <!-- Histórico -->
        <?php if (!empty($hist)): ?>
        <div class="section-divider"></div>
        <h6 class="fw-bold mb-3 text-uppercase small text-muted"><i class="bi bi-clock-history me-1"></i>Histórico de Preços</h6>
        <div style="max-height:200px; overflow-y:auto;">
            <table class="table table-sm table-hover table-borderless mb-0">
                <thead class="small text-muted">
                    <tr><th>Preço</th><th>Label</th><th>Início</th><th>Expirou</th><th>Admin</th><th>Notas</th></tr>
                </thead>
                <tbody class="small">
                    <?php foreach ($hist as $i => $h): ?>
                    <tr class="<?= $i === 0 ? 'history-row-new fw-semibold' : 'text-muted' ?>">
                        <td>
                            R$ <?= number_format($h['price'], 2, ',', '.') ?>
                            <?php if ($i === 0): ?><span class="badge bg-success ms-1" style="font-size:.65rem;">ATUAL</span><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($h['label'] ?? '—') ?></td>
                        <td><?= $h['effective_from'] ? date('d/m/Y H:i', strtotime($h['effective_from'])) : '—' ?></td>
                        <td><?= $h['effective_until'] ? date('d/m/Y H:i', strtotime($h['effective_until'])) : '<span class="text-success">Ativo</span>' ?></td>
                        <td><?= htmlspecialchars($h['admin_name'] ?? '—') ?></td>
                        <td class="text-muted fst-italic"><?= htmlspecialchars($h['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- ═══════════════════════════════════════════════════════════
     CONFIGURAÇÕES DE MÉTODOS DE PAGAMENTO
     ═══════════════════════════════════════════════════════════ -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-info bg-opacity-10 rounded-3 p-2">
                <i class="bi bi-sliders text-info fs-5"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">Métodos de Pagamento</h5>
                <p class="text-muted small mb-0">Habilite ou desabilite métodos na página de checkout.</p>
            </div>
        </div>
    </div>
    <div class="card-body px-4 py-4">
        <form method="POST" action="/index.php?url=<?= obfuscateUrl('admin/payment-settings') ?>">
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">

            <div class="row g-3">
                <!-- Cartão (sempre ativo) -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 rounded-2 p-2">
                                <i class="bi bi-credit-card-fill text-primary fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Cartão de Crédito / Débito</div>
                                <div class="small text-muted">Via Mercado Pago Brick</div>
                            </div>
                        </div>
                        <span class="badge bg-success px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>Sempre ativo
                        </span>
                    </div>
                </div>

                <!-- PIX toggle -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border <?= $pixEnabled ? 'border-success bg-success bg-opacity-10' : 'bg-light' ?>">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 rounded-2 p-2">
                                <i class="bi bi-qr-code text-success fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold">PIX</div>
                                <div class="small text-muted">Pagamento instantâneo</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0 ms-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="pix_payment_enabled" name="pix_payment_enabled"
                                   <?= $pixEnabled ? 'checked' : '' ?>
                                   style="width:2.5em; height:1.4em; cursor:pointer;">
                            <label class="form-check-label fw-semibold ms-1" for="pix_payment_enabled">
                                <span id="pix-toggle-label"><?= $pixEnabled ? '<span class="text-success">Habilitado</span>' : '<span class="text-muted">Desabilitado</span>' ?></span>
                            </label>
                        </div>
                    </div>
                    <?php if (!$pixEnabled): ?>
                    <div class="small text-muted mt-1 ps-1">
                        <i class="bi bi-info-circle me-1"></i>PIX desabilitado — não aparece no checkout para os usuários.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-floppy me-1"></i>Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle label dinâmico do PIX
document.getElementById('pix_payment_enabled').addEventListener('change', function() {
    const label = document.getElementById('pix-toggle-label');
    const card  = this.closest('.d-flex.align-items-center.justify-content-between');
    if (this.checked) {
        label.innerHTML = '<span class="text-success">Habilitado</span>';
        card.classList.add('border-success', 'bg-success', 'bg-opacity-10');
        card.classList.remove('bg-light');
    } else {
        label.innerHTML = '<span class="text-muted">Desabilitado</span>';
        card.classList.remove('border-success', 'bg-success', 'bg-opacity-10');
        card.classList.add('bg-light');
    }
});
</script>

<script>
// Dados dos preços atuais para preview JS
const planPrices = {
    monthly: <?= (float)($activePrices['monthly']['price'] ?? 29.90) ?>,
    yearly:  <?= (float)($activePrices['yearly']['price'] ?? 179.40) ?>,
};

// Live preview de preço
document.querySelectorAll('[name="price"]').forEach(inp => {
    const form     = inp.closest('form');
    const planType = form.querySelector('[name="plan_type"]').value;
    inp.addEventListener('input', () => showPricePreview(planType, parseFloat(inp.value)));
});
document.querySelectorAll('[name="original_price"]').forEach(inp => {
    const form     = inp.closest('form');
    const planType = form.querySelector('[name="plan_type"]').value;
    inp.addEventListener('input', () => {
        const priceEl = form.querySelector('[name="price"]');
        showPricePreview(planType, parseFloat(priceEl.value), parseFloat(inp.value));
    });
});

function showPricePreview(planType, newPrice, origPrice = 0) {
    const el   = document.getElementById('preview-' + planType);
    const text = document.getElementById('preview-text-' + planType);
    if (!isNaN(newPrice) && newPrice > 0) {
        let msg = `Novo preço: <strong>R$ ${newPrice.toLocaleString('pt-BR',{minimumFractionDigits:2})}</strong>`;
        if (origPrice > 0) msg += ` <del class="text-muted">R$ ${origPrice.toLocaleString('pt-BR',{minimumFractionDigits:2})}</del>`;
        const disc = origPrice > 0 ? Math.round((1 - newPrice/origPrice)*100) : 0;
        if (disc > 0) msg += ` <span class="badge bg-warning text-dark">${disc}% OFF</span>`;
        text.innerHTML = msg;
        el.style.display = '';
    } else {
        el.style.display = 'none';
    }
}

// Live preview de parcelamento
function renderInstallPreview(planType) {
    const price    = planPrices[planType];
    const maxInst  = parseInt(document.getElementById('maxInst-' + planType).value);
    const freeInst = parseInt(document.getElementById('freeInst-' + planType).value);
    const ratePct  = parseFloat(document.getElementById('rateInput-' + planType).value) || 0;
    const rate     = ratePct / 100;

    // Sync hidden input (converte % → decimal para PHP)
    document.getElementById('rateHidden-' + planType).value = rate.toFixed(4);

    let html = '';
    for (let n = 1; n <= maxInst; n++) {
        let val, total, hasInterest;
        if (n <= freeInst || rate === 0) {
            val = price / n; total = price; hasInterest = false;
        } else {
            const factor = rate * Math.pow(1+rate, n) / (Math.pow(1+rate, n) - 1);
            val = price * factor; total = val * n; hasInterest = true;
        }
        const badge = hasInterest
            ? `<span class="badge bg-warning text-dark" style="font-size:.65rem;">+juros</span>`
            : `<span class="badge bg-success" style="font-size:.65rem;">sem juros</span>`;
        html += `<div class="d-flex justify-content-between align-items-center py-1 border-bottom">
            <span class="small fw-bold">${n}x</span>
            <span class="small">R$ ${val.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
            <span class="small text-muted">Total: R$ ${total.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
            ${badge}
        </div>`;
    }
    document.getElementById('install-preview-' + planType).innerHTML = html;
}

// Converter % para decimal antes de submeter
document.querySelectorAll('[id^="form-inst-"]').forEach(form => {
    form.addEventListener('submit', function() {
        const planType = this.querySelector('[name="plan_type"]').value;
        const ratePct  = parseFloat(document.getElementById('rateInput-' + planType).value) || 0;
        document.getElementById('rateHidden-' + planType).value = (ratePct / 100).toFixed(4);
    });
});
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>

