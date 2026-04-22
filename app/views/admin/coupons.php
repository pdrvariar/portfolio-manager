<?php
/**
 * @var array $coupons  Lista de cupons
 * @var array $stats    Estatísticas
 */
$title = 'Cupons de Desconto';
$meta_robots = 'noindex, nofollow';
ob_start();

$typeLabel = fn($t) => $t === 'percent' ? '%' : 'R$';
$appliesToLabel = ['monthly' => 'Mensal', 'yearly' => 'Anual', 'both' => 'Ambos'];

function couponStatusBadge(array $c): string {
    $now = time();
    if (!$c['is_active']) return "<span class='badge bg-secondary rounded-pill'>Inativo</span>";
    if ($c['valid_until'] && strtotime($c['valid_until']) < $now) return "<span class='badge bg-warning text-dark rounded-pill'>Expirado</span>";
    if ($c['valid_from'] && strtotime($c['valid_from']) > $now) return "<span class='badge bg-info rounded-pill'>Agendado</span>";
    return "<span class='badge bg-success rounded-pill'>Ativo</span>";
}
?>

<style>
.coupon-code { font-family: 'Courier New', monospace; background:#f0f4ff; border:1px solid #c7d7fd; border-radius:6px; padding:2px 8px; font-size:.85rem; font-weight:700; letter-spacing:.05em; }
[data-theme="dark"] .coupon-code { background:#1a2040; border-color:#3d5298; }
.drawer-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1050; }
.drawer-backdrop.open { display:block; }
.side-drawer { position:fixed; top:0; right:-520px; width:520px; height:100vh; background:var(--card-bg, #fff); box-shadow:-8px 0 32px rgba(0,0,0,.15); z-index:1051; transition:right .3s ease; overflow-y:auto; }
.side-drawer.open { right:0; }
[data-theme="dark"] .side-drawer { background:#1e1e2e; }
.stat-pill { display:flex; flex-direction:column; align-items:center; background:var(--card-bg,#fff); border-radius:12px; padding:.75rem 1.25rem; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.coupon-row-inactive { opacity: .55; }
.use-progress { height:6px; border-radius:3px; }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-ticket-perforated-fill me-2 text-warning"></i>Cupons de Desconto</h2>
        <p class="text-muted small mb-0">Crie promoções com prazo, limite de uso e descontos inteligentes.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?url=<?= obfuscateUrl('admin/pricing') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-tags me-1"></i>Preços
        </a>
        <button class="btn btn-warning btn-sm fw-bold" onclick="openDrawer()">
            <i class="bi bi-plus-lg me-1"></i>Novo Cupom
        </button>
    </div>
</div>

<!-- Stats -->
<div class="d-flex flex-wrap gap-3 mb-4">
    <div class="stat-pill">
        <div class="small text-muted fw-bold text-uppercase mb-1">Total</div>
        <div class="h4 fw-bold mb-0"><?= $stats['total_coupons'] ?? 0 ?></div>
    </div>
    <div class="stat-pill">
        <div class="small text-muted fw-bold text-uppercase mb-1">Ativos</div>
        <div class="h4 fw-bold text-success mb-0"><?= $stats['active_coupons'] ?? 0 ?></div>
    </div>
    <div class="stat-pill">
        <div class="small text-muted fw-bold text-uppercase mb-1">Expirados</div>
        <div class="h4 fw-bold text-warning mb-0"><?= $stats['expired_coupons'] ?? 0 ?></div>
    </div>
    <div class="stat-pill">
        <div class="small text-muted fw-bold text-uppercase mb-1">Usos Totais</div>
        <div class="h4 fw-bold text-info mb-0"><?= $stats['total_uses'] ?? 0 ?></div>
    </div>
    <div class="stat-pill">
        <div class="small text-muted fw-bold text-uppercase mb-1">Desconto Concedido</div>
        <div class="h4 fw-bold text-danger mb-0">R$ <?= number_format($stats['total_discounted'] ?? 0, 2, ',', '.') ?></div>
    </div>
</div>

<!-- Actions bar -->
<div class="d-flex gap-2 mb-3">
    <input type="text" id="couponSearch" class="form-control form-control-sm w-auto" placeholder="🔍 Buscar código, nome...">
    <select id="statusFilter" class="form-select form-select-sm w-auto" onchange="filterCoupons()">
        <option value="">Todos os status</option>
        <option value="active">Ativos</option>
        <option value="inactive">Inativos</option>
        <option value="expired">Expirados</option>
    </select>
</div>

<!-- Coupons Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <?php if (empty($coupons)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-ticket-perforated display-4 mb-3 d-block opacity-25"></i>
                <p class="mb-3">Nenhum cupom criado ainda.</p>
                <button class="btn btn-warning" onclick="openDrawer()"><i class="bi bi-plus-lg me-1"></i>Criar Primeiro Cupom</button>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="couponsTable">
                <thead class="small text-muted bg-light-subtle">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Nome</th>
                        <th>Desconto</th>
                        <th>Plano</th>
                        <th>Validade</th>
                        <th>Usos</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $c): ?>
                    <?php
                        $now = time();
                        $isExpired = $c['valid_until'] && strtotime($c['valid_until']) < $now;
                        $rowClass  = !$c['is_active'] ? 'coupon-row-inactive' : '';
                        $maxUses   = $c['max_uses'] !== null ? (int)$c['max_uses'] : null;
                        $used      = (int)$c['used_count'];
                        $pct       = $maxUses ? min(100, round($used / $maxUses * 100)) : 0;
                    ?>
                    <tr class="<?= $rowClass ?>" data-code="<?= strtolower($c['code']) ?>" data-name="<?= strtolower($c['display_name']) ?>"
                        data-status="<?= !$c['is_active'] ? 'inactive' : ($isExpired ? 'expired' : 'active') ?>">
                        <td class="ps-4">
                            <span class="coupon-code"><?= htmlspecialchars($c['code']) ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold small"><?= htmlspecialchars($c['display_name']) ?></div>
                            <?php if ($c['created_by_name']): ?>
                                <div class="text-muted" style="font-size:.72rem;">por <?= htmlspecialchars($c['created_by_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['discount_type'] === 'percent'): ?>
                                <span class="badge bg-primary rounded-pill"><?= number_format($c['discount_value'], 0) ?>% OFF</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark rounded-pill">-R$ <?= number_format($c['discount_value'], 2, ',', '.') ?></span>
                            <?php endif; ?>
                            <?php if ($c['max_discount']): ?><div class="text-muted" style="font-size:.7rem;">máx R$ <?= number_format($c['max_discount'], 2, ',', '.') ?></div><?php endif; ?>
                        </td>
                        <td class="small"><?= $appliesToLabel[$c['applies_to']] ?? $c['applies_to'] ?></td>
                        <td class="small">
                            <?php if ($c['valid_from'] || $c['valid_until']): ?>
                                <?php if ($c['valid_from']): ?><div><i class="bi bi-play-circle text-success me-1"></i><?= date('d/m/Y', strtotime($c['valid_from'])) ?></div><?php endif; ?>
                                <?php if ($c['valid_until']): ?><div class="<?= $isExpired ? 'text-danger' : 'text-muted' ?>"><i class="bi bi-stop-circle me-1"></i><?= date('d/m/Y', strtotime($c['valid_until'])) ?></div><?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Sem prazo</span>
                            <?php endif; ?>
                        </td>
                        <td style="min-width:100px">
                            <div class="d-flex align-items-center gap-2">
                                <div style="flex:1">
                                    <?php if ($maxUses): ?>
                                        <div class="progress use-progress mb-1"><div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div></div>
                                        <div style="font-size:.72rem;" class="text-muted"><?= $used ?>/<?= $maxUses ?></div>
                                    <?php else: ?>
                                        <div style="font-size:.8rem;" class="fw-semibold"><?= $used ?></div>
                                        <div style="font-size:.7rem;" class="text-muted">ilimitado</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= couponStatusBadge($c) ?></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" title="Editar" onclick="openEditDrawer(<?= htmlspecialchars(json_encode($c)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-<?= $c['is_active'] ? 'warning' : 'success' ?>"
                                        title="<?= $c['is_active'] ? 'Desativar' : 'Ativar' ?>"
                                        onclick="toggleCoupon(<?= $c['id'] ?>, this)">
                                    <i class="bi bi-toggle-<?= $c['is_active'] ? 'on' : 'off' ?>"></i>
                                </button>
                                <button class="btn btn-outline-danger" title="Excluir"
                                        onclick="deleteCoupon(<?= $c['id'] ?>, '<?= htmlspecialchars($c['code']) ?>', <?= $c['used_count'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
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

<!-- ════════════════════════════════════════════════════════
     SIDE DRAWER — Criar/Editar Cupom
════════════════════════════════════════════════════════ -->
<div class="drawer-backdrop" id="drawerBackdrop" onclick="closeDrawer()"></div>
<div class="side-drawer" id="couponDrawer">
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center sticky-top bg-inherit" style="background:inherit;">
        <h5 class="mb-0 fw-bold" id="drawerTitle"><i class="bi bi-ticket-perforated me-2 text-warning"></i>Novo Cupom</h5>
        <button class="btn btn-sm btn-outline-secondary" onclick="closeDrawer()"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="p-4">
        <!-- Status toggle no topo -->
        <div class="d-flex align-items-center justify-content-between mb-4 p-3 rounded-3 border">
            <div>
                <div class="fw-bold small">Status do Cupom</div>
                <div class="text-muted" style="font-size:.8rem;">Cupom inativo não pode ser resgatado</div>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="drawer_is_active" checked style="width:2.5rem; height:1.3rem;">
            </div>
        </div>

        <form id="couponForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
            <input type="hidden" id="couponEditId" name="_edit_id" value="">

            <!-- Código -->
            <div class="mb-3">
                <label class="form-label fw-bold">Código / Alias <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control text-uppercase fw-bold" id="drawer_code" name="code"
                           placeholder="Ex: BLACKFRIDAY, BEMVINDO20" maxlength="50" required
                           oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9_]/g,''); checkCodeAvailability(this.value)">
                    <span class="input-group-text" id="codeCheckIcon"><i class="bi bi-dash"></i></span>
                </div>
                <div id="codeAvailabilityMsg" class="form-text"></div>
            </div>

            <!-- Nome de exibição -->
            <div class="mb-3">
                <label class="form-label fw-bold">Nome de Exibição <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="drawer_display_name" name="display_name"
                       placeholder="Ex: Black Friday 2025 🔥" maxlength="100" required>
                <div class="form-text">Exibido ao usuário durante o checkout.</div>
            </div>

            <!-- Tipo e valor de desconto -->
            <div class="mb-3">
                <label class="form-label fw-bold">Tipo de Desconto</label>
                <div class="btn-group w-100 mb-2" role="group">
                    <input type="radio" class="btn-check" name="discount_type" id="dt_percent" value="percent" checked onchange="updateDiscountPreview()">
                    <label class="btn btn-outline-primary btn-sm" for="dt_percent"><i class="bi bi-percent me-1"></i>Porcentagem (%)</label>
                    <input type="radio" class="btn-check" name="discount_type" id="dt_fixed" value="fixed" onchange="updateDiscountPreview()">
                    <label class="btn btn-outline-info btn-sm" for="dt_fixed"><i class="bi bi-currency-dollar me-1"></i>Valor Fixo (R$)</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text" id="discountPrefix">%</span>
                    <input type="number" class="form-control" id="drawer_discount_value" name="discount_value"
                           step="0.01" min="0.01" placeholder="Ex: 20" required onchange="updateDiscountPreview()" oninput="updateDiscountPreview()">
                </div>
            </div>

            <!-- Plano e teto -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-bold">Aplica-se a</label>
                    <select name="applies_to" id="drawer_applies_to" class="form-select" onchange="updateDiscountPreview()">
                        <option value="both">Ambos os Planos</option>
                        <option value="monthly">Somente Mensal</option>
                        <option value="yearly">Somente Anual</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold">Desconto Máx (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="max_discount" id="drawer_max_discount" class="form-control" step="0.01" min="0"
                               placeholder="Sem teto" onchange="updateDiscountPreview()">
                    </div>
                </div>
            </div>

            <!-- Preço mínimo e max usos -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-bold">Preço Mínimo (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="min_price" class="form-control" step="0.01" min="0" placeholder="Sem mínimo">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold">Máx. de Usos</label>
                    <input type="number" name="max_uses" id="drawer_max_uses" class="form-control" min="1" placeholder="Ilimitado">
                    <div class="form-text">Vazio = ilimitado.</div>
                </div>
            </div>

            <!-- Validade -->
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <label class="form-label fw-bold"><i class="bi bi-calendar-event me-1 text-success"></i>Início da Promoção</label>
                    <input type="datetime-local" name="valid_from" id="drawer_valid_from" class="form-control">
                    <div class="form-text">Vazio = imediatamente.</div>
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold"><i class="bi bi-calendar-x me-1 text-danger"></i>Término da Promoção</label>
                    <input type="datetime-local" name="valid_until" id="drawer_valid_until" class="form-control">
                    <div class="form-text">Vazio = sem prazo.</div>
                </div>
            </div>

            <!-- Hidden is_active -->
            <input type="hidden" name="is_active" id="hidden_is_active" value="1">

            <!-- Preview do checkout -->
            <div id="checkoutPreviewBox" class="rounded-3 border p-3 mb-4" style="background:var(--soft-primary, #eff6ff);">
                <div class="small fw-bold text-muted mb-2 text-uppercase"><i class="bi bi-eye me-1"></i>Prévia no Checkout</div>
                <div id="checkoutPreviewContent" class="small text-muted">Configure o cupom para ver a prévia.</div>
            </div>

            <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                <i class="bi bi-check-circle me-2"></i><span id="drawerSubmitLabel">Criar Cupom</span>
            </button>
        </form>
    </div>
</div>

<!-- Delete confirm modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-danger display-5 mb-3 d-block"></i>
                <h6 class="fw-bold" id="deleteModalTitle">Excluir Cupom</h6>
                <p class="text-muted small" id="deleteModalMsg"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const PLAN_PRICES = {
    monthly: <?= (float)($activePrices['monthly']['price'] ?? 29.90) ?>,
    yearly:  <?= (float)($activePrices['yearly']['price'] ?? 179.40) ?>,
};
const COUPON_BASE_URL = '/index.php?url=';
const CREATE_URL = COUPON_BASE_URL + '<?= obfuscateUrl('admin/coupons/create') ?>';
let editMode = false;
let codeCheckTimer = null;

// ── Drawer ──────────────────────────────────────────
function openDrawer(editData = null) {
    editMode = !!editData;
    const drawer = document.getElementById('couponDrawer');
    const backdrop = document.getElementById('drawerBackdrop');
    const form = document.getElementById('couponForm');
    form.reset();
    document.getElementById('couponEditId').value = '';
    document.getElementById('drawerTitle').innerHTML = '<i class="bi bi-ticket-perforated me-2 text-warning"></i>' + (editMode ? 'Editar Cupom' : 'Novo Cupom');
    document.getElementById('drawerSubmitLabel').textContent = editMode ? 'Salvar Alterações' : 'Criar Cupom';
    document.getElementById('drawer_is_active').checked = true;
    document.getElementById('hidden_is_active').value = '1';
    document.getElementById('codeAvailabilityMsg').textContent = '';
    document.getElementById('codeCheckIcon').innerHTML = '<i class="bi bi-dash"></i>';

    if (editData) {
        form.action = COUPON_BASE_URL + '<?= obfuscateUrl('admin/coupons/update/') ?>' + editData.id;
        document.getElementById('couponEditId').value = editData.id;
        document.getElementById('drawer_code').value = editData.code;
        document.getElementById('drawer_display_name').value = editData.display_name;
        document.getElementById('drawer_discount_value').value = editData.discount_value;
        document.getElementById('drawer_applies_to').value = editData.applies_to;
        document.getElementById('drawer_max_discount').value = editData.max_discount || '';
        document.getElementById('drawer_max_uses').value = editData.max_uses || '';
        document.getElementById('drawer_valid_from').value = editData.valid_from ? editData.valid_from.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('drawer_valid_until').value = editData.valid_until ? editData.valid_until.replace(' ', 'T').substring(0, 16) : '';
        document.querySelector(`[name="discount_type"][value="${editData.discount_type}"]`).checked = true;
        document.getElementById('drawer_is_active').checked = editData.is_active == 1;
        document.getElementById('hidden_is_active').value = editData.is_active ? '1' : '0';
        updateDiscountPrefix();
    } else {
        form.action = CREATE_URL;
    }
    updateDiscountPreview();
    drawer.classList.add('open');
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openEditDrawer(data) { openDrawer(data); }

function closeDrawer() {
    document.getElementById('couponDrawer').classList.remove('open');
    document.getElementById('drawerBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

// Sync toggle → hidden is_active
document.getElementById('drawer_is_active').addEventListener('change', function() {
    document.getElementById('hidden_is_active').value = this.checked ? '1' : '0';
});

// ── Discount preview ──────────────────────────────────────────
function updateDiscountPrefix() {
    const type = document.querySelector('[name="discount_type"]:checked')?.value || 'percent';
    document.getElementById('discountPrefix').textContent = type === 'percent' ? '%' : 'R$';
}

function updateDiscountPreview() {
    updateDiscountPrefix();
    const type       = document.querySelector('[name="discount_type"]:checked')?.value || 'percent';
    const val        = parseFloat(document.getElementById('drawer_discount_value').value) || 0;
    const appliesTo  = document.getElementById('drawer_applies_to').value;
    const maxDisc    = parseFloat(document.getElementById('drawer_max_discount').value) || 0;
    const validFrom  = document.getElementById('drawer_valid_from').value;
    const validUntil = document.getElementById('drawer_valid_until').value;
    const name       = document.getElementById('drawer_display_name').value || 'Cupom';
    const code       = document.getElementById('drawer_code').value || 'CODIGO';

    if (!val) {
        document.getElementById('checkoutPreviewContent').innerHTML = '<span class="text-muted">Configure o desconto para ver a prévia.</span>';
        return;
    }

    const plansToShow = appliesTo === 'both' ? ['monthly', 'yearly'] : [appliesTo];
    let html = '';
    plansToShow.forEach(p => {
        const base    = PLAN_PRICES[p];
        let disc      = type === 'percent' ? base * (val / 100) : val;
        if (maxDisc > 0) disc = Math.min(disc, maxDisc);
        disc          = Math.min(disc, base);
        const final   = Math.max(0, base - disc).toFixed(2);
        const pLabel  = p === 'monthly' ? 'Mensal' : 'Anual';
        html += `<div class="mb-2 p-2 rounded" style="background:rgba(255,255,255,.6);">
            <div class="d-flex justify-content-between">
                <span class="fw-bold">${pLabel}</span>
                <span><del class="text-muted">R$ ${base.toLocaleString('pt-BR',{minimumFractionDigits:2})}</del> → <strong class="text-success">R$ ${parseFloat(final).toLocaleString('pt-BR',{minimumFractionDigits:2})}</strong></span>
            </div>
            <div class="text-success small">✅ "${name}" — economize R$ ${disc.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
        </div>`;
    });
    if (validUntil) {
        const d = new Date(validUntil);
        html += `<div class="small text-warning mt-1">⏰ Expira em ${d.toLocaleDateString('pt-BR')}</div>`;
    }
    document.getElementById('checkoutPreviewContent').innerHTML = html;
}

// ── Code availability ──────────────────────────────────────────
function checkCodeAvailability(code) {
    clearTimeout(codeCheckTimer);
    const icon = document.getElementById('codeCheckIcon');
    const msg  = document.getElementById('codeAvailabilityMsg');
    if (code.length < 3) {
        icon.innerHTML = '<i class="bi bi-dash"></i>'; msg.textContent = ''; return;
    }
    icon.innerHTML = '<i class="bi bi-hourglass-split text-muted"></i>';
    const editId = document.getElementById('couponEditId').value || 0;
    codeCheckTimer = setTimeout(() => {
        fetch(`${COUPON_BASE_URL}<?= obfuscateUrl('admin/coupons/check-code') ?>&code=${encodeURIComponent(code)}&exclude=${editId}`)
            .then(r => r.json()).then(data => {
                if (data.exists) {
                    icon.innerHTML = '<i class="bi bi-x-circle text-danger"></i>';
                    msg.innerHTML = '<span class="text-danger">Código já em uso.</span>';
                } else {
                    icon.innerHTML = '<i class="bi bi-check-circle text-success"></i>';
                    msg.innerHTML = '<span class="text-success">Código disponível ✓</span>';
                }
            });
    }, 400);
}

// ── Toggle coupon status ──────────────────────────────────────────
function toggleCoupon(id, btn) {
    fetch(`${COUPON_BASE_URL}<?= obfuscateUrl('admin/coupons/toggle/') ?>${id}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= Session::generateCsrfToken() ?>'
    })
    .then(r => r.json()).then(data => {
        if (data.success) {
            const row = btn.closest('tr');
            row.querySelector('[data-status]')?.setAttribute('data-status', data.is_active ? 'active' : 'inactive');
            btn.className = `btn btn-outline-${data.is_active ? 'warning' : 'success'}`;
            btn.querySelector('i').className = `bi bi-toggle-${data.is_active ? 'on' : 'off'}`;
            btn.title = data.is_active ? 'Desativar' : 'Ativar';
            row.classList.toggle('coupon-row-inactive', !data.is_active);
            // Atualiza badge de status
            const statusCell = [...row.cells].find(c => c.querySelector('.badge'));
            // Reload only the status badge cell (simple refresh)
            setTimeout(() => location.reload(), 600);
        }
    });
}

// ── Delete coupon ──────────────────────────────────────────
function deleteCoupon(id, code, usedCount) {
    const modal  = new bootstrap.Modal(document.getElementById('deleteModal'));
    const form   = document.getElementById('deleteForm');
    form.action  = `${COUPON_BASE_URL}<?= obfuscateUrl('admin/coupons/delete/') ?>${id}`;
    document.getElementById('deleteModalTitle').textContent = `Excluir cupom "${code}"`;
    document.getElementById('deleteModalMsg').innerHTML = usedCount > 0
        ? `<strong>Atenção:</strong> Este cupom foi usado ${usedCount} vez(es). Ele será <strong>desativado</strong> em vez de excluído para preservar o histórico.`
        : 'Tem certeza? Esta ação não pode ser desfeita.';
    modal.show();
}

// ── Search & Filter ──────────────────────────────────────────
document.getElementById('couponSearch').addEventListener('input', filterCoupons);

function filterCoupons() {
    const search = document.getElementById('couponSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    document.querySelectorAll('#couponsTable tbody tr').forEach(row => {
        const code   = row.dataset.code   || '';
        const name   = row.dataset.name   || '';
        const rStatus = row.dataset.status || '';
        const matchSearch = !search || code.includes(search) || name.includes(search);
        const matchStatus = !status || rStatus === status;
        row.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
}

// Update discount type label on radio change
document.querySelectorAll('[name="discount_type"]').forEach(r => r.addEventListener('change', updateDiscountPrefix));
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>

