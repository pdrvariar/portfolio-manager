<?php
$title = 'Assinatura Confirmada!';
$meta_robots = 'noindex, nofollow';
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card border-0 shadow-lg p-5">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
            </div>
            <h2 class="fw-bold mb-3">Parabéns! 🎉</h2>
            <p class="lead text-muted mb-2">Sua assinatura do <strong>Plano PRO</strong> foi ativada com sucesso!</p>
            <p class="text-muted small mb-4">Você recebeu um e-mail de confirmação com os detalhes.</p>

            <div class="alert alert-info text-start small mb-4">
                <i class="bi bi-shield-check me-2"></i>
                <strong>Garantia de 7 dias:</strong> Se não ficar satisfeito, solicite reembolso completo em
                <a href="/index.php?url=<?= obfuscateUrl('subscription/manage') ?>" class="fw-bold">Gerenciar Assinatura</a>.
            </div>

            <div class="d-grid gap-2">
                <a href="/index.php?url=<?= obfuscateUrl('dashboard') ?>" class="btn btn-primary btn-lg rounded-pill fw-bold">
                    <i class="bi bi-house-door me-2"></i>Ir para o Dashboard
                </a>
                <a href="/index.php?url=<?= obfuscateUrl('subscription/manage') ?>" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-gear me-2"></i>Gerenciar Assinatura
                </a>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>

