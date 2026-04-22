<?php
$title = 'Pagamento em Processamento';`n$meta_robots = 'noindex, nofollow';
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card border-0 shadow-lg p-5">
            <div class="mb-4">
                <i class="bi bi-clock-history text-warning" style="font-size: 5rem;"></i>
            </div>
            <h2 class="fw-bold mb-3">Pagamento Pendente</h2>
            <p class="lead text-muted mb-4">Estamos aguardando a confirmaÃ§Ã£o do pagamento pelo Mercado Pago. Assim que for aprovado, seu plano PRO serÃ¡ ativado automaticamente.</p>
            <div class="d-grid">
                <a href="/index.php?url=<?= obfuscateUrl('dashboard') ?>" class="btn btn-primary btn-lg">Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>

