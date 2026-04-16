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

                    <div class="d-grid gap-3">
                        <!-- SÊNIOR: Container para o Card Payment Brick -->
                        <div id="paymentBrick_container"></div>
                        
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
                visual: {
                    style: {
                        theme: 'default', // 'default' | 'dark' | 'bootstrap' | 'flat'
                    }
                },
                paymentMethods: {
                    maxInstallments: 1
                }
            },
            callbacks: {
                onReady: () => {
                    console.log("Brick Ready");
                },
                onSubmit: (formData) => {
                    // SÊNIOR: Envia os dados para o backend processar via API
                    return new Promise((resolve, reject) => {
                        fetch('/index.php?url=<?= obfuscateUrl('subscription/checkout') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: json_encode_with_brick_data(formData),
                        })
                        .then((response) => response.json())
                        .then((result) => {
                            if (result.status === 'approved') {
                                window.location.href = '/index.php?url=<?= obfuscateUrl('subscription/success') ?>';
                                resolve();
                            } else if (result.status === 'in_process') {
                                window.location.href = '/index.php?url=<?= obfuscateUrl('subscription/pending') ?>';
                                resolve();
                            } else {
                                alert('Pagamento não aprovado: ' + (result.message || result.status_detail || 'Erro desconhecido'));
                                reject();
                            }
                        })
                        .catch((error) => {
                            console.error("Erro no processamento:", error);
                            alert("Ocorreu um erro ao processar seu pagamento. Tente novamente.");
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
        return JSON.stringify({
            token: formData.token,
            issuer_id: formData.issuer_id,
            payment_method_id: formData.payment_method_id,
            transaction_amount: formData.transaction_amount,
            installments: formData.installments,
            payer: {
                email: formData.payer.email,
                identification: {
                    type: formData.payer.identification.type,
                    number: formData.payer.identification.number,
                },
            },
        });
    }

    renderCardPaymentBrick(bricksBuilder);
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
