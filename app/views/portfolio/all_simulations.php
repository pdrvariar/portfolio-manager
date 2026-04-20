<?php
/**
 * @var array  $portfolios        Portfólios do usuário (para o filtro)
 * @var array  $simulations       Lista de simulações filtradas
 * @var int    $portfolioId       ID do portfólio selecionado (0 = todos)
 * @var array|null $selectedPortfolio Portfólio selecionado (null = todos)
 */

$title = $selectedPortfolio
    ? 'Histórico — ' . htmlspecialchars($selectedPortfolio['name'])
    : 'Histórico de Simulações';

ob_start();

$freqLabels = [
    'never'    => 'Nunca',    'monthly'  => 'Mensal',
    'quarterly'=> 'Trimestral','biannual' => 'Semestral','annual'   => 'Anual',
];
$simTypeLabels = [
    'standard'           => 'Padrão (sem aportes)',
    'monthly_deposit'    => 'Aporte periódico',
    'strategic_deposit'  => 'Aporte estratégico',
    'smart_deposit'      => 'Aporte inteligente',
    'selic_cash_deposit' => 'Reserva Selic + Aporte',
];
$rebalTypeLabels = [
    'full'    => 'Rebalanceamento total',
    'partial' => 'Rebalanceamento parcial',
    'none'    => 'Sem rebalanceamento',
];

// Sort portfolios alphabetically for the filter
$sortedPortfolios = $portfolios;
usort($sortedPortfolios, fn($a,$b) => strcasecmp($a['name'], $b['name']));

// Advanced simulation group filter (passed from controller)
$advancedGroup = $advancedGroup ?? null; // already set by controller

// If a group is active, filter only those simulations for display  
$displaySimulations = $simulations;
if ($advancedGroup) {
    $displaySimulations = array_filter($simulations, fn($s) => ($s['advanced_simulation_group'] ?? '') === $advancedGroup);
    $displaySimulations = array_values($displaySimulations);
}

// Build JS data structures
$snapshotsJs = [];
$metricsJs   = [];
foreach ($simulations as $sim) {
    $pc = $sim['portfolio_config'] ? json_decode($sim['portfolio_config'], true) : null;
    $ac = $sim['assets_config']    ? json_decode($sim['assets_config'],    true) : null;
    $snapshotsJs[$sim['id']] = ['portfolio' => $pc, 'assets' => $ac];
    $metricsJs[$sim['id']] = [
        'total_invested'         => $sim['total_invested']          ?? null,
        'total_deposits'         => $sim['total_deposits']          ?? null,
        'total_value'            => $sim['total_value']             ?? null,
        'interest_earned'        => $sim['interest_earned']         ?? null,
        'total_tax_paid'         => $sim['total_tax_paid']          ?? null,
        'roi'                    => $sim['roi']                     ?? null,
        'annual_return'          => $sim['annual_return']           ?? null,
        'strategy_annual_return' => $sim['strategy_annual_return']  ?? null,
        'strategy_return'        => $sim['strategy_return']         ?? null,
        'volatility'             => $sim['volatility']              ?? null,
        'sharpe_ratio'           => $sim['sharpe_ratio']            ?? null,
        'max_drawdown'           => $sim['max_drawdown']            ?? null,
        'max_monthly_gain'       => $sim['max_monthly_gain']        ?? null,
        'max_monthly_loss'       => $sim['max_monthly_loss']        ?? null,
        'portfolio_name'         => $sim['portfolio_name']          ?? null,
        'advanced_group'         => $sim['advanced_simulation_group'] ?? null,
        'allocation_label'       => $sim['allocation_label']        ?? null,
    ];
}

// Per-portfolio apply snapshot URLs and run URLs
$portfolioUrlsJs = [];
foreach ($portfolios as $p) {
    $portfolioUrlsJs[$p['id']] = [
        'apply'            => '/index.php?url=' . obfuscateUrl('portfolio/apply-snapshot/' . $p['id']),
        'create_from_snap' => '/index.php?url=' . obfuscateUrl('portfolio/create-from-snapshot'),
        'run'              => '/index.php?url=' . obfuscateUrl('portfolio/run/' . $p['id']),
        'view'             => '/index.php?url=' . obfuscateUrl('portfolio/view/' . $p['id']),
        'name'             => $p['name'],
    ];
}

$csrfToken     = Session::getCsrfToken();
$csrfTokenJson = json_encode($csrfToken);
$baseHistoryUrl = obfuscateUrl('portfolio/simulations');

// Summary stats
$totalCount = count($displaySimulations);
$bestSharpe = null; $bestReturn = null;
foreach ($displaySimulations as $s) {
    if ($bestSharpe === null || (float)$s['sharpe_ratio'] > (float)$bestSharpe['sharpe_ratio']) $bestSharpe = $s;
    if ($bestReturn === null || (float)$s['annual_return'] > (float)$bestReturn['annual_return']) $bestReturn = $s;
}

// Breadcrumb injetado no layout
$breadcrumbs = [
    ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=' . obfuscateUrl('dashboard')],
    ['label' => 'Portfólios', 'url' => '/index.php?url=' . obfuscateUrl('portfolio')],
];
if ($selectedPortfolio) {
    $breadcrumbs[] = ['label' => htmlspecialchars($selectedPortfolio['name']), 'url' => '/index.php?url=' . obfuscateUrl('portfolio/view/' . $selectedPortfolio['id'])];
}
$breadcrumbs[] = ['label' => 'Histórico de Simulações', 'url' => '#'];
?>

<!-- ── Cabeçalho ── -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i>
            Histórico de Simulações
        </h2>
        <p class="text-muted small mb-0 mt-1">
            <?php if ($selectedPortfolio): ?>
                Exibindo simulações de <strong><?= htmlspecialchars($selectedPortfolio['name']) ?></strong>.
            <?php else: ?>
                Todas as suas simulações em um só lugar. Use o filtro para focar em um portfólio específico.
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($selectedPortfolio): ?>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $selectedPortfolio['id']) ?>"
           class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-play-fill me-1"></i> Nova Simulação
        </a>
        <?php if (Auth::isPro()): ?>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run-advanced/' . $selectedPortfolio['id']) ?>"
           class="btn btn-warning rounded-pill px-4 shadow-sm"
           title="Gera automaticamente até 20 cenários com alocações variando pela volatilidade dos ativos">
            <i class="bi bi-stars me-1"></i> Simulação Avançada
        </a>
        <?php else: ?>
        <button type="button" class="btn btn-warning rounded-pill px-4 shadow-sm position-relative"
            onclick="showPaywallModal('Simulação Avançada (Monte Carlo)', 'Gere automaticamente até 20 cenários otimizados por volatilidade e encontre a alocação com o melhor Sharpe Ratio. Descubra combinações que você nunca imaginaria — exclusivo para assinantes PRO.')"
            title="Simulação Avançada — Recurso PRO">
            <i class="bi bi-stars me-1"></i> Simulação Avançada
            <i class="bi bi-lock-fill ms-1" style="font-size:.75rem;opacity:.85;"></i>
        </button>
        <?php endif; ?>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $selectedPortfolio['id']) ?>"
           class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <?php else: ?>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>"
           class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-grid-1x2 me-1"></i> Portfólios
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($advancedGroup): ?>
<!-- ── Advanced Simulation Group Banner ── -->
<div class="alert border-0 rounded-4 mb-3 shadow-sm d-flex align-items-center gap-3"
     style="background:linear-gradient(135deg,#fff8e1 0%,#fff3cd 100%); border-left: 4px solid #fd7e14 !important;">
    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:42px;height:42px;">
        <i class="bi bi-stars text-white fs-5"></i>
    </div>
    <div class="flex-grow-1">
        <div class="fw-bold" style="color:#9a4a00;font-size:.9rem;">
            Simulação Avançada · <?= $totalCount ?> cenário<?= $totalCount != 1 ? 's' : '' ?> gerado<?= $totalCount != 1 ? 's' : '' ?>
        </div>
        <div class="text-muted small">
            Alocações geradas automaticamente por <strong>Volatilidade Inversa + Monte Carlo</strong>.
            A linha destacada em verde é o melhor cenário pelo Índice Sharpe.
            <?php if ($selectedPortfolio): ?>
            <a href="/index.php?url=<?= $baseHistoryUrl ?>&portfolio_id=<?= $selectedPortfolio['id'] ?>" class="ms-2 link-secondary small">
                <i class="bi bi-x-circle me-1"></i>Limpar filtro de grupo
            </a>
            <?php endif; ?>
        </div>
    </div>
    <span class="badge rounded-pill px-3 py-2" style="background:#fd7e14;font-size:.78rem;">
        MC Group
    </span>
</div>
<?php endif; ?>

<!-- ── Filtro de Portfólio ── -->
<div class="card border-0 shadow-sm rounded-4 mb-3" id="filterCard">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <i class="bi bi-funnel-fill text-primary fs-5"></i>
                <span class="fw-bold text-dark" style="font-size:.9rem;">Filtrar por Portfólio</span>
            </div>

            <!-- Pill: Todos -->
            <a href="/index.php?url=<?= $baseHistoryUrl ?>"
               class="filter-pill <?= $portfolioId === 0 ? 'active' : '' ?>">
                <i class="bi bi-layers me-1"></i>Todos
                <span class="filter-pill-count"><?= $portfolioId === 0 ? $totalCount : count($simulations) ?></span>
            </a>

            <!-- Pills por portfólio -->
            <?php foreach ($sortedPortfolios as $p): ?>
            <a href="/index.php?url=<?= $baseHistoryUrl ?>&portfolio_id=<?= $p['id'] ?>"
               class="filter-pill <?= $portfolioId === (int)$p['id'] ? 'active' : '' ?>"
               title="<?= htmlspecialchars($p['name']) ?>">
                <i class="bi bi-briefcase me-1"></i><?= htmlspecialchars(mb_strlen($p['name']) > 22 ? mb_substr($p['name'],0,20).'…' : $p['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── Filtros Avançados (colapsável) ── -->
<div class="card border-0 shadow-sm rounded-4 mb-4" id="advancedFilterCard">
    <!-- Cabeçalho do painel -->
    <div class="card-body py-0 px-0">
        <button id="advFilterToggle" type="button"
                class="w-100 d-flex align-items-center justify-content-between px-4 py-3 bg-transparent border-0 text-start"
                aria-expanded="false" aria-controls="advFilterBody">
            <div class="d-flex align-items-center gap-2">
                <div class="adv-filter-icon-wrap">
                    <i class="bi bi-sliders2 text-primary"></i>
                </div>
                <span class="fw-bold text-dark" style="font-size:.9rem;">Filtros Avançados de Métricas</span>
                <span id="advFilterBadge" class="badge rounded-pill bg-primary ms-1 d-none" style="font-size:.7rem;">0</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span id="advFilterResultCount" class="text-muted d-none" style="font-size:.75rem;"></span>
                <i class="bi bi-chevron-down adv-filter-chevron" style="transition:transform .25s;font-size:.85rem;color:#6c757d;"></i>
            </div>
        </button>

        <div id="advFilterBody" class="collapse px-4 pb-4">
            <div class="adv-filter-divider mb-3"></div>

            <div class="row g-3" id="advFilterGrid">
                <!-- Retorno Anual Com Aportes -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-percent text-success"></i>
                            Ret. Anual c/ Aportes
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_annual_return_min"
                                   data-field="annual_return" data-bound="min"
                                   placeholder="Min %" step="0.1">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_annual_return_max"
                                   data-field="annual_return" data-bound="max"
                                   placeholder="Máx %" step="0.1">
                        </div>
                    </div>
                </div>
                <!-- Retorno Anual Estratégia -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-trophy text-primary"></i>
                            Ret. Anual Estratégia
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_strategy_annual_return_min"
                                   data-field="strategy_annual_return" data-bound="min"
                                   placeholder="Min %" step="0.1">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_strategy_annual_return_max"
                                   data-field="strategy_annual_return" data-bound="max"
                                   placeholder="Máx %" step="0.1">
                        </div>
                    </div>
                </div>
                <!-- Volatilidade -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-activity text-warning"></i>
                            Volatilidade
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_volatility_min"
                                   data-field="volatility" data-bound="min"
                                   placeholder="Min %" step="0.1" min="0">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_volatility_max"
                                   data-field="volatility" data-bound="max"
                                   placeholder="Máx %" step="0.1" min="0">
                        </div>
                    </div>
                </div>
                <!-- Sharpe -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-speedometer2 text-success"></i>
                            Índice Sharpe
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_sharpe_ratio_min"
                                   data-field="sharpe_ratio" data-bound="min"
                                   placeholder="Min" step="0.01">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_sharpe_ratio_max"
                                   data-field="sharpe_ratio" data-bound="max"
                                   placeholder="Máx" step="0.01">
                        </div>
                    </div>
                </div>
                <!-- Drawdown Máximo -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-arrow-down-circle text-danger"></i>
                            Drawdown Máx.
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_max_drawdown_min"
                                   data-field="max_drawdown" data-bound="min"
                                   placeholder="Min %" step="0.1" min="0">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_max_drawdown_max"
                                   data-field="max_drawdown" data-bound="max"
                                   placeholder="Máx %" step="0.1" min="0">
                        </div>
                    </div>
                </div>
                <!-- ROI -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-tags text-info"></i>
                            ROI
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_roi_min"
                                   data-field="roi" data-bound="min"
                                   placeholder="Min %" step="0.1">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_roi_max"
                                   data-field="roi" data-bound="max"
                                   placeholder="Máx %" step="0.1">
                        </div>
                    </div>
                </div>
                <!-- Calmar -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-shield-check text-warning"></i>
                            Calmar
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_calmar_min"
                                   data-field="calmar" data-bound="min"
                                   placeholder="Min" step="0.01">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_calmar_max"
                                   data-field="calmar" data-bound="max"
                                   placeholder="Máx" step="0.01">
                        </div>
                    </div>
                </div>
                <!-- Maior Queda Mensal -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-arrow-down-left text-danger"></i>
                            Maior Queda Mensal
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_max_monthly_loss_min"
                                   data-field="max_monthly_loss" data-bound="min"
                                   placeholder="Min %" step="0.1" min="0">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_max_monthly_loss_max"
                                   data-field="max_monthly_loss" data-bound="max"
                                   placeholder="Máx %" step="0.1" min="0">
                        </div>
                    </div>
                </div>
                <!-- Maior Alta Mensal -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="adv-filter-group">
                        <div class="adv-filter-label">
                            <i class="bi bi-arrow-up-right text-success"></i>
                            Maior Alta Mensal
                        </div>
                        <div class="adv-filter-inputs">
                            <input type="number" class="adv-filter-input" id="f_max_monthly_gain_min"
                                   data-field="max_monthly_gain" data-bound="min"
                                   placeholder="Min %" step="0.1" min="0">
                            <span class="adv-filter-sep">–</span>
                            <input type="number" class="adv-filter-input" id="f_max_monthly_gain_max"
                                   data-field="max_monthly_gain" data-bound="max"
                                   placeholder="Máx %" step="0.1" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rodapé do painel -->
            <div class="d-flex align-items-center justify-content-between mt-4 pt-3 adv-filter-footer">
                <div class="text-muted small d-flex align-items-center gap-2">
                    <i class="bi bi-lightbulb text-warning"></i>
                    <span>Deixe campos vazios para ignorar o critério. Valores em <strong>%</strong> onde indicado.</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="advFilterClear" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none">
                        <i class="bi bi-x-circle me-1"></i>Limpar Filtros
                    </button>
                    <button type="button" id="advFilterApply" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-funnel-fill me-1"></i>Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($simulations)): ?>
<!-- ── Estado Vazio ── -->
<div class="card shadow-sm border-0 rounded-4 text-center py-5">
    <div class="card-body">
        <i class="bi bi-bar-chart-line text-muted mb-3 d-block" style="font-size:3.5rem;opacity:.35;"></i>
        <h5 class="fw-bold text-muted">Nenhuma simulação encontrada</h5>
        <p class="text-muted small mb-4">
            <?php if ($selectedPortfolio): ?>
                Execute a primeira simulação neste portfólio para começar a comparar resultados.
            <?php else: ?>
                Crie um portfólio e execute simulações para ver seu histórico aqui.
            <?php endif; ?>
        </p>
        <?php if ($selectedPortfolio): ?>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $selectedPortfolio['id']) ?>"
           class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-play-fill me-1"></i> Executar Simulação
        </a>
        <?php else: ?>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>"
           class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-grid-1x2 me-1"></i> Ver Portfólios
        </a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- ── KPI Summary Strip ── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="summary-kpi-card">
            <div class="summary-kpi-icon bg-soft-primary"><i class="bi bi-list-check text-primary"></i></div>
            <div>
                <div class="summary-kpi-label">Simulações</div>
                <div class="summary-kpi-value text-primary"><?= $totalCount ?></div>
            </div>
        </div>
    </div>
    <?php if ($bestSharpe): ?>
    <div class="col-6 col-md-3">
        <div class="summary-kpi-card">
            <div class="summary-kpi-icon bg-soft-success"><i class="bi bi-speedometer2 text-success"></i></div>
            <div>
                <div class="summary-kpi-label">Melhor Sharpe</div>
                <div class="summary-kpi-value text-success"><?= number_format((float)$bestSharpe['sharpe_ratio'], 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-kpi-card">
            <div class="summary-kpi-icon bg-soft-info"><i class="bi bi-graph-up-arrow text-info"></i></div>
            <div>
                <div class="summary-kpi-label">Melhor Ret. Anual</div>
                <div class="summary-kpi-value text-info">+<?= number_format((float)$bestReturn['annual_return'], 2, ',', '.') ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-kpi-card">
            <div class="summary-kpi-icon bg-soft-warning"><i class="bi bi-folder2-open text-warning"></i></div>
            <div>
                <div class="summary-kpi-label">Portfólios</div>
                <div class="summary-kpi-value"><?= count($sortedPortfolios) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Banner melhor simulação ── -->
<?php if ($bestSharpe): ?>
<div class="alert border-0 rounded-4 mb-4 shadow-sm d-flex align-items-center gap-3"
     style="background:linear-gradient(135deg,#e8f5e9 0%,#f1f8e9 100%);">
    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:46px;height:46px;">
        <i class="bi bi-trophy-fill text-white fs-5"></i>
    </div>
    <div class="flex-grow-1">
        <div class="fw-bold text-success mb-0" style="font-size:.9rem;">Melhor Simulação · Maior Índice Sharpe</div>
        <div class="text-muted small">
            <span class="badge bg-soft-secondary border me-1"><?= htmlspecialchars($bestSharpe['portfolio_name']) ?></span>
            Executada em <strong><?= date('d/m/Y H:i', strtotime($bestSharpe['created_at'])) ?></strong> &nbsp;·&nbsp;
            Retorno anual: <strong><?= number_format((float)$bestSharpe['annual_return'], 2, ',', '.') ?>%</strong> &nbsp;·&nbsp;
            Sharpe: <strong><?= number_format((float)$bestSharpe['sharpe_ratio'], 2, ',', '.') ?></strong> &nbsp;·&nbsp;
            Drawdown máx.: <strong>-<?= number_format(abs((float)$bestSharpe['max_drawdown']), 2, ',', '.') ?>%</strong>
        </div>
    </div>
    <span class="badge bg-success rounded-pill px-3 py-2 fs-6 flex-shrink-0">#<?= $bestSharpe['id'] ?></span>
</div>
<?php endif; ?>

<!-- ── Dica de uso ── -->
<div class="alert border-0 rounded-3 mb-3 py-2 px-3 small d-flex align-items-center gap-2"
     style="background:#eef6fd;color:#374151;">
    <i class="bi bi-info-circle-fill flex-shrink-0" style="color:#3b82f6;"></i>
    Clique em <kbd><i class="bi bi-chevron-down"></i></kbd> ou em qualquer linha para ver a
    <strong>configuração completa</strong>. Marque até <strong>5 simulações</strong> e clique em
    <strong>Comparar</strong> para ver um comparativo detalhado lado a lado.
</div>

<!-- ── Tabela ── -->
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="allHistoryTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-3" style="width:3%">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="selectAllSims" title="Selecionar/desmarcar todas">
                            </div>
                        </th>
                        <th style="width:3%">&nbsp;</th>
                        <th style="width:4%">#</th>
                        <?php if (!$selectedPortfolio): ?>
                        <th style="width:13%">Portfólio</th>
                        <?php endif; ?>
                        <th style="width:10%">Data Simulada</th>
                        <th style="width:10%">Executada em</th>
                        <th class="text-end" style="width:11%">Valor Final</th>
                        <th class="text-end" style="width:9%">Ret. Anual<br><small class="fw-normal text-muted">Com aportes</small></th>
                        <th class="text-end" style="width:9%">Ret. Anual<br><small class="fw-normal text-muted">Estratégia</small></th>
                        <th class="text-end" style="width:7%">Volatili-<br>dade
                            <i class="bi bi-info-circle text-muted ms-1" style="font-size:.7rem;cursor:pointer;"
                               data-bs-toggle="tooltip" data-bs-placement="top"
                               title="Volatilidade anualizada: mede o quanto o retorno da carteira oscila. Quanto menor, mais estável. Verde ≤10%, Amarelo ≤20%, Vermelho >20%."></i>
                        </th>
                        <th class="text-end" style="width:7%">Sharpe
                            <i class="bi bi-info-circle text-muted ms-1" style="font-size:.7rem;cursor:pointer;"
                               data-bs-toggle="tooltip" data-bs-placement="top"
                               title="Índice de Sharpe: retorno obtido por unidade de risco assumido. ≥1 = excelente · 0,5–1 = bom · <0,5 = fraco. Quanto maior, melhor a relação risco×retorno."></i>
                        </th>
                        <th class="text-end" style="width:8%">Drawdown<br>Máx.
                            <i class="bi bi-info-circle text-muted ms-1" style="font-size:.7rem;cursor:pointer;"
                               data-bs-toggle="tooltip" data-bs-placement="top"
                               title="Drawdown Máximo: maior queda percentual do patrimônio, do pico ao vale, ao longo de todo o período simulado. Quanto menor (em módulo), mais protegido o portfólio contra perdas."></i>
                        </th>
                        <th class="text-end" style="width:7%">ROI
                            <i class="bi bi-info-circle text-muted ms-1" style="font-size:.7rem;cursor:pointer;"
                               data-bs-toggle="tooltip" data-bs-placement="top"
                               title="ROI (Return on Investment): retorno percentual total sobre todo o capital investido (aporte inicial + aportes periódicos). Indica o lucro líquido em relação ao que foi investido."></i>
                        </th>
                        <th class="text-end" style="width:7%">Calmar
                            <i class="bi bi-info-circle text-muted ms-1" style="font-size:.7rem;cursor:pointer;"
                               data-bs-toggle="tooltip" data-bs-placement="top"
                               title="Índice de Calmar: retorno anual da estratégia dividido pelo Drawdown Máximo. Mede a recompensa em relação à pior queda. ≥1 = excelente · 0,5–1 = razoável · <0,5 = fraco."></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $bestId = $bestSharpe ? $bestSharpe['id'] : null;
                foreach ($displaySimulations as $sim):
                    $isBest  = ($sim['id'] == $bestId);
                    $annRet  = (float)$sim['annual_return'];
                    $strRet  = (float)($sim['strategy_annual_return'] ?? 0);
                    $vol     = (float)$sim['volatility'];
                    $sharpe  = (float)$sim['sharpe_ratio'];
                    $dd      = abs((float)$sim['max_drawdown']);
                    $roi     = (float)($sim['roi'] ?? 0);
                    $gain    = (float)($sim['interest_earned'] ?? 0);
                    $final   = (float)$sim['total_value'];
                    $cur     = $sim['output_currency'] ?? 'BRL';
                    $calmar  = ($dd > 0) ? $strRet / $dd : null;
                    $isAdv   = !empty($sim['advanced_simulation_group']);
                ?>
                <tr class="<?= $isBest ? 'table-success' : '' ?>"
                    data-sim-id="<?= $sim['id'] ?>"
                    data-portfolio-id="<?= $sim['portfolio_id'] ?>">
                    <td class="ps-3" onclick="event.stopPropagation()">
                        <div class="form-check mb-0">
                            <input class="form-check-input sim-checkbox" type="checkbox"
                                   value="<?= $sim['id'] ?>"
                                   data-sim-id="<?= $sim['id'] ?>"
                                   data-label="Simulação #<?= $sim['id'] ?> — <?= htmlspecialchars($sim['portfolio_name']) ?>"
                                   title="Selecionar para comparar">
                        </div>
                    </td>
                    <td class="ps-1 text-center">
                        <button class="btn btn-sm btn-outline-secondary border-0 btn-expand rounded-circle p-0"
                                style="width:28px;height:28px;line-height:1;" title="Ver configuração">
                            <i class="bi bi-chevron-down" style="font-size:.75rem;"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge <?= $isBest ? 'bg-success' : 'bg-light text-muted border' ?> rounded-pill px-2"><?= $sim['id'] ?></span>
                        <?php if ($isBest): ?><i class="bi bi-trophy-fill text-success ms-1" title="Melhor Sharpe"></i><?php endif; ?>
                        <?php if ($isAdv): ?><i class="bi bi-stars ms-1" style="color:#fd7e14;" title="Simulação Avançada (Monte Carlo)"></i><?php endif; ?>
                    </td>
                    <?php if (!$selectedPortfolio): ?>
                    <td>
                        <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $sim['portfolio_id']) ?>"
                           class="text-decoration-none fw-medium text-primary small text-truncate d-block" style="max-width:160px;"
                           title="<?= htmlspecialchars($sim['portfolio_name']) ?>">
                            <?= htmlspecialchars($sim['portfolio_name']) ?>
                        </a>
                    </td>
                    <?php endif; ?>
                    <td>
                        <span class="fw-medium text-dark small"><?= date('d/m/Y', strtotime($sim['simulation_date'])) ?></span>
                        <?php if ($isAdv && !empty($sim['allocation_label'])): ?>
                        <div class="text-muted" style="font-size:.65rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;" title="<?= htmlspecialchars($sim['allocation_label']) ?>">
                            <i class="bi bi-stars" style="color:#fd7e14;"></i> <?= htmlspecialchars($sim['allocation_label']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="text-muted small">
                            <?= date('d/m/Y', strtotime($sim['created_at'])) ?><br>
                            <span style="font-size:.7rem;"><?= date('H:i', strtotime($sim['created_at'])) ?></span>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-dark small"><?= formatCurrency($final, $cur) ?></td>
                    <td class="text-end">
                        <span class="badge rounded-pill <?= $annRet >= 0 ? 'bg-success' : 'bg-danger' ?> bg-opacity-75 px-2">
                            <?= ($annRet >= 0 ? '+' : '') . number_format($annRet, 2, ',', '.') ?>%
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="badge rounded-pill <?= $strRet >= 0 ? 'bg-primary' : 'bg-danger' ?> bg-opacity-75 px-2">
                            <?= ($strRet >= 0 ? '+' : '') . number_format($strRet, 2, ',', '.') ?>%
                        </span>
                    </td>
                    <td class="text-end small <?= $vol <= 10 ? 'text-success' : ($vol <= 20 ? 'text-warning' : 'text-danger') ?>">
                        <?= number_format($vol, 2, ',', '.') ?>%
                    </td>
                    <td class="text-end small <?= $sharpe >= 1 ? 'text-success fw-bold' : ($sharpe >= 0.5 ? 'text-warning fw-medium' : 'text-danger') ?>">
                        <?= number_format($sharpe, 2, ',', '.') ?>
                    </td>
                    <td class="text-end small <?= $dd <= 10 ? 'text-success' : ($dd <= 25 ? 'text-warning' : 'text-danger') ?>">
                        -<?= number_format($dd, 2, ',', '.') ?>%
                    </td>
                    <td class="text-end small <?= $roi >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($roi >= 0 ? '+' : '') . number_format($roi, 2, ',', '.') ?>%
                    </td>
                    <td class="text-end pe-3 small <?= $calmar === null ? 'text-muted' : ($calmar >= 1 ? 'text-success fw-bold' : ($calmar >= 0.5 ? 'text-warning' : 'text-danger')) ?>">
                        <?php if ($calmar === null): ?>—<?php else: ?><?= number_format($calmar, 2, ',', '.') ?><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Floating Compare Bar ── -->
<div id="compareBar" class="compare-bar d-none">
    <div class="compare-bar-inner d-flex align-items-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <div class="compare-bar-icon">
                <i class="bi bi-bar-chart-steps text-white fs-5"></i>
            </div>
            <div>
                <div class="text-white fw-bold" style="font-size:.9rem;">Comparar Simulações</div>
                <div class="text-white-50 small" id="compareCount">0 selecionadas</div>
            </div>
        </div>
        <div id="compareChips" class="d-flex flex-wrap gap-2 flex-grow-1"></div>
        <div class="d-flex gap-2 flex-shrink-0 ms-auto">
            <button id="clearCompare" class="btn btn-outline-light rounded-pill px-3 btn-sm">
                <i class="bi bi-x-circle me-1"></i>Limpar
            </button>
            <a id="compareBtn" href="#" class="btn btn-warning rounded-pill px-4 fw-bold shadow btn-sm">
                <i class="bi bi-table me-1"></i>Ver Comparativo
            </a>
        </div>
    </div>
</div>

<!-- ── Legenda ── -->
<div class="card border-0 rounded-4 bg-light shadow-sm mt-3">
    <div class="card-body py-3 px-4">
        <h6 class="fw-bold mb-2 text-muted small text-uppercase" style="letter-spacing:.05em;">
            <i class="bi bi-info-circle me-1"></i> Guia de Interpretação
        </h6>
        <div class="row g-2">
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mt-1 text-nowrap" style="font-size:.65rem;">Ret. Estratégia</span>
                    <span class="text-muted" style="font-size:.75rem;">Performance pura dos ativos, sem influência dos aportes.</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border mt-1" style="font-size:.65rem;">Sharpe</span>
                    <span class="text-muted" style="font-size:.75rem;">≥ 1 = excelente · 0,5–1 = bom · &lt; 0,5 = fraco. Retorno por unidade de risco.</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border mt-1" style="font-size:.65rem;">Drawdown</span>
                    <span class="text-muted" style="font-size:.75rem;">Maior queda. Quanto menor, mais estável a estratégia.</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border mt-1" style="font-size:.65rem;">ROI</span>
                    <span class="text-muted" style="font-size:.75rem;">Retorno sobre todo o capital aportado (inicial + periódicos).</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 mt-1 text-nowrap" style="font-size:.65rem;">Calmar</span>
                    <span class="text-muted" style="font-size:.75rem;">Retorno anual da estratégia ÷ Drawdown máx. ≥ 1 = excelente. Recompensa vs pior queda.</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-stars mt-1 flex-shrink-0" style="color:#fd7e14;font-size:.85rem;"></i>
                    <span class="text-muted" style="font-size:.75rem;">Ícone laranja = simulação gerada pelo modo <strong>Simulação Avançada</strong> (Monte Carlo + Volatilidade Inversa).</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$snapshotsJsonPhp    = json_encode($snapshotsJs,       JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$metricsJsonPhp      = json_encode($metricsJs,         JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$portfolioUrlsJsonPhp= json_encode($portfolioUrlsJs,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$showPortfolioCol    = !$selectedPortfolio ? 'true' : 'false';

$additional_css = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* ── Filter Pills ── */
    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 14px;
        border-radius: 50rem;
        font-size: .8rem;
        font-weight: 600;
        border: 1.5px solid var(--border-color);
        color: var(--text-muted);
        background: var(--bg-card);
        text-decoration: none;
        transition: all .18s ease;
        white-space: nowrap;
    }
    .filter-pill:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--soft-primary);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(13,110,253,.12);
    }
    .filter-pill.active {
        border-color: var(--primary);
        color: #fff;
        background: var(--primary);
        box-shadow: 0 2px 8px rgba(13,110,253,.25);
    }
    .filter-pill.active:hover {
        color: #fff;
        transform: translateY(-1px);
    }
    .filter-pill-count {
        background: rgba(255,255,255,.25);
        border-radius: 50rem;
        padding: 1px 7px;
        font-size: .72rem;
        font-weight: 700;
    }
    .filter-pill:not(.active) .filter-pill-count {
        background: var(--soft-secondary);
        color: var(--text-muted);
    }
    /* Scroll horizontal suave no filtro em mobile */
    #filterCard .card-body { overflow-x: auto; flex-wrap: nowrap !important; }
    #filterCard .d-flex { flex-wrap: nowrap; }
    @media (min-width: 768px) {
        #filterCard .d-flex { flex-wrap: wrap !important; }
    }

    /* ── Summary KPI Strip ── */
    .summary-kpi-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 14px 16px;
        height: 100%;
    }
    .summary-kpi-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }
    .summary-kpi-label {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .06em; color: var(--text-muted); line-height: 1.1; margin-bottom: 2px;
    }
    .summary-kpi-value { font-size: 1.15rem; font-weight: 800; line-height: 1.1; color: var(--text-main); }

    /* ── Table ── */
    #allHistoryTable thead th { font-size:.78rem; font-weight:700; white-space:nowrap; vertical-align:middle; }
    #allHistoryTable tbody td { font-size:.82rem; vertical-align:middle; }
    .table-success td { background-color:rgba(25,135,84,.06) !important; }
    tr.dt-hasChild td { background-color:rgba(13,110,253,.04) !important; }

    /* ── Child row ── */
    .child-config {
        background: var(--bg-body, #f8f9fa);
        border-radius: 12px;
        border: 1px solid var(--border-color, #dee2e6);
    }
    .child-config .child-config-title { color: var(--text-main, #212529); }
    .config-section-title {
        font-size:.7rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.08em; color:var(--text-muted, #6c757d); margin-bottom:.5rem;
    }
    .config-pill {
        display:inline-flex; align-items:center; gap:4px;
        background: var(--bg-card, #fff); border: 1px solid var(--border-color, #dee2e6);
        border-radius:20px; padding:3px 10px; font-size:.75rem; white-space:nowrap;
        color: var(--text-main, #212529);
    }
    .config-pill .text-muted { color: var(--text-muted, #6c757d) !important; }
    .config-pill strong { color: var(--text-main, #212529); }
    .child-config table td { color: var(--text-main, #212529); }
    .asset-bar-wrap { background: var(--border-color, #e9ecef); border-radius:6px; height:8px; overflow:hidden; }
    .asset-bar { height:8px; border-radius:6px; }
    #allHistoryTable tbody tr.child td { background-color: var(--bg-body, #f8f9fa) !important; padding:0 !important; }

    /* ── Result KPI Cards ── */
    .result-summary-block {
        background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #dee2e6);
        border-radius: 10px; padding: 14px 16px 10px;
    }
    .result-group-label {
        font-size:.68rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.08em; color:var(--text-muted, #6c757d); margin-bottom:8px;
    }
    .result-kpi-grid { display:flex; flex-wrap:wrap; gap:8px; }
    .result-kpi {
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        text-align:center; min-width:110px; flex:1 1 110px; max-width:160px;
        padding:10px 12px 8px; border-radius:10px;
        background: var(--bg-body, #f8f9fa); border: 1px solid var(--border-color, #e9ecef); gap:2px;
    }
    .result-kpi-icon { font-size:1rem; margin-bottom:3px; }
    .result-kpi-label { font-size:.63rem; color:var(--text-muted,#6c757d); font-weight:600; text-transform:uppercase; letter-spacing:.04em; line-height:1.2; }
    .result-kpi-value { font-size:.88rem; font-weight:800; margin-top:2px; line-height:1.1; }
    .result-kpi.kpi-primary { border-color:rgba(13,110,253,.2);  background:rgba(13,110,253,.05); }
    .result-kpi.kpi-info    { border-color:rgba(13,202,240,.25); background:rgba(13,202,240,.06); }
    .result-kpi.kpi-success { border-color:rgba(25,135,84,.2);   background:rgba(25,135,84,.05); }
    .result-kpi.kpi-danger  { border-color:rgba(220,53,69,.2);   background:rgba(220,53,69,.05); }
    .result-summary-block + .d-flex { padding-top:16px; border-top:1px solid var(--border-color,#dee2e6); margin-top:12px; }

    /* Dark mode */
    [data-theme="dark"] .filter-pill { border-color:var(--border-color); color:var(--text-muted); background:var(--bg-card); }
    [data-theme="dark"] .filter-pill:hover { border-color:var(--primary); color:var(--primary); }
    [data-theme="dark"] .summary-kpi-card { background:var(--bg-card); border-color:var(--border-color); }
    [data-theme="dark"] .result-summary-block { background:var(--bg-body); }
    [data-theme="dark"] .result-kpi { background:var(--bg-card); border-color:var(--border-color); }
    [data-theme="dark"] .result-kpi.kpi-primary { background:rgba(13,110,253,.1); }
    [data-theme="dark"] .result-kpi.kpi-info    { background:rgba(13,202,240,.08); }
    [data-theme="dark"] .result-kpi.kpi-success { background:rgba(25,135,84,.1); }
    [data-theme="dark"] .result-kpi.kpi-danger  { background:rgba(220,53,69,.1); }
    [data-theme="dark"] #allHistoryTable tbody tr.child td { background-color:var(--bg-card) !important; }
    [data-theme="dark"] .child-config { background:var(--bg-card) !important; border-color:var(--border-color) !important; }
    [data-theme="dark"] .config-pill { background:var(--bg-body) !important; border-color:var(--border-color) !important; color:var(--text-main) !important; }
    [data-theme="dark"] .asset-bar-wrap { background:var(--border-color) !important; }
    [data-theme="dark"] tr.dt-hasChild td { background-color:rgba(13,110,253,.08) !important; }

    /* ── Action Cards (snapshot actions) ── */
    .action-card {
        display: flex; flex-direction: column;
        border-radius: 12px; padding: 14px 16px;
        border: 1.5px solid transparent;
        transition: box-shadow .18s, transform .15s;
    }
    .action-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.1); transform: translateY(-2px); }
    .action-card-warning { background: rgba(255,193,7,.07); border-color: rgba(255,193,7,.35); }
    .action-card-success { background: rgba(25,135,84,.07); border-color: rgba(25,135,84,.3); }
    .action-card-icon { font-size: 1.5rem; margin-bottom: 6px; }
    .action-card-warning .action-card-icon { color: #e6a800; }
    .action-card-success .action-card-icon { color: #157347; }
    .action-card-title { font-size: .88rem; font-weight: 700; margin-bottom: 4px; color: var(--text-main,#212529); }
    .action-card-desc { font-size: .75rem; color: var(--text-muted,#6c757d); line-height: 1.45; }
    [data-theme="dark"] .action-card-warning { background: rgba(255,193,7,.1); border-color: rgba(255,193,7,.3); }
    [data-theme="dark"] .action-card-success { background: rgba(25,135,84,.1); border-color: rgba(25,135,84,.3); }

    /* ── Compare Bar ── */
    .compare-bar {
        position: fixed; bottom: 0; left: 0; right: 0; z-index: 1050;
        background: linear-gradient(135deg, #1a1f36 0%, #0d1b2a 100%);
        border-top: 2px solid rgba(255,193,7,.5);
        padding: 14px 24px;
        box-shadow: 0 -4px 24px rgba(0,0,0,.35);
        animation: slideUp .3s ease;
    }
    .compare-bar-inner { max-width: 1320px; margin: 0 auto; }
    .compare-bar-icon {
        width: 42px; height: 42px; border-radius: 10px;
        background: rgba(255,193,7,.2); border: 1px solid rgba(255,193,7,.4);
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .compare-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
        border-radius: 20px; padding: 3px 10px 3px 10px; font-size: .78rem;
        color: #fff; white-space: nowrap;
    }
    .compare-chip .chip-remove {
        cursor: pointer; opacity: .6; transition: opacity .15s;
        background: none; border: none; color: #fff; padding: 0; line-height: 1;
    }
    .compare-chip .chip-remove:hover { opacity: 1; }
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    @keyframes shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-6px)} 40%,80%{transform:translateX(6px)} }
    .shake { animation: shake .4s ease; }

    /* ── Row selected ── */
    tr.sim-selected td { background-color: rgba(13,110,253,.07) !important; }
    tr.sim-selected .sim-checkbox { accent-color: #0d6efd; }

    /* ── Advanced Filter Panel ── */
    #advancedFilterCard { overflow: hidden; }
    #advFilterToggle {
        cursor: pointer;
        border-radius: 1rem !important;
        transition: background .18s;
    }
    #advFilterToggle:hover { background: rgba(13,110,253,.04) !important; }
    #advFilterToggle[aria-expanded="true"] { background: rgba(13,110,253,.04) !important; border-radius: 1rem 1rem 0 0 !important; }
    #advFilterToggle[aria-expanded="true"] .adv-filter-chevron { transform: rotate(180deg); }
    .adv-filter-icon-wrap {
        width: 32px; height: 32px; border-radius: 8px;
        background: rgba(13,110,253,.1); display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .adv-filter-divider { border-top: 1px solid var(--border-color, #dee2e6); }
    .adv-filter-group {
        background: var(--bg-body, #f8f9fa);
        border: 1.5px solid var(--border-color, #e9ecef);
        border-radius: 12px;
        padding: 12px 14px 10px;
        transition: border-color .2s, box-shadow .2s;
    }
    .adv-filter-group:focus-within {
        border-color: rgba(13,110,253,.45);
        box-shadow: 0 0 0 3px rgba(13,110,253,.08);
    }
    .adv-filter-group.has-value {
        border-color: rgba(13,110,253,.5);
        background: rgba(13,110,253,.04);
    }
    .adv-filter-label {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .06em; color: var(--text-muted, #6c757d);
        display: flex; align-items: center; gap: 5px; margin-bottom: 8px;
        white-space: nowrap;
    }
    .adv-filter-inputs {
        display: flex; align-items: center; gap: 4px;
    }
    .adv-filter-input {
        flex: 1 1 0; min-width: 0;
        border: 1px solid var(--border-color, #dee2e6);
        border-radius: 8px; padding: 5px 8px;
        font-size: .78rem; background: var(--bg-card, #fff);
        color: var(--text-main, #212529);
        transition: border-color .18s;
        width: 100%;
        -moz-appearance: textfield;
    }
    .adv-filter-input::-webkit-inner-spin-button,
    .adv-filter-input::-webkit-outer-spin-button { opacity: .5; }
    .adv-filter-input:focus { outline: none; border-color: #0d6efd; }
    .adv-filter-sep {
        color: var(--text-muted, #adb5bd); font-size: .75rem;
        font-weight: 700; flex-shrink: 0;
    }
    .adv-filter-footer { border-top: 1px solid var(--border-color, #dee2e6); }
    /* Active filter badge pulse */
    @keyframes badgePop { 0%{transform:scale(.5);opacity:0} 70%{transform:scale(1.2)} 100%{transform:scale(1);opacity:1} }
    #advFilterBadge { animation: badgePop .25s ease; }

    /* Dark mode overrides */
    [data-theme="dark"] #advFilterToggle:hover,
    [data-theme="dark"] #advFilterToggle[aria-expanded="true"] { background: rgba(255,255,255,.04) !important; }
    [data-theme="dark"] .adv-filter-group { background: var(--bg-card); border-color: var(--border-color); }
    [data-theme="dark"] .adv-filter-group.has-value { background: rgba(13,110,253,.08); }
    [data-theme="dark"] .adv-filter-input { background: var(--bg-body); border-color: var(--border-color); color: var(--text-main); }
</style>';

$compareUrlBase     = '/index.php?url=' . obfuscateUrl('portfolio/compare');

$additional_js = <<<ENDJS
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
const SNAPSHOTS         = {$snapshotsJsonPhp};
const METRICS           = {$metricsJsonPhp};
const PORTFOLIO_URLS    = {$portfolioUrlsJsonPhp};
const CSRF_TOKEN        = {$csrfTokenJson};
const SHOW_PORTFOLIO_COL = {$showPortfolioCol};
const FREQ_LABELS = {never:"Nunca",monthly:"Mensal",quarterly:"Trimestral",biannual:"Semestral",annual:"Anual"};
const SIM_TYPE_LABELS = {standard:"Padrão (sem aportes)",monthly_deposit:"Aporte periódico",strategic_deposit:"Aporte estratégico",smart_deposit:"Aporte inteligente",selic_cash_deposit:"Reserva Selic + Aporte"};
const REBAL_TYPE_LABELS = {full:"Rebalanceamento total",partial:"Rebalanceamento parcial",none:"Sem rebalanceamento"};
const BAR_COLORS = ["#0d6efd","#198754","#dc3545","#fd7e14","#6f42c1","#20c997","#0dcaf0","#ffc107","#6c757d","#d63384"];

function fmt(val, dec) {
    if (val === null || val === undefined || val === "") return "-";
    return parseFloat(val).toLocaleString("pt-BR",{minimumFractionDigits:dec??2,maximumFractionDigits:dec??2});
}
function pill(icon, label, value, colorClass) {
    return '<span class="config-pill"><i class="bi '+icon+' text-muted"></i><span class="text-muted">'+label+':</span> <strong class="'+(colorClass||"text-dark")+'">'+value+'</strong></span>';
}
function fmtCurrency(val, cur) {
    if (!val) return "-";
    const v = parseFloat(val);
    return cur === "USD" ? "$ "+v.toLocaleString("en-US",{minimumFractionDigits:2}) : "R$ "+v.toLocaleString("pt-BR",{minimumFractionDigits:2});
}

function buildChildRow(simId, portfolioId) {
    const data = SNAPSHOTS[simId];
    const m    = METRICS[simId] || {};
    const pUrls= PORTFOLIO_URLS[portfolioId] || {};
    const cur  = (data && data.portfolio && data.portfolio.output_currency) ? data.portfolio.output_currency : "BRL";

    function kpi(icon, label, value, colorClass, bgClass) {
        return '<div class="result-kpi '+(bgClass||"")+'">'+
               '<div class="result-kpi-icon '+(colorClass||"text-primary")+'"><i class="bi '+icon+'"></i></div>'+
               '<div class="result-kpi-label">'+label+'</div>'+
               '<div class="result-kpi-value '+(colorClass||"")+'">'+value+'</div>'+
               '</div>';
    }
    function pctBadge(val) {
        if (val === null || val === undefined || val === "") return '<span class="text-muted">—</span>';
        const v = parseFloat(val); const sign = v >= 0 ? "+" : ""; const cls = v >= 0 ? "text-success" : "text-danger";
        return '<span class="'+cls+'">'+sign+fmt(v)+'%</span>';
    }

    const totalInvested  = parseFloat(m.total_invested  || 0);
    const totalDeposits  = parseFloat(m.total_deposits  || 0);
    const totalValue     = parseFloat(m.total_value     || 0);
    const interestEarned = parseFloat(m.interest_earned || 0);
    const taxPaid        = parseFloat(m.total_tax_paid  || 0);
    const roi            = parseFloat(m.roi             || 0);
    const annReturn      = parseFloat(m.annual_return   || 0);
    const strReturn      = parseFloat(m.strategy_annual_return || 0);
    const vol            = parseFloat(m.volatility      || 0);
    const sharpe         = parseFloat(m.sharpe_ratio    || 0);
    const dd             = Math.abs(parseFloat(m.max_drawdown || 0));
    const maxGain        = parseFloat(m.max_monthly_gain || 0);
    const maxLoss        = Math.abs(parseFloat(m.max_monthly_loss || 0));

    const volColor   = vol   <= 10 ? "text-success" : (vol   <= 20 ? "text-warning" : "text-danger");
    const sharpeColor= sharpe >= 1 ? "text-success" : (sharpe >= 0.5 ? "text-warning" : "text-danger");
    const ddColor    = dd    <= 10 ? "text-success" : (dd    <= 25 ? "text-warning" : "text-danger");

    // Portfolio badge header
    const pNameBadge = pUrls.name
        ? '<div class="mb-3 d-flex align-items-center gap-2"><i class="bi bi-briefcase text-muted"></i><span class="text-muted" style="font-size:.8rem;">Portfólio:</span> <a href="'+pUrls.view+'" class="fw-bold text-primary text-decoration-none" style="font-size:.85rem;">'+pUrls.name+'</a></div>'
        : '';

    const summaryHtml =
        '<div class="result-summary-block mb-3">'+
        pNameBadge +
        '<div class="result-group-label"><i class="bi bi-wallet2 me-1"></i>Patrimônio</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-bank","Capital Inicial",fmtCurrency(totalInvested,cur),"text-primary","kpi-primary")+
            kpi("bi-plus-circle","Total de Aportes",fmtCurrency(totalDeposits,cur),"text-info","kpi-info")+
            kpi("bi-graph-up-arrow","Patrimônio Final",fmtCurrency(totalValue,cur),totalValue>=totalInvested?"text-success":"text-danger",totalValue>=totalInvested?"kpi-success":"kpi-danger")+
            kpi("bi-cash-stack","Ganho Bruto",(interestEarned>=0?"+":"")+fmtCurrency(interestEarned,cur),interestEarned>=0?"text-success":"text-danger",interestEarned>=0?"kpi-success":"kpi-danger")+
            (taxPaid>0?kpi("bi-receipt","Imposto Pago","−"+fmtCurrency(taxPaid,cur),"text-danger","kpi-danger"):"")+
        '</div>'+
        '<div class="result-group-label mt-3"><i class="bi bi-bar-chart-line me-1"></i>Performance</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-percent","Retorno Anual<br><small style=\'font-size:.6rem;opacity:.7;\'>com aportes</small>",pctBadge(m.annual_return),annReturn>=0?"text-success":"text-danger")+
            kpi("bi-trophy","Retorno Estratégia<br><small style=\'font-size:.6rem;opacity:.7;\'>puro dos ativos</small>",pctBadge(m.strategy_annual_return),strReturn>=0?"text-success":"text-danger")+
            kpi("bi-tags","ROI Total",(roi>=0?"+":"")+fmt(roi)+"%",roi>=0?"text-success":"text-danger")+
        '</div>'+
        '<div class="result-group-label mt-3"><i class="bi bi-shield-exclamation me-1"></i>Risco</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-activity","Volatilidade",fmt(vol)+"%",volColor)+
            kpi("bi-speedometer2","Índice Sharpe",fmt(sharpe),sharpeColor)+
            kpi("bi-arrow-down-circle","Drawdown Máx.","−"+fmt(dd)+"%",ddColor)+
            (dd > 0 ? kpi("bi-shield-check","Calmar<br><small style='font-size:.6rem;opacity:.7;'>ret.estr./drawdown</small>",
                (function(){const c=strReturn/dd;return (c>=1?'<span class="text-success fw-bold">':'<span class="'+(c>=0.5?"text-warning":"text-danger")+'">') + fmt(c) + '</span>';})(),
                strReturn/dd >= 1 ? "text-success" : (strReturn/dd >= 0.5 ? "text-warning" : "text-danger")) : "")+
            kpi("bi-arrow-up-right","Melhor Mês","+"+fmt(maxGain)+"%","text-success")+
            kpi("bi-arrow-down-left","Pior Mês","−"+fmt(maxLoss)+"%","text-danger")+
        '</div>'+
        (m.allocation_label ? '<div class="result-group-label mt-3"><i class="bi bi-stars me-1" style="color:#fd7e14;"></i>Cenário de Alocação (Monte Carlo)</div>'+
            '<div class="d-flex flex-wrap gap-2"><span class="config-pill"><i class="bi bi-pie-chart text-warning"></i><strong>'+m.allocation_label+'</strong></span></div>' : '')+
        '</div>';

    if (!data || !data.portfolio) {
        return '<div class="child-config p-3 m-2">'+summaryHtml+
               '<div class="p-3 text-muted small"><i class="bi bi-exclamation-circle me-1"></i>Configuração não disponível (simulação anterior à ativação desta funcionalidade).</div></div>';
    }
    const p = data.portfolio;
    const assets = data.assets || [];

    const genPills = [
        pill("bi-cash-coin","Capital inicial",fmtCurrency(p.initial_capital,p.output_currency)),
        pill("bi-calendar3","Período",
             (p.start_date?p.start_date.substring(0,7).split("-").reverse().join("/"):"?")+
             " > "+(p.end_date?p.end_date.substring(0,7).split("-").reverse().join("/"):"Hoje")),
        pill("bi-currency-exchange","Moeda saída",p.output_currency||"BRL"),
        pill("bi-arrow-repeat","Rebalanceamento",FREQ_LABELS[p.rebalance_frequency]||p.rebalance_frequency||"-"),
        pill("bi-sliders","Tipo rebal.",REBAL_TYPE_LABELS[p.rebalance_type]||p.rebalance_type||"-"),
    ];
    if (p.rebalance_margin) genPills.push(pill("bi-arrows-expand","Margem rebal.",fmt(p.rebalance_margin)+"%"));
    if (p.use_cash_assets_for_rebalance==1) genPills.push('<span class="config-pill"><i class="bi bi-piggy-bank text-primary"></i><strong class="text-primary">Caixa no rebalanceamento</strong></span>');

    let depositHtml = "";
    if (p.simulation_type && p.simulation_type !== "standard") {
        const dPills = [pill("bi-wallet2","Tipo",SIM_TYPE_LABELS[p.simulation_type]||p.simulation_type)];
        if (p.deposit_amount) {
            dPills.push(pill("bi-plus-circle","Valor aporte",fmtCurrency(p.deposit_amount,p.deposit_currency)));
            dPills.push(pill("bi-calendar-check","Frequência",FREQ_LABELS[p.deposit_frequency]||p.deposit_frequency||"-"));
        }
        if (p.deposit_inflation_adjusted==1) dPills.push('<span class="config-pill"><i class="bi bi-graph-up text-success"></i><strong class="text-success">Ajustado pela inflação</strong></span>');
        if (p.strategic_threshold)           dPills.push(pill("bi-bullseye","Gatilho estratégico",fmt(p.strategic_threshold)+"%"));
        if (p.strategic_deposit_percentage)  dPills.push(pill("bi-percent","% no gatilho",fmt(p.strategic_deposit_percentage)+"%"));
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet-fill me-1"></i>Estratégia de Aportes</div><div class="d-flex flex-wrap gap-2">'+dPills.join("")+'</div></div>';
    } else {
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet me-1"></i>Aportes</div><span class="config-pill text-muted"><i class="bi bi-dash-circle"></i> Sem aportes periódicos</span></div>';
    }

    let taxHtml = "";
    const TAX_GROUP_LABELS = {CRIPTOMOEDA:"Criptomoeda",ETF_US:"ETF (EUA)",ETF_BR:"ETF (BR)",RENDA_FIXA:"Renda Fixa",FUNDO_IMOBILIARIO:"Fundo Imobiliário"};
    const TAX_GROUPS_ORDER = ["CRIPTOMOEDA","ETF_US","ETF_BR","RENDA_FIXA","FUNDO_IMOBILIARIO"];
    let taxRates = null;
    if (p.profit_tax_rates_json) { try { taxRates = (typeof p.profit_tax_rates_json==="string")?JSON.parse(p.profit_tax_rates_json):p.profit_tax_rates_json; } catch(e){} }
    if (taxRates && typeof taxRates==="object" && Object.keys(taxRates).length>0) {
        let taxPills = TAX_GROUPS_ORDER.map(function(group){
            const rate=taxRates[group]; if(rate===undefined||rate===null||rate==="") return null;
            return pill("bi-percent",TAX_GROUP_LABELS[group]||group,fmt(rate)+"%","text-danger");
        }).filter(Boolean).join("");
        if (taxPills) taxHtml='<div class="mt-3"><div class="config-section-title"><i class="bi bi-receipt me-1"></i>Imposto de Renda</div><div class="d-flex flex-wrap gap-2">'+taxPills+'</div></div>';
    } else if (p.profit_tax_rate) {
        taxHtml='<div class="mt-3"><div class="config-section-title"><i class="bi bi-receipt me-1"></i>Imposto de Renda</div><div class="d-flex flex-wrap gap-2">'+pill("bi-percent","Alíquota geral",fmt(p.profit_tax_rate)+"%","text-danger")+'</div></div>';
    }

    let assetsRows = "";
    assets.forEach(function(a, i) {
        const pct   = parseFloat(a.allocation_percentage||0);
        const color = BAR_COLORS[i%BAR_COLORS.length];
        const margin= (a.rebalance_margin_down||a.rebalance_margin_up)
            ? '<span>Entre '+fmt(a.rebalance_margin_down||0)+'% à '+fmt(a.rebalance_margin_up||0)+'%</span>' : "-";
        assetsRows +=
            '<tr>'+
            '<td style="font-size:.8rem;padding:5px 8px;"><span class="fw-bold">'+(a.name||"-")+'</span> <span class="text-muted" style="font-size:.72rem;">'+(a.code||"")+'</span></td>'+
            '<td style="font-size:.75rem;padding:5px 8px;" class="text-muted">'+(a.currency||"-")+'</td>'+
            '<td style="padding:5px 8px;min-width:160px;"><div class="d-flex align-items-center gap-2"><div class="asset-bar-wrap flex-grow-1"><div class="asset-bar" style="width:'+pct+'%;background:'+color+';"></div></div><strong style="font-size:.8rem;min-width:44px;text-align:right;">'+fmt(pct)+'%</strong></div></td>'+
            '<td style="font-size:.72rem;padding:5px 8px;" class="text-muted text-end">'+margin+'</td>'+
            '</tr>';
    });

    const assetsHtml = assets.length>0
        ? '<div class="mt-3"><div class="config-section-title"><i class="bi bi-pie-chart-fill me-1"></i>Composição dos Ativos</div>'+
          '<table class="table table-borderless mb-0" style="background:transparent;"><thead><tr>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Ativo</th>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Moeda</th>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Alocação</th>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;text-align:right;">Margem Rebal.</th>'+
          '</tr></thead><tbody>'+assetsRows+'</tbody></table></div>'
        : "";

    const applyUrl       = pUrls.apply || "#";
    const createSnapUrl  = pUrls.create_from_snap || "#";
    const pDisplayName   = pUrls.name ? pUrls.name : "este portfólio";

    const applyBtn =
        '<div class="mt-4 pt-3 border-top">'+
        '<div class="d-flex align-items-center gap-2 mb-3">'+
        '<i class="bi bi-lightbulb-fill text-warning fs-5"></i>'+
        '<span class="fw-bold child-config-title" style="font-size:.88rem;">O que deseja fazer com esta configuração?</span>'+
        '</div>'+
        '<div class="row g-3">'+
        /* ── Card: Aplicar ao portfólio atual ── */
        '<div class="col-12 col-md-6">'+
        '<div class="action-card action-card-warning h-100">'+
        '<div class="action-card-icon"><i class="bi bi-arrow-counterclockwise"></i></div>'+
        '<div class="action-card-body">'+
        '<div class="action-card-title">Aplicar ao Portfólio Atual</div>'+
        '<div class="action-card-desc">Substitui as configurações de <strong>'+pDisplayName+'</strong> pelos parâmetros desta simulação. Ação reversível via edição.</div>'+
        '</div>'+
        '<form method="POST" action="'+applyUrl+'" onsubmit="return confirm(\'Atenção: isso irá substituir as configurações atuais do portfólio.\\n\\nCapital, período, aportes, rebalanceamento e ativos serão alterados.\\n\\nDeseja continuar?\')">'+
        '<input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'">'+
        '<input type="hidden" name="simulation_id" value="'+simId+'">'+
        '<button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold shadow-sm no-spinner w-100 mt-2">'+
        '<i class="bi bi-arrow-counterclockwise me-2"></i>Aplicar ao Portfólio'+
        '</button></form>'+
        '</div></div>'+
        /* ── Card: Criar novo portfólio ── */
        '<div class="col-12 col-md-6">'+
        '<div class="action-card action-card-success h-100">'+
        '<div class="action-card-icon"><i class="bi bi-plus-circle"></i></div>'+
        '<div class="action-card-body">'+
        '<div class="action-card-title">Criar Novo Portfólio</div>'+
        '<div class="action-card-desc">Gera um portfólio novo com exatamente esta configuração, sem alterar nenhum portfólio existente.</div>'+
        '</div>'+
        '<button type="button" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm w-100 mt-2"'+
        ' data-sim-id="'+simId+'" data-create-url="'+createSnapUrl+'" data-bs-toggle="modal" data-bs-target="#createFromSnapshotModal" onclick="prepareCreateFromSnapshot(this)">'+
        '<i class="bi bi-plus-lg me-2"></i>Criar Novo Portfólio'+
        '</button>'+
        '</div></div>'+
        '</div>'+ /* /row */
        '</div>'; /* /border-top */

    return '<div class="child-config p-3 m-2">'+
        summaryHtml+
        '<div class="d-flex align-items-center gap-2 mb-3">'+
        '<i class="bi bi-clipboard-data text-primary fs-5"></i>'+
        '<span class="fw-bold child-config-title" style="font-size:.9rem;">Configuração usada nesta simulação</span>'+
        '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1" style="font-size:.68rem;">ID #'+simId+'</span>'+
        '</div>'+
        '<div class="config-section-title"><i class="bi bi-gear me-1"></i>Parâmetros Gerais</div>'+
        '<div class="d-flex flex-wrap gap-2">'+genPills.join("")+'</div>'+
        depositHtml + taxHtml + assetsHtml + applyBtn +
        '</div>';
}

const COMPARE_URL_BASE = "{$compareUrlBase}";

$(document).ready(function () {
    // Ativar tooltips Bootstrap nos ícones (i) dos cabeçalhos
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el, { html: false });
    });

    // Column indexes: now there's an extra checkbox col at index 0
    // cols: 0=checkbox, 1=expand, 2=#id, [3=portfolio?], 3/4=simDate, 4/5=createdAt, ...
    const colOffset = SHOW_PORTFOLIO_COL ? 1 : 0;
    // text-end columns (value/return/etc.) — shifted by 1 due to new checkbox col
    const textEndCols = SHOW_PORTFOLIO_COL
        ? [6,7,8,9,10,11,12,13]
        : [5,6,7,8,9,10,11,12];

    const table = $("#allHistoryTable").DataTable({
        order: [[4 + colOffset, "desc"]],
        pageLength: 25,
        autoWidth: false,
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 1] },
            { className: "text-end", targets: textEndCols }
        ],
        language: {
            sProcessing:"Processando...", sLengthMenu:"Mostrar _MENU_ registros",
            sZeroRecords:"Nenhuma simulação encontrada", sEmptyTable:"Nenhum dado disponível",
            sInfo:"Mostrando _START_ a _END_ de _TOTAL_ registros",
            sInfoEmpty:"Mostrando 0 a 0 de 0 registros",
            sInfoFiltered:"(filtrado de _MAX_ no total)",
            sSearch:"Pesquisar:", sSearchPlaceholder:"Buscar simulação ou portfólio...",
            oPaginate:{
                sFirst:'<i class="bi bi-chevron-bar-left"></i>',
                sPrevious:'<i class="bi bi-chevron-left"></i>',
                sNext:'<i class="bi bi-chevron-right"></i>',
                sLast:'<i class="bi bi-chevron-bar-right"></i>'
            }
        },
        dom:"<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-sm-end'f>><'row'<'col-12'tr>><'row mt-3 align-items-center'<'col-sm-5 text-muted small'i><'col-sm-7'p>>"
    });

    // ── Expand button ──
    $("#allHistoryTable tbody").on("click", "button.btn-expand", function (e) {
        e.stopPropagation();
        const btn        = $(this);
        const tr         = btn.closest("tr");
        const simId      = tr.attr("data-sim-id");
        const portfolioId= tr.attr("data-portfolio-id");
        const icon       = btn.find("i");
        const row        = table.row(tr[0]);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass("dt-hasChild");
            icon.removeClass("bi-chevron-up").addClass("bi-chevron-down");
            btn.removeClass("btn-primary text-white").addClass("btn-outline-secondary");
        } else {
            row.child(buildChildRow(simId, portfolioId)).show();
            tr.addClass("dt-hasChild");
            icon.removeClass("bi-chevron-down").addClass("bi-chevron-up");
            btn.removeClass("btn-outline-secondary").addClass("btn-primary text-white");
        }
    });

    $("#allHistoryTable tbody").on("click", "tr", function (e) {
        if ($(this).hasClass("child")) return;
        if ($(e.target).closest("button, a, input, select, .form-check").length) return;
        $(this).find(".btn-expand").trigger("click");
    });

    // ── Compare selection ──
    const MAX_SELECT = 5;
    let selectedSims = {}; // {id: label}

    function updateCompareBar() {
        const count = Object.keys(selectedSims).length;
        const bar   = $("#compareBar");
        const chips = $("#compareChips");
        const btn   = $("#compareBtn");

        if (count === 0) {
            bar.addClass("d-none");
            return;
        }
        bar.removeClass("d-none");
        $("#compareCount").text(count + (count === 1 ? " selecionada" : " selecionadas") + " (mín. 2 para comparar)");

        chips.empty();
        Object.entries(selectedSims).forEach(([id, label]) => {
            chips.append(
                '<span class="compare-chip">' +
                '<i class="bi bi-bar-chart-line" style="font-size:.7rem;opacity:.7;"></i>' +
                label +
                '<button class="chip-remove" data-id="' + id + '" title="Remover">' +
                '<i class="bi bi-x-lg" style="font-size:.65rem;"></i></button></span>'
            );
        });

        if (count >= 2) {
            const ids = Object.keys(selectedSims).map(function(id){ return 'ids[]=' + id; }).join('&');
            btn.attr("href", COMPARE_URL_BASE + '&' + ids).removeClass("disabled");
        } else {
            btn.attr("href", "#").addClass("disabled");
        }
    }

    // Checkbox change
    $("#allHistoryTable tbody").on("change", ".sim-checkbox", function () {
        const cb    = $(this);
        const id    = cb.val();
        const label = cb.data("label");
        const tr    = cb.closest("tr");

        if (cb.is(":checked")) {
            if (Object.keys(selectedSims).length >= MAX_SELECT) {
                cb.prop("checked", false);
                // Flash the bar
                $("#compareBar").addClass("shake");
                setTimeout(() => $("#compareBar").removeClass("shake"), 500);
                return;
            }
            selectedSims[id] = label;
            tr.addClass("sim-selected");
        } else {
            delete selectedSims[id];
            tr.removeClass("sim-selected");
        }
        updateCompareBar();
    });

    // Chip remove
    $("#compareChips").on("click", ".chip-remove", function () {
        const id = $(this).data("id");
        delete selectedSims[id];
        $(`.sim-checkbox[value="` + id + `"]`).prop("checked", false).closest("tr").removeClass("sim-selected");
        updateCompareBar();
    });

    // Clear all
    $("#clearCompare").on("click", function () {
        selectedSims = {};
        $(".sim-checkbox").prop("checked", false);
        $("tr.sim-selected").removeClass("sim-selected");
        $("#selectAllSims").prop("checked", false);
        updateCompareBar();
    });

    // Select all (only visible page rows, up to MAX)
    $("#selectAllSims").on("change", function () {
        const checked = $(this).is(":checked");
        if (!checked) {
            // Uncheck all
            $(".sim-checkbox:checked").each(function () {
                $(this).prop("checked", false).trigger("change");
            });
        } else {
            // Check up to MAX visible rows
            table.rows({ page: 'current' }).nodes().each(function () {
                const cb = $(this).find(".sim-checkbox");
                if (!cb.is(":checked") && Object.keys(selectedSims).length < MAX_SELECT) {
                    cb.prop("checked", true).trigger("change");
                }
            });
        }
    });

    // ── Advanced Metric Filters ──────────────────────────────────────────────

    // Toggle panel
    const advToggle = document.getElementById("advFilterToggle");
    const advBody   = document.getElementById("advFilterBody");
    const advChevron = advToggle.querySelector(".adv-filter-chevron");
    const bsCollapse = new bootstrap.Collapse(advBody, { toggle: false });

    advToggle.addEventListener("click", function () {
        bsCollapse.toggle();
        const expanded = advToggle.getAttribute("aria-expanded") === "true";
        advToggle.setAttribute("aria-expanded", String(!expanded));
    });
    advBody.addEventListener("shown.bs.collapse",  () => { advToggle.setAttribute("aria-expanded","true"); });
    advBody.addEventListener("hidden.bs.collapse", () => { advToggle.setAttribute("aria-expanded","false"); });

    // Read filter values from inputs
    function getAdvFilters() {
        const filters = {};
        document.querySelectorAll(".adv-filter-input").forEach(function(inp) {
            const field = inp.dataset.field;
            const bound = inp.dataset.bound;
            const val   = inp.value.trim();
            if (!filters[field]) filters[field] = {};
            if (val !== "") filters[field][bound] = parseFloat(val);
        });
        return filters;
    }

    // Count active (filled) filter inputs
    function countActiveFilters(filters) {
        let n = 0;
        Object.values(filters).forEach(function(bounds) {
            if (bounds.min !== undefined) n++;
            if (bounds.max !== undefined) n++;
        });
        return n;
    }

    // Update badge + clear button
    function updateAdvFilterUI(activeCount) {
        const badge  = document.getElementById("advFilterBadge");
        const clearBtn = document.getElementById("advFilterClear");
        if (activeCount > 0) {
            badge.textContent = activeCount;
            badge.classList.remove("d-none");
            clearBtn.classList.remove("d-none");
        } else {
            badge.classList.add("d-none");
            clearBtn.classList.add("d-none");
        }
        // Highlight groups that have values
        document.querySelectorAll(".adv-filter-group").forEach(function(grp) {
            const inputs = grp.querySelectorAll(".adv-filter-input");
            const anyFilled = Array.from(inputs).some(i => i.value.trim() !== "");
            grp.classList.toggle("has-value", anyFilled);
        });
    }

    // Get metric value for a sim row (calmar computed on-the-fly)
    function getMetricValue(simId, field) {
        const m = METRICS[simId] || {};
        if (field === "calmar") {
            const dd  = Math.abs(parseFloat(m.max_drawdown || 0));
            const str = parseFloat(m.strategy_annual_return || 0);
            return dd > 0 ? str / dd : null;
        }
        if (field === "max_drawdown")   return Math.abs(parseFloat(m.max_drawdown   || 0));
        if (field === "max_monthly_loss") return Math.abs(parseFloat(m.max_monthly_loss || 0));
        const val = m[field];
        return (val === null || val === undefined || val === "") ? null : parseFloat(val);
    }

    // Custom DataTable search function
    let advFilters = {};
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
        if (settings.nTable.id !== "allHistoryTable") return true;
        if (Object.keys(advFilters).length === 0) return true;

        // Get sim ID from the actual DOM row
        const tr    = table.row(dataIndex).node();
        if (!tr) return true;
        const simId = $(tr).attr("data-sim-id");
        if (!simId) return true;

        for (const [field, bounds] of Object.entries(advFilters)) {
            const val = getMetricValue(simId, field);
            if (val === null) continue; // skip null values (don't exclude)
            if (bounds.min !== undefined && val < bounds.min) return false;
            if (bounds.max !== undefined && val > bounds.max) return false;
        }
        return true;
    });

    function applyAdvFilters() {
        advFilters = getAdvFilters();
        // Remove empty filter groups
        Object.keys(advFilters).forEach(k => {
            if (Object.keys(advFilters[k]).length === 0) delete advFilters[k];
        });
        const activeCount = countActiveFilters(advFilters);
        updateAdvFilterUI(activeCount);

        table.draw();

        // Update result count
        const resultCount = document.getElementById("advFilterResultCount");
        if (activeCount > 0) {
            const shown = table.rows({ search: 'applied' }).count();
            const total = table.rows().count();
            resultCount.textContent = shown + " de " + total + " simulações";
            resultCount.classList.remove("d-none");
        } else {
            resultCount.classList.add("d-none");
        }
    }

    // Apply on button click
    document.getElementById("advFilterApply").addEventListener("click", applyAdvFilters);

    // Also apply on Enter key in any filter input
    document.querySelectorAll(".adv-filter-input").forEach(function(inp) {
        inp.addEventListener("keydown", function(e) {
            if (e.key === "Enter") applyAdvFilters();
        });
        // Live highlight of group
        inp.addEventListener("input", function() {
            const grp = inp.closest(".adv-filter-group");
            const anyFilled = Array.from(grp.querySelectorAll(".adv-filter-input")).some(i => i.value.trim() !== "");
            grp.classList.toggle("has-value", anyFilled);
        });
    });

    // Clear filters
    document.getElementById("advFilterClear").addEventListener("click", function() {
        document.querySelectorAll(".adv-filter-input").forEach(i => { i.value = ""; });
        advFilters = {};
        updateAdvFilterUI(0);
        document.getElementById("advFilterResultCount").classList.add("d-none");
        table.draw();
    });

    // Auto-open filter panel if any filter is active on page load
    // (useful for back-navigation persistence — no server state needed here)
});

// ── Modal: Criar Novo Portfólio a partir do Snapshot ──
function prepareCreateFromSnapshot(btn) {
    const simId     = btn.getAttribute('data-sim-id');
    const createUrl = btn.getAttribute('data-create-url');
    document.getElementById('cfsSimId').value      = simId;
    document.getElementById('cfsFormAction').action = createUrl;
    // Sugestão automática de nome
    const snap = SNAPSHOTS[simId];
    const dateStr = new Date().toLocaleDateString('pt-BR', {day:'2-digit',month:'2-digit',year:'numeric'});
    const periodStr = snap && snap.portfolio && snap.portfolio.start_date
        ? snap.portfolio.start_date.substring(0,7).split('-').reverse().join('/')
        : '';
    const suggested = 'Portfólio — Simulação #' + simId + (periodStr ? ' (' + periodStr + ')' : '');
    const nameInput = document.getElementById('cfsPortfolioName');
    nameInput.value = suggested;
    setTimeout(() => { nameInput.focus(); nameInput.select(); }, 400);
}
</script>
ENDJS;

// ── Modal HTML (outside ob_get_clean output, added to $content) ──
$modalHtml = <<<HTML
<!-- Modal: Criar Novo Portfólio a partir do Snapshot -->
<div class="modal fade" id="createFromSnapshotModal" tabindex="-1" aria-labelledby="cfsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 pb-0 pt-4 px-4">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10" style="width:44px;height:44px;">
            <i class="bi bi-folder-plus text-success fs-4"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="cfsModalLabel">Criar Novo Portfólio</h5>
            <p class="text-muted mb-0" style="font-size:.78rem;">A partir da configuração desta simulação</p>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form method="POST" id="cfsFormAction">
        <div class="modal-body px-4 pt-3 pb-2">
          <input type="hidden" name="csrf_token" value="{$csrfToken}">
          <input type="hidden" name="simulation_id" id="cfsSimId" value="">
          <div class="mb-1">
            <label for="cfsPortfolioName" class="form-label fw-semibold" style="font-size:.85rem;">
              <i class="bi bi-tag me-1 text-success"></i>Nome do novo portfólio
            </label>
            <input type="text" class="form-control form-control-lg rounded-3" id="cfsPortfolioName" name="portfolio_name"
              placeholder="Ex: Estratégia Conservadora 2025" maxlength="80" required autofocus>
            <div class="form-text" style="font-size:.73rem;">Escolha um nome descritivo para identificar facilmente este portfólio.</div>
          </div>
          <div class="alert alert-success border-0 rounded-3 d-flex align-items-start gap-2 mt-3 mb-0 py-2 px-3" style="font-size:.78rem;background:rgba(25,135,84,.07);">
            <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
            <span>Será criado um portfólio com todos os parâmetros e ativos desta simulação. <strong>Nenhum portfólio existente será alterado.</strong></span>
          </div>
        </div>
        <div class="modal-footer border-0 px-4 pt-2 pb-4 gap-2">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success rounded-pill px-5 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg me-2"></i>Criar Portfólio
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
HTML;

$content = ob_get_clean() . $modalHtml;
include_once __DIR__ . '/../layouts/main.php';
?>

