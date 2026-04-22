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
                            Em conformidade com o <strong>Artigo 49 do Código de Defesa do Consumidor (Brasil)</strong>, o usuário possui o direito de desistir da assinatura (Plano PRO) no prazo de <strong>7 (sete) dias</strong> a contar da data da aprovação do pagamento.
                        </p>
                        <h5 class="fw-bold mt-4">Como solicitar o estorno:</h5>
                        <p>
                            Para solicitar o reembolso integral do valor pago, o usuário deve:
                        </p>
                        <ol>
                            <li>Enviar um e-mail para <span class="text-primary">contato@smartreturns.com.br</span> (ou o e-mail de suporte indicado no painel).</li>
                            <li>Informar o e-mail da conta e o comprovante de transação do Mercado Pago.</li>
                            <li>O estorno será processado diretamente através da plataforma Mercado Pago conforme os prazos da operadora do seu cartão.</li>
                        </ol>
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
