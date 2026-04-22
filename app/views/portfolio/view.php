<?php
/**
 * @var array $portfolio Dados do portfólio (id, name, start_date, etc.)
 * @var array $assets Lista de ativos vinculados
 * @var array|null $latest Último resultado de simulação
 * @var array $metrics Métricas calculadas (total_return, volatility, etc.)
 * @var array $chartData Dados formatados para os gráficos JS
 */

$title = 'Resultados: ' . htmlspecialchars($portfolio['name']);
$meta_robots = 'noindex, nofollow';

$breadcrumbs = [
    ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=' . obfuscateUrl('dashboard')],
    ['label' => 'Portfólios', 'url' => '/index.php?url=' . obfuscateUrl('portfolio')],
    ['label' => htmlspecialchars($portfolio['name']), 'url' => '#'],
];

ob_start();
?>
<?php if ($portfolio['is_system_default']): ?>
    <div class="alert border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm" style="background-color: rgba(13, 110, 253, 0.05); border-left: 4px solid var(--primary) !important;">
        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; flex-shrink: 0;">
            <i class="bi bi-patch-check-fill text-white fs-5"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-0 text-dark">Estratégia Oficial do Sistema</h6>
            <p class="text-muted smaller mb-0">Este portfólio é um modelo curado para servir de benchmark e referência profissional.</p>
        </div>
        <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-2 smaller fw-bold ms-3">
        CURADORIA ATIVA
    </span>
    </div>
<?php endif; ?>

    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center gap-3">
                <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($portfolio['name']); ?></h2>
                <button class="btn btn-sm btn-outline-primary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#compositionCollapse">
                    <i class="bi bi-pie-chart-fill me-1"></i> Composição
                </button>
            </div>
            <p class="text-muted mb-0 mt-1">
                <i class="bi bi-calendar3 me-1"></i> <?php echo formatDate($portfolio['start_date']); ?>
                <?php echo $portfolio['end_date'] ? ' até ' . formatDate($portfolio['end_date']) : ' (Até hoje)'; ?>
                | <i class="bi bi-currency-exchange ms-2 me-1"></i> <?php echo $portfolio['output_currency']; ?>
                <?php if ($portfolio['simulation_type'] != 'standard'): ?>
                    | <i class="bi bi-calculator ms-2 me-1"></i> Simulação:
                    <?php
                    $simTooltips = [
                        'monthly_deposit'    => 'Com Aportes Periódicos: a cada período configurado, um valor fixo é investido no portfólio e distribuído entre os ativos conforme as alocações-alvo.',
                        'strategic_deposit'  => 'Aporte Estratégico: um aporte extra é realizado apenas quando o portfólio cai acima de um percentual configurado em um único mês — comprando na baixa.',
                        'smart_deposit'      => 'Aporte Direcionado ao Alvo: o aporte periódico é direcionado ao ativo que está mais abaixo de sua alocação-alvo, maximizando a eficiência do rebalanceamento.',
                        'selic_cash_deposit' => 'Aporte em Caixa (SELIC): os aportes ficam rendendo a taxa SELIC em caixa e são investidos no portfólio somente no momento do próximo rebalanceamento.',
                    ];
                    $currentSimTooltip = $simTooltips[$portfolio['simulation_type']] ?? '';
                    ?>
                    <?php if ($portfolio['simulation_type'] == 'monthly_deposit'): ?>
                        <span class="badge bg-info bg-soft">Com Aportes Periódicos</span>
                    <?php elseif ($portfolio['simulation_type'] == 'strategic_deposit'): ?>
                        <span class="badge bg-warning bg-soft">Aporte Estratégico</span>
                    <?php elseif ($portfolio['simulation_type'] == 'smart_deposit'): ?>
                        <span class="badge bg-success bg-soft">Aporte Direcionado ao Alvo</span>
                        <?php if (($portfolio['rebalance_type'] ?? 'full') == 'buy_only'): ?>
                            <span class="badge bg-soft-success text-success rounded-pill ms-1 small border border-success">Apenas Compras</span>
                        <?php endif; ?>
                    <?php elseif ($portfolio['simulation_type'] == 'selic_cash_deposit'): ?>
                        <span class="badge bg-secondary bg-soft">Aporte em Caixa (SELIC)</span>
                        <?php if (($portfolio['rebalance_type'] ?? 'full') == 'buy_only'): ?>
                            <span class="badge bg-soft-secondary text-secondary rounded-pill ms-1 small border border-secondary">Apenas Compras</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($currentSimTooltip): ?>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-muted align-middle info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="<?= htmlspecialchars($currentSimTooltip) ?>">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end d-flex align-items-center justify-content-end gap-2 flex-wrap">
            <!-- Ações principais -->
            <div class="btn-group shadow-sm" role="group" aria-label="Ações da simulação">
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/history/' . $portfolio['id']) ?>" class="btn btn-outline-secondary" title="Histórico de Simulações">
                    <i class="bi bi-clock-history me-1"></i> Histórico
                </a>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/simulation-details/' . $portfolio['id']) ?>" class="btn btn-outline-info" title="Detalhes da Simulação">
                    <i class="bi bi-list-check me-1"></i> Detalhes
                </a>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-play-fill me-1"></i> Simular
                </a>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/clone/' . $portfolio['id']) ?>" class="btn btn-outline-secondary" title="Clonar estratégia">
                    <i class="bi bi-files me-1"></i> Clonar
                </a>
            </div>

            <?php if (!$portfolio['is_system_default'] || Auth::isAdmin()): ?>
            <!-- Ações de gestão -->
            <div class="btn-group shadow-sm" role="group" aria-label="Gestão da estratégia">
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/edit/' . $portfolio['id']) ?>"
                   class="btn btn-outline-secondary"
                   title="Editar estratégia"
                   data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="bi bi-pencil-square me-1"></i> Editar
                </a>
                <button type="button"
                        class="btn btn-outline-danger"
                        title="Excluir estratégia"
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        onclick="if(confirm('Tem certeza que deseja excluir esta estratégia?')) { document.getElementById('delete-portfolio-form').submit(); }">
                    <i class="bi bi-trash3 me-1"></i> Excluir
                </button>
            </div>
            <form id="delete-portfolio-form" action="/index.php?url=<?= obfuscateUrl('portfolio/delete/' . $portfolio['id']) ?>" method="POST" class="d-none">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
            </form>
            <?php endif; ?>
        </div>
    </div>

<?php if (Auth::isAdmin()): ?>
    <div class="alert alert-soft-info d-flex justify-content-between align-items-center mb-4 rounded-4 border-0">
        <div class="small">
            <i class="bi bi-shield-check me-2"></i>
            <strong>Painel Administrativo:</strong>
            <?= $portfolio['is_system_default'] ? 'Este é um portfólio oficial do sistema.' : 'Deseja tornar este portfólio visível para todos?' ?>
        </div>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/toggle-system/' . $portfolio['id']) ?>"
           class="btn btn-sm <?= $portfolio['is_system_default'] ? 'btn-outline-danger' : 'btn-primary' ?> rounded-pill px-3">
            <?= $portfolio['is_system_default'] ? 'Remover do Sistema' : 'Tornar Portfólio de Sistema' ?>
        </a>
    </div>
<?php endif; ?>

<?php if ($portfolio['simulation_type'] != 'standard'): ?>
    <div class="alert alert-info border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm" style="background-color: rgba(23, 162, 184, 0.1); border-left: 4px solid #17a2b8 !important;">
        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; flex-shrink: 0;">
            <i class="bi bi-cash-stack text-white fs-5"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1 text-dark">Estratégia de Aportes Ativa</h6>
            <p class="text-muted smaller mb-0">
                <?php 
                $freqTranslations = [
                    'monthly'   => 'mês',
                    'quarterly' => 'trimestre',
                    'biannual'  => 'semestre',
                    'annual'    => 'ano'
                ];
                $freq = $freqTranslations[$portfolio['deposit_frequency']] ?? $portfolio['deposit_frequency'];
                $inflationAdj = ($portfolio['deposit_inflation_adjusted'] ?? 0) ? ' (Corrigido pelo IPCA)' : ' (Sem correção)';
                ?>
                <?php if ($portfolio['simulation_type'] == 'monthly_deposit'): ?>
                    <strong>Com Aportes Periódicos:</strong>
                    <?php echo formatCurrency($portfolio['deposit_amount'], $portfolio['deposit_currency'] ?? 'BRL'); ?>
                    a cada <?php echo $freq; ?>
                    <?php echo $inflationAdj; ?>
                    <?php if ($portfolio['deposit_currency'] != $portfolio['output_currency']): ?>
                        (convertido para <?php echo $portfolio['output_currency']; ?> no momento do aporte)
                    <?php endif; ?>
                <?php elseif ($portfolio['simulation_type'] == 'strategic_deposit'): ?>
                    <strong>Aporte Estratégico:</strong>
                    Se o portfólio cair <?php echo number_format($portfolio['strategic_threshold'], 1); ?>% em um mês,
                    será aportado <?php echo number_format($portfolio['strategic_deposit_percentage'], 1); ?>% do valor atual.
                <?php elseif ($portfolio['simulation_type'] == 'smart_deposit'): ?>
                    <strong>Aporte Direcionado ao Alvo:</strong>
                    <?php echo formatCurrency($portfolio['deposit_amount'], $portfolio['deposit_currency'] ?? 'BRL'); ?>
                    a cada <?php echo $freq; ?>
                    <?php echo $inflationAdj; ?>
                    — direcionado ao ativo mais abaixo do percentual-alvo.
                    Sobras acumuladas em Caixa SELIC até o próximo rebalanceamento.
                    <?php if ($portfolio['use_cash_assets_for_rebalance']): ?>
                        <br><i class="bi bi-check2-circle text-success me-1"></i> Ativos marcados como <strong>Caixa</strong> podem ser vendidos no rebalanceamento para comprar ativos em déficit.
                    <?php endif; ?>
                <?php elseif ($portfolio['simulation_type'] == 'selic_cash_deposit'): ?>
                    <strong>Aporte em Caixa (SELIC):</strong>
                    <?php echo formatCurrency($portfolio['deposit_amount'], $portfolio['deposit_currency'] ?? 'BRL'); ?>
                    a cada <?php echo $freq; ?>
                    <?php echo $inflationAdj; ?>
                    — acumulado em Caixa SELIC e
                    investido integralmente a cada rebalanceamento.
                    <?php if ($portfolio['use_cash_assets_for_rebalance']): ?>
                        <br><i class="bi bi-check2-circle text-success me-1"></i> Ativos marcados como <strong>Caixa</strong> podem ser vendidos no rebalanceamento para comprar ativos em déficit.
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>
        <span class="badge bg-soft-info text-info rounded-pill px-3 py-2 smaller fw-bold ms-3">
        <?php
        $simTypeLabels = [
            'standard'           => 'PADRÃO',
            'monthly_deposit'    => 'COM APORTES PERIÓDICOS',
            'strategic_deposit'  => 'APORTE ESTRATÉGICO',
            'smart_deposit'      => 'APORTE DIRECIONADO AO ALVO',
            'selic_cash_deposit' => 'APORTE EM CAIXA (SELIC)',
        ];
        echo $simTypeLabels[$portfolio['simulation_type']] ?? strtoupper($portfolio['simulation_type']);
        ?>
    </span>
    </div>
<?php endif; ?>

<?php
//  AVISO: Caixa SELIC com rebalanceamento e aporte mensais 
$isSelicMonthlyConflict = (
    $portfolio['simulation_type'] === 'selic_cash_deposit' &&
    $portfolio['rebalance_frequency'] === 'monthly' &&
    (empty($portfolio['deposit_frequency']) || $portfolio['deposit_frequency'] === 'monthly')
);
?>
<?php if ($isSelicMonthlyConflict): ?>
    <div class="alert border-0 rounded-4 d-flex align-items-start p-3 mb-4 shadow-sm"
         style="background-color: rgba(255,193,7,0.10); border-left: 4px solid #ffc107 !important;">
        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
             style="width: 42px; height: 42px;">
            <i class="bi bi-lightbulb-fill text-white fs-5"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1 text-dark">Configuração pouco eficiente para esta estratégia</h6>
            <p class="text-muted smaller mb-0">
                Com <strong>aporte mensal</strong> e <strong>rebalanceamento mensal</strong>, o Caixa SELIC é
                reinvestido todo mês, sem acumular rendimentos ao longo do tempo. A estratégia
                <strong>Aporte em Caixa SELIC</strong> é mais eficiente quando o rebalanceamento é
                <strong>trimestral, semestral ou anual</strong> — assim os aportes acumulam rendimentos
                da SELIC por vários meses antes de serem investidos no portfólio.
                <br>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/edit/' . $portfolio['id']) ?>"
                   class="fw-bold text-warning-emphasis">
                    <i class="bi bi-pencil me-1"></i>Editar portfólio para ajustar a frequência de rebalanceamento
                </a>
            </p>
        </div>
        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 smaller fw-bold ms-3 flex-shrink-0">
            ATENÇÃO
        </span>
    </div>
<?php endif; ?>

    <div class="collapse mb-4" id="compositionCollapse">
        <div class="card card-body shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Composição da Carteira</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openQuickEditModal()">
                    <i class="bi bi-pencil me-1"></i> Editar Alocações
                </button>
            </div>

            <?php
            // Calcula o total para validação
            $totalPercent = 0;
            foreach ($assets as $asset) {
                $totalPercent += $asset['allocation_percentage'];
            }
            ?>

            <?php if (!empty($portfolio['description'])): ?>
                <p class="text-muted small border-bottom pb-2 mb-3">
                    <i class="bi bi-info-circle me-1"></i> <?php echo htmlspecialchars($portfolio['description']); ?>
                </p>
            <?php endif; ?>

            <div class="row row-cols-1 row-cols-md-4 g-3">
                <?php foreach ($assets as $asset): ?>
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-dark fw-bold small text-truncate" title="<?php echo htmlspecialchars($asset['name']); ?>">
                                <?php echo htmlspecialchars($asset['name']); ?>
                            </span>
                            <span class="badge bg-light text-dark border small allocation-badge"
                                  data-asset-id="<?php echo $asset['asset_id']; ?>"
                                  data-allocation="<?php echo $asset['allocation_percentage']; ?>">
                                <?php echo formatPercentage($asset['allocation_percentage']); ?>
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary"
                                 role="progressbar"
                                 style="width: <?php echo $asset['allocation_percentage']; ?>%"
                                 data-asset-id="<?php echo $asset['asset_id']; ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Status da composição -->
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Total da alocação:</small>
                        <span class="ms-2 fw-bold <?php echo $totalPercent == 100 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($totalPercent, 2); ?>%
                        </span>
                        <?php if ($totalPercent != 100): ?>
                            <span class="badge bg-danger ms-2">Ajuste necessário</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="suggestAllocation()">
                            <i class="bi bi-magic"></i> Sugerir Ajuste
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if ($latest && $portfolio['end_date'] && $latest['simulation_date'] < $portfolio['end_date']): ?>
    <div class="alert alert-soft-warning border-0 rounded-4 d-flex align-items-center p-3 mb-4 shadow-sm" style="background-color: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107 !important;">
        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; flex-shrink: 0;">
            <i class="bi bi-exclamation-triangle-fill text-white fs-5"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1 text-dark">Horizonte de Simulação Ajustado</h6>
            <p class="text-muted smaller mb-0">
                Esta simulação foi processada até **<?= date('m/Y', strtotime($latest['simulation_date'])) ?>** porque um ou mais ativos da sua carteira não possuem dados históricos disponíveis após este mês.
            </p>
        </div>
    </div>
<?php endif; ?>

    <?php if (!$latest): ?>
    <div class="alert border-0 rounded-4 d-flex align-items-start p-3 mb-4 shadow-sm"
         style="background-color: rgba(255,193,7,0.08); border-left: 4px solid #ffc107 !important;">
        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
             style="width: 42px; height: 42px;">
            <i class="bi bi-bar-chart-line-fill text-white fs-5"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1 text-dark">Nenhuma simulação disponível para este período</h6>
            <p class="text-muted smaller mb-0">
                Os gráficos e métricas aparecerão após você executar a simulação.
                Clique em <strong>Simular</strong> para processar os dados históricos do período selecionado.
                <?php
                $start = new \DateTime($portfolio['start_date']);
                $endDisplay = $portfolio['end_date'] ? new \DateTime($portfolio['end_date']) : new \DateTime();
                $periodMonths = ($start->diff($endDisplay)->y * 12) + $start->diff($endDisplay)->m;
                if ($periodMonths < 12): ?>
                    <br><small class="text-warning fw-bold">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Período curto detectado (<?= $periodMonths ?> meses). Certifique-se de que os ativos possuem
                        dados históricos disponíveis a partir de <strong><?= date('m/Y', strtotime($portfolio['start_date'])) ?></strong>.
                    </small>
                <?php endif; ?>
            </p>
        </div>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>"
           class="btn btn-warning btn-sm rounded-pill px-3 ms-3 flex-shrink-0 align-self-center">
            <i class="bi bi-play-fill me-1"></i> Simular Agora
        </a>
    </div>
    <?php endif; ?>

    <?php
    //  Hero: 3 métricas em destaque 
    $heroHasDeposits = isset($metrics['total_deposits']) && $metrics['total_deposits'] > 0;
    ?>
    <div class="row g-3 mb-4">
        <!-- Patrimônio Inicial -->
        <div class="col-md-3">
            <div class="card border-0 rounded-4 shadow h-100 overflow-hidden position-relative"
                 style="background: var(--hero-initial-bg);">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center opacity-05" style="pointer-events:none;">
                    <i class="bi bi-wallet2" style="font-size:8rem;color:var(--hero-initial-icon);opacity:.08;"></i>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge rounded-pill px-3 py-2 fw-semibold text-uppercase small"
                              style="background:var(--hero-initial-icon);color:#fff;letter-spacing:.05em;">
                            <i class="bi bi-wallet2 me-1"></i> Patrimônio Inicial
                        </span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                title="Capital investido no <strong>início da simulação</strong>, antes de qualquer rendimento ou aporte. É o ponto de partida de todo o cálculo.">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                        </button>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold lh-1 mb-1" style="color: var(--hero-initial-icon);">
                            <?php echo formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']); ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-calendar3 me-1"></i> Início em <?php echo formatDate($portfolio['start_date']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total de Aportes -->
        <div class="col-md-3">
            <?php if ($heroHasDeposits): ?>
            <div class="card border-0 rounded-4 shadow h-100 overflow-hidden position-relative"
                 style="background: var(--hero-deposits-bg);">
                <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge rounded-pill px-3 py-2 fw-semibold text-uppercase small"
                              style="background:var(--hero-deposits-icon);color:#fff;letter-spacing:.05em;">
                            <i class="bi bi-cash-stack me-1"></i> Total de Aportes
                        </span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                title="Soma de <strong>todos os aportes realizados</strong> durante o período simulado, além do capital inicial.<br><br>Total Investido = Capital Inicial + Aportes.">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                        </button>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold lh-1 mb-1" style="color: var(--hero-deposits-icon);">
                            <?php echo formatCurrency($metrics['total_deposits'], $portfolio['output_currency']); ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-stack me-1"></i> Total investido:
                            <strong><?php echo formatCurrency($metrics['total_invested'], $portfolio['output_currency']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0 rounded-4 shadow h-100 overflow-hidden position-relative"
                 style="background: var(--hero-deposits-empty-bg);">
                <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge rounded-pill px-3 py-2 fw-semibold text-uppercase small bg-secondary">
                            <i class="bi bi-cash-stack me-1"></i> Total de Aportes
                        </span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                title="Nenhum aporte adicional foi configurado para esta simulação.<br>Apenas o <strong>capital inicial</strong> foi considerado.">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                        </button>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-muted lh-1 mb-1">—</div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-dash-circle me-1"></i> Sem aportes periódicos
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Total de Impostos -->
        <div class="col-md-3">
            <?php 
            $heroTotalTax = $metrics['total_tax_paid'] ?? 0;
            $hasTax = $heroTotalTax > 0;
            $isPro = Auth::isPro();
            ?>
            <div id="tax-paid-card" class="card border-0 rounded-4 shadow h-100 overflow-hidden position-relative"
                 style="background: <?= $isPro && $hasTax ? 'var(--hero-tax-bg)' : 'var(--hero-empty-bg)' ?>;">
                <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span id="tax-paid-badge" class="badge rounded-pill px-3 py-2 fw-semibold text-uppercase small <?= $isPro && $hasTax ? 'bg-danger' : 'bg-secondary' ?>">
                            <i class="bi bi-calculator me-1"></i> Total de Impostos
                        </span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                title="Soma de todos os impostos estimados pagos ao longo da simulação.<br><br>Leva em conta a <strong>compensação de prejuízos acumulados</strong> por grupo de ativos.">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                        </button>
                    </div>
                    <?php if ($isPro): ?>
                    <div>
                        <div id="tax-paid-value" class="fs-2 fw-bold lh-1 mb-1 <?= $hasTax ? 'text-danger' : 'text-muted' ?>">
                            <?= $hasTax ? formatCurrency($heroTotalTax, $portfolio['output_currency']) : '—' ?>
                        </div>
                        <div id="tax-paid-label" class="text-muted small mt-2">
                            <i class="bi bi-receipt me-1"></i> <?= $hasTax ? 'Impostos sobre lucro' : 'Sem impostos no período' ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-2">
                        <div class="fs-4 fw-bold text-muted mb-2">Bloqueado</div>
                        <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem;">
                            <i class="bi bi-gem me-1"></i> ASSINE O PRO
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Patrimônio Final -->
        <div class="col-md-3">
            <?php
            $heroFinalValue = $metrics['final_value'] ?? $metrics['total_value'] ?? $portfolio['initial_capital'];
            $heroTotalReturn = $metrics['total_return'] ?? 0;
            $heroPositive = $heroTotalReturn >= 0;
            ?>
            <div id="final-value-card" class="card border-0 rounded-4 shadow h-100 overflow-hidden position-relative"
                 style="background: var(<?= $heroPositive ? '--hero-final-pos-bg' : '--hero-final-neg-bg' ?>);">
                <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span id="final-value-badge" class="badge rounded-pill px-3 py-2 fw-semibold text-uppercase small <?= $heroPositive ? 'bg-primary' : 'bg-danger' ?>">
                            <i class="bi bi-graph-up-arrow me-1"></i> Patrimônio Final
                        </span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                title="Valor total do portfólio ao <strong>final do período simulado</strong>, incluindo capital inicial, aportes e todos os rendimentos obtidos.">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                        </button>
                    </div>
                    <?php if ($latest): ?>
                    <div>
                        <div id="final-value-amount" class="fs-2 fw-bold lh-1 mb-1 <?= $heroPositive ? 'text-primary' : 'text-danger' ?>">
                            <?php echo formatCurrency($heroFinalValue, $portfolio['output_currency']); ?>
                        </div>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <span id="total-return-badge" class="badge rounded-pill <?= $heroPositive ? 'bg-success' : 'bg-danger' ?> fs-6 px-3 py-2">
                                <?= ($heroPositive ? '+' : '') . number_format($heroTotalReturn, 2, ',', '.') ?>%
                            </span>
                            <span class="text-muted small">retorno total</span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div>
                        <div class="fs-2 fw-bold text-muted lh-1 mb-1">—</div>
                        <div class="text-muted small mt-2"><i class="bi bi-play-circle me-1"></i> Execute a simulação</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    // --- Categorização das Métricas (Analista Sênior) ---
    $hasDeposits = isset($metrics['total_deposits']) && $metrics['total_deposits'] > 0;
    $isShort = $metrics['is_short_period'] ?? false;

    // Calcula inflação média anual (IPCA) do período
    $startDateObj = new DateTime($portfolio['start_date']);
    $endDateObj   = $portfolio['end_date'] ? new DateTime($portfolio['end_date']) : new DateTime();
    $periodMonths = max(1, $startDateObj->diff($endDateObj)->m + ($startDateObj->diff($endDateObj)->y * 12));
    $totalIpcaFactor = 1 + ($metrics['total_inflation'] ?? 0) / 100;
    $avgAnnualInflation = ($periodMonths >= 12)
        ? (pow($totalIpcaFactor, 12 / $periodMonths) - 1) * 100
        : ($metrics['total_inflation'] ?? 0);

    $metricGroups = [
        [
            'title' => 'Performance do Patrimônio (Com Aportes)',
            'icon'  => 'bi-bank',
            'color' => '#0d6efd',
            'description' => 'Estes números refletem o que você veria no saldo da sua corretora. Incluem tanto o rendimento quanto o dinheiro que você tirou do bolso.',
            'metrics' => [
                [
                    'label' => 'Retorno Total do Patrimônio',
                    'val' => formatPercentage($metrics['total_return'], 2),
                    'class' => 'border-primary',
                    'text' => $metrics['total_return'] >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'Crescimento total do saldo da conta, do início ao fim, somando rendimentos e aportes.',
                    'sub' => 'Saldo Final vs Capital Inicial'
                ],
                [
                    'label' => $isShort ? 'Rentabilidade no Período' : 'Rentabilidade Anual (Média)',
                    'val'   => formatPercentage($metrics['annual_return'], 2),
                    'class' => 'border-primary',
                    'text'  => 'text-primary',
                    'tooltip' => 'A taxa média de crescimento do seu patrimônio por ano, considerando o efeito dos aportes.',
                    'sub' => $isShort ? 'Período inferior a 1 ano' : 'CAGR do Patrimônio'
                ],
                [
                    'label' => 'ROI (Retorno sobre Investimento)',
                    'val' => formatPercentage($metrics['roi'] ?? 0, 2),
                    'class' => 'border-success',
                    'visible' => $hasDeposits,
                    'text' => ($metrics['roi'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'O quanto o seu capital rendeu de fato sobre <b>todo o dinheiro investido</b> (Início + Aportes).',
                    'footer' => 'Investido: ' . formatCurrency($metrics['total_invested'], $portfolio['output_currency'])
                ],
                [
                    'label' => 'Lucro Nominal Total',
                    'val' => formatCurrency($metrics['interest_earned'] ?? ($metrics['final_value'] - $metrics['total_invested']), $portfolio['output_currency']),
                    'class' => 'border-success',
                    'text' => ($metrics['interest_earned'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'O valor em dinheiro que o mercado te deu, subtraindo tudo o que você investiu.',
                    'sub' => 'Ganhos de capital + Proventos'
                ]
            ]
        ],
        [
            'title' => 'Desempenho da Carteira (Sem Aportes)',
            'icon'  => 'bi-gear-wide-connected',
            'color' => '#6610f2',
            'description' => 'Isolamos o efeito do seu bolso para medir apenas a qualidade da alocação de ativos. É a métrica de performance do gestor.',
            'metrics' => [
                [
                    'label' => 'Performance da Carteira',
                    'val' => formatPercentage($metrics['strategy_return'] ?? 0, 2),
                    'class' => 'border-indigo',
                    'text' => ($metrics['strategy_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'Quanto a carteira rendeu <b>por conta própria</b>. É como se você tivesse investido R$ 100 no início e nunca mais mexido.',
                    'sub' => 'Retorno Teórico da Carteira'
                ],
                [
                    'label' => 'Performance Anualizada',
                    'val' => formatPercentage($metrics['strategy_annual_return'] ?? 0, 2),
                    'class' => 'border-indigo',
                    'text' => ($metrics['strategy_annual_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'Rentabilidade anualizada da carteira (sem aportes), permitindo comparar com benchmarks como IBOV ou S&P500.',
                    'sub' => 'CAGR da Performance'
                ],
                [
                    'label' => 'Lucro da Carteira (em valor)',
                    'val' => formatCurrency(($portfolio['initial_capital'] * ($metrics['strategy_return'] ?? 0) / 100), $portfolio['output_currency']),
                    'class' => 'border-indigo',
                    'visible' => $hasDeposits,
                    'text' => ($metrics['strategy_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'Quanto o capital inicial rendeu sozinho, desconsiderando os lucros vindos dos aportes posteriores.',
                    'footer' => 'Base: ' . formatCurrency($portfolio['initial_capital'], $portfolio['output_currency'])
                ],
                [
                    'label' => 'BETA DA CARTEIRA',
                    'val' => '<span id="betaValue">--</span>',
                    'class' => 'border-dark',
                    'text'  => 'text-main',
                    'tooltip' => 'Mede o risco em relação ao mercado. <b>Beta > 1</b> indica que a carteira oscila mais que o benchmark selecionado.',
                    'footer_id' => 'betaBenchmarkName',
                    'footer' => 'Selecione um benchmark'
                ]
            ]
        ],
        [
            'title' => 'Poder de Compra e Inflação (Ganhos REAIS)',
            'icon'  => 'bi-shield-lock',
            'color' => '#fd7e14',
            'description' => 'O termo "Real" aqui refere-se ao ganho acima do IPCA. É o que realmente te deixa mais rico após descontar o aumento de preços.',
            'metrics' => [
                [
                    'label' => 'Ganho Real Acima da Inflação',
                    'val' => formatPercentage($metrics['real_roi'] ?? 0, 2),
                    'class' => 'border-orange',
                    'text' => ($metrics['real_roi'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'O seu retorno total já descontando a inflação (IPCA) acumulada no período.',
                    'sub' => 'Poder de Compra Adquirido'
                ],
                [
                    'label' => 'Ganho Real Anualizado',
                    'val' => formatPercentage($metrics['real_roi_annual'] ?? 0, 2),
                    'class' => 'border-orange',
                    'text' => ($metrics['real_roi_annual'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'Taxa de crescimento real ao ano. Se este número for positivo, seu patrimônio está vencendo o custo de vida.',
                    'sub' => 'Acima do IPCA'
                ],
                [
                    'label' => 'Inflação Acumulada (IPCA)',
                    'val' => formatPercentage($metrics['total_inflation'] ?? 0, 2),
                    'class' => 'border-secondary',
                    'text' => 'text-muted',
                    'tooltip' => 'A variação do IPCA no período simulado. Representa o quanto o seu dinheiro perdeu de valor para os preços.',
                    'sub' => 'Custo de vida no período'
                ],
                [
                    'label' => 'IPCA Médio ao Ano',
                    'val' => formatPercentage($avgAnnualInflation, 2),
                    'class' => 'border-secondary',
                    'text' => 'text-muted',
                    'tooltip' => 'Inflação média anual (IPCA) ao longo do período simulado. É o "custo" anual que o dinheiro paga para manter o poder de compra.',
                    'sub' => 'Inflação anualizada do período'
                ]
            ]
        ],
        [
            'title' => 'Risco e Volatilidade',
            'icon'  => 'bi-graph-down',
            'color' => '#dc3545',
            'description' => 'A jornada importa tanto quanto o destino. Aqui medimos o quão "turbulenta" foi a simulação.',
            'metrics' => [
                [
                    'label' => 'Volatilidade Anual',
                    'val' => formatPercentage($metrics['volatility'], 2),
                    'class' => 'border-warning',
                    'text' => 'text-main',
                    'tooltip' => 'Desvio padrão dos retornos. Quanto maior, mais a carteira "balança". Carteiras conservadoras buscam volatilidade baixa.',
                    'sub' => 'Risco da jornada'
                ],
                [
                    'label' => 'Índice de Sharpe',
                    'val' => number_format($metrics['sharpe_ratio'], 2),
                    'class' => 'border-info',
                    'text' => 'text-main',
                    'tooltip' => 'Relação retorno/risco. Acima de 1 é considerado bom. Mostra se o risco que você correu valeu a pena em retorno.',
                    'sub' => 'Eficiência de Risco'
                ],
                [
                    'label' => 'Maior Alta Mensal',
                    'val' => formatPercentage($metrics['max_monthly_gain'] ?? 0, 2),
                    'class' => 'border-success',
                    'text' => 'text-success',
                    'tooltip' => 'O melhor mês da história desta carteira. Reflete o potencial de "tiro" positivo.',
                    'sub' => 'Recorde positivo mensal'
                ],
                [
                    'label' => 'Maior Queda Mensal',
                    'val' => formatPercentage($metrics['max_monthly_loss'] ?? 0, 2),
                    'class' => 'border-danger',
                    'text' => 'text-danger',
                    'tooltip' => 'O pior mês enfrentado. Importante para testar seu estômago como investidor.',
                    'sub' => 'Drawdown máximo mensal'
                ]
            ]
        ]
    ];

    foreach ($metricGroups as $group): 
        $isGroupLocked = ($group['title'] === 'Poder de Compra e Inflação (Ganhos REAIS)' && !Auth::isPro());
    ?>
        <div class="col-12 mt-4 mb-2">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 32px; height: 32px; background-color: <?= $group['color'] ?>20; color: <?= $group['color'] ?>;">
                    <i class="bi <?= $group['icon'] ?> fs-5"></i>
                </div>
                <h5 class="fw-bold mb-0" style="color: #333;"><?= $group['title'] ?></h5>
                <?php if ($isGroupLocked): ?>
                    <span class="badge bg-soft-primary text-primary rounded-pill px-2 py-1 ms-2" style="font-size: 0.7rem;">
                        <i class="bi bi-lock-fill me-1"></i> APENAS PRO
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-3 ms-5"><?= $group['description'] ?></p>
        </div>

        <div class="row g-3 mb-2 ms-4 position-relative">
            <?php if ($isGroupLocked): ?>
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center rounded-4" 
                     style="background: rgba(255,255,255,0.7); backdrop-filter: blur(4px); z-index: 10;">
                    <div class="text-center p-4 bg-white shadow-lg rounded-4 border">
                        <div class="rounded-circle bg-soft-primary text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-gem fs-2"></i>
                        </div>
                        <h6 class="fw-bold mb-1">Análise de Ganhos Reais</h6>
                        <p class="text-muted small mb-3">Veja o quanto você realmente enriqueceu acima da inflação.</p>
                        <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-rocket-takeoff me-1"></i> Desbloquear Plano PRO
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($group['metrics'] as $m): 
                if (isset($m['visible']) && !$m['visible']) continue;
            ?>
                <div class="col-md-3">
                    <div class="card metric-card shadow-sm h-100 border-start border-4 <?= $m['class'] ?> border-top-0 border-end-0 border-bottom-0">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="text-muted smaller text-uppercase fw-bold mb-0 me-1" style="font-size: 0.7rem; letter-spacing: 0.02em;">
                                    <?= $m['label'] ?>
                                </h6>
                                <?php if (!empty($m['tooltip'])): ?>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted flex-shrink-0 info-tooltip"
                                        data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                        title="<?= htmlspecialchars($m['tooltip']) ?>">
                                    <i class="bi bi-info-circle-fill" style="font-size: 0.75rem;"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <h4 id="<?= ($m['label'] == 'ROI (Retorno sobre Investimento)') ? 'roi-value' : '' ?>" class="<?= $m['text'] ?> fw-bold mb-1">
                                <?= $m['val'] ?>
                            </h4>
                            <?php if (isset($m['sub'])): ?>
                                <div class="smaller text-muted" style="font-size: 0.75rem; opacity: 0.8;">
                                    <?= $m['sub'] ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($m['footer'])): ?>
                                <div id="<?= $m['footer_id'] ?? '' ?>" class="mt-2 pt-2 border-top smaller text-muted" style="font-size: 0.7rem;">
                                    <?= $m['footer'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        Evolução do Patrimônio
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Mostra o <strong>valor total do portfólio</strong> a cada mês simulado. Inclui o efeito dos aportes, rebalanceamentos e variação de preços dos ativos.<br><br>Use o seletor <em>Comparar com</em> para adicionar um benchmark.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($hasDeposits): ?>
                            <div class="d-flex align-items-center gap-2 border-end pe-3">
                            <span class="text-success">
                                <i class="bi bi-cash-stack me-1"></i>
                                Total Aportado: <?php echo formatCurrency($metrics['total_deposits'], $portfolio['output_currency']); ?>
                            </span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex align-items-center gap-2 border-start ps-3">
                            <label class="smaller text-muted fw-bold">Comparar com:</label>
                            <select class="form-select form-select-sm border-0 bg-light shadow-none transition" id="benchmarkSelector" style="width: 250px;">
                                <option value="">Nenhum</option>
                                <?php
                                $assetModel = new Asset();
                                $allAssets = $assetModel->getAllWithDetails();

                                $pStart = $portfolio['start_date'];
                                $pEnd = $latest ? $latest['simulation_date'] : ($portfolio['end_date'] ?? date('Y-m-d'));

                                foreach ($allAssets as $b):
                                    $isValid = ($b['min_date'] <= $pStart && (empty($b['max_date']) || $b['max_date'] >= $pEnd));
                                    $isSP500 = ($b['code'] === 'SP500' || $b['name'] === 'S&P 500');
                                    ?>
                                    <option value="<?= $b['id'] ?>" <?= !$isValid ? 'disabled' : '' ?> <?= $isSP500 ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['name']) ?>
                                        <?= !$isValid ? ' (Histórico insuficiente)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body"><div class="chart-container"><canvas id="valueChart"></canvas></div></div>
            </div>
        </div>
    </div>
<?php
// Extrair os últimos valores do gráfico de performance da estratégia
$strategyChart = $chartData['strategy_performance_chart'] ?? null;
$lastStrategyReturn = 0;
$lastPortfolioReturn = 0;

if ($strategyChart && !empty($strategyChart['datasets'])) {
    $strategyData = $strategyChart['datasets'][0]['data'] ?? [];
    $portfolioData = $strategyChart['datasets'][1]['data'] ?? [];

    if (!empty($strategyData)) {
        $lastStrategyReturn = end($strategyData);
    }
    if (!empty($portfolioData)) {
        $lastPortfolioReturn = end($portfolioData);
    }
}
?>

    <?php if (isset($chartData['projection_chart'])): ?>
    <!-- NOVO: Gráfico de Projeção de Patrimônio Futuro -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-primary d-flex align-items-center gap-2">
                            <i class="bi bi-graph-up-arrow me-1"></i>Projeção de Patrimônio Futuro (<span id="titleProjectionYears">10</span> anos)
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                    data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                    title="<strong>Estimativa futura</strong> baseada na rentabilidade anual histórica da estratégia, com juros compostos mensais.<br><br>âš ï¸ Rentabilidade passada <strong>não garante</strong> rentabilidade futura. Use como referência de planejamento.">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </h5>
                        <p class="text-muted small mb-0">
                            Baseado na rentabilidade anual da estratégia de <strong><?= number_format($metrics['strategy_annual_return'], 4) ?>%</strong>
                            <?php if (isset($monthlyDeposit) && $monthlyDeposit > 0): ?>
                                e aporte mensal de <strong><?= formatCurrency($monthlyDeposit, $portfolio['output_currency']) ?></strong>.
                            <?php else: ?>
                                (sem novos aportes).
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center">
                            <label for="projectionYears" class="me-2 small fw-bold text-muted text-uppercase" style="white-space: nowrap;">Período:</label>
                            <select id="projectionYears" class="form-select form-select-sm border-0 bg-light-subtle text-main shadow-sm rounded-pill px-3" style="width: 100px;">
                                <option value="5">5 anos</option>
                                <option value="10" selected>10 anos</option>
                                <option value="15">15 anos</option>
                                <option value="20">20 anos</option>
                                <option value="25">25 anos</option>
                                <option value="30">30 anos</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-2">PROJEÇÃO</span>
                        </div>
                    </div>
                </div>
                <div class="card-body bg-light-subtle">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-card rounded-4 border shadow-sm h-100">
                                <div class="text-muted smaller fw-bold mb-1 text-uppercase">Patrimônio Inicial (Simulado)</div>
                                <div class="input-group input-group-sm mt-2">
                                    <span class="input-group-text border-0 bg-light-subtle text-muted border-end-0">
                                        <?= $portfolio['output_currency'] === 'BRL' ? 'R$' : '$' ?>
                                    </span>
                                    <input type="text" class="form-control border-0 bg-light-subtle fw-bold text-main border-start-0" id="projectionInitialValue" 
                                           value="<?= number_format($metrics['final_value'], 2, ',', '.') ?>">
                                </div>
                                <div class="smaller text-muted mt-1">Valor atual do portfólio</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-card rounded-4 border shadow-sm h-100">
                                <div class="text-muted smaller fw-bold mb-1 text-uppercase" id="labelProjectionYears">Patrimônio em 10 anos</div>
                                <div class="h4 fw-bold mb-0 text-primary mt-2" id="valueProjectionFinal">
                                    <?php 
                                    $projectionValues = $chartData['projection_chart']['datasets'][0]['data'];
                                    echo formatCurrency(end($projectionValues), $portfolio['output_currency']); 
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-card rounded-4 border shadow-sm h-100">
                                <div class="text-muted smaller fw-bold mb-1 text-uppercase">Total Investido (Aportes)</div>
                                <div class="h4 fw-bold mb-0 text-secondary mt-2" id="valueProjectionInvested">
                                    <?php 
                                    $investedValues = $chartData['projection_chart']['datasets'][1]['data'];
                                    echo formatCurrency(end($investedValues), $portfolio['output_currency']); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="projectionChart"></canvas>
                    </div>
                </div>
                <div class="card-footer border-top-0 py-3">
                    <div class="alert alert-soft-warning border-0 rounded-4 small mb-0 d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                        <div>
                            <strong>Importante:</strong> Esta é uma simulação baseada em rentabilidade passada, que não é garantia de rentabilidade futura. 
                            O cálculo utiliza juros compostos mensais e considera que os aportes configurados serão mantidos fielmente ao longo de todo o período.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- NOVO: Gráfico de Performance da Estratégia (sem aportes) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        Desempenho da Carteira
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Compara o crescimento do patrimônio <strong>com aportes</strong> (linha verde) versus o <strong>desempenho puro da carteira</strong> (linha roxa), sem aportes.<br><br>A diferença entre as linhas representa o quanto o seu esforço de poupança (aportes) contribuiu para o valor final.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                    <p class="text-muted small mb-0">Comparação entre o patrimônio total (com aportes) e o desempenho da carteira (sem aportes).</p>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="strategyPerformanceChart"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <div class="d-inline-block me-4">
                            <span class="badge bg-primary me-1" style="width: 15px; height: 15px; display: inline-block;"></span>
                            <span class="small">Performance (<?php echo formatPercentage($lastStrategyReturn); ?>)</span>
                        </div>
                        <div class="d-inline-block">
                            <span class="badge bg-success me-1" style="width: 15px; height: 15px; display: inline-block;"></span>
                            <span class="small">Portfólio Total (<?php echo formatPercentage($lastPortfolioReturn); ?>)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NOVO: Gráfico de Juros Acumulados -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        Evolução dos Juros
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Exibe os <strong>juros mensais obtidos</strong> (barras) e o <strong>total acumulado de juros</strong> (linha) ao longo do tempo.<br><br>Juros = rendimento gerado pelos ativos, excluindo capital aportado.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                    <p class="text-muted small mb-0">Juros mensais obtidos e acumulados ao longo do tempo.</p>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="interestChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if ($hasDeposits && isset($chartData['audit_log'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        Histórico de Aportes
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Exibe os <strong>aportes realizados</strong> a cada mês (barras verdes) e o valor total do portfólio ao longo do tempo (linha azul).<br><br>Meses sem aporte correspondem a períodos em que a condição de aporte não foi atingida.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="depositsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-pie-chart-fill text-warning"></i>
                                Composição Histórica
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                        data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                        title="<strong>Como a alocação entre os ativos evoluiu</strong> ano a ano — mostra a fatia de cada ativo no portfólio ao longo do tempo, refletindo os rebalanceamentos periódicos.">
                                    <i class="bi bi-info-circle-fill"></i>
                                </button>
                            </h5>
                            <p class="mb-0 mt-1 text-muted small">Distribuição dos ativos ano a ano</p>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 text-nowrap small">Alocação</span>
                    </div>
                </div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="compositionChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-wallet2 text-primary"></i>
                                Rentabilidade da Carteira
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                        data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                        title="<strong>Como sua carteira efetivamente se comportou</strong> ano a ano — considera todos os aportes realizados, rebalanceamentos e o fluxo de caixa. É o resultado que você, de fato, obteve.">
                                    <i class="bi bi-info-circle-fill"></i>
                                </button>
                            </h5>
                            <p class="mb-0 mt-1 text-muted small">Inclui aportes e rebalanceamentos</p>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 text-nowrap small">Com aportes</span>
                    </div>
                </div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="returnsChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-graph-up-arrow text-success"></i>
                                Performance da Estratégia
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                        data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                        title="<strong>Quanto os ativos selecionados renderam</strong>, isolados de qualquer aporte ou retirada. Mede a qualidade da alocação em si — o quanto a estratégia de investimento entregou, independentemente do capital investido.">
                                    <i class="bi bi-info-circle-fill"></i>
                                </button>
                            </h5>
                            <p class="mb-0 mt-1 text-muted small">Performance pura dos ativos, sem efeito dos aportes</p>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 text-nowrap small">Estratégia pura</span>
                    </div>
                </div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="strategyReturnsChart"></canvas></div></div>
            </div>
        </div>
    </div>



    <!-- Modal de Edição Rápida de Alocações -->
    <div class="modal fade" id="editCompositionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar Alocação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="quickEditForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                        <input type="hidden" name="portfolio_id" value="<?php echo $portfolio['id']; ?>">

                        <div class="p-3 border-bottom">
                            <div class="alert alert-info py-2 small">
                                <i class="bi bi-lightbulb me-1"></i> Ajuste os percentuais e experimente diferentes configurações. A soma deve ser 100%.
                            </div>
                        </div>

                        <div class="p-3">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Ativo</th>
                                        <th style="width: 150px;">Alocação Atual</th>
                                        <th style="width: 180px;">Novo Percentual</th>
                                        <th style="width: 120px;">Variação</th>
                                    </tr>
                                    </thead>
                                    <tbody id="editCompositionBody">
                                    <?php foreach ($assets as $asset): ?>
                                        <tr data-asset-id="<?php echo $asset['asset_id']; ?>">
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($asset['name']); ?></div>
                                                <div class="text-muted small"><?php echo $asset['currency']; ?></div>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?php echo formatPercentage($asset['allocation_percentage']); ?></span>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           class="form-control allocation-input"
                                                           name="assets[<?php echo $asset['asset_id']; ?>]"
                                                           value="<?php echo $asset['allocation_percentage']; ?>"
                                                           step="0.01"
                                                           min="0"
                                                           max="100"
                                                           data-asset-id="<?php echo $asset['asset_id']; ?>"
                                                           data-current="<?php echo $asset['allocation_percentage']; ?>">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark" id="change-<?php echo $asset['asset_id']; ?>">0%</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 border-top pt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-3">
                                            <strong>Total:</strong>
                                            <span class="fs-4 fw-bold <?php echo $totalPercent == 100 ? 'text-success' : 'text-danger'; ?>" id="totalPercent">
                                                <?php echo number_format($totalPercent, 2); ?>%
                                            </span>
                                            <div id="totalStatus">
                                                <?php if ($totalPercent == 100): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> OK</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Ajustar</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="button" class="btn btn-secondary" onclick="resetAllocations()">
                                            <i class="bi bi-arrow-clockwise"></i> Resetar
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="quickSaveAllocations()" id="saveAllocationsBtn" <?php echo $totalPercent != 100 ? 'disabled' : ''; ?>>
                                            <i class="bi bi-save"></i> Salvar & Simular
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="alert alert-warning py-2" id="allocationWarning" style="display: <?php echo $totalPercent != 100 ? 'block' : 'none'; ?>;">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        A soma das alocações deve ser exatamente 100%. Diferença atual:
                                        <span id="difference"><?php echo number_format(abs($totalPercent - 100), 2); ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Inicialização de dados e gráficos
        const currency = '<?php echo $portfolio['output_currency']; ?>';
        const chartData = <?php echo json_encode($chartData); ?>;
        const assetNames = {<?php foreach ($assets as $a) echo '"'.$a['asset_id'].'": "'.htmlspecialchars($a['name']).'",'; ?>};
        const assetTargets = {<?php foreach ($assets as $a) echo '"'.$a['asset_id'].'": '.$a['allocation_percentage'].','; ?>};
        const assetTaxGroups = {<?php foreach ($assets as $a) echo '"'.$a['asset_id'].'": "'.($a['tax_group'] ?? 'RENDA_FIXA').'",'; ?>};
        const outputCurrency = '<?php echo $portfolio['output_currency']; ?>';

        // Remove metadados do log de auditoria para gráficos e cálculos
        const auditLog = { ...chartData.audit_log };
        delete auditLog._metadata;

        /*  Helper: formata moeda  */
        function fmtCur(value, currency) {
            const targetCurrency = currency || outputCurrency;
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: targetCurrency,
                minimumFractionDigits: 2
            }).format(value);
        }

        /*  Cálculo de Impostos e Atualização do Hero  */
        (function processTaxAndHero() {
            const log = auditLog;
            if (!log) return;

            const dates = Object.keys(log).sort();
            const currentCosts = {};
            const groupTaxResults = {};
            const TAX_RATES = <?php 
                $defaultRates = [
                    'CRIPTOMOEDA' => 0.15,
                    'ETF_US' => 0.15,
                    'ETF_BR' => 0.15,
                    'RENDA_FIXA' => 0.20,
                    'FUNDO_IMOBILIARIO' => 0.20
                ];
                $configuredRates = !empty($portfolio['profit_tax_rates_json']) ? json_decode($portfolio['profit_tax_rates_json'], true) : [];
                $finalRates = [];
                foreach ($defaultRates as $group => $default) {
                    $val = isset($configuredRates[$group]) ? (float)$configuredRates[$group] / 100 : ($portfolio['profit_tax_rate'] ? (float)$portfolio['profit_tax_rate'] / 100 : $default);
                    $finalRates[$group] = $val;
                }
                echo json_encode($finalRates);
            ?>;

            dates.forEach(date => {
                const data = log[date];
                const assets = data.asset_values || {};
                const trades = data.trades || {};
                const depositDetails = data.deposit_details || {};
                data.tax_summary = {};
                const monthlyGroupResults = {};

                for (const id in assets) {
                    if (currentCosts[id] === undefined) currentCosts[id] = 0;
                    const isInitialPoint = data.is_initial_point || false;
                    const trade = trades[id];
                    const deposit = depositDetails[id];
                    const tradeDelta = (trade && trade.delta !== undefined) ? parseFloat(trade.delta) : 0;
                    const depositDelta = (deposit && deposit.amount !== undefined) ? parseFloat(deposit.amount) : 0;
                    const delta = tradeDelta + depositDelta;
                    let taxGroup = assetTaxGroups[id] || 'RENDA_FIXA';

                    if (currentCosts[id] === 0 || isInitialPoint) {
                        if (isInitialPoint) {
                            currentCosts[id] = parseFloat(assets[id] || 0);
                        } else if (delta > 0) {
                            currentCosts[id] = delta;
                        } else if (parseFloat(assets[id] || 0) > 0) {
                            const assetValueBefore = parseFloat(data.asset_values_before ? data.asset_values_before[id] : 0);
                            currentCosts[id] = assetValueBefore > 0 ? assetValueBefore : parseFloat(assets[id] || 0);
                        }
                    } else {
                        if (delta > 0) {
                            currentCosts[id] += delta;
                        } else if (delta < 0) {
                            const sellAmount = Math.abs(delta);
                            const preTradeValue = parseFloat(assets[id] || 0) + sellAmount;
                            if (preTradeValue > 0) {
                                const proportionSold = Math.min(1, sellAmount / preTradeValue);
                                const costSold = currentCosts[id] * proportionSold;
                                const realizedProfit = sellAmount - costSold;
                                if (taxGroup !== 'RENDA_FIXA') {
                                    if (monthlyGroupResults[taxGroup] === undefined) monthlyGroupResults[taxGroup] = 0;
                                    monthlyGroupResults[taxGroup] += realizedProfit;
                                }
                                currentCosts[id] -= costSold;
                            }
                        }
                    }
                    if (parseFloat(assets[id] || 0) <= 0.01) currentCosts[id] = 0;
                }

                for (const group in monthlyGroupResults) {
                    if (!groupTaxResults[group]) groupTaxResults[group] = { accumulatedLoss: 0 };
                    const profit = monthlyGroupResults[group];
                    const previousLoss = groupTaxResults[group].accumulatedLoss;
                    let taxableBase = 0;
                    let tax = 0;
                    if (profit > 0) {
                        if (previousLoss < 0) {
                            const compensation = Math.min(profit, Math.abs(previousLoss));
                            taxableBase = profit - compensation;
                            groupTaxResults[group].accumulatedLoss += compensation;
                        } else {
                            taxableBase = profit;
                        }
                        if (taxableBase > 0) {
                            tax = taxableBase * (TAX_RATES[group] || 0.15);
                        }
                    } else {
                        groupTaxResults[group].accumulatedLoss += profit;
                    }
                    if (tax > 0.01) {
                        data.tax_summary[group] = { tax: tax };
                    }
                }
            });

            const totalTaxPaid = Object.values(log)
                .reduce((acc, d) => acc + Object.values(d.tax_summary || {}).reduce((sum, g) => sum + (g.tax || 0), 0), 0);

            const taxValueEl = document.getElementById('tax-paid-value');
            if (taxValueEl && totalTaxPaid > 0.01) {
                taxValueEl.innerText = fmtCur(totalTaxPaid);
                const taxCard = document.getElementById('tax-paid-card');
                const taxBadge = document.getElementById('tax-paid-badge');
                if (taxCard) taxCard.style.background = 'var(--hero-tax-bg)';
                if (taxBadge) { taxBadge.classList.remove('bg-secondary'); taxBadge.classList.add('bg-danger'); }
                taxValueEl.classList.remove('text-muted'); taxValueEl.classList.add('text-danger');
            }

            const finalDate = dates[dates.length - 1];
            if (finalDate && log[finalDate]) {
                const finalValue = log[finalDate].total_value || 0;
                const netFinalValue = finalValue - totalTaxPaid;
                const initialCapital = <?= (float)$portfolio['initial_capital'] ?>;
                const totalDeposits = Object.values(log).reduce((acc, d) => acc + (d.deposit_made || 0), 0);
                const totalInvested = initialCapital + totalDeposits;
                const netTotalReturn = totalInvested > 0 ? ((netFinalValue / totalInvested) - 1) * 100 : 0;
                
                const finalAmountEl = document.getElementById('final-value-amount');
                if (finalAmountEl && totalTaxPaid > 0.01) {
                    finalAmountEl.innerText = fmtCur(netFinalValue);
                    finalAmountEl.title = `Valor Bruto: ${fmtCur(finalValue)} | Impostos: ${fmtCur(totalTaxPaid)}`;
                    const totalReturnBadgeEl = document.getElementById('total-return-badge');
                    if (totalReturnBadgeEl) {
                        const isPos = netTotalReturn >= 0;
                        totalReturnBadgeEl.innerText = (isPos ? '+' : '') + netTotalReturn.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%';
                        totalReturnBadgeEl.classList.remove('bg-success', 'bg-danger');
                        totalReturnBadgeEl.classList.add(isPos ? 'bg-success' : 'bg-danger');
                    }
                }
            }
        })();

        // ============================================================
        // Helpers: formatação de eixo X e tooltip de período
        // ============================================================

        /**
         * Converte data ISO (YYYY-MM-DD) para rótulo compacto MM/AA.
         * Ex.: "2026-01-31" -> "01/26"
         */
        function formatXAxisLabel(isoDate) {
            if (!isoDate || !String(isoDate).match(/^\d{4}-\d{2}-\d{2}$/)) return isoDate;
            const d = new Date(isoDate + "T12:00:00");
            return String(d.getMonth() + 1).padStart(2, '0') + '/' + String(d.getFullYear()).slice(2);
        }

        /**
         * Gera título de tooltip com período de referência.
         * Clarifica que "01/26" = variação entre o fechamento de 31/12/25 e 31/01/26.
         * Retorna array de duas linhas para exibição no Chart.js.
         */
        function formatPeriodTitle(isoDate) {
            if (!isoDate || !String(isoDate).match(/^\d{4}-\d{2}-\d{2}$/)) {
                return [String(isoDate)];
            }
            const d    = new Date(isoDate + "T12:00:00");
            const mm   = String(d.getMonth() + 1).padStart(2, '0');
            const yy   = String(d.getFullYear()).slice(2);
            // Último dia do mês atual
            const endDate  = new Date(d.getFullYear(), d.getMonth() + 1, 0);
            const endDay   = String(endDate.getDate()).padStart(2, '0');
            // Último dia do mês anterior
            const prevDate = new Date(d.getFullYear(), d.getMonth(), 0);
            const prevDay  = String(prevDate.getDate()).padStart(2, '0');
            const prevMm   = String(prevDate.getMonth() + 1).padStart(2, '0');
            const prevYy   = String(prevDate.getFullYear()).slice(2);
            return [
                mm + '/' + yy,
                'Variação: ' + prevDay + '/' + prevMm + '/' + prevYy + ' -> ' + endDay + '/' + mm + '/' + yy
            ];
        }

        /** Configuração padrão de eixo X para gráficos mensais (MM/AA, inclinado 45°) */
        const xAxisMonthly = {
            ticks: {
                callback: function(value) {
                    return formatXAxisLabel(this.getLabelForValue(value));
                },
                maxRotation: 45,
                minRotation: 45
            }
        };

        window.valueChart = new Chart(document.getElementById('valueChart'), {
            type: 'line',
            data: {
                labels: chartData.value_chart.labels, // ISO dates – formatados via ticks.callback
                datasets: chartData.value_chart.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return formatPeriodTitle(tooltipItems[0].label);
                            },
                            label: (ctx) => `Valor: ${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    x: xAxisMonthly,
                    y: {
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(value);
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('compositionChart'), {
            type: 'bar',
            data: chartData.composition_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: {
                        stacked: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:4})}%`;
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('returnsChart'), {
            type: 'bar',
            data: chartData.returns_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Retorno: ${context.raw.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:4})}%`;
                            }
                        }
                    }
                }
            }
        });

        if (chartData.strategy_returns_chart) {
            new Chart(document.getElementById('strategyReturnsChart'), {
                type: 'bar',
                data: chartData.strategy_returns_chart,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Performance: ${context.raw.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:4})}%`;
                            }
                        }
                    }
                    }
                }
            });
        }

        <?php if ($hasDeposits && isset($chartData['audit_log'])): ?>
        // Gráfico de Aportes
        const depositDates = [];
        const depositAmounts = [];
        const portfolioValuesPlot = [];

        Object.entries(auditLog).forEach(([date, data]) => {
            depositDates.push(date);
            depositAmounts.push(data.deposit_made || 0);
            portfolioValuesPlot.push(data.total_value);
        });

        new Chart(document.getElementById('depositsChart'), {
            type: 'bar',
            data: {
                labels: depositDates, // ISO dates – formatados via ticks.callback
                datasets: [
                    {
                        label: 'Valor do Portfólio',
                        data: portfolioValuesPlot,
                        type: 'line',
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true,
                        yAxisID: 'y',
                        tension: 0.1
                    },
                    {
                        label: 'Aportes Realizados',
                        data: depositAmounts,
                        type: 'bar',
                        backgroundColor: 'rgba(40, 167, 69, 0.5)',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: xAxisMonthly,
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(value);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(value);
                            }
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return formatPeriodTitle(tooltipItems[0].label);
                            },
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return `Portfólio: ${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(context.raw)}`;
                                } else {
                                    return `Aporte: ${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(context.raw)}`;
                                }
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>



        // Lógica para Projeção de Patrimônio Futuro (Cards de resumo)
        const currentPatrimonyInput = document.getElementById('currentPatrimony');
        if (currentPatrimonyInput) {
            currentPatrimonyInput.addEventListener('input', function(e) {
                // Remove tudo que não é dígito
                let value = this.value.replace(/\D/g, '');
                
                // Formata como moeda
                if (value.length > 0) {
                    value = (parseInt(value) / 100).toFixed(2);
                    this.value = parseFloat(value).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    this.value = '0,00';
                }

                // Cálculo da projeção para os cards de 5 a 30 anos
                const numericValue = parseFloat(value) || 0;
                const monthlyDeposit = <?= $monthlyDeposit ?>;
                const annualReturn = <?= $metrics['strategy_annual_return'] ?? $metrics['annual_return'] ?>;
                const monthlyRate = Math.pow(1 + (annualReturn / 100), 1/12) - 1;
                const outputCurrency = '<?= $portfolio['output_currency'] ?>';

                document.querySelectorAll('.projection-value').forEach(el => {
                    const years = parseInt(el.getAttribute('data-years'));
                    const n = years * 12;
                    let futureValue = 0;

                    if (monthlyRate > 0) {
                        futureValue = numericValue * Math.pow(1 + monthlyRate, n) + 
                                     monthlyDeposit * ((Math.pow(1 + monthlyRate, n) - 1) / monthlyRate);
                    } else {
                        futureValue = numericValue + (monthlyDeposit * n);
                    }

                    // Atualiza o título (valor completo)
                    el.setAttribute('title', formatCurrencyJS(futureValue, outputCurrency));

                    // Atualiza o texto (formato compacto)
                    if (futureValue >= 1000000000) {
                        el.innerText = formatCurrencyJS(futureValue / 1000000000, outputCurrency) + ' Bi';
                    } else if (futureValue >= 1000000) {
                        el.innerText = formatCurrencyJS(futureValue / 1000000, outputCurrency) + ' Mi';
                    } else {
                        el.innerText = formatCurrencyJS(futureValue, outputCurrency);
                    }
                });
            });
        }

        // Função auxiliar de formatação para JS (equivalente ao PHP formatCurrency)
        function formatCurrencyJS(value, currency) {
            const symbols = { 'BRL': 'R$', 'USD': '$', 'EUR': 'â‚¬' };
            const symbol = symbols[currency] || currency;
            return symbol + ' ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        document.getElementById('benchmarkSelector').addEventListener('change', function() {
            const assetId = this.value;
            const chart = window.valueChart;

            if (!chart) return;

            // Remove benchmark anterior
            if (chart.data.datasets.length > 1) {
                chart.data.datasets.pop();
                chart.update();
                document.getElementById('betaValue').innerText = '--';
                document.getElementById('betaBenchmarkName').innerText = 'Selecione um benchmark';
            }

            if (!assetId) return;

            const start = "<?= $portfolio['start_date'] ?>";
            const end = "<?= $latest ? $latest['simulation_date'] : ($portfolio['end_date'] ?? date('Y-m-d')) ?>";
            const base = <?= $portfolio['initial_capital'] ?>;
            const currency = '<?= $portfolio['output_currency'] ?>';

            fetch(`/index.php?url=api/assets/benchmark/${assetId}&start=${start}&end=${end}&base=${base}&currency=${currency}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;

                    // Cálculo do Beta em tempo real
                    // Utilizamos os dados do gráfico (valueChart) para garantir sincronia temporal com o benchmark
                    const portfolioValues = chart.data.datasets[0].data;
                    const portfolioReturns = [];
                    for (let i = 1; i < portfolioValues.length; i++) {
                        const prev = portfolioValues[i-1];
                        if (prev > 0) {
                            portfolioReturns.push((portfolioValues[i] / prev) - 1);
                        }
                    }

                    const beta = calculateBeta(portfolioReturns, res.returns);
                    document.getElementById('betaValue').innerText = isFinite(beta) ? beta.toFixed(2) : '--';
                    document.getElementById('betaBenchmarkName').innerText = 'Benchmark: ' + this.options[this.selectedIndex].text;

                    // Se o gráfico tem o ponto 0 (capital inicial), o benchmark precisa de um
                    // null no início para alinhar corretamente (benchmark não tem dado para t=0)
                    const chartLabelCount = chart.data.labels.length;
                    const benchmarkData = chartLabelCount > res.values.length
                        ? [null, ...res.values]
                        : res.values;

                    // Adiciona a linha ao gráfico
                    chart.data.datasets.push({
                        label: 'Benchmark: ' + this.options[this.selectedIndex].text,
                        data: benchmarkData,
                        borderColor: '#6c757d',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        pointRadius: 0,
                        borderWidth: 2,
                        fill: false,
                        tension: 0.1
                    });
                    chart.update();
                });
        });

        // Função auxiliar de estatística
        function calculateBeta(pRet, bRet) {
            const minLen = Math.min(pRet.length, bRet.length);
            if (minLen < 2) return 1;

            const mean = (a) => a.reduce((s, v) => s + v, 0) / a.length;
            const mP = mean(pRet.slice(0, minLen));
            const mB = mean(bRet.slice(0, minLen));

            let cov = 0, varB = 0;
            for (let i = 0; i < minLen; i++) {
                cov += (pRet[i] - mP) * (bRet[i] - mB);
                varB += Math.pow(bRet[i] - mB, 2);
            }
            return varB === 0 ? 1 : cov / varB;
        }

        // Gráfico de Performance da Estratégia
        const strategyPerformanceData = chartData.strategy_performance_chart || { labels: [], datasets: [] };
        // Labels já são ISO dates (YYYY-MM-DD) – formatados via ticks.callback

        new Chart(document.getElementById('strategyPerformanceChart'), {
            type: 'line',
            data: strategyPerformanceData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return formatPeriodTitle(tooltipItems[0].label);
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                label += context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:4}) + '%';
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: xAxisMonthly,
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2) + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Retorno Acumulado (%)'
                        }
                    }
                }
            }
        });

        // Gráfico de Juros
        new Chart(document.getElementById('interestChart'), {
            type: 'line',
            data: chartData.interest_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    x: xAxisMonthly,
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('pt-BR', {
                                    style: 'currency',
                                    currency: '<?php echo $portfolio['output_currency']; ?>'
                                }).format(value);
                            }
                        },
                        title: {
                            display: true,
                            text: 'Juros Acumulados'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('pt-BR', {
                                    style: 'currency',
                                    currency: '<?php echo $portfolio['output_currency']; ?>'
                                }).format(value);
                            }
                        },
                        title: {
                            display: true,
                            text: 'Juros Mensais'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return formatPeriodTitle(tooltipItems[0].label);
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                label += new Intl.NumberFormat('pt-BR', {
                                    style: 'currency',
                                    currency: '<?php echo $portfolio['output_currency']; ?>'
                                }).format(context.parsed.y);
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Projeção
        if (chartData.projection_chart && document.getElementById('projectionChart')) {
            window.projectionChartInstance = new Chart(document.getElementById('projectionChart'), {
                type: 'line',
                data: chartData.projection_chart,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            ticks: {
                                // Para projeções longas: exibe apenas Janeiro de cada ano
                                callback: function(value, index) {
                                    const lbl = this.getLabelForValue(value);
                                    if (!lbl || !String(lbl).match(/^\d{4}-\d{2}-\d{2}$/)) return lbl;
                                    const month = lbl.substring(5, 7);
                                    return month === '01' ? formatXAxisLabel(lbl) : null;
                                },
                                maxRotation: 45,
                                minRotation: 45,
                                autoSkip: false
                            }
                        },
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('pt-BR', {
                                        style: 'currency',
                                        currency: '<?php echo $portfolio['output_currency']; ?>',
                                        maximumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    return formatPeriodTitle(tooltipItems[0].label);
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    label += new Intl.NumberFormat('pt-BR', {
                                        style: 'currency',
                                        currency: '<?php echo $portfolio['output_currency']; ?>'
                                    }).format(context.parsed.y);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Lógica para atualização dinâmica do Gráfico de Projeção (Anos e Capital Inicial)
        const projectionYearsSelect = document.getElementById('projectionYears');
        const projectionInitialInput = document.getElementById('projectionInitialValue');

        function updateProjection() {
            if (!projectionYearsSelect) return;
            
            const years = projectionYearsSelect.value;
            const portfolioId = <?= $portfolio['id'] ?>;
            const outputCurrency = '<?= $portfolio['output_currency'] ?>';
            
            // Desabilita controles durante o fetch
            projectionYearsSelect.disabled = true;
            if (projectionInitialInput) projectionInitialInput.disabled = true;

            // Filtra o initialCapital para remover qualquer coisa que não seja dígito, vírgula ou ponto
            // Se o campo estiver vazio, envia vazio para o controlador usar o padrão
            let initialCapitalParam = '';
            if (projectionInitialInput && projectionInitialInput.value.trim() !== '') {
                initialCapitalParam = projectionInitialInput.value.replace(/[^\d,.]/g, '');
            }
            
            fetch(`/index.php?url=api/portfolio/projection/${portfolioId}&years=${years}&initial_capital=${encodeURIComponent(initialCapitalParam)}`)
                .then(response => {
                    console.log('API Response status:', response.status);
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Falha ao parsear JSON. Resposta recebida:', text);
                            throw new Error('Resposta do servidor não é um JSON válido');
                        }
                    });
                })
                .then(data => {
                    console.log('API Data received:', data);
                    projectionYearsSelect.disabled = false;
                    if (projectionInitialInput) projectionInitialInput.disabled = false;
                    
                    if (!data.success) {
                        console.error('Erro ao carregar projeção:', data.message);
                        alert('Erro ao carregar projeção: ' + data.message);
                        return;
                    }

                    // Atualiza o gráfico
                    if (window.projectionChartInstance) {
                        console.log('Updating chart instance...');
                        window.projectionChartInstance.data = data.chart;
                        window.projectionChartInstance.update();
                        console.log('Chart updated.');
                    } else {
                        console.warn('window.projectionChartInstance not found!');
                    }

                    // Atualiza os labels e valores nos cards
                    const titleYears = document.getElementById('titleProjectionYears');
                    if (titleYears) titleYears.innerText = years;

                    const labelYears = document.getElementById('labelProjectionYears');
                    if (labelYears) labelYears.innerText = `Patrimônio em ${years} anos`;
                    
                    const valueFinal = document.getElementById('valueProjectionFinal');
                    if (valueFinal) valueFinal.innerText = formatCurrencyJS(data.final_value, outputCurrency);
                    
                    const valueInvested = document.getElementById('valueProjectionInvested');
                    if (valueInvested) valueInvested.innerText = formatCurrencyJS(data.total_invested, outputCurrency);
                })
                .catch(error => {
                    projectionYearsSelect.disabled = false;
                    if (projectionInitialInput) projectionInitialInput.disabled = false;
                    console.error('Erro na requisição de projeção:', error);
                    alert('Erro na requisição de projeção. Verifique o console.');
                });
        }

        if (projectionYearsSelect) {
            projectionYearsSelect.addEventListener('change', updateProjection);
        }

        if (projectionInitialInput) {
            projectionInitialInput.addEventListener('blur', updateProjection);
            projectionInitialInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    updateProjection();
                    this.blur();
                }
            });

            // Máscara de moeda simples para o input
            projectionInitialInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = (parseInt(value) / 100).toFixed(2);
                    this.value = parseFloat(value).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });
        }

        // Funções para edição rápida de alocações
        function openQuickEditModal() {
            // Calcula o total inicial
            updateAllocationTotal();
            // Mostra o modal
            new bootstrap.Modal(document.getElementById('editCompositionModal')).show();
        }

        function updateAllocationTotal() {
            let total = 0;
            let hasChanges = false;

            document.querySelectorAll('.allocation-input').forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;

                // Atualiza a variação
                const current = parseFloat(input.dataset.current) || 0;
                const change = value - current;
                const changeBadge = document.getElementById(`change-${input.dataset.assetId}`);

                if (change !== 0) {
                    hasChanges = true;
                    changeBadge.textContent = (change > 0 ? '+' : '') + change.toFixed(2) + '%';
                    changeBadge.className = 'badge ' + (change > 0 ? 'bg-success' : 'bg-danger');
                } else {
                    changeBadge.textContent = '0%';
                    changeBadge.className = 'badge bg-light text-dark';
                }
            });

            const totalElement = document.getElementById('totalPercent');
            const statusElement = document.getElementById('totalStatus');
            const warningElement = document.getElementById('allocationWarning');
            const differenceElement = document.getElementById('difference');
            const saveBtn = document.getElementById('saveAllocationsBtn');

            totalElement.textContent = total.toFixed(2) + '%';

            const diff = Math.abs(total - 100);
            differenceElement.textContent = diff.toFixed(2) + '%';

            if (Math.abs(total - 100) < 0.01) {
                totalElement.className = 'fs-4 fw-bold text-success';
                statusElement.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> OK</span>';
                warningElement.style.display = 'none';
                saveBtn.disabled = false;
            } else {
                totalElement.className = 'fs-4 fw-bold text-danger';
                statusElement.innerHTML = '<span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Ajustar</span>';
                warningElement.style.display = 'block';
                saveBtn.disabled = true;
            }
        }

        function resetAllocations() {
            document.querySelectorAll('.allocation-input').forEach(input => {
                input.value = input.dataset.current;
            });
            updateAllocationTotal();
        }

        function quickSaveAllocations() {
            const form = document.getElementById('quickEditForm');
            const formData = new FormData(form);

            // Adiciona a ação específica
            formData.append('action', 'update_allocation');

            // Mostra loading
            const saveBtn = document.getElementById('saveAllocationsBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';
            saveBtn.disabled = true;

            fetch('/index.php?url=<?= obfuscateUrl('portfolio/quick-update/' . $portfolio['id']) ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('Erro do servidor (' + response.status + '): ' + text.substring(0, 100));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Atualiza a interface
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao salvar alocações');
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erro detalhado:', error);
                    alert('Erro na comunicação com o servidor: ' + error.message);
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        // Adiciona eventos aos inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializa todos os tooltips Bootstrap da página
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(function(el) {
                new bootstrap.Tooltip(el, { html: true, trigger: 'hover focus' });
            });

            document.querySelectorAll('.allocation-input').forEach(input => {
                input.addEventListener('input', updateAllocationTotal);
                input.addEventListener('change', updateAllocationTotal);
            });

            // Disparar o benchmark padrão (S&P 500) se estiver selecionado
            const benchmarkSelector = document.getElementById('benchmarkSelector');
            if (benchmarkSelector && benchmarkSelector.value) {
                benchmarkSelector.dispatchEvent(new Event('change'));
            }
        });

        // Função para sugerir ajuste automático
        function suggestAllocation() {
            const badges = document.querySelectorAll('.allocation-badge');
            let total = 0;
            let count = 0;

            // Calcula total atual
            badges.forEach(badge => {
                const value = parseFloat(badge.dataset.allocation) || 0;
                total += value;
                if (value > 0) count++;
            });

            if (count === 0) return;

            const diff = 100 - total;
            const adjustment = diff / count;

            // Aplica ajuste proporcional
            badges.forEach(badge => {
                const current = parseFloat(badge.dataset.allocation) || 0;
                if (current > 0) {
                    const newValue = Math.max(0, current + adjustment);
                    badge.textContent = newValue.toFixed(2) + '%';
                    badge.dataset.allocation = newValue;

                    // Atualiza a barra de progresso
                    const progressBar = document.querySelector(`.progress-bar[data-asset-id="${badge.dataset.assetId}"]`);
                    if (progressBar) {
                        progressBar.style.width = newValue + '%';
                    }
                }
            });

            // Atualiza o total
            const totalElement = document.querySelector('.card-body .fw-bold');
            if (totalElement) {
                const newTotal = total + diff;
                totalElement.textContent = newTotal.toFixed(2) + '%';
                totalElement.className = newTotal === 100 ? 'ms-2 fw-bold text-success' : 'ms-2 fw-bold text-danger';
            }
        }


    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>


