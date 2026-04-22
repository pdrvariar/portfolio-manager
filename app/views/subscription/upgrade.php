<?php
$user = Auth::getUser();
$userModel = new User();
$userData = $userModel->findById($user['id']);

// Variáveis injetadas pelo controller
$upgradeMode         = $upgradeMode         ?? false;
$proratedCredit      = $proratedCredit      ?? 0;
$proratedYearlyPrice = $proratedYearlyPrice ?? 179.40;
$activeSub           = $activeSub           ?? null;

$currentExpiration = !empty($userData['subscription_expires_at'])
    ? date('d/m/Y', strtotime($userData['subscription_expires_at']))
    : null;

$title = $upgradeMode ? 'Upgrade para PRO Anual - Smart Returns' : 'Planos e Preços - Smart Returns | Plano PRO';
$meta_description = $upgradeMode
    ? 'Faça upgrade para o plano Anual PRO da Smart Returns e economize com o crédito dos seus dias restantes.'
    : 'Desbloqueie recursos premium da Smart Returns: 1000 simulações/mês, Monte Carlo, cálculo de impostos, histórico completo e muito mais. Plano PRO a partir de R$ 29,90/mês.';
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
                <div class="card h-100 shadow-sm plan-card active" id="card-monthly" onclick="selectPlan('monthly', 29.90)">
                    <div class="card-body p-4 text-center">
                        <h4 class="fw-bold">Mensal</h4>
                        <div class="my-4">
                            <span class="display-5 fw-bold">R$ 29,90</span>
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
            <div class="col-md-5">
                <div class="card h-100 shadow-sm plan-card position-relative <?= $upgradeMode ? 'active' : '' ?>"
                     id="card-yearly" onclick="selectPlan('yearly', <?= $proratedYearlyPrice ?>)">
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge-save">
                            <?= $upgradeMode ? 'CRÉDITO APLICADO' : '50% DE DESCONTO' ?>
                        </span>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="d-flex justify-content-center align-items-center mb-1">
                            <h4 class="fw-bold mb-0">Anual</h4>
                        </div>
                        <div class="my-4">
                            <?php if ($upgradeMode && $proratedCredit > 0): ?>
                                <div class="text-strike">R$ 179,40</div>
                                <span class="display-5 fw-bold text-primary">R$ <?= number_format($proratedYearlyPrice, 2, ',', '.') ?></span>
                                <span class="text-muted">/upgrade</span>
                                <div class="small text-success fw-bold">
                                    Crédito de R$ <?= number_format($proratedCredit, 2, ',', '.') ?> aplicado
                                </div>
                            <?php else: ?>
                                <div class="text-strike">R$ 358,80</div>
                                <span class="display-5 fw-bold text-primary">R$ 179,40</span>
                                <span class="text-muted">/ano</span>
                                <div class="small text-success fw-bold">R$ 14,95 /mês</div>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small"><?= $upgradeMode ? 'Aproveite o crédito dos seus dias restantes.' : 'O melhor custo-benefício para investidores sérios.' ?></p>
                        <hr>
                        <ul class="list-unstyled text-start mb-0">
                            <?php if ($upgradeMode): ?>
                                <li class="mb-2"><i class="bi bi-arrow-up-circle text-primary me-2"></i> <strong>Upgrade imediato para Anual</strong></li>
                            <?php else: ?>
                                <li class="mb-2"><i class="bi bi-star-fill text-warning me-2"></i> <strong>Economize R$ 179,40 por ano</strong></li>
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
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Total a pagar:</span>
                                        <span id="summary-plan-price" class="fs-4 fw-bold">
                                            R$ <?= number_format($upgradeMode ? $proratedYearlyPrice : 29.90, 2, ',', '.') ?>
                                        </span>
                                    </div>
                                </div>


                                 <div id="payment-error-container" class="alert alert-danger d-none mb-3" role="alert">
                                     <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                     <span id="payment-error-message"></span>
                                 </div>

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
    const IS_UPGRADE    = <?= $upgradeMode ? 'true' : 'false' ?>;
    const PRORATED_PRICE = <?= (float)$proratedYearlyPrice ?>;

    let selectedPlan  = IS_UPGRADE ? 'yearly' : 'monthly';
    let selectedPrice = IS_UPGRADE ? PRORATED_PRICE : 29.90;
    let cardPaymentBrickController = null;
    const mp = new MercadoPago('<?= getenv('MERCADOPAGO_PUBLIC_KEY') ?: ($_ENV['MERCADOPAGO_PUBLIC_KEY'] ?? '') ?>');

    function selectPlan(plan, price) {
        selectedPlan  = plan;
        selectedPrice = price;

        document.getElementById('card-monthly') && document.getElementById('card-monthly').classList.toggle('active', plan === 'monthly');
        document.getElementById('card-yearly').classList.toggle('active', plan === 'yearly');

        document.getElementById('summary-plan-name').textContent = plan === 'monthly' ? 'Mensal' : (IS_UPGRADE ? 'Anual (com crédito)' : 'Anual');
        document.getElementById('summary-plan-price').textContent = 'R$ ' + price.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

        if (cardPaymentBrickController) {
            cardPaymentBrickController.unmount();
            renderCardPaymentBrick(mp.bricks());
        }
    }

    const renderCardPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: selectedPrice,
                payer: { email: "<?= Auth::getUser()['email'] ?>" },
            },
            customization: {
                visual: {
                    style: { theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'default' },
                    hideFormTitle: true,
                    hidePaymentButton: false,
                },
                paymentMethods: { types: { includedByPriority: ['card'] } },
            },
            callbacks: {
                onReady: () => { console.log("Brick Ready"); },
                onSubmit: (formData) => {
                    return new Promise((resolve, reject) => {
                        const payload = JSON.parse(json_encode_with_brick_data(formData));
                        payload.plan_type  = selectedPlan;
                        payload.is_upgrade = IS_UPGRADE;
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
    renderCardPaymentBrick(mp.bricks());
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
