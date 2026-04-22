<?php
$title = 'Ocorreu um problema';
$meta_robots = 'noindex, nofollow';
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card border-0 shadow-lg p-5">
            <div class="mb-4">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 5rem;"></i>
            </div>
            <h2 class="fw-bold mb-3">Ops! Algo deu errado.</h2>
            <p class="lead text-muted mb-4">O pagamento nÃ£o foi concluÃ­do. Caso tenha havido algum desconto, entre em contato com nosso suporte.</p>
            <div class="d-grid gap-3">
                <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="btn btn-primary btn-lg">Tentar Novamente</a>
                <a href="/index.php?url=<?= obfuscateUrl('dashboard') ?>" class="btn btn-outline-secondary">Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>

