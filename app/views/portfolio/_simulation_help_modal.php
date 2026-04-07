<!-- Simulation Type Help Modal -->
<div class="modal fade" id="simulationHelpModal" tabindex="-1" aria-labelledby="simulationHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="simulationHelpModalLabel border-0">
                    <i class="bi bi-info-circle-fill me-2"></i>Guia de Tipos de Simulação
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="lead mb-4">Escolher o tipo de simulação correto permite modelar cenários reais de investimento e entender como sua estratégia se comporta ao longo do tempo.</p>

                <!-- Tipo: Padrão -->
                <div class="card mb-4 border-0 bg-light-subtle shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-3 p-2 me-3">
                                <i class="bi bi-briefcase-fill fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">Padrão (Buy & Hold)</h5>
                        </div>
                        <p class="card-text text-muted">A simulação mais simples: você investe o <strong>Capital Inicial</strong> na data de início e não faz mais nenhum aporte.</p>
                        <div class="alert alert-light border-0 py-2 small">
                            <strong>Comportamento:</strong> O portfólio oscila apenas pela variação dos ativos e pelos rebalanceamentos periódicos definidos.
                        </div>
                    </div>
                </div>

                <!-- Tipo: Aportes Periódicos -->
                <div class="card mb-4 border-0 bg-light-subtle shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success text-white rounded-3 p-2 me-3">
                                <i class="bi bi-calendar-plus-fill fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">Com Aportes Periódicos</h5>
                        </div>
                        <p class="card-text text-muted">Simula o investidor que poupa e investe regularmente (ex: mensalmente) um valor fixo.</p>
                        <ul class="small text-muted mb-3">
                            <li><strong>Distribuição:</strong> O valor é dividido entre <strong>todos os ativos</strong> seguindo o peso-alvo definido.</li>
                            <li><strong>Moeda:</strong> Se o aporte for em USD e o portfólio em BRL, o sistema converte automaticamente na data do aporte.</li>
                        </ul>
                    </div>
                </div>

                <!-- Tipo: Aporte Direcionado (Smart) -->
                <div class="card mb-4 border-0 bg-light-subtle shadow-sm border-start border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success text-white rounded-3 p-2 me-3">
                                <i class="bi bi-bullseye fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">Aporte Direcionado (Smart)</h5>
                        </div>
                        <p class="card-text text-muted">A estratégia mais eficiente para manter o equilíbrio sem precisar vender ativos (evita impostos).</p>
                        <div class="row g-3 small mb-3">
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-white h-100">
                                    <strong>Como funciona:</strong> Todo o dinheiro do aporte vai primeiro para o ativo que está <strong>mais longe do seu alvo</strong> (o que caiu mais ou subiu menos).
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 border rounded bg-white h-100">
                                    <strong>Vantagem:</strong> Você reequilibra a carteira "pela compra", focando sempre no que está barato em relação à sua meta original.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipo: Aporte em Caixa SELIC -->
                <div class="card mb-4 border-0 bg-light-subtle shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-secondary text-white rounded-3 p-2 me-3">
                                <i class="bi bi-piggy-bank-fill fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">Aporte em Caixa (SELIC)</h5>
                        </div>
                        <p class="card-text text-muted">Simula o acúmulo de capital em uma reserva de liquidez antes de investir nos ativos principais.</p>
                        <p class="small text-muted">O aporte entra em uma conta "Caixa" que rende a <strong>Taxa SELIC</strong> diária. O dinheiro só é investido nos ativos da carteira quando ocorre um <strong>Rebalanceamento</strong>.</p>
                    </div>
                </div>

                <!-- Tipo: Aportes Estratégicos -->
                <div class="card mb-4 border-0 bg-light-subtle shadow-sm border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning text-dark rounded-3 p-2 me-3">
                                <i class="bi bi-lightning-charge-fill fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">Aportes Estratégicos (Buy the Dip)</h5>
                        </div>
                        <p class="card-text text-muted">Simula a entrada de capital extra apenas em momentos de forte queda do mercado.</p>
                        <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning border-opacity-25 small mb-3">
                            <strong>Exemplo:</strong> Se você definir um limiar de 10%, o sistema monitora o portfólio mês a mês. Se em algum mês a queda for ≥ 10%, ele injeta o percentual de aporte definido.
                        </div>
                        <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i> Ideal para testar se ter uma reserva de oportunidade realmente melhora seu retorno histórico.</p>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold mb-3"><i class="bi bi-gear-wide-connected me-2"></i>Entendendo os Ajustes</h6>
                <div class="row g-4 small">
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold text-primary">Rebalanceamento "Apenas Compras"</p>
                        <p class="text-muted">Ao marcar esta opção, o sistema nunca venderá ativos para atingir o alvo. Ele apenas usará o dinheiro novo (aportes) para equilibrar. Útil para simular a fase de acúmulo.</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold text-primary">Correção pela Inflação (IPCA)</p>
                        <p class="text-muted">Faz com que o valor do seu aporte aumente todos os meses conforme a inflação oficial (IPCA), mantendo o poder de compra real do seu investimento ao longo de décadas.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-3">
                <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">Entendi, vamos lá!</button>
            </div>
        </div>
    </div>
</div>