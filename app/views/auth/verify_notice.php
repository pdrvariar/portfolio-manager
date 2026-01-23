<?php
/**
 * @var array $data Dados do usuário para verificação (email, full_name)
 */
$title = 'Verifique seu e-mail';
ob_start();
?>

<div class="row justify-content-center min-vh-100 align-items-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden animate__animated animate__fadeIn">
            <div class="card-body p-5 text-center">
                
                <div class="mb-4">
                    <div class="bg-soft-primary d-inline-block p-4 rounded-circle mb-3">
                        <i class="bi bi-envelope-check-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                </div>

                <h2 class="fw-bold mb-3">Quase lá, <?php echo explode(' ', htmlspecialchars($data['full_name']))[0]; ?>!</h2>
                
                <p class="text-muted mb-4">
                    Enviamos um link de ativação para o e-mail:<br>
                    <strong class="text-dark"><?php echo htmlspecialchars($data['email']); ?></strong>
                </p>

                <div class="alert alert-bordered-warning text-start mb-4">
                    <div class="d-flex">
                        <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                        <div class="small">
                            <strong>Não encontrou o e-mail?</strong><br>
                            Verifique a sua pasta de <strong>Spam</strong> ou <strong>Promoções</strong>. Às vezes os filtros de segurança podem desviar nossa mensagem.
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mb-4">
                    <a href="https://<?php echo explode('@', $data['email'])[1]; ?>" target="_blank" class="btn btn-primary py-3 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Ir para meu E-mail
                    </a>
                </div>

                <div class="border-top pt-4">
                    <p class="text-muted small mb-2">Ainda não recebeu nada?</p>
                    <button class="btn btn-link text-decoration-none small fw-bold p-0" onclick="location.reload();">
                        Reenviar e-mail de confirmação
                    </button>
                </div>
            </div>
            
            <div class="card-footer bg-light border-0 py-3 text-center">
                <a href="/index.php?url=<?php echo obfuscateUrl('login'); ?>" class="text-decoration-none small text-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Voltar para o Login
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos locais para animação e refinamento */
    .animate__fadeIn { animation-duration: 0.8s; }
    .bg-soft-primary { background-color: var(--soft-primary); }
    
    /* Melhora a legibilidade do texto no alerta customizado que criamos no style.css */
    .alert-bordered-warning {
        border-left: 4px solid #ffc107;
        background-color: #fff9e6;
        color: #856404;
    }
</style>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>