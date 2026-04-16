<?php
/**
 * @var array $assets Lista de ativos disponíveis
 */
$title = 'Criar Portfólio';
ob_start();

$assetModel = new Asset();
$assets = $assetModel->getAllWithDetails();
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Criar Novo Portfólio</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('portfolio/create'); ?>" id="portfolioForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label d-flex align-items-center">
                                    Nome do Portfólio *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Dê um nome claro à sua estratégia (ex: Aposentadoria 2050)."></i>
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="initial_capital" class="form-label d-flex align-items-center">
                                    Capital Inicial *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O valor em dinheiro que você possui para investir no primeiro dia da simulação."></i>
                                </label>
                                <input type="number" class="form-control" id="initial_capital" name="initial_capital" step="0.01" min="100" value="100000" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label d-flex align-items-center">
                            Descrição
                            <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Use este espaço para anotar as premissas ou objetivos desta carteira específica."></i>
                        </label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_date" class="form-label d-flex align-items-center">
                                    Data Início *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Data do primeiro aporte. O sistema buscará preços históricos a partir deste dia."></i>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                <?php if (!Auth::isPro()): ?>
                                <div class="form-text text-primary small">
                                    <i class="bi bi-info-circle me-1"></i> No Plano Starter, o histórico é limitado aos últimos 5 anos. 
                                    <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="fw-bold text-decoration-none">Desbloquear PRO</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label d-flex align-items-center">
                                    Data Fim (opcional)
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Data final do backtest. Se vazio, usará os dados mais recentes disponíveis."></i>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rebalance_frequency" class="form-label d-flex align-items-center">
                                    Frequência Rebalanceamento *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Define de quanto em quanto tempo o sistema deve 'forçar' a volta dos ativos ao peso-alvo original."></i>
                                </label>
                                <select class="form-select" id="rebalance_frequency" name="rebalance_frequency" required>
                                    <option value="monthly">Mensal</option>
                                    <option value="quarterly">Trimestral</option>
                                    <option value="biannual">Semestral</option>
                                    <option value="annual">Anual</option>
                                    <option value="never">Nunca</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="output_currency" class="form-label d-flex align-items-center">
                            Moeda de Saída *
                            <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="A moeda em que todos os relatórios e gráficos serão apresentados. O sistema faz a conversão automática se houver ativos em moedas diferentes."></i>
                        </label>
                        <select class="form-select" id="output_currency" name="output_currency" required>
                            <option value="BRL">BRL (Real)</option>
                            <option value="USD">USD (Dólar)</option>
                        </select>
                    </div>
                    
                    <hr>

                    <h5 class="mb-3">Tipo de Simulação</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="simulation_type" class="form-label d-flex justify-content-between align-items-center">
                                    <span>Tipo de Simulação *</span>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none small" data-bs-toggle="modal" data-bs-target="#simulationHelpModal">
                                        <i class="bi bi-question-circle me-1"></i>Como escolher?
                                    </button>
                                </label>
                                <select class="form-select" id="simulation_type" name="simulation_type" required onchange="handleSimulationTypeChange(this)">
                                    <option value="standard">Padrão (sem aportes)</option>
                                    <option value="monthly_deposit">Com Aportes Periódicos</option>
                                    <option value="strategic_deposit" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Com Aportes Estratégicos <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                    <option value="smart_deposit" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Aporte Direcionado ao Alvo <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                    <option value="selic_cash_deposit" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Aporte em Caixa (SELIC) <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label d-flex align-items-center">
                                    Imposto sobre o Lucro
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Se ativado, o sistema calculará o imposto devido sobre o lucro realizado em cada venda (rebalanceamento)."></i>
                                </label>
                                <div class="card bg-light border-0 rounded-3">
                                    <div class="card-body p-2">
                                        <div class="form-check form-switch mb-2 ms-2">
                                            <input class="form-check-input" type="checkbox" id="enable_tax" onchange="handleTaxToggle(this)">
                                            <label class="form-check-label small text-muted" for="enable_tax">Calcular Imposto <?= !Auth::isPro() ? '🔒' : '' ?></label>
                                        </div>
                                        <div id="tax_input_container" style="display: none;">
                                            <hr class="my-2 opacity-10">
                                            <?php if (Auth::isPro()): ?>
                                            <?php 
                                                $defaultRates = [
                                                    'CRIPTOMOEDA' => '15.0',
                                                    'ETF_US' => '15.0',
                                                    'ETF_BR' => '15.0',
                                                    'RENDA_FIXA' => '20.0',
                                                    'FUNDO_IMOBILIARIO' => '20.0'
                                                ];
                                                
                                                foreach ($defaultRates as $group => $defaultRate):
                                                    $label = str_replace('_', ' ', $group);
                                            ?>
                                            <div class="row align-items-center mb-2 g-2">
                                                <div class="col-7">
                                                    <span class="small text-muted fw-bold ms-2"><?= $label ?></span>
                                                </div>
                                                <div class="col-5">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control tax-rate-input" 
                                                               name="profit_tax_rates[<?= $group ?>]" 
                                                               step="0.1" min="0" max="100" 
                                                               value="<?= $defaultRate ?>">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <div class="text-center py-3 px-2 cursor-pointer" onclick="showPaywallModal('Cálculo de Impostos', 'O sistema calcula automaticamente o imposto de renda devido em cada rebalanceamento, facilitando sua gestão fiscal.')">
                                                <div class="mb-2">
                                                    <i class="bi bi-lock-fill text-primary fs-4"></i>
                                                </div>
                                                <p class="small text-muted mb-3">O cálculo automatizado de impostos está disponível apenas para assinantes PRO.</p>
                                                <span class="btn btn-sm btn-outline-primary fw-bold">
                                                    <i class="bi bi-stars me-1"></i>Ver Detalhes
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Campo oculto para compatibilidade -->
                                            <input type="hidden" id="profit_tax_rate" name="profit_tax_rate" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos para Aportes Periódicos / Direcionado / Caixa SELIC -->
                    <div id="monthly_deposit_fields" class="simulation-fields" style="display: none;">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" id="deposit_card_header"><i class="bi bi-calendar-plus me-2"></i>Configuração de Aportes Periódicos</h6>
                                <span class="badge bg-white text-primary rounded-pill small">Dica UEX</span>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-end mb-3" id="deposit_inputs_row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="deposit_amount" class="form-label d-flex align-items-center">
                                                Valor do Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Valor a ser investido periodicamente segundo a frequência escolhida."></i>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="deposit_currency_label">BRL</span>
                                                <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" step="0.01" min="0" placeholder="Ex: 5000.00">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="deposit_currency" class="form-label d-flex align-items-center">
                                                Moeda do Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="A moeda original do seu aporte periódico. Se for diferente da moeda de saída, será convertida pelo câmbio do dia."></i>
                                            </label>
                                            <select class="form-select" id="deposit_currency" name="deposit_currency" onchange="document.getElementById('deposit_currency_label').innerText = this.value">
                                                <option value="BRL">BRL (Real)</option>
                                                <option value="USD">USD (Dólar)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="deposit_frequency" class="form-label d-flex align-items-center">
                                                Frequência do Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O intervalo regular em que você injeta capital novo na carteira."></i>
                                            </label>
                                            <select class="form-select" id="deposit_frequency" name="deposit_frequency">
                                                <option value="monthly">Mensal</option>
                                                <option value="bimonthly">Bimestral</option>
                                                <option value="quarterly">Trimestral</option>
                                                <option value="biannual">Semestral</option>
                                                <option value="annual">Anual</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="rebalance_type" class="form-label d-flex align-items-center">
                                                Tipo de Rebalanceamento
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="'Apenas Compras' evita vender o que subiu, usando o aporte para comprar apenas o que está abaixo do alvo. 'Completo' vende e compra para manter os pesos exatos."></i>
                                            </label>
                                            <select class="form-select" id="rebalance_type" name="rebalance_type" onchange="handleRebalanceTypeChange(this)">
                                                <option value="full">Completo (Compra e Venda)</option>
                                                <option value="buy_only">Apenas Compras (Sem Vendas)</option>
                                                <option value="with_margin" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Com Margem Global (Venda se superar X%) <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                                <option value="custom_margin" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Com Margens Customizadas por Ativo <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="deposit_inflation_adjusted" name="deposit_inflation_adjusted" value="1">
                                                <label class="form-check-label d-flex align-items-center" for="deposit_inflation_adjusted">
                                                    Corrigir pela Inflação (IPCA)
                                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Aumenta o valor do seu aporte mensalmente seguindo o IPCA histórico, preservando o valor real investido."></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="rebalance_margin_container" style="display: none;">
                                        <div class="mb-3">
                                            <label for="rebalance_margin" class="form-label d-flex align-items-center">
                                                Margem de Venda (%)
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O ativo só será vendido se seu peso atual superar o peso-alvo em mais do que esta porcentagem. Ex: Alvo 40% com margem 20%, só vende se passar de 48% (40 * 1.20)."></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="rebalance_margin" name="rebalance_margin" step="0.1" min="0" placeholder="Ex: 20.0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="use_cash_assets_container" style="display: none;">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="use_cash_assets_for_rebalance" name="use_cash_assets_for_rebalance" value="1">
                                                <label class="form-check-label d-flex align-items-center" for="use_cash_assets_for_rebalance">
                                                    Usar ativos caixa no rebalanceamento
                                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Permite que os ativos 'Caixa SELIC' ou 'Caixa Dólar' sejam usados para comprar outros ativos da carteira durante o rebalanceamento. Esses ativos caixa devem estar definidos e com peso na sua alocação."></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Descrição dinâmica por tipo -->
                                <div id="desc_standard" class="alert alert-light border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-info-circle-fill text-primary fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Estratégia Buy & Hold</strong>
                                            O capital inicial é investido uma única vez. A carteira evolui apenas pela variação dos preços e pelos rebalanceamentos periódicos que você definir. Ideal para comparar o desempenho puro dos ativos escolhidos.
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_monthly_deposit" class="alert alert-info border shadow-sm py-3 small mb-0">
                                    <div class="d-flex">
                                        <i class="bi bi-calendar-check-fill text-info fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Aportes Regulares</strong>
                                            Simula o hábito de poupar mensalmente. O valor do aporte é distribuído entre todos os ativos seguindo o peso-alvo. É a forma clássica de acumulação de patrimônio (Dollar Cost Averaging).
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_smart_deposit" class="alert alert-success border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-bullseye text-success fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Rebalanceamento "Pela Compra"</strong>
                                            A estratégia mais inteligente: o aporte é usado para comprar o ativo que está mais "barato" (mais longe do alvo). Isso evita vendas desnecessárias e reduz custos com impostos, mantendo a carteira equilibrada organicamente.
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_selic_cash_deposit" class="alert alert-secondary border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-piggy-bank-fill text-secondary fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Acúmulo em Liquidez</strong>
                                            O aporte mensal é guardado em um "Caixa" rendendo SELIC. O montante total acumulado só entra na carteira nos meses de rebalanceamento. Útil para simular quem prefere fazer grandes compras periódicas em vez de pequenas compras mensais.
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_strategic_deposit" class="alert alert-warning border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-lightning-charge-fill text-warning fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Aproveitando as Quedas (Buy the Dip)</strong>
                                            O sistema monitora o mercado e só injeta capital novo se houver uma queda brusca (definida pelo limiar). Simula a "reserva de oportunidade" sendo usada para comprar ativos em momentos de pessimismo.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos para Aportes Estratégicos -->
                    <div id="strategic_deposit_fields" class="simulation-fields" style="display: none;">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="bi bi-graph-down me-2"></i>Configuração de Aportes Estratégicos</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="strategic_threshold" class="form-label d-flex align-items-center">
                                                Limiar de Queda para Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Se o portfólio cair este percentual em um único mês, o sistema dispara um aporte extra."></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="strategic_threshold" name="strategic_threshold" step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Aporte será feito se o portfólio cair este percentual em um mês</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="strategic_deposit_percentage" class="form-label d-flex align-items-center">
                                                Percentual do Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O valor do aporte será calculado como este percentual sobre o patrimônio atual do portfólio no momento da queda."></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="strategic_deposit_percentage" name="strategic_deposit_percentage" step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Percentual do valor atual do portfólio a ser aportado</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="bi bi-lightbulb me-1"></i> Exemplo: Se cair 10% em um mês, será aportado 10% do valor atual do portfólio.
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <h5 class="mb-3">
                        Alocação Estratégica de Ativos
                        <?php if (!Auth::isPro()): ?>
                            <span class="ms-2 cursor-pointer" onclick="showPaywallModal('Limite de Ativos', 'No plano Starter você pode adicionar até 5 ativos por portfólio. No plano PRO, não há limites.')">🔒</span>
                        <?php endif; ?>
                    </h5>
                    <div class="table-responsive mb-3">
                        <table class="table" id="assetsTable">
                            <thead>
                                <tr>
                                    <th>Ativo</th>
                                    <th style="width: 150px;">Alocação (%)</th>
                                    <th style="width: 150px;">Fator Performance</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="assetsBody">
                                <!-- Linhas serão adicionadas aqui -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>
                                        <select class="form-select" id="assetSelect">
                                            <option value="">+ Adicionar novo ativo...</option>
                                            <?php foreach ($assets as $asset): 
                                                $start = !empty($asset['min_date']) ? date('m/Y', strtotime($asset['min_date'])) : 'Início';
                                                $end = !empty($asset['max_date']) ? date('m/Y', strtotime($asset['max_date'])) : 'Hoje';
                                            ?>
                                                <option value="<?php echo $asset['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                                        data-min="<?php echo $asset['min_date']; ?>"
                                                        data-max="<?php echo $asset['max_date']; ?>">
                                                    <?php echo htmlspecialchars($asset['name']); ?> 
                                                    (<?php echo $start; ?> a <?php echo $end; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="assetAllocation" step="any" min="0" max="100" placeholder="%">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="assetFactor" step="0.01" min="0.1" max="10" value="1.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success" onclick="addAsset()">+</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">
                                        <strong>Total: <span id="totalAllocation">0</span>%</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning" id="allocationWarning" style="display: none;">
                        A soma da alocação deve ser 100%
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Criar Portfólio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let assets = [];
let nextId = 1;
let lastValidSimulationType = 'standard';

function handleSimulationTypeChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption.getAttribute('data-premium') === 'true') {
        const feature = selectedOption.text.replace(' 🔒', '');
        let desc = '';
        
        if (selectedOption.value === 'smart_deposit') desc = 'O aporte é usado para comprar o ativo que está mais longe do alvo, evitando vendas e reduzindo impostos.';
        if (selectedOption.value === 'selic_cash_deposit') desc = 'O aporte mensal é guardado em um Caixa SELIC e investido apenas nos meses de rebalanceamento.';
        if (selectedOption.value === 'strategic_deposit') desc = 'Simula a reserva de oportunidade, aportando apenas quando o mercado cai bruscamente.';

        showPaywallModal(feature, desc);
        select.value = lastValidSimulationType; // Volta para o anterior
        return;
    }
    
    lastValidSimulationType = select.value;
    toggleSimulationFields();
}

function handleTaxToggle(checkbox) {
    const isPro = <?= Auth::isPro() ? 'true' : 'false' ?>;
    if (!isPro && checkbox.checked) {
        checkbox.checked = false;
        showPaywallModal('Cálculo de Impostos', 'O sistema calcula automaticamente o imposto de renda devido em cada rebalanceamento, facilitando sua gestão fiscal.');
        return;
    }
    toggleTaxField();
}

let lastValidRebalanceType = 'full';
function handleRebalanceTypeChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption.getAttribute('data-premium') === 'true') {
        const feature = selectedOption.text.replace(' 🔒', '');
        let desc = '';
        
        if (selectedOption.value === 'with_margin') desc = 'Define uma tolerância para o desvio dos ativos. O sistema só vende se o ativo subir além de uma margem X% do seu peso original.';
        if (selectedOption.value === 'custom_margin') desc = 'Permite definir margens de rebalanceamento diferentes para cada ativo da sua carteira.';

        showPaywallModal(feature, desc);
        select.value = lastValidRebalanceType; // Volta para o anterior
        return;
    }
    
    lastValidRebalanceType = select.value;
    toggleUseCashAssetsField();
}

function addAsset() {
    const assetSelect = document.getElementById('assetSelect');
    const allocationInput = document.getElementById('assetAllocation');
    const factorInput = document.getElementById('assetFactor');

    // Verificação de limite de ativos para Plano Starter
    const isPro = <?= Auth::isPro() ? 'true' : 'false' ?>;
    if (!isPro && assets.length >= 5) {
        showPaywallModal('Limite de Ativos', 'No plano Starter você pode adicionar até 5 ativos por portfólio. No plano PRO, não há restrição de quantidade.');
        return;
    }
    
    if (!assetSelect.value || parseFloat(allocationInput.value) <= 0) {
        alert('Selecione um ativo e informe a alocação.');
        return;
    }
    
    // Verificar se ativo já foi adicionado
    if (assets.some(a => a.asset_id == assetSelect.value)) {
        alert('Este ativo já foi adicionado ao portfólio.');
        return;
    }
    
    const opt = assetSelect.options[assetSelect.selectedIndex];
    
    const asset = {
        id: nextId++,
        asset_id: assetSelect.value,
        name: opt.getAttribute('data-name'),
        allocation: parseFloat(allocationInput.value) || 0,
        factor: parseFloat(factorInput.value) || 1.0,
        rebalance_margin_down: null,
        rebalance_margin_up: null,
        // Sênior: Metadados para validação de UEX
        min_date: opt.getAttribute('data-min'),
        max_date: opt.getAttribute('data-max')
    };
    
    assets.push(asset);
    updateAssetsTable();
    updateTotal();
    
    // Limpar campos
    assetSelect.value = '';
    allocationInput.value = '';
    factorInput.value = '1.00';
}

function removeAsset(id) {
    assets = assets.filter(a => a.id !== id);
    updateAssetsTable();
    updateTotal();
}

function updateAssetsTable() {
    const tbody = document.getElementById('assetsBody');
    const rebalanceType = document.getElementById('rebalance_type').value;
    const isCustomMargin = rebalanceType === 'custom_margin';
    tbody.innerHTML = '';
    
    // Atualiza o header da tabela se necessário
    const headerRow = tbody.closest('table').querySelector('thead tr');
    if (headerRow) {
        const marginHeader = headerRow.querySelector('.margin-header');
        if (isCustomMargin) {
            if (!marginHeader) {
                const th = document.createElement('th');
                th.className = 'margin-header';
                th.innerHTML = 'Margens de Rebalanceamento (%) <i class="bi bi-info-circle-fill ms-1 text-muted info-tooltip" data-bs-toggle="tooltip" title="Define o percentual mínimo e máximo aceitável para o ativo na carteira antes de disparar o rebalanceamento."></i>';
                headerRow.insertBefore(th, headerRow.cells[headerRow.cells.length - 1]);
            }
        } else if (marginHeader) {
            marginHeader.remove();
        }
    }
    
    assets.forEach(asset => {
        // Formatação sênior de data (AAAA-MM-DD -> MM/AAAA)
        const formatDate = (dateStr) => {
            if (!dateStr) return "-";
            const parts = dateStr.split('-');
            return parts.length >= 2 ? `${parts[1]}/${parts[0]}` : dateStr;
        };

        let marginFields = '';
        if (isCustomMargin) {
            marginFields = `
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Min</span>
                            <input type="number" name="assets[${asset.asset_id}][rebalance_margin_down]"
                                value="${asset.rebalance_margin_down !== null ? asset.rebalance_margin_down : ''}" step="any" class="form-control"
                                placeholder="Min %" oninput="updateAssetCustomMargin(${asset.id}, 'rebalance_margin_down', this.value)">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Max</span>
                            <input type="number" name="assets[${asset.asset_id}][rebalance_margin_up]"
                                value="${asset.rebalance_margin_up !== null ? asset.rebalance_margin_up : ''}" step="any" class="form-control"
                                placeholder="Max %" oninput="updateAssetCustomMargin(${asset.id}, 'rebalance_margin_up', this.value)">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </td>
            `;
        }

        const row = tbody.insertRow();
        row.innerHTML = `
            <td>
                <div class="fw-bold text-dark">${asset.name}</div>
                <div class="text-muted smaller" style="font-size: 0.7rem;">
                    <i class="bi bi-calendar-check me-1"></i>
                    Histórico: ${formatDate(asset.min_date)} a ${formatDate(asset.max_date)}
                </div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm allocation-input" 
                       value="${asset.allocation}" step="any" 
                       onchange="updateAssetAllocation(${asset.id}, this.value)">
                <input type="hidden" name="assets[${asset.asset_id}][asset_id]" value="${asset.asset_id}">
                <input type="hidden" name="assets[${asset.asset_id}][allocation]" value="${asset.allocation}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${asset.factor}" step="0.01"
                       onchange="updateAssetFactor(${asset.id}, this.value)">
                <input type="hidden" name="assets[${asset.asset_id}][performance_factor]" value="${asset.factor}">
            </td>
            ${marginFields}
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0" 
                        onclick="removeAsset(${asset.id})"><i class="bi bi-trash"></i></button>
            </td>
        `;
    });
    
    // Re-inicializa tooltips se houver
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
}

function updateAssetCustomMargin(id, field, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        asset[field] = value === '' ? null : parseFloat(value);
    }
}

function updateAssetAllocation(id, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        asset.allocation = parseFloat(value) || 0;
        updateTotal();
        updateAssetsTable(); // Atualiza os inputs hidden também
    }
}

function updateAssetFactor(id, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        asset.factor = parseFloat(value) || 1.0;
        updateAssetsTable(); // Atualiza os inputs hidden também
    }
}

function updateTotal() {
    const total = assets.reduce((sum, asset) => sum + asset.allocation, 0);
    document.getElementById('totalAllocation').textContent = total.toFixed(5);
    
    const warning = document.getElementById('allocationWarning');
    const submitBtn = document.getElementById('submitBtn');
    
    if (Math.abs(total - 100) < 0.00001) {
        warning.style.display = 'none';
        submitBtn.disabled = false;
    } else {
        warning.style.display = 'block';
        submitBtn.disabled = true;
    }
}

document.getElementById('portfolioForm').addEventListener('submit', function(e) {
    // Adicionar ativos ao formulário
    assets.forEach((asset) => {
        const assetInput = document.createElement('input');
        assetInput.type = 'hidden';
        assetInput.name = `assets[${asset.asset_id}][asset_id]`;
        assetInput.value = asset.asset_id;
        
        const allocationInput = document.createElement('input');
        allocationInput.type = 'hidden';
        allocationInput.name = `assets[${asset.asset_id}][allocation]`;
        allocationInput.value = asset.allocation;
        
        const factorInput = document.createElement('input');
        factorInput.type = 'hidden';
        factorInput.name = `assets[${asset.asset_id}][performance_factor]`;
        factorInput.value = asset.factor;
        
        this.appendChild(assetInput);
        this.appendChild(allocationInput);
        this.appendChild(factorInput);
    });
});

// Definir data padrão (primeiro dia do mês atual)
const today = new Date();
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
document.getElementById('start_date').valueAsDate = firstDay;


// Controle dos campos de simulação
function toggleSimulationFields() {
    const type = document.getElementById('simulation_type').value;

    // Esconde todos os campos primeiro
    document.querySelectorAll('.simulation-fields').forEach(field => {
        field.style.display = 'none';
    });

    // Esconde todas as descrições dinâmicas
    ['desc_standard', 'desc_monthly_deposit', 'desc_smart_deposit', 'desc_selic_cash_deposit', 'desc_strategic_deposit'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    const depositHeaderTexts = {
        'standard':          '<i class="bi bi-gear me-2"></i>Configuração da Simulação',
        'monthly_deposit':   '<i class="bi bi-calendar-plus me-2"></i>Configuração — Aportes Periódicos',
        'strategic_deposit': '<i class="bi bi-lightning-charge me-2"></i>Configuração — Aportes Estratégicos',
        'smart_deposit':     '<i class="bi bi-bullseye me-2"></i>Configuração — Aporte Direcionado ao Alvo',
        'selic_cash_deposit':'<i class="bi bi-piggy-bank me-2"></i>Configuração — Aporte em Caixa (SELIC)'
    };

    const descEl = document.getElementById('desc_' + type);
    if (descEl) descEl.style.display = 'block';

    if (type === 'monthly_deposit' || type === 'smart_deposit' || type === 'selic_cash_deposit' || type === 'standard') {
        document.getElementById('monthly_deposit_fields').style.display = 'block';
        document.getElementById('deposit_card_header').innerHTML = depositHeaderTexts[type] || depositHeaderTexts['standard'];
        
        const depositInputsRow = document.getElementById('deposit_inputs_row');
        if (type === 'standard') {
            if (depositInputsRow) depositInputsRow.style.display = 'none';
        } else {
            if (depositInputsRow) depositInputsRow.style.display = 'flex';
        }
        
        // Exibir seletor de tipo de rebalanceamento apenas para smart_deposit e selic_cash_deposit
        const rtContainer = document.getElementById('rebalance_type').closest('.col-md-3');
        if (type === 'smart_deposit' || type === 'selic_cash_deposit') {
            rtContainer.style.display = 'block';
        } else {
            rtContainer.style.display = 'none';
            document.getElementById('rebalance_type').value = 'full';
        }

        toggleUseCashAssetsField();

        // Define valor padrão se estiver vazio
        if (!document.getElementById('deposit_amount').value) {
            document.getElementById('deposit_amount').value = '1000.00';
        }
    } else if (type === 'strategic_deposit') {
        document.getElementById('strategic_deposit_fields').style.display = 'block';
        toggleUseCashAssetsField(); // Ensure it's hidden for strategic
        // Define valores padrão
        if (!document.getElementById('strategic_threshold').value) {
            document.getElementById('strategic_threshold').value = '10.0';
        }
        if (!document.getElementById('strategic_deposit_percentage').value) {
            document.getElementById('strategic_deposit_percentage').value = '10.0';
        }
    } else {
        toggleUseCashAssetsField(); // Ensure it's hidden for standard
    }
}

function toggleUseCashAssetsField() {
    const type = document.getElementById('simulation_type').value;
    const rebalanceType = document.getElementById('rebalance_type').value;
    const cashContainer = document.getElementById('use_cash_assets_container');
    const marginContainer = document.getElementById('rebalance_margin_container');
    
    // Controle do container de ativos caixa
    if ((type === 'smart_deposit' || type === 'selic_cash_deposit') && (rebalanceType === 'buy_only' || rebalanceType === 'custom_margin')) {
        cashContainer.style.display = 'block';
    } else {
        cashContainer.style.display = 'none';
        if (document.getElementById('use_cash_assets_for_rebalance')) {
            document.getElementById('use_cash_assets_for_rebalance').checked = false;
        }
    }

    // Controle do container de margem de rebalanceamento
    if (rebalanceType === 'with_margin') {
        marginContainer.style.display = 'block';
        if (!document.getElementById('rebalance_margin').value) {
            document.getElementById('rebalance_margin').value = '10.0';
        }
    } else {
        marginContainer.style.display = 'none';
    }
    
    // Sempre que mudar o tipo de rebalanceamento, atualizamos a tabela para mostrar/esconder margens customizadas
    updateAssetsTable();
}

function toggleTaxField() {
    const enableTax = document.getElementById('enable_tax').checked;
    const container = document.getElementById('tax_input_container');
    const inputs = document.querySelectorAll('.tax-rate-input');
            
    if (enableTax) {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
        inputs.forEach(input => input.value = ''); // Envia null/vazio para o servidor
        document.getElementById('profit_tax_rate').value = '';
    }
}

document.getElementById('rebalance_type').addEventListener('change', handleRebalanceTypeChange);

// Inicializa ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa todos os tooltips Bootstrap da página
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function(el) {
        new bootstrap.Tooltip(el, { html: true, trigger: 'hover focus' });
    });

    toggleSimulationFields();

    // Restrição de 5 anos para Plano Starter (Data Início)
    const startDateInput = document.getElementById('start_date');
    const isPro = <?= Auth::isPro() ? 'true' : 'false' ?>;
    
    if (!isPro && startDateInput) {
        const fiveYearsAgo = new Date();
        fiveYearsAgo.setFullYear(fiveYearsAgo.getFullYear() - 5);
        
        const minDateStr = fiveYearsAgo.toISOString().split('T')[0];
        startDateInput.min = minDateStr;

        startDateInput.addEventListener('change', function() {
            if (this.value < minDateStr) {
                this.value = minDateStr;
                showPaywallModal('Histórico de Dados', 'No Plano Starter, o histórico é limitado aos últimos 5 anos. No Plano PRO, você tem acesso a décadas de dados históricos para simulações ultra-precisas.');
            }
        });
    }
});

</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/_simulation_help_modal.php';
include_once __DIR__ . '/../layouts/main.php';
?>