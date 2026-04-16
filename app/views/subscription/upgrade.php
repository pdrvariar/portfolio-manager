<?php
$title = 'Desbloquear Plano PRO';
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-lg overflow-hidden">
            <div class="row g-0">
                <div class="col-md-5 bg-primary d-flex align-items-center justify-content-center p-5 text-white">
                    <div class="text-center">
                        <i class="bi bi-rocket-takeoff-fill display-1 mb-4"></i>
                        <h2 class="fw-bold">Eleve o nível!</h2>
                        <p class="lead opacity-75">Desbloqueie todo o poder da nossa plataforma de gestão de portfólio.</p>
                    </div>
                </div>
                <div class="col-md-7 p-5">
                    <div class="mb-4">
                        <h3 class="fw-bold text-dark">Plano PRO</h3>
                        <p class="text-muted">Acesso ilimitado a ferramentas avançadas de análise.</p>
                    </div>

                    <ul class="list-unstyled mb-5">
                        <li class="mb-3 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span><strong>Histórico Completo:</strong> Mais de 5 anos de dados históricos.</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span><strong>Portfólios Ilimitados:</strong> Sem limites de carteiras.</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span><strong>Ativos Ilimitados:</strong> Mais de 5 ativos por carteira.</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span><strong>Cálculo de Impostos:</strong> Automação total de tributos.</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span><strong>Aportes Estratégicos:</strong> Algoritmos inteligentes de aporte.</span>
                        </li>
                    </ul>

                    <div class="alert alert-info border-0 bg-light-primary mb-4 p-3 rounded-3">
                        <div class="d-flex">
                            <i class="bi bi-shield-check fs-2 me-3 text-primary"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Garantia Incondicional de 7 Dias</h6>
                                <p class="small mb-0 opacity-75">Teste sem riscos. Se não gostar, devolvemos seu dinheiro integralmente, conforme o Código de Defesa do Consumidor.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check small">
                            <input class="form-check-input" type="checkbox" id="accept_terms" required>
                            <label class="form-check-label text-muted" for="accept_terms">
                                Eu li e aceito os <a href="/index.php?url=terms" target="_blank" class="fw-bold">Termos de Uso</a>, incluindo as cláusulas de isenção de responsabilidade financeira.
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-3">
                        <!-- SÊNIOR: Container para o Card Payment Brick (Apenas Cartão) -->
                        <div id="payment-error-container" class="alert alert-danger d-none mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <span id="payment-error-message"></span>
                        </div>

                        <div id="paymentBrick_container" class="opacity-50" style="pointer-events: none;"></div>
                        <div id="terms_warning" class="text-danger small text-center">
                            Marque o aceite dos termos acima para habilitar o pagamento.
                        </div>
                        
                        <p class="text-center text-muted small mb-0">
                            Pagamento seguro via Checkout Transparente Mercado Pago.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="javascript:history.back()" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i> Voltar para onde eu estava
            </a>
        </div>
    </div>
</div>

<!-- SÊNIOR: Script para habilitar o pagamento apenas após o aceite -->
<script>
    document.getElementById('accept_terms').addEventListener('change', function() {
        const container = document.getElementById('paymentBrick_container');
        const warning = document.getElementById('terms_warning');
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
</script>

<!-- SÊNIOR: Integração elegante com Checkout Bricks do Mercado Pago -->
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    const mp = new MercadoPago('<?= getenv('MERCADOPAGO_PUBLIC_KEY') ?: ($_ENV['MERCADOPAGO_PUBLIC_KEY'] ?? '') ?>');
    const bricksBuilder = mp.bricks();

    const renderCardPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: 29.90, // Valor fixo do plano PRO
                payer: {
                    email: "<?= Auth::getUser()['email'] ?>",
                },
            },
            customization: {
                paymentMethods: {
                    maxInstallments: 1,
                    types: {
                        includedByPriority: ['card'] // Apenas Cartão
                    }
                },
                visual: {
                    style: {
                        theme: 'default',
                    },
                    hideFormTitle: true,
                },
            },
            callbacks: {
                onReady: () => {
                    console.log("Brick Ready");
                },
                onSubmit: (formData) => {
                    // SÊNIOR: Log para debug (não enviar para produção com dados sensíveis)
                    console.log("Form Data:", formData);
                    
                    // SÊNIOR: Envia os dados para o backend processar via API
                    return new Promise((resolve, reject) => {
                        fetch('/index.php?url=' + '<?= obfuscateUrl('subscription/checkout') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: json_encode_with_brick_data(formData),
                        })
                        .then((response) => response.json())
                        .then((result) => {
                            if (result.status === 'approved') {
                                window.location.href = '/index.php?url=' + '<?= obfuscateUrl('subscription/success') ?>';
                                resolve();
                            } else if (result.status === 'pending' || result.status === 'in_process') {
                                window.location.href = '/index.php?url=' + '<?= obfuscateUrl('subscription/pending') ?>';
                                resolve();
                            } else {
                                // SÊNIOR: Melhora a exibição do erro para o usuário integrado ao layout
                                const errorMsg = result.message || result.status_detail || 'Pagamento não aprovado';
                                showPaymentError('Erro no Pagamento: ' + errorMsg);
                                reject();
                            }
                        })
                        .catch((error) => {
                            console.error("Erro no processamento:", error);
                            showPaymentError("Ocorreu um erro ao processar seu pagamento. Verifique sua conexão ou tente novamente.");
                            reject();
                        });
                    });
                },
                onError: (error) => {
                    console.error("Brick Error:", error);
                },
            },
        };
        window.cardPaymentBrickController = await bricksBuilder.create(
            'cardPayment',
            'paymentBrick_container',
            settings
        );
    };

    function json_encode_with_brick_data(formData) {
        // Converte os dados do Brick para o formato esperado pelo MercadoPagoService
        const data = {
            transaction_amount: formData.transaction_amount,
            payment_method_id: formData.payment_method_id,
            payer: {
                email: formData.payer.email,
                identification: {
                    type: formData.payer.identification.type,
                    number: formData.payer.identification.number ? formData.payer.identification.number.replace(/\D/g, '') : '',
                },
            },
        };

        if (formData.token) data.token = formData.token;
        if (formData.installments) data.installments = formData.installments;
        // SÊNIOR: Apenas envia issuer_id se estiver presente e não for vazio
        if (formData.issuer_id && formData.issuer_id !== "") {
            data.issuer_id = formData.issuer_id;
        }

        return JSON.stringify(data);
    }

    function showPaymentError(message) {
        const errorContainer = document.getElementById('payment-error-container');
        const errorMessage = document.getElementById('payment-error-message');
        
        errorMessage.textContent = message;
        errorContainer.classList.remove('d-none');
        
        // Scroll suave até o erro se estiver longe da visão
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    renderCardPaymentBrick(bricksBuilder);
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
