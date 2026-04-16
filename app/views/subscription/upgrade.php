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
                        <a href="/index.php?url=<?= obfuscateUrl('subscription/checkout') ?>" class="btn btn-primary btn-lg fw-bold py-3">
                            <i class="bi bi-credit-card me-2"></i>Assinar Agora com Mercado Pago
                        </a>
                        <p class="text-center text-muted small mb-0">
                            Pagamento seguro processado pelo Mercado Pago.
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

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
