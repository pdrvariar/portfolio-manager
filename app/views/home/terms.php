<?php
$title = 'Termos de Uso e Isenção de Responsabilidade - Smart Returns';
$meta_description = 'Leia os Termos de Uso da Smart Returns: isenção de responsabilidade, política de reembolso em 7 dias e natureza educacional da plataforma de simulação de investimentos.';
$canonical_url = 'https://smartreturns.com.br/index.php?url=terms';
ob_start();
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5">
                    <h1 class="fw-bold mb-4">Termos de Uso e Isenção de Responsabilidade</h1>
                    
                    <section class="mb-5">
                        <h4 class="fw-bold text-primary">1. Natureza do Serviço</h4>
                        <p>
                            Este website é uma ferramenta de <strong>estudo e simulação</strong>. Todo o conteúdo, dados e ferramentas aqui disponibilizados têm caráter estritamente educativo e informativo.
                        </p>
                        <div class="alert alert-warning border-0 bg-light-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Aviso Importante:</strong> Este sistema NÃO fornece recomendações de investimento, consultoria financeira, jurídica ou tributária.
                        </div>
                    </section>

                    <section class="mb-5">
                        <h4 class="fw-bold text-primary">2. Responsabilidade do Usuário</h4>
                        <p>
                            O usuário é o único e exclusivo responsável por suas decisões financeiras e investimentos. Ao utilizar este sistema, você reconhece que:
                        </p>
                        <ul>
                            <li>Investimentos financeiros envolvem riscos e podem resultar em perda de capital.</li>
                            <li>Resultados passados de simulações não são garantia de rentabilidade futura.</li>
                            <li>É de sua responsabilidade verificar a veracidade dos dados antes de tomar qualquer decisão real.</li>
                        </ul>
                    </section>

                    <section class="mb-5" id="isencao">
                        <h4 class="fw-bold text-primary">3. Isenção de Responsabilidade</h4>
                        <p>
                            Os desenvolvedores e proprietários deste site <strong>não se responsabilizam</strong> por:
                        </p>
                        <ul>
                            <li>Eventuais prejuízos financeiros, lucros cessantes ou danos de qualquer natureza decorrentes do uso das ferramentas.</li>
                            <li>Inexatidão ou atraso nos dados de cotações obtidos de fontes externas.</li>
                            <li>Decisões tomadas com base nas informações apresentadas pelo sistema.</li>
                        </ul>
                    </section>

                    <section class="mb-5" id="reembolso">
                        <h4 class="fw-bold text-primary">4. Garantia e Direito de Arrependimento (Reembolso)</h4>
                        <p>
                            Em plena conformidade com o <strong>Art. 49 do Código de Defesa do Consumidor (Lei nº 8.078/1990)</strong>, o usuário que contratar o Plano PRO por meio eletrônico possui o direito de arrependimento e de solicitar o cancelamento da assinatura com reembolso integral, sem necessidade de justificativa, dentro do prazo de <strong>7 (sete) dias corridos</strong> contados da data de aprovação do pagamento.
                        </p>
                        <div class="alert alert-info border-0 bg-light-info mt-3">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Reembolso 100% self-service:</strong> O estorno é solicitado diretamente pelo próprio site, sem necessidade de entrar em contato com o suporte.
                        </div>
                        <h5 class="fw-bold mt-4">Como solicitar o reembolso:</h5>
                        <ol>
                            <li>Acesse sua conta e vá até <strong>Minha Assinatura</strong> (menu do usuário).</li>
                            <li>Dentro do prazo de 7 dias, o botão <strong>"Solicitar Reembolso"</strong> estará disponível automaticamente.</li>
                            <li>Confirme a solicitação. O sistema cancela o acesso PRO imediatamente e aciona o estorno via Mercado Pago de forma automática.</li>
                            <li>Um e-mail de confirmação será enviado ao endereço cadastrado na conta.</li>
                        </ol>
                        <h5 class="fw-bold mt-4">Prazo de crédito:</h5>
                        <p>
                            O valor é estornado integralmente ao meio de pagamento utilizado na contratação. O prazo para o crédito aparecer na fatura depende da operadora do cartão e pode chegar a <strong>até 5 dias úteis</strong> para cartão de débito, ou até <strong>2 faturas subsequentes</strong> para cartão de crédito, conforme as regras da bandeira.
                        </p>
                        <p class="text-muted small">
                            <strong>Observação:</strong> Após o encerramento do prazo de 7 dias, não haverá direito a reembolso proporcional pelo período não utilizado, salvo nos casos previstos em lei ou mediante análise individual pelo suporte (<span class="text-primary">contato@smartreturns.com.br</span>).
                        </p>
                    </section>

                    <section class="mb-4">
                        <h4 class="fw-bold text-primary">5. Aceitação dos Termos</h4>
                        <p>
                            Ao clicar em "Aceito as condições" ou ao utilizar os serviços deste site, você declara ter lido, compreendido e concordado com todos os termos e condições aqui estabelecidos.
                        </p>
                    </section>

                    <div class="text-center mt-5">
                        <a href="javascript:history.back()" class="btn btn-primary px-5 py-2 rounded-3 fw-bold">Voltar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
