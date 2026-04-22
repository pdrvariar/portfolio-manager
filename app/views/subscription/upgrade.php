<?php
$user = Auth::getUser();
$userModel = new User();
$userData = $userModel->findById($user['id']);

// Variáveis injetadas pelo controller
$upgradeMode         = $upgradeMode         ?? false;
$proratedCredit      = $proratedCredit      ?? 0;
$proratedYearlyPrice = $proratedYearlyPrice ?? 179.40;
$activeSub           = $activeSub           ?? null;
$plans               = $plans               ?? [];

// Preços dinâmicos do banco
$planModel    = new SubscriptionPlan();
$monthlyPrice = (float)(($plans['monthly']['price'] ?? null) ?: $planModel->getPriceFor('monthly'));
$yearlyPrice  = $upgradeMode ? $proratedYearlyPrice : (float)(($plans['yearly']['price'] ?? null) ?: $planModel->getPriceFor('yearly'));

$monthlyPlan  = $plans['monthly'] ?? [];
$yearlyPlan   = $plans['yearly']  ?? [];

// Configurações de parcelamento
$yearlyInstallConfig  = $planModel->getInstallmentConfig('yearly');
$monthlyInstallConfig = $planModel->getInstallmentConfig('monthly');
// Array de configs (para uso no JS inline)
$installments = [
    'monthly' => $monthlyInstallConfig,
    'yearly'  => $yearlyInstallConfig,
];
$monthlyInstallRows  = SubscriptionPlan::calculateInstallments($monthlyPrice, $monthlyInstallConfig);
$yearlyInstallRows   = SubscriptionPlan::calculateInstallments($yearlyPrice, $yearlyInstallConfig);
$bestInstall         = count($yearlyInstallRows) > 0 ? $yearlyInstallRows[count($yearlyInstallRows) - 1] : null;

$currentExpiration = !empty($userData['subscription_expires_at'])
    ? date('d/m/Y', strtotime($userData['subscription_expires_at']))
    : null;

$title = $upgradeMode ? 'Upgrade para PRO Anual - Smart Returns' : 'Planos e Preços - Smart Returns | Plano PRO';
$meta_description = $upgradeMode
    ? 'Faça upgrade para o plano Anual PRO da Smart Returns e economize com o crédito dos seus dias restantes.'
    : 'Desbloqueie recursos premium da Smart Returns: 1000 simulações/mês, Monte Carlo, cálculo de impostos, histórico completo e muito mais. Plano PRO a partir de R$ ' . number_format($monthlyPrice, 2, ',', '.') . '/mês.';
$meta_robots = 'noindex, nofollow';
ob_start();
?>

<style>
    .plan-card {
        transition: all 0.3s ease;
        border: 2px solid transparent;
        cursor: pointer;
    }
    .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .plan-card.active {
        border-color: #0d6efd;
        background-color: var(--soft-primary);
    }
    [data-theme="dark"] .plan-card.active {
        background-color: rgba(13, 110, 253, 0.2);
    }
    .badge-save {
        background-color: #ffc107;
        color: #000;
        font-weight: bold;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    .text-strike {
        text-decoration: line-through;
        color: #6c757d;
        font-size: 0.9rem;
    }
    .text-main {
        color: var(--text-main) !important;
    }
    /* ── Seletor de Parcelas (dropdown compacto) ────── */
    .installment-select-wrapper { position: relative; }
    .installment-select-wrapper .inst-icon {
        position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
        color: #0d6efd; pointer-events: none; font-size: 1rem;
    }
    #installment-select {
        appearance: none; -webkit-appearance: none;
        padding: .55rem 2.4rem .55rem 2.2rem;
        border: 2px solid #dee2e6; border-radius: 10px;
        font-size: .88rem; width: 100%; cursor: pointer;
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
        transition: border-color .18s, box-shadow .18s;
        font-weight: 500;
    }
    #installment-select:focus {
        outline: none; border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13,110,253,.15);
    }
    .inst-chevron {
        position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
        pointer-events: none; color: #6c757d; font-size: .8rem;
    }
    [data-theme="dark"] #installment-select {
        border-color: #3d3d3d;
        background-color: var(--bs-body-bg, #1a1a2e);
    }
    [data-theme="dark"] #installment-select:focus { border-color: #4d7eff; box-shadow: 0 0 0 3px rgba(77,126,255,.2); }
    #installment-hint { display: none; }
</style>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-5">
                <?= $upgradeMode ? 'Upgrade para PRO Anual' : 'Escolha seu Plano PRO' ?>
            </h2>
            <p class="lead text-muted">
                <?= $upgradeMode
                    ? 'Mude para o plano anual e economize com o crédito proporcional dos dias restantes.'
                    : 'Maximize seus retornos com inteligência e ferramentas exclusivas.' ?>
            </p>
            <?php if ($currentExpiration): ?>
                <div class="alert alert-info d-inline-block mt-2">
                    <i class="bi bi-calendar-check me-2"></i>
                    Sua assinatura atual é válida até: <strong class="text-main"><?= $currentExpiration ?></strong>
                </div>
            <?php endif; ?>
            <?php if ($upgradeMode && $proratedCredit > 0): ?>
                <div class="alert alert-success d-inline-block mt-2 ms-2">
                    <i class="bi bi-gift me-2"></i>
                    Você tem <strong>R$ <?= number_format($proratedCredit, 2, ',', '.') ?></strong>
                    de crédito proporcional aplicado no upgrade anual!
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4 mb-5 justify-content-center">
            <!-- Plano Mensal (oculto no modo upgrade) -->
            <?php if (!$upgradeMode): ?>
            <div class="col-md-5">
                <div class="card h-100 shadow-sm plan-card active" id="card-monthly" onclick="selectPlan('monthly', <?= (float)$monthlyPrice ?>)">
                    <?php if (!empty($monthlyPlan['label']) && $monthlyPlan['label'] !== 'Padrão'): ?>
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge-save" style="background:#0d6efd; color:#fff;"><?= htmlspecialchars($monthlyPlan['label']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="card-body p-4 text-center">
                        <h4 class="fw-bold">Mensal</h4>
                        <div class="my-4">
                            <?php if (!empty($monthlyPlan['original_price'])): ?>
                                <div class="text-strike">R$ <?= number_format($monthlyPlan['original_price'], 2, ',', '.') ?></div>
                            <?php endif; ?>
                            <span class="display-5 fw-bold">R$ <?= number_format($monthlyPrice, 2, ',', '.') ?></span>
                            <span class="text-muted">/mês</span>
                        </div>
                        <p class="text-muted small">Ideal para quem quer testar por pouco tempo.</p>
                        <hr>
                        <ul class="list-unstyled text-start mb-0">
                            <li class="mb-2"><i class="bi bi-check-circle text-primary me-2"></i> Renovação a cada 30 dias</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-primary me-2"></i> Todos os recursos PRO</li>
                            <li><i class="bi bi-calendar-event text-primary me-2"></i> Válido até: <strong class="text-main"><?= date('d/m/Y', strtotime('+1 month')) ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Plano Anual -->
            <?php
                $yearlyOriginal = !empty($yearlyPlan['original_price']) ? (float)$yearlyPlan['original_price'] : ($monthlyPrice * 24);
                $yearlyMonthly  = round($yearlyPrice / 12, 2);
                $yearlyLabel    = !empty($yearlyPlan['label']) ? $yearlyPlan['label'] : '50% DE DESCONTO';
            ?>
            <div class="col-md-5">
                <div class="card h-100 shadow-sm plan-card position-relative <?= $upgradeMode ? 'active' : '' ?>"
                     id="card-yearly" onclick="selectPlan('yearly', <?= $proratedYearlyPrice ?>)">
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge-save">
                            <?= $upgradeMode ? 'CRÉDITO APLICADO' : htmlspecialchars(strtoupper($yearlyLabel)) ?>
                        </span>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="d-flex justify-content-center align-items-center mb-1">
                            <h4 class="fw-bold mb-0">Anual</h4>
                        </div>
                        <div class="my-4">
                            <?php if ($upgradeMode && $proratedCredit > 0): ?>
                                <div class="text-strike">R$ <?= number_format($planModel->getPriceFor('yearly'), 2, ',', '.') ?></div>
                                <span class="display-5 fw-bold text-primary">R$ <?= number_format($proratedYearlyPrice, 2, ',', '.') ?></span>
                                <span class="text-muted">/upgrade</span>
                                <div class="small text-success fw-bold">
                                    Crédito de R$ <?= number_format($proratedCredit, 2, ',', '.') ?> aplicado
                                </div>
                            <?php else: ?>
                                <?php if ($yearlyOriginal > $yearlyPrice): ?>
                                    <div class="text-strike">R$ <?= number_format($yearlyOriginal, 2, ',', '.') ?></div>
                                <?php endif; ?>
                                <span class="display-5 fw-bold text-primary">R$ <?= number_format($yearlyPrice, 2, ',', '.') ?></span>
                                <span class="text-muted">/ano</span>
                                <div class="small text-success fw-bold" id="yearly-monthly-equiv">R$ <?= number_format($yearlyMonthly, 2, ',', '.') ?> /mês</div>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small"><?= $upgradeMode ? 'Aproveite o crédito dos seus dias restantes.' : 'O melhor custo-benefício para investidores sérios.' ?></p>
                        <?php if ($bestInstall && $bestInstall['installments'] > 1): ?>
                            <div class="small text-primary fw-semibold mb-2">
                                ou <?= $bestInstall['installments'] ?>x de R$ <?= number_format($bestInstall['installment_value'], 2, ',', '.') ?>
                                <?= $bestInstall['has_interest'] ? '(com juros)' : 'sem juros' ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($yearlyPlan['effective_until'])): ?>
                            <div class="alert alert-warning p-1 small mb-2">
                                <i class="bi bi-clock me-1"></i>Promoção até <?= date('d/m/Y', strtotime($yearlyPlan['effective_until'])) ?>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <ul class="list-unstyled text-start mb-0">
                            <?php if ($upgradeMode): ?>
                                <li class="mb-2"><i class="bi bi-arrow-up-circle text-primary me-2"></i> <strong>Upgrade imediato para Anual</strong></li>
                            <?php else: ?>
                                <li class="mb-2"><i class="bi bi-star-fill text-warning me-2"></i> <strong>Maior economia do ano</strong></li>
                            <?php endif; ?>
                            <li class="mb-2"><i class="bi bi-check-circle text-primary me-2"></i> Todos os recursos PRO</li>
                            <li><i class="bi bi-calendar-event text-primary me-2"></i> Válido até: <strong class="text-main"><?= date('d/m/Y', strtotime('+1 year')) ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-lg overflow-hidden mb-4">
            <div class="row g-0">
                <div class="col-md-12 p-5">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="fw-bold mb-4">O que você ganha com o PRO:</h4>
                            <ul class="list-unstyled">
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-graph-up-arrow text-primary me-3 fs-5"></i>
                                    <div>
                                        <strong>Histórico e Ativos Ilimitados:</strong>
                                        <p class="small text-muted mb-0">Simule com o histórico completo de décadas e adicione quantos ativos desejar (limite de 5 no Starter).</p>
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-calculator text-primary me-3 fs-5"></i>
                                    <div>
                                        <strong>1000 Simulações mensais:</strong>
                                        <p class="small text-muted mb-0">Enquanto o plano Starter permite apenas 20 execuções por mês.</p>
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-lightning-charge text-primary me-3 fs-5"></i>
                                    <div>
                                        <strong>Recursos Premium de Rebalanceamento:</strong>
                                        <p class="small text-muted mb-0">Acesse simulações de Aporte Direcionado, Estratégico e Rebalanceamento com Margem.</p>
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-receipt text-primary me-3 fs-5"></i>
                                    <div>
                                        <strong>Cálculo de Impostos:</strong>
                                        <p class="small text-muted mb-0">Visualize o impacto tributário nas suas simulações automaticamente.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6 border-start ps-md-5 mt-4 mt-md-0">
                            <div id="payment-section">
                                <h4 class="fw-bold mb-4">Finalizar Assinatura</h4>
                                
                                <div class="mb-4">
                                    <div class="form-check small">
                                        <input class="form-check-input" type="checkbox" id="accept_terms" required>
                                        <label class="form-check-label text-muted" for="accept_terms">
                                            Eu li e aceito os <a href="/index.php?url=terms" target="_blank" class="fw-bold">Termos de Uso</a>.
                                        </label>
                                    </div>
                                </div>

                                <div id="order-summary" class="bg-light-subtle p-3 rounded mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Plano selecionado:</span>
                                        <span id="summary-plan-name" class="fw-bold text-primary">
                                            <?= $upgradeMode ? 'Anual (com crédito)' : 'Mensal' ?>
                                        </span>
                                    </div>
                                    <div id="summary-installment-row" class="d-flex justify-content-between mb-2 d-none">
                                        <span class="text-muted">Parcelamento:</span>
                                        <span id="summary-installment-text" class="fw-semibold small"></span>
                                    </div>
                                    <div id="coupon-applied-row" class="d-flex justify-content-between mb-2 d-none">
                                        <span class="text-muted">Desconto cupom:</span>
                                        <span id="summary-coupon-discount" class="fw-bold text-success">— R$ 0,00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Total a pagar:</span>
                                        <span id="summary-plan-price" class="fs-4 fw-bold">
                                            R$ <?= number_format($upgradeMode ? $proratedYearlyPrice : $monthlyPrice, 2, ',', '.') ?>
                                        </span>
                                    </div>
                                    <div id="summary-total-note" class="text-muted d-none" style="font-size:.75rem; text-align:right;"></div>
                                </div>

                                <!-- Cupom de desconto -->
                                <?php if (!$upgradeMode): ?>

                                <!-- ■ Seletor de Parcelas -->
                                <div id="installment-picker-wrapper" class="mb-4 d-none">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-2" for="installment-select">
                                        <i class="bi bi-credit-card-2-front me-1"></i>Como deseja pagar?
                                    </label>
                                    <div class="installment-select-wrapper">
                                        <i class="bi bi-cash-coin inst-icon"></i>
                                        <select id="installment-select" onchange="chooseInstallment(parseInt(this.value))">
                                            <!-- Preenchido pelo JS -->
                                        </select>
                                        <i class="bi bi-chevron-down inst-chevron"></i>
                                    </div>
                                </div>

                                <!-- ■ Cupom -->
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase"><i class="bi bi-ticket-perforated me-1"></i>Cupom de Desconto</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="couponInput" class="form-control text-uppercase"
                                               placeholder="Ex: BLACKFRIDAY" maxlength="50"
                                               oninput="this.value=this.value.toUpperCase()">
                                        <button class="btn btn-outline-warning fw-bold" type="button" id="applyCouponBtn" onclick="applyCoupon()">
                                            Aplicar
                                        </button>
                                        <button class="btn btn-outline-danger d-none" type="button" id="removeCouponBtn" onclick="removeCoupon()">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <div id="couponMessage" class="mt-1 small"></div>
                                </div>
                                <?php endif; ?>


                                 <div id="payment-error-container" class="alert alert-danger d-none mb-3" role="alert">
                                     <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                     <span id="payment-error-message"></span>
                                 </div>

                                 <!-- Hint de parcelamento (aparece quando plano anual selecionado) -->
                                 <div id="installment-hint" class="alert alert-info py-2 px-3 small mb-3 d-none" role="alert"></div>

                                 <div id="paymentBrick_container" class="opacity-50" style="pointer-events: none;"></div>
                                <div id="terms_warning" class="text-danger small text-center mb-3">
                                    Habilite o aceite dos termos para pagar.
                                </div>
                                
                                <p class="text-center text-muted small mb-0">
                                    <i class="bi bi-lock-fill"></i> Pagamento 100% seguro via Mercado Pago
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="javascript:history.back()" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    const IS_UPGRADE     = <?= $upgradeMode ? 'true' : 'false' ?>;
    const PRORATED_PRICE = <?= (float)$proratedYearlyPrice ?>;
    const MONTHLY_PRICE  = <?= (float)$monthlyPrice ?>;
    const YEARLY_PRICE   = <?= (float)$yearlyPrice ?>;

    // Configuração de parcelamento por plano (carregada do banco via PHP)
    const INSTALLMENT_CONFIG = {
        monthly: {
            maxInstallments: <?= (int)($installments['monthly']['max_installments']    ?? 1) ?>,
            minInstallments: 1,
        },
        yearly: {
            maxInstallments: <?= (int)($installments['yearly']['max_installments']     ?? 12) ?>,
            minInstallments: 1,
        },
    };

    // Parcelas pré-calculadas (PHP → JS) — usadas no seletor visual
    const INSTALLMENT_ROWS = {
        monthly: <?= json_encode($monthlyInstallRows) ?>,
        yearly:  <?= json_encode($yearlyInstallRows) ?>,
    };

    let selectedInstallments = 1; // parcela selecionada pelo usuário

    let selectedPlan   = IS_UPGRADE ? 'yearly' : 'monthly';
    let selectedPrice  = IS_UPGRADE ? PRORATED_PRICE : MONTHLY_PRICE;
    let basePrice      = selectedPrice;  // before coupon
    let appliedCoupon  = null;           // {code, discount, final_price}
    let cardPaymentBrickController = null;
    const mp = new MercadoPago('<?= getenv('MERCADOPAGO_PUBLIC_KEY') ?: ($_ENV['MERCADOPAGO_PUBLIC_KEY'] ?? '') ?>');

    function selectPlan(plan, price) {
        selectedPlan         = plan;
        basePrice            = price;
        appliedCoupon        = null;
        selectedPrice        = price;
        selectedInstallments = 1;

        document.getElementById('card-monthly') && document.getElementById('card-monthly').classList.toggle('active', plan === 'monthly');
        document.getElementById('card-yearly').classList.toggle('active', plan === 'yearly');

        renderInstallmentPicker(plan, price);
        updateOrderSummary();

        // Reset coupon
        const couponInput = document.getElementById('couponInput');
        if (couponInput) couponInput.value = '';
        document.getElementById('couponMessage') && (document.getElementById('couponMessage').innerHTML = '');
        hideCouponApplied();

        if (cardPaymentBrickController) {
            cardPaymentBrickController.unmount();
            renderCardPaymentBrick(mp.bricks());
        }
    }

    function updateOrderSummary() {
        const planName = selectedPlan === 'monthly' ? 'Mensal' : (IS_UPGRADE ? 'Anual (com crédito)' : 'Anual');
        document.getElementById('summary-plan-name').textContent = planName;
        document.getElementById('summary-plan-price').textContent = 'R$ ' + selectedPrice.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

        // Linha de parcelamento
        const rows = INSTALLMENT_ROWS[selectedPlan] || [];
        const chosen = rows.find(r => r.installments === selectedInstallments);
        const instRow  = document.getElementById('summary-installment-row');
        const instText = document.getElementById('summary-installment-text');
        const noteEl   = document.getElementById('summary-total-note');
        if (chosen && chosen.installments > 1 && instRow && instText) {
            instRow.classList.remove('d-none');
            const badge = chosen.has_interest
                ? `<span class="badge bg-warning text-dark ms-1" style="font-size:.7rem;">+juros</span>`
                : `<span class="badge bg-success ms-1" style="font-size:.7rem;">sem juros</span>`;
            instText.innerHTML = `${chosen.installments}x R$ ${chosen.installment_value.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}${badge}`;
            if (chosen.has_interest && noteEl) {
                noteEl.textContent = `Total com juros: R$ ${chosen.total_value.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
                noteEl.classList.remove('d-none');
            } else { noteEl && noteEl.classList.add('d-none'); }
        } else {
            instRow && instRow.classList.add('d-none');
            noteEl  && noteEl.classList.add('d-none');
        }

        if (appliedCoupon) {
            document.getElementById('coupon-applied-row')?.classList.remove('d-none');
            document.getElementById('summary-coupon-discount').textContent = '- R$ ' + appliedCoupon.discount.toLocaleString('pt-BR', {minimumFractionDigits:2});
        } else {
            hideCouponApplied();
        }
    }

    function hideCouponApplied() {
        document.getElementById('coupon-applied-row')?.classList.add('d-none');
        document.getElementById('removeCouponBtn')?.classList.add('d-none');
        document.getElementById('applyCouponBtn')?.classList.remove('d-none');
    }

    // ── Coupon ─────────────────────────────────────────────────
    function applyCoupon() {
        const code = document.getElementById('couponInput')?.value?.trim();
        const msgEl = document.getElementById('couponMessage');
        if (!code) { msgEl.innerHTML = '<span class="text-danger">Informe o código do cupom.</span>'; return; }

        document.getElementById('applyCouponBtn').disabled = true;
        document.getElementById('applyCouponBtn').innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch('/index.php?url=<?= obfuscateUrl('subscription/validate-coupon') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ code, plan_type: selectedPlan, base_price: basePrice })
        })
        .then(r => r.json())
        .then(result => {
            document.getElementById('applyCouponBtn').disabled = false;
            document.getElementById('applyCouponBtn').innerHTML = 'Aplicar';
            if (result.valid) {
                appliedCoupon  = { code, discount: result.discount, final_price: result.final_price };
                selectedPrice  = result.final_price;
                msgEl.innerHTML = `<span class="text-success">${result.message}</span>`;
                document.getElementById('removeCouponBtn')?.classList.remove('d-none');
                document.getElementById('applyCouponBtn')?.classList.add('d-none');
                // Re-calcular parcelas com novo preço após cupom
                recalcInstallmentRows(selectedPlan, result.final_price);
                updateOrderSummary();
                if (cardPaymentBrickController) {
                    cardPaymentBrickController.unmount();
                    renderCardPaymentBrick(mp.bricks());
                }
            } else {
                msgEl.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle me-1"></i>${result.message}</span>`;
                appliedCoupon = null;
            }
        })
        .catch(() => {
            document.getElementById('applyCouponBtn').disabled = false;
            document.getElementById('applyCouponBtn').innerHTML = 'Aplicar';
            msgEl.innerHTML = '<span class="text-danger">Erro ao verificar cupom.</span>';
        });
    }

    function removeCoupon() {
        appliedCoupon = null;
        selectedPrice = basePrice;
        document.getElementById('couponInput').value = '';
        document.getElementById('couponMessage').innerHTML = '';
        hideCouponApplied();
        updateOrderSummary();
        if (cardPaymentBrickController) {
            cardPaymentBrickController.unmount();
            renderCardPaymentBrick(mp.bricks());
        }
    }

    document.getElementById('couponInput')?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); } });

    const renderCardPaymentBrick = async (bricksBuilder) => {
        const instCfg = INSTALLMENT_CONFIG[selectedPlan] || { maxInstallments: 1, minInstallments: 1 };

        const initObj = {
            amount: selectedPrice,
            payer:  { email: "<?= Auth::getUser()['email'] ?>" },
        };
        // Pré-selecionar parcelas no brick se o usuário escolheu mais de 1
        if (selectedInstallments > 1) {
            initObj.installments = selectedInstallments;
        }

        const settings = {
            initialization: initObj,
            customization: {
                visual: {
                    style: { theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'default' },
                    hideFormTitle: true,
                    hidePaymentButton: false,
                },
                paymentMethods: {
                    types: { includedByPriority: ['credit_card', 'debit_card'] },
                    maxInstallments: instCfg.maxInstallments,
                    minInstallments: instCfg.minInstallments,
                },
            },
            callbacks: {
                onReady: () => {
                    console.log("Brick Ready | plan=" + selectedPlan + " installments=" + selectedInstallments);
                },
                onSubmit: (formData) => {
                    return new Promise((resolve, reject) => {
                        const payload = JSON.parse(json_encode_with_brick_data(formData));
                        payload.plan_type   = selectedPlan;
                        payload.is_upgrade  = IS_UPGRADE;
                        payload.coupon_code = appliedCoupon ? appliedCoupon.code : '';
                        // Garantir que installments é enviado (brick ou nossa seleção)
                        if (!payload.installments && selectedInstallments > 1) {
                            payload.installments = selectedInstallments;
                        }
                        if (!payload.transaction_amount) payload.transaction_amount = selectedPrice;

                        fetch('/index.php?url=' + '<?= obfuscateUrl('checkout') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload),
                        })
                        .then(r => r.json())
                        .then(result => {
                            if (result.status === 'approved') {
                                window.location.href = '/index.php?url=' + '<?= obfuscateUrl('subscription-success') ?>';
                                resolve();
                            } else {
                                let errorMsg = 'Erro: ' + (result.message || result.status_detail || 'Pagamento não aprovado');
                                if (result.debug_info) {
                                    errorMsg += ' [DEBUG: ' + result.debug_info.message + ' (ID: ' + result.debug_info.id + ')]';
                                }
                                showPaymentError(errorMsg);
                                reject();
                            }
                        })
                        .catch(() => { showPaymentError("Erro ao processar. Tente novamente."); reject(); });
                    });
                },
                onError: (error) => { console.error("Brick Error:", error); },
            },
        };
        cardPaymentBrickController = await bricksBuilder.create('cardPayment', 'paymentBrick_container', settings);
    };

    // ── Seletor de Parcelas ──────────────────────────────────────
    function renderInstallmentPicker(plan, baseAmt) {
        const wrapper = document.getElementById('installment-picker-wrapper');
        const select  = document.getElementById('installment-select');
        if (!wrapper || !select) return;

        const rows = INSTALLMENT_ROWS[plan] || [];
        if (rows.length <= 1) {
            wrapper.classList.add('d-none');
            return;
        }

        wrapper.classList.remove('d-none');
        select.innerHTML = '';

        rows.forEach(row => {
            const opt = document.createElement('option');
            opt.value = row.installments;

            const valFmt = row.installment_value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const totalFmt = row.total_value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const interestTxt = row.has_interest
                ? `  (total R$ ${totalFmt} c/ juros)`
                : (row.installments > 1 ? '  sem juros' : '');

            opt.textContent = `${row.installments}x  R$ ${valFmt}${interestTxt}`;
            opt.selected = (row.installments === selectedInstallments);
            select.appendChild(opt);
        });
    }

    function chooseInstallment(n) {
        selectedInstallments = n;
        updateOrderSummary();
        // Remontar o brick com installments pré-selecionado
        if (cardPaymentBrickController) {
            cardPaymentBrickController.unmount();
            renderCardPaymentBrick(mp.bricks());
        }
    }

    function updateInstallmentHint() { /* substituído por renderInstallmentPicker */ }

    function json_encode_with_brick_data(formData) {
        const data = {
            transaction_amount: formData.transaction_amount,
            payment_method_id:  formData.payment_method_id,
            payer: {
                email: formData.payer.email,
                identification: {
                    type:   formData.payer.identification.type,
                    number: formData.payer.identification.number ? formData.payer.identification.number.replace(/\D/g, '') : '',
                },
            },
        };
        if (formData.token)                            data.token        = formData.token;
        if (formData.installments)                     data.installments = formData.installments;
        if (formData.issuer_id && formData.issuer_id !== "") data.issuer_id = formData.issuer_id;
        return JSON.stringify(data);
    }

    const MP_STATUS_MESSAGES = {
        'cc_rejected_bad_filled_security_code': 'Código de segurança (CVV) inválido. Verifique os 3 dígitos no verso do cartão.',
        'cc_rejected_bad_filled_date':          'Data de validade incorreta. Verifique o mês e o ano do cartão.',
        'cc_rejected_bad_filled_other':         'Dados do cartão inválidos. Verifique as informações e tente novamente.',
        'cc_rejected_bad_filled_card_number':   'Número do cartão incorreto. Verifique e tente novamente.',
        'cc_rejected_blacklist':                'Cartão recusado. Entre em contato com seu banco.',
        'cc_rejected_call_for_authorize':       'Pagamento não autorizado. Ligue para o seu banco para liberar a transação.',
        'cc_rejected_card_disabled':            'Cartão desativado. Entre em contato com o banco emissor.',
        'cc_rejected_duplicated_payment':       'Pagamento duplicado detectado. Aguarde alguns minutos antes de tentar novamente.',
        'cc_rejected_high_risk':                'Pagamento recusado por segurança. Tente com outro cartão ou entre em contato com o suporte.',
        'cc_rejected_insufficient_amount':      'Saldo insuficiente no cartão.',
        'cc_rejected_invalid_installments':     'Número de parcelas inválido para este cartão.',
        'cc_rejected_max_attempts':             'Número máximo de tentativas atingido. Tente novamente mais tarde.',
        'cc_rejected_other_reason':             'Pagamento recusado pelo banco. Tente com outro cartão ou entre em contato com o suporte.',
        'pending_contingency':                  'Pagamento em processamento. Aguarde a confirmação.',
        'pending_review_manual':                'Pagamento em análise. Você será notificado em breve.',
    };

    function friendlyMpMessage(rawMessage) {
        if (!rawMessage) return null;
        for (const [key, msg] of Object.entries(MP_STATUS_MESSAGES)) {
            if (rawMessage.includes(key)) return msg;
        }
        return null;
    }

    function showPaymentError(message) {
        const errorContainer = document.getElementById('payment-error-container');
        const errorMessage   = document.getElementById('payment-error-message');
        
        // Se a mensagem contém [DEBUG:], extraímos para mostrar separadamente
        let debugPart = '';
        const debugMatch = message.match(/\[DEBUG: (.*)\]/);
        if (debugMatch) {
            debugPart = '<br><small class="text-muted" style="font-size: 0.8em;">Erro Interno: ' + debugMatch[1] + '</small>';
            message = message.replace(/\[DEBUG: .*\]/, '');
        }

        const friendly = friendlyMpMessage(message);
        errorMessage.innerHTML = (friendly || message) + debugPart;
        errorContainer.classList.remove('d-none');
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.getElementById('accept_terms').addEventListener('change', function() {
        const container = document.getElementById('paymentBrick_container');
        const warning   = document.getElementById('terms_warning');
        if (this.checked) {
            container.classList.remove('opacity-50');
            container.style.pointerEvents = 'auto';
            warning.classList.add('d-none');
        } else {
            container.classList.add('opacity-50');
            container.style.pointerEvents = 'none';
            warning.classList.remove('d-none');
        }
    });

    // Inicializar com o plano padrão selecionado
    selectPlan(selectedPlan, selectedPrice);
    renderCardPaymentBrick(mp.bricks());</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
