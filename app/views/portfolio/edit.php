<?php
/**
 * @var array $portfolio Dados do portfólio
 * @var array $allAssets Lista de todos os ativos disponíveis
 * @var array $portfolioAssets Ativos já vinculados a este portfólio
 */

$title = 'Editar Portfólio: ' . htmlspecialchars($portfolio['name']);
$meta_robots = 'noindex, nofollow';

$breadcrumbs = [
    ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=' . obfuscateUrl('dashboard')],
    ['label' => 'Portfólios', 'url' => '/index.php?url=' . obfuscateUrl('portfolio')],
    ['label' => htmlspecialchars($portfolio['name']), 'url' => '/index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolio['id'])],
    ['label' => 'Editar', 'url' => '#'],
];

ob_start();
?>

    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="alert alert-warning shadow-sm mb-4" id="rangeWarning">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold">Conflito de Datas Detectado</h6>
                        <p class="mb-1 small" id="rangeWarningText"></p>
                        <button type="button" class="btn btn-sm btn-dark mt-2" onclick="autoAdjustDates()">
                            <i class="bi bi-magic me-1"></i> Ajustar Período do Portfólio Automaticamente
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Editar Configurações do Portfólio</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('portfolio/update/' . $portfolio['id']); ?>" id="portfolioForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-bold d-flex align-items-center">
                                    Nome do Portfólio *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Dê um nome claro  sua estratégia (ex: Aposentadoria 2050)."></i>
                                </label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($portfolio['name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="initial_capital" class="form-label fw-bold d-flex align-items-center">
                                    Capital Inicial *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="O valor em dinheiro que você possui para investir no primeiro dia da simulação."></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo $portfolio['output_currency']; ?></span>
                                    <input type="number" class="form-control" id="initial_capital" name="initial_capital" step="0.01" min="100" value="<?php echo $portfolio['initial_capital']; ?>" required>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label fw-bold d-flex align-items-center">
                                    Descrição / Estratégia
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Use este espaço para anotar as premissas ou objetivos desta carteira específica."></i>
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($portfolio['description']); ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="start_date" class="form-label fw-bold d-flex align-items-center">
                                    Data Início *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Data do primeiro aporte. O sistema buscará preços históricos a partir deste dia."></i>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $portfolio['start_date']; ?>" required>
                                <?php if (!Auth::isPro()): ?>
                                <div class="form-text text-primary small">
                                    <i class="bi bi-info-circle me-1"></i> No Plano Starter, o histórico é limitado aos últimos 5 anos. 
                                    <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="fw-bold text-decoration-none">Desbloquear PRO</a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label fw-bold d-flex align-items-center">
                                    Data Fim (Opcional)
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Data final do backtest. Se vazio, usará os dados mais recentes disponíveis."></i>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $portfolio['end_date']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="rebalance_frequency" class="form-label fw-bold d-flex align-items-center">
                                    Rebalanceamento *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Define de quanto em quanto tempo o sistema deve 'forçar' a volta dos ativos ao peso-alvo original."></i>
                                </label>
                                <select class="form-select" id="rebalance_frequency" name="rebalance_frequency" required>
                                    <?php
                                    $freqs = ['monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'biannual' => 'Semestral', 'annual' => 'Anual', 'never' => 'Nunca'];
                                    foreach ($freqs as $val => $label): ?>
                                        <option value="<?php echo $val; ?>" <?php echo $portfolio['rebalance_frequency'] == $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-4">
                                <label for="output_currency" class="form-label fw-bold d-flex align-items-center">
                                    Moeda de Exibição *
                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="A moeda em que todos os relatórios e gráficos serão apresentados. O sistema faz a conversão automática se houver ativos em moedas diferentes."></i>
                                </label>
                                <select class="form-select" id="output_currency" name="output_currency" required>
                                    <option value="BRL" <?php echo $portfolio['output_currency'] == 'BRL' ? 'selected' : ''; ?>>BRL (Real)</option>
                                    <option value="USD" <?php echo $portfolio['output_currency'] == 'USD' ? 'selected' : ''; ?>>USD (Dólar)</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3 fw-bold"><i class="bi bi-calculator me-2 text-primary"></i>Tipo de Simulação</h5>
                        <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="simulation_type" class="form-label fw-bold d-flex justify-content-between align-items-center">
                                            <span>Tipo de Simulação *</span>
                                            <button type="button" class="btn btn-link p-0 text-decoration-none small" data-bs-toggle="modal" data-bs-target="#simulationHelpModal">
                                                <i class="bi bi-question-circle me-1"></i>Como escolher?
                                            </button>
                                        </label>
                                        <select class="form-select" id="simulation_type" name="simulation_type" required onchange="handleSimulationTypeChange(this)">
                                            <option value="standard" <?= $portfolio['simulation_type'] == 'standard' ? 'selected' : '' ?>>Padrão (sem aportes)</option>
                                            <option value="monthly_deposit" <?= $portfolio['simulation_type'] == 'monthly_deposit' ? 'selected' : '' ?>>Com Aportes Periódicos</option>
                                            <option value="strategic_deposit" <?= $portfolio['simulation_type'] == 'strategic_deposit' ? 'selected' : '' ?> <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Com Aportes Estratégicos <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                            <option value="smart_deposit" <?= $portfolio['simulation_type'] == 'smart_deposit' ? 'selected' : '' ?> <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Aporte Direcionado ao Alvo <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                            <option value="selic_cash_deposit" <?= $portfolio['simulation_type'] == 'selic_cash_deposit' ? 'selected' : '' ?> <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Aporte em Caixa (SELIC) <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold d-flex align-items-center">
                                            Imposto sobre o Lucro
                                            <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Se ativado, o sistema calculará o imposto devido sobre o lucro realizado em cada venda (rebalanceamento)."></i>
                                        </label>
                                        <div class="card bg-light border-0 rounded-3">
                                            <div class="card-body p-2">
                                                <div class="form-check form-switch mb-2 ms-2">
                                                    <?php $hasTax = !empty($portfolio['profit_tax_rates_json']) || !empty($portfolio['profit_tax_rate']); ?>
                                                    <input class="form-check-input" type="checkbox" id="enable_tax" onchange="handleTaxToggle(this)" <?= $hasTax ? 'checked' : '' ?>>
                                                    <label class="form-check-label small text-muted" for="enable_tax">Calcular Imposto <?= !Auth::isPro() ? '🔒' : '' ?></label>
                                                </div>
                                                <div id="tax_input_container" style="display: <?= $hasTax ? 'block' : 'none' ?>;">
                                                    <hr class="my-2 opacity-10">
                                                    <?php if (Auth::isPro()): ?>
                                                    <?php 
                                                        $taxRates = !empty($portfolio['profit_tax_rates_json']) ? json_decode($portfolio['profit_tax_rates_json'], true) : [];
                                                        $defaultRates = [
                                                            'CRIPTOMOEDA' => '15.0',
                                                            'ETF_US' => '15.0',
                                                            'ETF_BR' => '15.0',
                                                            'RENDA_FIXA' => '20.0',
                                                            'FUNDO_IMOBILIARIO' => '20.0'
                                                        ];
                                                        
                                                        foreach ($defaultRates as $group => $defaultRate):
                                                            $currentRate = isset($taxRates[$group]) ? $taxRates[$group] : ($portfolio['profit_tax_rate'] ?? $defaultRate);
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
                                                                       value="<?= $currentRate ?>">
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
                                                    
                                                    <!-- Campo oculto para compatibilidade com o legado se necessário -->
                                                    <input type="hidden" id="profit_tax_rate" name="profit_tax_rate" value="<?= $portfolio['profit_tax_rate'] ?? '' ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <!-- Campos para Aportes Periódicos / Direcionado / Caixa SELIC -->
                        <?php $showDepositFields = in_array($portfolio['simulation_type'], ['monthly_deposit', 'smart_deposit', 'selic_cash_deposit']); ?>
                        <div id="monthly_deposit_fields" class="simulation-fields" style="display: <?= ($showDepositFields || $portfolio['simulation_type'] == 'standard') ? 'block' : 'none' ?>;">
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <?php
                                    $headerTextsPhp = [
                                        'standard'           => '<i class="bi bi-gear me-2"></i>Configuração da Simulação',
                                        'monthly_deposit'    => '<i class="bi bi-calendar-plus me-2"></i>Configuração — Aportes Periódicos',
                                        'strategic_deposit'  => '<i class="bi bi-lightning-charge me-2"></i>Configuração — Aportes Estratégicos',
                                        'smart_deposit'      => '<i class="bi bi-bullseye me-2"></i>Configuração — Aporte Direcionado ao Alvo',
                                        'selic_cash_deposit' => '<i class="bi bi-piggy-bank me-2"></i>Configuração — Aporte em Caixa (SELIC)'
                                    ];
                                    $currentHeader = $headerTextsPhp[$portfolio['simulation_type']] ?? $headerTextsPhp['standard'];
                                    ?>
                                    <h6 class="mb-0" id="deposit_card_header"><?= $currentHeader ?></h6>
                                    <span class="badge bg-white text-primary rounded-pill small">Dica UEX</span>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-end mb-3" id="deposit_inputs_row" style="display: <?= $showDepositFields ? 'flex' : 'none' ?>;">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="deposit_amount" class="form-label d-flex align-items-center">
                                                    Valor do Aporte
                                                    <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Valor a ser investido periodicamente segundo a frequência escolhida."></i>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text" id="deposit_currency_label"><?= $portfolio['deposit_currency'] ?? 'BRL' ?></span>
                                                    <input type="number" class="form-control" id="deposit_amount" name="deposit_amount"
                                                           value="<?= htmlspecialchars($portfolio['deposit_amount'] ?? '') ?>"
                                                           step="0.01" min="0" placeholder="Ex: 5000.00">
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
                                                    <option value="BRL" <?= ($portfolio['deposit_currency'] ?? 'BRL') == 'BRL' ? 'selected' : '' ?>>BRL (Real)</option>
                                                    <option value="USD" <?= ($portfolio['deposit_currency'] ?? 'BRL') == 'USD' ? 'selected' : '' ?>>USD (Dólar)</option>
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
                                                    <option value="monthly"   <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'monthly'   ? 'selected' : '' ?>>Mensal</option>
                                                    <option value="bimonthly" <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'bimonthly' ? 'selected' : '' ?>>Bimestral</option>
                                                    <option value="quarterly" <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'quarterly' ? 'selected' : '' ?>>Trimestral</option>
                                                    <option value="biannual"  <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'biannual'  ? 'selected' : '' ?>>Semestral</option>
                                                    <option value="annual"    <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'annual'    ? 'selected' : '' ?>>Anual</option>
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
                                                    <option value="full" <?= ($portfolio['rebalance_type'] ?? 'full') == 'full' ? 'selected' : '' ?>>Completo (Compra e Venda)</option>
                                                    <option value="buy_only" <?= ($portfolio['rebalance_type'] ?? 'full') == 'buy_only' ? 'selected' : '' ?>>Apenas Compras (Sem Vendas)</option>
                                                    <option value="custom_margin" <?= ($portfolio['rebalance_type'] ?? 'full') == 'custom_margin' ? 'selected' : '' ?> <?= !Auth::isPro() ? 'data-premium="true"' : '' ?>>Com Margens Customizadas por Ativo <?= !Auth::isPro() ? '🔒' : '' ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" id="deposit_inflation_adjusted" name="deposit_inflation_adjusted" value="1" <?= ($portfolio['deposit_inflation_adjusted'] ?? 0) ? 'checked' : '' ?>>
                                                    <label class="form-check-label d-flex align-items-center" for="deposit_inflation_adjusted">
                                                        Corrigir pela Inflação (IPCA)
                                                        <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Aumenta o valor do seu aporte mensalmente seguindo o IPCA histórico, preservando o valor real investido."></i>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="use_cash_assets_container" style="display: none;">
                                            <div class="mb-3">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" id="use_cash_assets_for_rebalance" name="use_cash_assets_for_rebalance" value="1" <?= ($portfolio['use_cash_assets_for_rebalance'] ?? 0) ? 'checked' : '' ?>>
                                                    <label class="form-check-label d-flex align-items-center" for="use_cash_assets_for_rebalance">
                                                        Usar ativos caixa no rebalanceamento
                                                        <i class="bi bi-info-circle-fill ms-2 text-muted info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Permite que os ativos 'Caixa SELIC' ou 'Caixa Dólar' sejam usados para comprar outros ativos da carteira durante o rebalanceamento. Esses ativos caixa devem estar definidos e com peso na sua alocação."></i>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Descrição dinâmica por tipo -->
                                    <div id="desc_standard" class="alert alert-light border shadow-sm py-3 small mb-0" style="display: <?= $portfolio['simulation_type'] == 'standard' ? 'block' : 'none' ?>;">
                                        <div class="d-flex">
                                            <i class="bi bi-info-circle-fill text-primary fs-5 me-3"></i>
                                            <div>
                                                <strong class="d-block mb-1">Estratégia Buy & Hold</strong>
                                                O capital inicial é investido uma única vez. A carteira evolui apenas pela variação dos preços e pelos rebalanceamentos periódicos que você definir. Ideal para comparar o desempenho puro dos ativos escolhidos.
                                            </div>
                                        </div>
                                    </div>
                                    <div id="desc_monthly_deposit" class="alert alert-info border shadow-sm py-3 small mb-0" style="display: <?= $portfolio['simulation_type'] == 'monthly_deposit' ? 'block' : 'none' ?>;">
                                        <div class="d-flex">
                                            <i class="bi bi-calendar-check-fill text-info fs-5 me-3"></i>
                                            <div>
                                                <strong class="d-block mb-1">Aportes Regulares</strong>
                                                Simula o hábito de poupar mensalmente. O valor do aporte é distribuído entre todos os ativos seguindo o peso-alvo. É a forma clássica de acumulação de patrimônio (Dollar Cost Averaging).
                                            </div>
                                        </div>
                                    </div>
                                    <div id="desc_smart_deposit" class="alert alert-success border shadow-sm py-3 small mb-0" style="display: <?= $portfolio['simulation_type'] == 'smart_deposit' ? 'block' : 'none' ?>;">
                                        <div class="d-flex">
                                            <i class="bi bi-bullseye text-success fs-5 me-3"></i>
                                            <div>
                                                <strong class="d-block mb-1">Rebalanceamento "Pela Compra"</strong>
                                                A estratégia mais inteligente: o aporte é usado para comprar o ativo que está mais "barato" (mais longe do alvo). Isso evita vendas desnecessárias e reduz custos com impostos, mantendo a carteira equilibrada organicamente.
                                            </div>
                                        </div>
                                    </div>
                                    <div id="desc_selic_cash_deposit" class="alert alert-secondary border shadow-sm py-3 small mb-0" style="display: <?= $portfolio['simulation_type'] == 'selic_cash_deposit' ? 'block' : 'none' ?>;">
                                        <div class="d-flex">
                                            <i class="bi bi-piggy-bank-fill text-secondary fs-5 me-3"></i>
                                            <div>
                                                <strong class="d-block mb-1">Acúmulo em Liquidez</strong>
                                                O aporte mensal é guardado em um "Caixa" rendendo SELIC. O montante total acumulado só entra na carteira nos meses de rebalanceamento. Útil para simular quem prefere fazer grandes compras periódicas em vez de pequenas compras mensais.
                                            </div>
                                        </div>
                                    </div>
                                    <div id="desc_strategic_deposit" class="alert alert-warning border shadow-sm py-3 small mb-0" style="display: <?= $portfolio['simulation_type'] == 'strategic_deposit' ? 'block' : 'none' ?>;">
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
                        <div id="strategic_deposit_fields" class="simulation-fields" style="display: <?= $portfolio['simulation_type'] == 'strategic_deposit' ? 'block' : 'none' ?>;">
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
                                                    <input type="number" class="form-control" id="strategic_threshold" name="strategic_threshold"
                                                           value="<?= htmlspecialchars($portfolio['strategic_threshold'] ?? '') ?>"
                                                           step="0.1" min="0" max="100" placeholder="Ex: 10.0">
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
                                                    <input type="number" class="form-control" id="strategic_deposit_percentage" name="strategic_deposit_percentage"
                                                           value="<?= htmlspecialchars($portfolio['strategic_deposit_percentage'] ?? '') ?>"
                                                           step="0.1" min="0" max="100" placeholder="Ex: 10.0">
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

                        <hr class="my-4">

                        <h5 class="mb-3 fw-bold">
                            <i class="bi bi-pie-chart me-2 text-primary"></i>Alocação Estratégica de Ativos
                            <?php if (!Auth::isPro()): ?>
                                <span class="ms-2 cursor-pointer" onclick="showPaywallModal('Limite de Ativos', 'No plano Starter você pode adicionar até 5 ativos por portfólio. No plano PRO, não há limites.')">🔒</span>
                            <?php endif; ?>
                        </h5>

                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle" id="assetsTable">
                                <thead class="table-light">
                                <tr>
                                    <th>Ativo e Disponibilidade Histórica</th>
                                    <th style="width: 175px;">Alocação (%)</th>
                                    <th style="width: 140px;">Fator Perf.</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                                </thead>
                                <tbody id="assetsBody">
                                </tbody>
                                <tfoot class="bg-light">
                                <tr>
                                    <td>
                                        <select class="form-select" id="assetSelect">
                                            <option value="">+ Adicionar novo ativo...</option>
                                            <?php foreach ($allAssets as $asset): ?>
                                                <option value="<?php echo $asset['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                                        data-min="<?php echo $asset['min_date']; ?>"
                                                        data-max="<?php echo $asset['max_date']; ?>">
                                                    <?php echo htmlspecialchars($asset['name']); ?>
                                                    (<?php echo date('m/Y', strtotime($asset['min_date'])); ?> a <?php echo date('m/Y', strtotime($asset['max_date'])); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="assetAllocation" step="0.01" min="0" max="100" placeholder="0.00"
                                                   onkeydown="if(event.key==='Enter'){event.preventDefault();addAsset();}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="assetFactor" step="0.01" min="0.1" max="10" value="1.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success" onclick="addAsset()" title="Adicionar ativo"><i class="bi bi-plus-lg"></i></button>
                                    </td>
                                </tr>
                                <tr class="table-secondary">
                                    <td class="text-end fw-bold">TOTAL DA CARTEIRA:</td>
                                    <td>
                                        <span id="totalAllocation" class="fw-bold fs-5">0</span><span class="fw-bold">%</span>
                                        <span id="totalDiff" class="ms-2 badge small"></span>
                                    </td>
                                    <td colspan="2" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="distributeEqually()" title="Divide 100% igualmente entre todos os ativos">
                                            <i class="bi bi-distribute-horizontal me-1"></i>Igualar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="normalizeToHundred()" title="Escala proporcionalmente para que a soma seja exatamente 100%">
                                            <i class="bi bi-arrows-angle-contract me-1"></i>Normalizar 100%
                                        </button>
                                    </td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="alert alert-danger py-2" id="allocationWarning" style="display: none;">
                            <i class="bi bi-exclamation-octagon me-2"></i>A soma das alocações deve ser exatamente 100.00% para salvar.
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm" id="submitBtn">
                                <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // toggleSimulationFields is defined below alongside the asset/table logic

        // Inicialização do array de ativos com dados históricos
        let assets = [
            <?php foreach ($portfolioAssets as $pa):
            // Busca as datas limites na lista global de ativos para popular o JS
            $min = ""; $max = "";
            foreach($allAssets as $aa) if($aa['id'] == $pa['asset_id']) { $min = $aa['min_date']; $max = $aa['max_date']; break; }
            ?>
            {
                id: <?php echo $pa['id']; ?>,
                asset_id: <?php echo $pa['asset_id']; ?>,
                name: "<?php echo htmlspecialchars($pa['name']); ?>",
                allocation: <?php echo (float)$pa['allocation_percentage']; ?>,
                factor: <?php echo (float)$pa['performance_factor']; ?>,
                rebalance_margin_down: <?php echo $pa['rebalance_margin_down'] !== null ? (float)$pa['rebalance_margin_down'] : 'null'; ?>,
                rebalance_margin_up: <?php echo $pa['rebalance_margin_up'] !== null ? (float)$pa['rebalance_margin_up'] : 'null'; ?>,
                min_date: "<?php echo $min; ?>",
                max_date: "<?php echo $max; ?>"
            },
            <?php endforeach; ?>
        ];

        let nextId = assets.length > 0 ? Math.max(...assets.map(a => a.id)) + 1 : 1;
        let suggestedMaxStart = "";
        let suggestedMinEnd = "";
        let lastValidSimulationType = document.getElementById('simulation_type').value;

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

        let lastValidRebalanceType = document.getElementById('rebalance_type').value;
        function handleRebalanceTypeChange(select) {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption.getAttribute('data-premium') === 'true') {
                const feature = selectedOption.text.replace(' 🔒', '');
                let desc = '';
                
                if (selectedOption.value === 'custom_margin') desc = 'Permite definir margens de rebalanceamento diferentes para cada ativo da sua carteira.';

                showPaywallModal(feature, desc);
                select.value = lastValidRebalanceType; // Volta para o anterior
                return;
            }
            
            lastValidRebalanceType = select.value;
            toggleUseCashAssetsField();

            // Sênior UEX: Sugerir margens se trocar para custom_margin e não tiverem preenchidas
            if (lastValidRebalanceType === 'custom_margin') {
                assets.forEach(asset => {
                    if (asset.rebalance_margin_down === null || asset.rebalance_margin_up === null) {
                        const suggestions = getMarginSuggestions(asset.allocation);
                        asset.rebalance_margin_down = asset.rebalance_margin_down ?? suggestions.down;
                        asset.rebalance_margin_up = asset.rebalance_margin_up ?? suggestions.up;
                    }
                });
                updateTable();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateTable(); // CORREÇÃO: Nome correto da função sincronizado com o carregamento
        });

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
                'standard':           '<i class="bi bi-gear me-2"></i>Configuração da Simulação',
                'monthly_deposit':    '<i class="bi bi-calendar-plus me-2"></i>Configuração — Aportes Periódicos',
                'strategic_deposit':  '<i class="bi bi-lightning-charge me-2"></i>Configuração — Aportes Estratégicos',
                'smart_deposit':      '<i class="bi bi-bullseye me-2"></i>Configuração — Aporte Direcionado ao Alvo',
                'selic_cash_deposit': '<i class="bi bi-piggy-bank me-2"></i>Configuração — Aporte em Caixa (SELIC)'
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

                // Define valor padrão se estiver vazio
                const depositAmount = document.getElementById('deposit_amount');
                if (!depositAmount.value) {
                    depositAmount.value = '1000.00';
                }

                toggleUseCashAssetsField();
            } else if (type === 'strategic_deposit') {
                document.getElementById('strategic_deposit_fields').style.display = 'block';
                toggleUseCashAssetsField(); // Ensure it's hidden for strategic
                // Define valores padrão se estiver vazio
                const threshold = document.getElementById('strategic_threshold');
                const percentage = document.getElementById('strategic_deposit_percentage');
                if (!threshold.value) {
                    threshold.value = '10.0';
                }
                if (!percentage.value) {
                    percentage.value = '10.0';
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
            
            if (cashContainer) {
                if ((type === 'smart_deposit' || type === 'selic_cash_deposit') && (rebalanceType === 'buy_only' || rebalanceType === 'custom_margin')) {
                    cashContainer.style.display = 'block';
                } else {
                    cashContainer.style.display = 'none';
                    if (document.getElementById('use_cash_assets_for_rebalance')) {
                        document.getElementById('use_cash_assets_for_rebalance').checked = false;
                    }
                }
            }

            if (marginContainer) {
                // marginContainer.style.display = 'none'; // Campo removido
            }
            
            // Sempre que mudar o tipo de rebalanceamento, atualizamos a tabela para mostrar/esconder margens customizadas
            updateTable();
        }

        function toggleTaxField() {
            const enableTax = document.getElementById('enable_tax').checked;
            const container = document.getElementById('tax_input_container');
            const inputs = document.querySelectorAll('.tax-rate-input');
            
            if (enableTax) {
                container.style.display = 'block';
                // Se algum input estiver vazio ao habilitar, podemos colocar o default neles (opcional)
            } else {
                container.style.display = 'none';
                inputs.forEach(input => input.value = ''); // Envia null/vazio para o servidor
                document.getElementById('profit_tax_rate').value = '';
            }
        }

        document.getElementById('rebalance_type').addEventListener('change', handleRebalanceTypeChange);

    function addAsset() {
        const select = document.getElementById('assetSelect');
        const allocationInput = document.getElementById('assetAllocation');
        const factorInput = document.getElementById('assetFactor');

        // Verificação de limite de ativos para Plano Starter
        const isPro = <?= Auth::isPro() ? 'true' : 'false' ?>;
        if (!isPro && assets.length >= 5) {
            showPaywallModal('Limite de Ativos', 'No plano Starter você pode adicionar até 5 ativos por portfólio. No plano PRO, não há restrição de quantidade.');
            return;
        }

            if (!select.value || !allocationInput.value) {
                alert("Selecione um ativo e informe a alocação.");
                return;
            }

            const opt = select.options[select.selectedIndex];
            const assetId = parseInt(select.value);

            if (assets.find(a => a.asset_id === assetId)) {
                alert("Este ativo já está na lista.");
                return;
            }

            const allocation = parseFloat(allocationInput.value);
            const suggestions = getMarginSuggestions(allocation);

            assets.push({
                id: nextId++,
                asset_id: assetId,
                name: opt.getAttribute('data-name'),
                allocation: allocation,
                factor: parseFloat(factorInput.value) || 1.0,
                rebalance_margin_down: suggestions.down,
                rebalance_margin_up: suggestions.up,
                min_date: opt.getAttribute('data-min'),
                max_date: opt.getAttribute('data-max')
            });

            select.value = '';
            allocationInput.value = '';
            factorInput.value = '1.00';

            updateTable();
        }

        function removeAsset(id) {
            assets = assets.filter(a => a.id !== id);
            updateTable();
        }

        function updateTable() {
            const tbody = document.getElementById('assetsBody');
            const rebalanceType = document.getElementById('rebalance_type').value;
            const isCustomMargin = rebalanceType === 'custom_margin';
            let total = 0;

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

            tbody.innerHTML = '';

            assets.forEach(asset => {
                total += asset.allocation;
                const dateInfo = asset.min_date ? `<div class="asset-range-info"><i class="bi bi-calendar-check me-1"></i>Histórico: ${formatDateLabel(asset.min_date)} a ${formatDateLabel(asset.max_date)}</div>` : '';

                let marginFields = '';
                if (isCustomMargin) {
                    const suggestions = getMarginSuggestions(asset.allocation);
                    marginFields = `
                        <td class="bg-light-subtle rounded-3">
                            <div class="margin-input-group">
                                <div class="margin-input-item">
                                    <span class="margin-input-label">Mín</span>
                                    <div class="margin-input-control">
                                        <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.asset_id}, 'down', -0.1)">-</button>
                                        <input type="number" class="margin-input-field" step="0.1" 
                                            value="${(asset.rebalance_margin_down !== null && asset.rebalance_margin_down !== undefined) ? asset.rebalance_margin_down.toFixed(1) : ''}" 
                                            onchange="updateAssetData(${asset.asset_id}, 'rebalance_margin_down', this.value)">
                                        <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.asset_id}, 'down', 0.1)">+</button>
                                    </div>
                                </div>
                                <div class="margin-input-item">
                                    <span class="margin-input-label">Máx</span>
                                    <div class="margin-input-control">
                                        <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.asset_id}, 'up', -0.1)">-</button>
                                        <input type="number" class="margin-input-field" step="0.1" 
                                            value="${(asset.rebalance_margin_up !== null && asset.rebalance_margin_up !== undefined) ? asset.rebalance_margin_up.toFixed(1) : ''}" 
                                            onchange="updateAssetData(${asset.asset_id}, 'rebalance_margin_up', this.value)">
                                        <button type="button" class="margin-input-btn" onclick="adjustMargin(${asset.asset_id}, 'up', 0.1)">+</button>
                                    </div>
                                </div>
                                <div class="margin-suggested-row">
                                    <div class="margin-suggested-badge">Sugestão: ${suggestions.label}</div>
                                    <button type="button" class="margin-reset-btn" onclick="resetMarginToSuggestion(${asset.asset_id})" title="Reaplicar valores sugeridos">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reaplicar
                                    </button>
                                </div>
                                <input type="hidden" name="assets[${asset.asset_id}][rebalance_margin_down]" value="${asset.rebalance_margin_down}">
                                <input type="hidden" name="assets[${asset.asset_id}][rebalance_margin_up]" value="${asset.rebalance_margin_up}">
                            </div>
                        </td>
                    `;
                }

                tbody.innerHTML += `
            <tr>
                <td>
                    <div class="fw-bold text-dark">${asset.name}</div>
                    ${dateInfo}
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" name="assets[${asset.asset_id}][allocation]"
                            value="${asset.allocation.toFixed(2)}" step="0.01" min="0" max="100"
                            class="form-control form-control-lg fw-bold text-primary allocation-input"
                            style="font-size:1.05rem;"
                            oninput="liveAllocationUpdate(${asset.asset_id}, this.value)"
                            onblur="commitAllocation(${asset.asset_id}, this.value)">
                        <span class="input-group-text fw-bold">%</span>
                    </div>
                </td>
                <td>
                    <input type="number" name="assets[${asset.asset_id}][performance_factor]"
                        value="${asset.factor}" step="0.01" min="0.1" max="10"
                        class="form-control form-control-sm"
                        onchange="updateFactor(${asset.asset_id}, this.value)">
                </td>
                ${marginFields}
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeAsset(${asset.id})"><i class="bi bi-trash"></i></button>
                    <input type="hidden" name="assets[${asset.asset_id}][asset_id]" value="${asset.asset_id}">
                </td>
            </tr>
        `;
            });

            refreshTotalDisplay(total);
            validatePortfolioRange(); // Valida as datas após qualquer mudança na lista
            
            // Re-inicializa tooltips se houver
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        }

        // Lógica de Sugestão de Margens (UEX Senior)
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
                label = "±5%";
            }

            const downValue = allocation * (1 - downPer / 100);
            const upValue = allocation * (1 + upPer / 100);

            return {
                down: parseFloat(downValue.toFixed(2)),
                up: parseFloat(upValue.toFixed(2)),
                label: label
            };
        }

        // Função para resetar margens para a sugestão (UEX Senior)
        function resetMarginToSuggestion(assetId) {
            const asset = assets.find(a => a.asset_id == assetId);
            if (!asset) return;
            
            const suggestions = getMarginSuggestions(asset.allocation);
            asset.rebalance_margin_down = suggestions.down;
            asset.rebalance_margin_up = suggestions.up;
            updateTable();
        }

        // Função para ajustar margens com botões +/- (UEX Senior Revise)
        function adjustMargin(assetId, type, delta) {
            const asset = assets.find(a => a.asset_id == assetId);
            if (!asset) return;

            if (type === 'down') {
                let val = (asset.rebalance_margin_down || 0) + delta;
                val = Math.max(0, parseFloat(val.toFixed(2)));
                // Garantir que não ultrapasse o topo
                if (val > asset.rebalance_margin_up) val = asset.rebalance_margin_up;
                asset.rebalance_margin_down = val;
            } else {
                let val = (asset.rebalance_margin_up || 0) + delta;
                val = parseFloat(val.toFixed(2));
                // Garantir que não seja menor que o piso
                if (val < asset.rebalance_margin_down) val = asset.rebalance_margin_down;
                asset.rebalance_margin_up = val;
            }
            updateTable();
        }

        function updateAssetData(assetId, field, value) {
            const asset = assets.find(a => a.asset_id == assetId);
            if (!asset) return;

            if (field === 'rebalance_margin_down') {
                let val = value === '' ? null : parseFloat(value);
                if (val !== null && asset.rebalance_margin_up !== null && val > asset.rebalance_margin_up) {
                    val = asset.rebalance_margin_up;
                }
                asset.rebalance_margin_down = val;
                updateTable();
            } else if (field === 'rebalance_margin_up') {
                let val = value === '' ? null : parseFloat(value);
                if (val !== null && asset.rebalance_margin_down !== null && val < asset.rebalance_margin_down) {
                    val = asset.rebalance_margin_down;
                }
                asset.rebalance_margin_up = val;
                updateTable();
            }
        }

        // Atualização ao vivo do total enquanto digita (SEM reconstruir a tabela)
        function liveAllocationUpdate(assetId, value) {
            const asset = assets.find(a => a.asset_id == assetId);
            if (!asset) return;
            asset.allocation = parseFloat(value) || 0;
            const total = assets.reduce((s, a) => s + a.allocation, 0);
            refreshTotalDisplay(total);
        }

        // Commit ao sair do campo (onblur) — recria tabela só se necessário
        function commitAllocation(assetId, value) {
            const asset = assets.find(a => a.asset_id == assetId);
            if (!asset) return;
            asset.allocation = parseFloat(value) || 0;
            const rebalanceType = document.getElementById('rebalance_type').value;
            if (rebalanceType === 'custom_margin') {
                // Recalcula sugestões de margem e atualiza
                const suggestions = getMarginSuggestions(asset.allocation);
                asset.rebalance_margin_down = suggestions.down;
                asset.rebalance_margin_up = suggestions.up;
                updateTable();
            } else {
                // Apenas revalida datas sem reconstruir a tabela
                const total = assets.reduce((s, a) => s + a.allocation, 0);
                refreshTotalDisplay(total);
                validatePortfolioRange();
            }
        }

        // Atualiza o fator de desempenho (sem reconstruir a tabela)
        function updateFactor(assetId, value) {
            const asset = assets.find(a => a.asset_id == assetId);
            if (asset) asset.factor = parseFloat(value) || 1.0;
        }

        // Atualiza display do total + badge de diferença
        function refreshTotalDisplay(total) {
            document.getElementById('totalAllocation').innerText = total.toFixed(2);
            const diff = parseFloat((total - 100).toFixed(2));
            const diffEl = document.getElementById('totalDiff');
            if (Math.abs(diff) < 0.01) {
                diffEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>OK</span>';
            } else if (diff > 0) {
                diffEl.innerHTML = `<span class="badge bg-danger">+${diff.toFixed(2)}% excesso</span>`;
            } else {
                diffEl.innerHTML = `<span class="badge bg-warning text-dark">${Math.abs(diff).toFixed(2)}% faltando</span>`;
            }
            checkTotal(total);
        }

        // Distribuir igualmente entre todos os ativos
        function distributeEqually() {
            if (assets.length === 0) return;
            const share = parseFloat((100 / assets.length).toFixed(2));
            let assigned = 0;
            assets.forEach((a, i) => {
                if (i < assets.length - 1) {
                    a.allocation = share;
                    assigned += share;
                } else {
                    // Último ativo recebe o restante para fechar 100%
                    a.allocation = parseFloat((100 - assigned).toFixed(2));
                }
            });
            updateTable();
        }

        // Normalizar proporcionalmente para 100%
        function normalizeToHundred() {
            const total = assets.reduce((s, a) => s + a.allocation, 0);
            if (total === 0) { alert('Nenhuma alocação para normalizar.'); return; }
            assets.forEach(a => {
                a.allocation = parseFloat((a.allocation * 100 / total).toFixed(2));
            });
            // Corrige erro de arredondamento no último ativo
            const newTotal = assets.reduce((s, a) => s + a.allocation, 0);
            const remainder = parseFloat((100 - newTotal).toFixed(2));
            if (Math.abs(remainder) > 0 && assets.length > 0) {
                assets[assets.length - 1].allocation = parseFloat((assets[assets.length - 1].allocation + remainder).toFixed(2));
            }
            updateTable();
        }

        function checkTotal(total) {
            const isCorrect = Math.abs(total - 100) < 0.01;
            const submitBtn = document.getElementById('submitBtn');
            const warning = document.getElementById('allocationWarning');

            submitBtn.disabled = !isCorrect;
            warning.style.display = isCorrect ? 'none' : 'block';
        }

        function validatePortfolioRange() {
            if (assets.length === 0) return;

            // O limitador de INÍCIO é o ativo que começou por ÚLTIMO (data mais recente)
            const limitStartAsset = assets.reduce((prev, curr) => (prev.min_date > curr.min_date) ? prev : curr);
            // O limitador de FIM é o ativo que terminou PRIMEIRO (data mais antiga)
            const limitEndAsset = assets.reduce((prev, curr) => (prev.max_date < curr.max_date) ? prev : curr);

            suggestedMaxStart = limitStartAsset.min_date;
            suggestedMinEnd = limitEndAsset.max_date;

            const portfolioStart = document.getElementById('start_date').value;
            const portfolioEnd = document.getElementById('end_date').value;
            const warning = document.getElementById('rangeWarning');
            const warningText = document.getElementById('rangeWarningText');

            let errors = [];
            if (portfolioStart < suggestedMaxStart) {
                errors.push(`O ativo <strong>${limitStartAsset.name}</strong> só possui dados a partir de <strong>${formatDateLabel(suggestedMaxStart)}</strong>.`);
            }
            if (portfolioEnd && portfolioEnd > suggestedMinEnd) {
                errors.push(`O ativo <strong>${limitEndAsset.name}</strong> só possui dados até <strong>${formatDateLabel(suggestedMinEnd)}</strong>.`);
            }

            if (errors.length > 0) {
                warning.style.display = 'block';
                warningText.innerHTML = errors.join('<br>');
                document.getElementById('submitBtn').disabled = true;
            } else {
                warning.style.display = 'none';
                checkTotal(assets.reduce((sum, a) => sum + a.allocation, 0));
            }
        }

        function autoAdjustDates() {
            document.getElementById('start_date').value = suggestedMaxStart;
            if (suggestedMinEnd) document.getElementById('end_date').value = suggestedMinEnd;
            validatePortfolioRange();
        }

        function formatDateLabel(dateStr) {
            if (!dateStr) return "-";
            const [y, m] = dateStr.split('-');
            return `${m}/${y}`;
        }

        // Ouvintes para validar em tempo real ao mudar as datas do portfólio
        document.getElementById('start_date').addEventListener('change', validatePortfolioRange);
        document.getElementById('end_date').addEventListener('change', validatePortfolioRange);

        // Inicializa os campos de simulação ao carregar
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
                        validatePortfolioRange();
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
