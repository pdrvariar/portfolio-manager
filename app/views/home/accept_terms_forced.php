<?php
$title = 'Aceite dos Termos de Uso';
ob_start();
?>
<div class="row justify-content-center min-vh-100 align-items-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock-fill text-primary display-4 mb-3"></i>
                    <h3 class="fw-bold">Atualizamos nossos Termos</h3>
                    <p class="text-muted">Para continuar utilizando a plataforma, precisamos que você leia e aceite as novas condições legais.</p>
                </div>

                <div class="alert alert-info border-0 bg-light-primary mb-4">
                    <p class="small mb-0">
                        Nossos termos incluem cláusulas importantes sobre:
                        <br>• Isenção de responsabilidade sobre prejuízos financeiros.
                        <br>• Natureza estritamente educacional do site.
                        <br>• Política de garantia de 7 dias (CDC).
                    </p>
                </div>

                <div class="mb-4" style="max-height: 200px; overflow-y: auto; font-size: 0.9rem; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px;">
                    <h6 class="fw-bold">Resumo dos Termos:</h6>
                    <p>1. Este site é uma ferramenta de estudo e simulação. Não realizamos recomendações de investimento.</p>
                    <p>2. O usuário é o único responsável por suas decisões financeiras. O sistema não se responsabiliza por eventuais prejuízos.</p>
                    <p>3. Garantia de 7 dias para assinaturas PRO conforme o Código de Defesa do Consumidor.</p>
                    <p>4. Os dados de cotações podem apresentar atrasos ou imprecisões dependendo da fonte.</p>
                    <p class="mb-0">Para ler o documento completo, <a href="/index.php?url=terms" target="_blank">clique aqui</a>.</p>
                </div>

                <form method="POST" action="/index.php?url=terms/accept">
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="accept" id="accept_check" required>
                        <label class="form-check-label text-dark fw-semibold" for="accept_check">
                            Eu li e aceito integralmente os Termos de Uso e Condições.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3">
                        Aceitar e Ir para o Dashboard
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="/index.php?url=logout" class="text-muted small text-decoration-none">Sair do sistema</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
