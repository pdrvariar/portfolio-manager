<?php
$title = 'Assinatura Realizada!';
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card border-0 shadow-lg p-5">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
            </div>
            <h2 class="fw-bold mb-3">Parabéns!</h2>
            <p class="lead text-muted mb-4">Sua assinatura do <strong>Plano PRO</strong> foi processada com sucesso. Agora você tem acesso ilimitado a todas as ferramentas.</p>
            <div class="d-grid">
                <a href="/index.php?url=<?= obfuscateUrl('dashboard') ?>" class="btn btn-primary btn-lg">Ir para o Dashboard</a>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
