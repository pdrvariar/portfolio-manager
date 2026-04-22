<?php
/**
 * @var array $assets Lista de ativos disponÃ­veis
 */
$title = 'Criar PortfÃ³lio';`n$meta_robots = 'noindex, nofollow';
ob_start();

$assetModel = new Asset();
$assets = $assetModel->getAllWithDetails();
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Criar Novo PortfÃ³lio</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('portfolio/create'); ?>" id="portfolioForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label d-flex align-items-center">
                                    Nome do PortfÃ³lio *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="DÃª um nome claro Ã  sua estratÃ©gia (ex: Aposentadoria 2050)."></i>
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="initial_capital" class="form-label d-flex align-items-center">
                                    Capital Inicial *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O valor em dinheiro que vocÃª possui para investir no primeiro dia da simulaÃ§Ã£o."></i>
                                </label>
                                <input type="number" class="form-control" id="initial_capital" name="initial_capital" step="0.01" min="100" value="100000" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label d-flex align-items-center">
                            DescriÃ§Ã£o
                            <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Use este espaÃ§o para anotar as premissas ou objetivos desta carteira especÃ­fica."></i>
                        </label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_date" class="form-label d-flex align-items-center">
                                    Data InÃ­cio *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Data do primeiro aporte. O sistema buscarÃ¡ preÃ§os histÃ³ricos a partir deste dia."></i>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                <?php if (!Auth::isPro()): ?>
                                <div class="form-text text-primary small">
                                    <i class="bi bi-info-circle me-1"></i> No Plano Starter, o histÃ³rico Ã© limitado aos Ãºltimos 5 anos. 
                                    <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="fw-bold text-decoration-none">Desbloquear PRO</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label d-flex align-items-center">
                                    Data Fim (opcional)
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Data final do backtest. Se vazio, usarÃ¡ os dados mais recentes disponÃ­veis."></i>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rebalance_frequency" class="form-label d-flex align-items-center">
                                    FrequÃªncia Rebalanceamento *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Define de quanto em quanto tempo o sistema deve 'forÃ§ar' a volta dos ativos ao peso-alvo original."></i>
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
                            Moeda de SaÃ­da *
                            <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="A moeda em que todos os relatÃ³rios e grÃ¡ficos serÃ£o apresentados. O sistema faz a conversÃ£o automÃ¡tica se houver ativos em moedas diferentes."></i>
                        </label>
                        <select class="form-select" id="output_currency" name="output_currency" required>
                            <option value="BRL">BRL (Real)</option>
                            <option value="USD">USD (DÃ³lar)</option>
                        </select>
                    </div>
                    
                    <hr>

                    <h5 class="mb-3">Tipo de SimulaÃ§Ã£o</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="simulation_type" class="form-label d-flex justify-content-between align-items-center">
                                    <span>Tipo de SimulaÃ§Ã£o *</span>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none small" data-bs-toggle="modal" data-bs-target="#simulationHelpModal">
                                        <i class="bi bi-question-circle me-1"></i>Como escolher?
                                    </button>
                                </label>
                                <select class="form-select" id="simulation_type" name="simulation_type" required onchange="handleSimulationTypeChange(this)">
                                    <option value="standard">PadrÃ£o (sem aportes)</option>
                                    <option value="monthly_deposit">Com Aportes PeriÃ³dicos</option>
                                    <option value="strategic_deposit" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Com Aportes EstratÃ©gicos <?= !Auth::isPro() ? 'ðŸ”’' : '' ?></option>
                                    <option value="smart_deposit" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Aporte Direcionado ao Alvo <?= !Auth::isPro() ? 'ðŸ”’' : '' ?></option>
                                    <option value="selic_cash_deposit" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Aporte em Caixa (SELIC) <?= !Auth::isPro() ? 'ðŸ”’' : '' ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label d-flex align-items-center">
                                    Imposto sobre o Lucro
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Se ativado, o sistema calcularÃ¡ o imposto devido sobre o lucro realizado em cada venda (rebalanceamento)."></i>
                                </label>
                                <div class="card bg-light border-0 rounded-3">
                                    <div class="card-body p-2">
                                        <div class="form-check form-switch mb-2 ms-2">
                                            <input class="form-check-input" type="checkbox" id="enable_tax" onchange="handleTaxToggle(this)">
                                            <label class="form-check-label small text-muted" for="enable_tax">Calcular Imposto <?= !Auth::isPro() ? 'ðŸ”’' : '' ?></label>
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
                                            <div class="text-center py-3 px-2 cursor-pointer" onclick="showPaywallModal('CÃ¡lculo de Impostos', 'O sistema calcula automaticamente o imposto de renda devido em cada rebalanceamento, facilitando sua gestÃ£o fiscal.')">
                                                <div class="mb-2">
                                                    <i class="bi bi-lock-fill text-primary fs-4"></i>
                                                </div>
                                                <p class="small text-muted mb-3">O cÃ¡lculo automatizado de impostos estÃ¡ disponÃ­vel apenas para assinantes PRO.</p>
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

                    <!-- Campos para Aportes PeriÃ³dicos / Direcionado / Caixa SELIC -->
                    <div id="monthly_deposit_fields" class="simulation-fields" style="display: none;">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" id="deposit_card_header"><i class="bi bi-calendar-plus me-2"></i>ConfiguraÃ§Ã£o de Aportes PeriÃ³dicos</h6>
                                <span class="badge bg-white text-primary rounded-pill small">Dica UEX</span>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-end mb-3" id="deposit_inputs_row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="deposit_amount" class="form-label d-flex align-items-center">
                                                Valor do Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Valor a ser investido periodicamente segundo a frequÃªncia escolhida."></i>
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
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="A moeda original do seu aporte periÃ³dico. Se for diferente da moeda de saÃ­da, serÃ¡ convertida pelo cÃ¢mbio do dia."></i>
                                            </label>
                                            <select class="form-select" id="deposit_currency" name="deposit_currency" onchange="document.getElementById('deposit_currency_label').innerText = this.value">
                                                <option value="BRL">BRL (Real)</option>
                                                <option value="USD">USD (DÃ³lar)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="deposit_frequency" class="form-label d-flex align-items-center">
                                                FrequÃªncia do Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O intervalo regular em que vocÃª injeta capital novo na carteira."></i>
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
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="'Apenas Compras' evita vender o que subiu, usando o aporte para comprar apenas o que estÃ¡ abaixo do alvo. 'Completo' vende e compra para manter os pesos exatos."></i>
                                            </label>
                                            <select class="form-select" id="rebalance_type" name="rebalance_type" onchange="handleRebalanceTypeChange(this)">
                                                <option value="full">Completo (Compra e Venda)</option>
                                                <option value="buy_only">Apenas Compras (Sem Vendas)</option>
                                                <option value="custom_margin" <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Com Margens Customizadas por Ativo <?= !Auth::isPro() ? 'ðŸ”’' : '' ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="deposit_inflation_adjusted" name="deposit_inflation_adjusted" value="1">
                                                <label class="form-check-label d-flex align-items-center" for="deposit_inflation_adjusted">
                                                    Corrigir pela InflaÃ§Ã£o (IPCA)
                                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Aumenta o valor do seu aporte mensalmente seguindo o IPCA histÃ³rico, preservando o valor real investido."></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="use_cash_assets_container" style="display: none;">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="use_cash_assets_for_rebalance" name="use_cash_assets_for_rebalance" value="1">
                                                <label class="form-check-label d-flex align-items-center" for="use_cash_assets_for_rebalance">
                                                    Usar ativos caixa no rebalanceamento
                                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Permite que os ativos 'Caixa SELIC' ou 'Caixa DÃ³lar' sejam usados para comprar outros ativos da carteira durante o rebalanceamento. Esses ativos caixa devem estar definidos e com peso na sua alocaÃ§Ã£o."></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- DescriÃ§Ã£o dinÃ¢mica por tipo -->
                                <div id="desc_standard" class="alert alert-light border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-info-circle-fill text-primary fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">EstratÃ©gia Buy & Hold</strong>
                                            O capital inicial Ã© investido uma Ãºnica vez. A carteira evolui apenas pela variaÃ§Ã£o dos preÃ§os e pelos rebalanceamentos periÃ³dicos que vocÃª definir. Ideal para comparar o desempenho puro dos ativos escolhidos.
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_monthly_deposit" class="alert alert-info border shadow-sm py-3 small mb-0">
                                    <div class="d-flex">
                                        <i class="bi bi-calendar-check-fill text-info fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Aportes Regulares</strong>
                                            Simula o hÃ¡bito de poupar mensalmente. O valor do aporte Ã© distribuÃ­do entre todos os ativos seguindo o peso-alvo. Ã‰ a forma clÃ¡ssica de acumulaÃ§Ã£o de patrimÃ´nio (Dollar Cost Averaging).
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_smart_deposit" class="alert alert-success border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-bullseye text-success fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Rebalanceamento "Pela Compra"</strong>
                                            A estratÃ©gia mais inteligente: o aporte Ã© usado para comprar o ativo que estÃ¡ mais "barato" (mais longe do alvo). Isso evita vendas desnecessÃ¡rias e reduz custos com impostos, mantendo a carteira equilibrada organicamente.
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_selic_cash_deposit" class="alert alert-secondary border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-piggy-bank-fill text-secondary fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">AcÃºmulo em Liquidez</strong>
                                            O aporte mensal Ã© guardado em um "Caixa" rendendo SELIC. O montante total acumulado sÃ³ entra na carteira nos meses de rebalanceamento. Ãštil para simular quem prefere fazer grandes compras periÃ³dicas em vez de pequenas compras mensais.
                                        </div>
                                    </div>
                                </div>
                                <div id="desc_strategic_deposit" class="alert alert-warning border shadow-sm py-3 small mb-0" style="display:none;">
                                    <div class="d-flex">
                                        <i class="bi bi-lightning-charge-fill text-warning fs-5 me-3"></i>
                                        <div>
                                            <strong class="d-block mb-1">Aproveitando as Quedas (Buy the Dip)</strong>
                                            O sistema monitora o mercado e sÃ³ injeta capital novo se houver uma queda brusca (definida pelo limiar). Simula a "reserva de oportunidade" sendo usada para comprar ativos em momentos de pessimismo.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos para Aportes EstratÃ©gicos -->
                    <div id="strategic_deposit_fields" class="simulation-fields" style="display: none;">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="bi bi-graph-down me-2"></i>ConfiguraÃ§Ã£o de Aportes EstratÃ©gicos</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="strategic_threshold" class="form-label d-flex align-items-center">
                                                Limiar de Queda para Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Se o portfÃ³lio cair este percentual em um Ãºnico mÃªs, o sistema dispara um aporte extra."></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="strategic_threshold" name="strategic_threshold" step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Aporte serÃ¡ feito se o portfÃ³lio cair este percentual em um mÃªs</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="strategic_deposit_percentage" class="form-label d-flex align-items-center">
                                                Percentual do Aporte
                                                <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O valor do aporte serÃ¡ calculado como este percentual sobre o patrimÃ´nio atual do portfÃ³lio no momento da queda."></i>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="strategic_deposit_percentage" name="strategic_deposit_percentage" step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Percentual do valor atual do portfÃ³lio a ser aportado</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="bi bi-lightbulb me-1"></i> Exemplo: Se cair 10% em um mÃªs, serÃ¡ aportado 10% do valor atual do portfÃ³lio.
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <h5 class="mb-3">
                        AlocaÃ§Ã£o EstratÃ©gica de Ativos
                        <?php if (!Auth::isPro()): ?>
                            <span class="ms-2 cursor-pointer" onclick="showPaywallModal('Limite de Ativos', 'No plano Starter vocÃª pode adicionar atÃ© 5 ativos por portfÃ³lio. No plano PRO, nÃ£o hÃ¡ limites.')">ðŸ”’</span>
                        <?php endif; ?>
                    </h5>
                    <div class="table-responsive mb-3">
                        <table class="table" id="assetsTable">
                            <thead>
                                <tr>
                                    <th>Ativo</th>
                                    <th style="width: 150px;">AlocaÃ§Ã£o (%)</th>
                                    <th style="width: 150px;">Fator Performance</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="assetsBody">
                                <!-- Linhas serÃ£o adicionadas aqui -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>
                                        <select class="form-select" id="assetSelect">
                                            <option value="">+ Adicionar novo ativo...</option>
                                            <?php foreach ($assets as $asset): 
                                                $start = !empty($asset['min_date']) ? date('m/Y', strtotime($asset['min_date'])) : 'InÃ­cio';
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
                        A soma da alocaÃ§Ã£o deve ser 100%
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Criar PortfÃ³lio</button>
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
        const feature = selectedOption.text.replace(' ðŸ”’', '');
        let desc = '';
        
        if (selectedOption.value === 'smart_deposit') desc = 'O aporte Ã© usado para comprar o ativo que estÃ¡ mais longe do alvo, evitando vendas e reduzindo impostos.';
        if (selectedOption.value === 'selic_cash_deposit') desc = 'O aporte mensal Ã© guardado em um Caixa SELIC e investido apenas nos meses de rebalanceamento.';
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
        showPaywallModal('CÃ¡lculo de Impostos', 'O sistema calcula automaticamente o imposto de renda devido em cada rebalanceamento, facilitando sua gestÃ£o fiscal.');
        return;
    }
    toggleTaxField();
}

let lastValidRebalanceType = 'full';
function handleRebalanceTypeChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption.getAttribute('data-premium') === 'true') {
        const feature = selectedOption.text.replace(' ðŸ”’', '');
        let desc = '';
        
        if (selectedOption.value === 'custom_margin') desc = 'Permite definir margens de rebalanceamento diferentes para cada ativo da sua carteira.';

        showPaywallModal(feature, desc);
        select.value = lastValidRebalanceType; // Volta para o anterior
        return;
    }
    
    lastValidRebalanceType = select.value;
    toggleUseCashAssetsField();

    // SÃªnior UEX: Sugerir margens se trocar para custom_margin e nÃ£o tiverem preenchidas
    if (lastValidRebalanceType === 'custom_margin') {
        assets.forEach(asset => {
            if (asset.rebalance_margin_down === null || asset.rebalance_margin_up === null) {
                const suggestions = getMarginSuggestions(asset.allocation);
                asset.rebalance_margin_down = asset.rebalance_margin_down ?? suggestions.down;
                asset.rebalance_margin_up = asset.rebalance_margin_up ?? suggestions.up;
            }
        });
        updateAssetsTable();
    }
}

function addAsset() {
    const assetSelect = document.getElementById('assetSelect');
    const allocationInput = document.getElementById('assetAllocation');
    const factorInput = document.getElementById('assetFactor');

    // VerificaÃ§Ã£o de limite de ativos para Plano Starter
    const isPro = <?= Auth::isPro() ? 'true' : 'false' ?>;
    if (!isPro && assets.length >= 5) {
        showPaywallModal('Limite de Ativos', 'No plano Starter vocÃª pode adicionar atÃ© 5 ativos por portfÃ³lio. No plano PRO, nÃ£o hÃ¡ restriÃ§Ã£o de quantidade.');
        return;
    }
    
    if (!assetSelect.value || parseFloat(allocationInput.value) <= 0) {
        alert('Selecione um ativo e informe a alocaÃ§Ã£o.');
        return;
    }
    
    // Verificar se ativo jÃ¡ foi adicionado
    if (assets.some(a => a.asset_id == assetSelect.value)) {
        alert('Este ativo jÃ¡ foi adicionado ao portfÃ³lio.');
        return;
    }
    
    const opt = assetSelect.options[assetSelect.selectedIndex];
    
    const allocation = parseFloat(allocationInput.value) || 0;
    const suggestions = getMarginSuggestions(allocation);
    
    const asset = {
        id: nextId++,
        asset_id: assetSelect.value,
        name: opt.getAttribute('data-name'),
        allocation: allocation,
        factor: parseFloat(factorInput.value) || 1.0,
        rebalance_margin_down: suggestions.down,
        rebalance_margin_up: suggestions.up,
        // SÃªnior: Metadados para validaÃ§Ã£o de UEX
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
    
    // Atualiza o header da tabela se necessÃ¡rio
    const headerRow = tbody.closest('table').querySelector('thead tr');
    if (headerRow) {
        const marginHeader = headerRow.querySelector('.margin-header');
        if (isCustomMargin) {
            if (!marginHeader) {
                const th = document.createElement('th');
                th.className = 'margin-header';
                th.innerHTML = 'Margens de Rebalanceamento (%) <i class="bi bi-info-circle-fill ms-1 text-muted info-tooltip" data-bs-toggle="tooltip" title="Define o percentual mÃ­nimo e mÃ¡ximo aceitÃ¡vel para o ativo na carteira antes de disparar o rebalanceamento."></i>';
                headerRow.insertBefore(th, headerRow.cells[headerRow.cells.length - 1]);
            }
        } else if (marginHeader) {
            marginHeader.remove();
        }
    }
    
    assets.forEach(asset => {
        // FormataÃ§Ã£o sÃªnior de data (AAAA-MM-DD -> MM/AAAA)
        const formatDate = (dateStr) => {
            if (!dateStr) return "-";
            const parts = dateStr.split('-');
            return parts.length >= 2 ? `${parts[1]}/${parts[0]}` : dateStr;
        };

        let marginFields = '';
        if (isCustomMargin) {
            const suggestions = getMarginSuggestions(asset.allocation);
            marginFields = `
                <td class="bg-light-subtle rounded-3">
                    <div class="margin-input-group">
                        <div class="margin-input-item">
                            <span class="margin-input-label">MÃ­n</span>
                            <div class="margin-input-control">
                                <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.id}, 'down', -0.1)">-</button>
                                <input type="number" class="margin-input-field" step="0.1" 
                                    value="${(asset.rebalance_margin_down !== null && asset.rebalance_margin_down !== undefined) ? asset.rebalance_margin_down.toFixed(1) : ''}" 
                                    onchange="updateAssetCustomMargin(${asset.id}, 'rebalance_margin_down', this.value)">
                                <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.id}, 'down', 0.1)">+</button>
                            </div>
                        </div>
                        <div class="margin-input-item">
                            <span class="margin-input-label">MÃ¡x</span>
                            <div class="margin-input-control">
                                <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.id}, 'up', -0.1)">-</button>
                                <input type="number" class="margin-input-field" step="0.1" 
                                    value="${(asset.rebalance_margin_up !== null && asset.rebalance_margin_up !== undefined) ? asset.rebalance_margin_up.toFixed(1) : ''}" 
                                    onchange="updateAssetCustomMargin(${asset.id}, 'rebalance_margin_up', this.value)">
                                <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.id}, 'up', 0.1)">+</button>
                            </div>
                        </div>
                        <div class="margin-suggested-row">
                            <div class="margin-suggested-badge">SugestÃ£o: ${suggestions.label}</div>
                            <button type="button" class="margin-reset-btn" onclick="resetMarginToSuggestion(${asset.id})" title="Reaplicar valores sugeridos">
                                <i class="bi bi-arrow-counterclockwise"></i> Reaplicar
                            </button>
                        </div>
                        <input type="hidden" name="assets[${asset.asset_id}][rebalance_margin_down]" value="${asset.rebalance_margin_down}">
                        <input type="hidden" name="assets[${asset.asset_id}][rebalance_margin_up]" value="${asset.rebalance_margin_up}">
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
                    HistÃ³rico: ${formatDate(asset.min_date)} a ${formatDate(asset.max_date)}
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

// LÃ³gica de SugestÃ£o de Margens (UEX Senior)
function getMarginSuggestions(allocation) {
    let downPer, upPer;
    let label = "";
    
    if (allocation < 5) {
        downPer = 10; upPer = 100;
        label = "-10% / +100%";
    } else if (allocation >= 5 && allocation < 10) {
        downPer = 20; upPer = 50;
        label = "-20% / +50%";
    } else if (allocation >= 10 && allocation <= 50) {
        downPer = 10; upPer = 20;
        label = "-10% / +20%";
    } else {
        downPer = 5; upPer = 5;
        label = "Â±5%";
    }

    const downValue = allocation * (1 - downPer / 100);
    const upValue = allocation * (1 + upPer / 100);

    return {
        down: parseFloat(downValue.toFixed(2)),
        up: parseFloat(upValue.toFixed(2)),
        label: label
    };
}

// FunÃ§Ã£o para resetar margens para a sugestÃ£o (UEX Senior)
function resetMarginToSuggestion(assetId) {
    const asset = assets.find(a => a.id == assetId);
    if (!asset) return;
    
    const suggestions = getMarginSuggestions(asset.allocation);
    asset.rebalance_margin_down = suggestions.down;
    asset.rebalance_margin_up = suggestions.up;
    updateAssetsTable();
}

// FunÃ§Ã£o para ajustar margens com botÃµes +/- (UEX Senior Revise)
function adjustMargin(id, type, delta) {
    const asset = assets.find(a => a.id === id);
    if (!asset) return;

    if (type === 'down') {
        let val = (asset.rebalance_margin_down || 0) + delta;
        val = Math.max(0, parseFloat(val.toFixed(2)));
        // Garantir que nÃ£o ultrapasse o topo
        if (val > asset.rebalance_margin_up) val = asset.rebalance_margin_up;
        asset.rebalance_margin_down = val;
    } else {
        let val = (asset.rebalance_margin_up || 0) + delta;
        val = parseFloat(val.toFixed(2));
        // Garantir que nÃ£o seja menor que o piso
        if (val < asset.rebalance_margin_down) val = asset.rebalance_margin_down;
        asset.rebalance_margin_up = val;
    }
    updateAssetsTable();
}

function updateAssetCustomMargin(id, field, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        let val = value === '' ? null : parseFloat(value);
        if (field === 'rebalance_margin_down' && val !== null && asset.rebalance_margin_up !== null && val > asset.rebalance_margin_up) {
            val = asset.rebalance_margin_up;
        }
        if (field === 'rebalance_margin_up' && val !== null && asset.rebalance_margin_down !== null && val < asset.rebalance_margin_down) {
            val = asset.rebalance_margin_down;
        }
        asset[field] = val;
        updateAssetsTable();
    }
}

function updateAssetAllocation(id, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        asset.allocation = parseFloat(value) || 0;
        
        // SÃªnior UEX: Ao mudar a alocaÃ§Ã£o, recalculamos as sugestÃµes de margem se for custom_margin
        const rebalanceType = document.getElementById('rebalance_type').value;
        if (rebalanceType === 'custom_margin') {
            const suggestions = getMarginSuggestions(asset.allocation);
            asset.rebalance_margin_down = suggestions.down;
            asset.rebalance_margin_up = suggestions.up;
        }
        
        updateTotal();
        updateAssetsTable(); // Atualiza os inputs hidden tambÃ©m
    }
}

function updateAssetFactor(id, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        asset.factor = parseFloat(value) || 1.0;
        updateAssetsTable(); // Atualiza os inputs hidden tambÃ©m
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
    // Adicionar ativos ao formulÃ¡rio
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

// Definir data padrÃ£o (primeiro dia do mÃªs atual)
const today = new Date();
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
document.getElementById('start_date').valueAsDate = firstDay;


// Controle dos campos de simulaÃ§Ã£o
function toggleSimulationFields() {
    const type = document.getElementById('simulation_type').value;

    // Esconde todos os campos primeiro
    document.querySelectorAll('.simulation-fields').forEach(field => {
        field.style.display = 'none';
    });

    // Esconde todas as descriÃ§Ãµes dinÃ¢micas
    ['desc_standard', 'desc_monthly_deposit', 'desc_smart_deposit', 'desc_selic_cash_deposit', 'desc_strategic_deposit'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    const depositHeaderTexts = {
        'standard':          '<i class="bi bi-gear me-2"></i>ConfiguraÃ§Ã£o da SimulaÃ§Ã£o',
        'monthly_deposit':   '<i class="bi bi-calendar-plus me-2"></i>ConfiguraÃ§Ã£o â€” Aportes PeriÃ³dicos',
        'strategic_deposit': '<i class="bi bi-lightning-charge me-2"></i>ConfiguraÃ§Ã£o â€” Aportes EstratÃ©gicos',
        'smart_deposit':     '<i class="bi bi-bullseye me-2"></i>ConfiguraÃ§Ã£o â€” Aporte Direcionado ao Alvo',
        'selic_cash_deposit':'<i class="bi bi-piggy-bank me-2"></i>ConfiguraÃ§Ã£o â€” Aporte em Caixa (SELIC)'
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

        // Define valor padrÃ£o se estiver vazio
        if (!document.getElementById('deposit_amount').value) {
            document.getElementById('deposit_amount').value = '1000.00';
        }
    } else if (type === 'strategic_deposit') {
        document.getElementById('strategic_deposit_fields').style.display = 'block';
        toggleUseCashAssetsField(); // Ensure it's hidden for strategic
        // Define valores padrÃ£o
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

    marginContainer.style.display = 'none';
    // marginContainer.style.display = 'none'; // Campo removido
    
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

// Inicializa ao carregar a pÃ¡gina
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa todos os tooltips Bootstrap da pÃ¡gina
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function(el) {
        new bootstrap.Tooltip(el, { html: true, trigger: 'hover focus' });
    });

    toggleSimulationFields();

    // RestriÃ§Ã£o de 5 anos para Plano Starter (Data InÃ­cio)
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
                showPaywallModal('HistÃ³rico de Dados', 'No Plano Starter, o histÃ³rico Ã© limitado aos Ãºltimos 5 anos. No Plano PRO, vocÃª tem acesso a dÃ©cadas de dados histÃ³ricos para simulaÃ§Ãµes ultra-precisas.');
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
