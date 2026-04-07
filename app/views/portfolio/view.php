<?php
/**
 * @var array $portfolio Dados do portfólio (id, name, start_date, etc.)
 * @var array $assets Lista de ativos vinculados
 * @var array|null $latest Último resultado de simulação
 * @var array $metrics Métricas calculadas (total_return, volatility, etc.)
 * @var array $chartData Dados formatados para os gráficos JS
 */

$title = 'Resultados: ' . htmlspecialchars($portfolio['name']);
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
        <div class="col-md-4 text-end">
            <div class="btn-group shadow-sm">
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-play-fill"></i> Simular
                </a>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/clone/' . $portfolio['id']) ?>" class="btn btn-outline-secondary" title="Clonar">
                    <i class="bi bi-files"></i>
                </a>

                <?php if (!$portfolio['is_system_default'] || Auth::isAdmin()): ?>
                    <a href="/index.php?url=<?= obfuscateUrl('portfolio/edit/' . $portfolio['id']) ?>" class="btn btn-outline-secondary" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="/index.php?url=<?= obfuscateUrl('portfolio/delete/' . $portfolio['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta estratégia?')">
                        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                        <button type="submit" class="btn btn-outline-danger no-spinner" title="Excluir">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
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
// ─── AVISO: Caixa SELIC com rebalanceamento e aporte mensais ───────────────
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
    // ── Hero: 3 métricas em destaque ────────────────────────────────────────
    $heroHasDeposits = isset($metrics['total_deposits']) && $metrics['total_deposits'] > 0;
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
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
                        <div class="display-6 fw-bold lh-1 mb-1" style="color: var(--hero-initial-icon);">
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
        <div class="col-md-4">
            <?php if ($heroHasDeposits): ?>
            <div class="card border-0 rounded-4 shadow h-100 overflow-hidden position-relative"
                 style="background: var(--hero-deposits-bg);">
                <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge rounded-pill px-3 py-2 fw-semibold text-uppercase small bg-success">
                            <i class="bi bi-cash-stack me-1"></i> Total de Aportes
                        </span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                title="Soma de <strong>todos os aportes realizados</strong> durante o período simulado, além do capital inicial.<br><br>Total Investido = Capital Inicial + Aportes.">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                        </button>
                    </div>
                    <div>
                        <div class="display-6 fw-bold text-success lh-1 mb-1">
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
                 style="background: linear-gradient(135deg,#f7f8fa 0%,#ebedf0 100%);">
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
                        <div class="display-6 fw-bold text-muted lh-1 mb-1">—</div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-dash-circle me-1"></i> Sem aportes periódicos
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Patrimônio Final -->
        <div class="col-md-4">
            <?php
            $heroFinalValue = $metrics['final_value'] ?? $metrics['total_value'] ?? $portfolio['initial_capital'];
            $heroTotalReturn = $metrics['total_return'] ?? 0;
            $heroPositive = $heroTotalReturn >= 0;
            ?>
            <div class="card border-0 rounded-4 shadow h-100 overflow-hidden position-relative"
                 style="background: var(<?= $heroPositive ? '--hero-final-pos-bg' : '--hero-final-neg-bg' ?>);">
                <div class="card-body p-4 d-flex flex-column justify-content-between position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge rounded-pill px-3 py-2 fw-semibold text-uppercase small <?= $heroPositive ? 'bg-primary' : 'bg-danger' ?>">
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
                        <div class="display-6 fw-bold lh-1 mb-1 <?= $heroPositive ? 'text-primary' : 'text-danger' ?>">
                            <?php echo formatCurrency($heroFinalValue, $portfolio['output_currency']); ?>
                        </div>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <span class="badge rounded-pill <?= $heroPositive ? 'bg-success' : 'bg-danger' ?> fs-6 px-3 py-2">
                                <?= ($heroPositive ? '+' : '') . number_format($heroTotalReturn, 2, ',', '.') ?>%
                            </span>
                            <span class="text-muted small">retorno total</span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div>
                        <div class="display-6 fw-bold text-muted lh-1 mb-1">—</div>
                        <div class="text-muted small mt-2"><i class="bi bi-play-circle me-1"></i> Execute a simulação</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php
        // Adiciona métricas específicas para simulações com aportes
        $hasDeposits = isset($metrics['total_deposits']) && $metrics['total_deposits'] > 0;

        $metricsList = [
                ['label' => 'Retorno Total', 'val' => formatPercentage($metrics['total_return'], 2), 'class' => 'border-primary', 'text' => $metrics['total_return'] >= 0 ? 'text-success' : 'text-danger',
                 'tooltip' => 'Percentual de valorização total do portfólio do início ao fim da simulação. Inclui o efeito dos aportes e do rebalanceamento.'],
                [
                        'label' => ($metrics['is_short_period'] ?? false) ? 'Retorno no Período' : 'CAGR (Anual)',
                        'val'   => formatPercentage($metrics['annual_return'], 2),
                        'class' => 'border-success',
                        'text'  => 'text-success',
                        'tooltip' => 'CAGR (Compound Annual Growth Rate): taxa de crescimento anual composta. Representa o retorno anualizado consistente que teria gerado o mesmo resultado final.'
                ],
                ['label' => 'Volatilidade', 'val' => formatPercentage($metrics['volatility'], 2), 'class' => 'border-warning', 'text' => 'text-main',
                 'tooltip' => 'Desvio padrão dos retornos mensais. Mede a imprevisibilidade do portfólio: <strong>quanto maior, mais arriscado</strong>. Valores baixos indicam maior estabilidade.'],
                ['label' => 'Sharpe Ratio', 'val' => number_format($metrics['sharpe_ratio'], 2), 'class' => 'border-info', 'text' => 'text-main',
                 'tooltip' => 'Relação entre retorno e risco. <strong>Acima de 1</strong> = bom; <strong>acima de 2</strong> = excelente. Indica quanto de retorno foi obtido por unidade de risco assumido.'],
                ['label' => 'MAIOR ALTA MENSAL REAL', 'val' => formatPercentage($metrics['max_monthly_gain'] ?? 0, 2), 'class' => 'border-success', 'text' => 'text-success',
                 'tooltip' => 'O <strong>maior retorno positivo</strong> registrado em um único mês durante o período simulado. Reflete o melhor cenário mensal da estratégia.'],
                ['label' => 'MAIOR QUEDA REAL MENSAL', 'val' => formatPercentage($metrics['max_monthly_loss'] ?? 0, 2), 'class' => 'border-danger', 'text' => 'text-danger',
                 'tooltip' => 'A <strong>maior perda</strong> sofrida em um único mês durante o período simulado. Ajuda a dimensionar o risco de curto prazo da estratégia.'],
        ];

        // Se houver aportes, adicionamos métricas de Retorno Real e ROI
        if ($hasDeposits) {
            $metricsList[] = [
                    'label' => 'Performance Real (Sem Aportes)',
                    'val' => formatPercentage($metrics['strategy_return'] ?? 0, 2),
                    'class' => 'border-primary',
                    'text' => ($metrics['strategy_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'Retorno total gerado <strong>exclusivamente pela estratégia de investimento</strong>, isolando o efeito dos aportes. Mostra o quanto a alocação de ativos contribuiu para o resultado.'
            ];

            $metricsList[] = [
                'label' => ($metrics['is_short_period'] ?? false) ? 'Perf. Anual Real (Sem Aportes)' : 'Performance Anual Real (Sem Aportes)',
                'val' => formatPercentage($metrics['strategy_annual_return'] ?? 0, 2),
                'class' => 'border-success',
                'text' => ($metrics['strategy_annual_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                'tooltip' => 'Versão <strong>anualizada</strong> do retorno real da estratégia, sem considerar o efeito dos aportes. Permite comparar com benchmarks e outras estratégias.'
            ];
            
            $metricsList[] = [
                    'label' => 'Retorno Real (Sem Aportes)',
                    'val' => formatCurrency(($portfolio['initial_capital'] * ($metrics['strategy_return'] ?? 0) / 100), $portfolio['output_currency']),
                    'class' => 'border-info',
                    'text' => ($metrics['strategy_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => '<strong>Valor monetário</strong> gerado pela estratégia sobre o capital inicial, desconsiderando todos os aportes. É o lucro puro da alocação de ativos.'
            ];

            $metricsList[] = [
                    'label' => 'ROI (com aportes)',
                    'val' => formatPercentage($metrics['roi'] ?? 0, 2),
                    'class' => 'border-success',
                    'text' => ($metrics['roi'] ?? 0) >= 0 ? 'text-success' : 'text-danger',
                    'tooltip' => 'Return on Investment: retorno percentual sobre o <strong>total investido</strong> (capital inicial + todos os aportes). Indica a eficiência de todo o capital empregado.'
            ];
        }

        $metricsList[] = [
            'label' => 'BETA DA CARTEIRA',
            'val' => '<span id="betaValue">--</span>',
            'class' => 'border-dark',
            'text'  => 'text-main',
            'tooltip' => 'O <strong>Beta</strong> mede a sensibilidade do portfólio em relação a um benchmark (ex: IBOV). <br><br><strong>Beta > 1:</strong> Mais volátil que o mercado.<br><strong>Beta = 1:</strong> Mesma volatilidade.<br><strong>Beta < 1:</strong> Menos volátil que o mercado.'
        ];

        foreach ($metricsList as $m): ?>
            <div class="col-md-3 mb-3">
                <div class="card metric-card shadow-sm h-100 border-start border-4 <?php echo $m['class']; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0 me-1"><?php echo $m['label']; ?></h6>
                            <?php if (!empty($m['tooltip'])): ?>
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted flex-shrink-0 info-tooltip"
                                    data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                    title="<?= htmlspecialchars($m['tooltip']) ?>">
                                <i class="bi bi-info-circle-fill small"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <h3 class="<?php echo $m['text']; ?> fw-bold mb-0"><?php echo $m['val']; ?></h3>
                        <?php if ($m['label'] == 'BETA DA CARTEIRA'): ?>
                            <div class="mt-2 small text-muted">
                                <span id="betaBenchmarkName">Selecione um benchmark</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($m['label'] == 'Retorno Real (Sem Aportes)'): ?>
                            <div class="mt-2 small text-muted">
                                Capital Inicial: <?php echo formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($m['label'] == 'ROI (com aportes)' && $hasDeposits): ?>
                            <div class="mt-2 small text-muted">
                                Investido: <?php echo formatCurrency($metrics['total_invested'], $portfolio['output_currency']); ?>
                                <br>Aportes: <?php echo formatCurrency($metrics['total_deposits'], $portfolio['output_currency']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
                                    title="<strong>Estimativa futura</strong> baseada no retorno anual real histórico da estratégia, com juros compostos mensais.<br><br>⚠️ Rentabilidade passada <strong>não garante</strong> rentabilidade futura. Use como referência de planejamento.">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </h5>
                        <p class="text-muted small mb-0">
                            Baseado no retorno anual real da estratégia de <strong><?= number_format($metrics['strategy_annual_return'], 4) ?>%</strong>
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
                        Performance Real da Estratégia
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Compara o crescimento do portfólio <strong>com aportes</strong> (linha verde) versus a <strong>performance pura da estratégia</strong> (linha azul), sem aportes.<br><br>A diferença entre as linhas representa o impacto dos aportes no patrimônio.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                    <p class="text-muted small mb-0">Comparação entre o crescimento do portfólio total (com aportes) e a performance real da estratégia (excluindo aportes).</p>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="strategyPerformanceChart"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <div class="d-inline-block me-4">
                            <span class="badge bg-primary me-1" style="width: 15px; height: 15px; display: inline-block;"></span>
                            <span class="small">Estratégia (<?php echo formatPercentage($lastStrategyReturn); ?>)</span>
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
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        Composição Histórica
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Percentual de cada ativo no portfólio <strong>mês a mês</strong>. Mostra como as alocações evoluíram com o tempo e os rebalanceamentos periódicos.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                </div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="compositionChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        Retorno por Ano
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Retorno percentual total do portfólio (incluindo aportes) em <strong>cada ano</strong> do período simulado. Barras verdes = anos positivos; barras vermelhas = anos negativos.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                </div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="returnsChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        Retorno por Ano Real (Sem Aportes)
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                                title="Retorno percentual gerado pela <strong>estratégia pura</strong> em cada ano, excluindo o efeito dos aportes. Permite avaliar a qualidade da alocação de ativos independentemente do volume aportado.">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </h5>
                </div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="strategyReturnsChart"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                Auditoria Mensal
                <button type="button" class="btn btn-link btn-sm p-0 text-muted info-tooltip"
                        data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="right"
                        title="Registro detalhado de <strong>cada mês simulado</strong>: saldo, variação mensal, status de rebalanceamento e aportes realizados.<br><br>Clique em <em>Ver Ativos</em> para ver a composição exata de cada mês.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </h5>
            <div class="d-flex gap-2">
                <input type="text" id="auditSearch" class="form-control form-control-sm" placeholder="Buscar data..." style="width: 180px;">
                <button onclick="exportAuditToCSV()" class="btn btn-sm btn-outline-secondary" title="Exportar Auditoria Completa"><i class="bi bi-download"></i> CSV</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive-audit">
                <table class="table table-hover align-middle mb-0" id="auditTable">
                    <thead class="sticky-top-table">
                    <tr class="text-muted smaller text-uppercase">
                        <th class="ps-4">Mês/Ano</th>
                        <th>Saldo</th>
                        <th>Variação</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aportes</th>
                        <?php if ($portfolio['simulation_type'] === 'selic_cash_deposit'): ?>
                        <th class="text-center">Caixa SELIC</th>
                        <?php endif; ?>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $prevValue = $portfolio['initial_capital'];
                    if (isset($chartData['audit_log'])):
                        foreach ($chartData['audit_log'] as $date => $data):
                            if ($date === '_metadata') continue;

                            $currentValue = $data['total_value'];
                            // CORREÇÃO: Usar o valor ANTES do aporte para calcular a variação real dos ativos no mês
                            $totalBeforeDeposit = $data['total_before_deposit'] ?? $currentValue;
                            $variation = (($totalBeforeDeposit / $prevValue) - 1) * 100;
                            $rebalanced = $data['rebalanced'] ?? false;
                            $depositMade = $data['deposit_made'] ?? 0;
                            $depositType = $data['deposit_type'] ?? 'none';

                            $dateLabel = date('m/Y', strtotime($date));
                            $assetValuesJson = htmlspecialchars(json_encode($data['asset_values']), ENT_QUOTES, 'UTF-8');
                            $tradesJson = htmlspecialchars(json_encode($data['trades'] ?? []), ENT_QUOTES, 'UTF-8');
                            $depositInfoJson = htmlspecialchars(json_encode(['amount' => $depositMade, 'type' => $depositType]), ENT_QUOTES, 'UTF-8');
                            $selicCashValue    = $data['selic_cash'] ?? 0;
                            $selicCashEarnings = $data['selic_cash_earnings'] ?? 0;
                            $selicCashInjected = $data['selic_cash_injected'] ?? 0;
                            $selicCashInfoJson = htmlspecialchars(json_encode([
                                'selic_cash'          => $selicCashValue,
                                'selic_cash_earnings' => $selicCashEarnings,
                                'selic_cash_injected' => $selicCashInjected
                            ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-main" style="color: var(--text-main) !important;"><?php echo $dateLabel; ?></td>
                                <td class="fw-bold text-primary" style="color: var(--primary) !important;"><?php echo formatCurrency($currentValue, $portfolio['output_currency']); ?></td>
                                <td>
                            <span class="badge <?php echo $variation >= 0 ? 'bg-soft-success' : 'bg-soft-danger'; ?>">
                                <?php echo ($variation >= 0 ? '+' : '') . number_format($variation, 2, ',', '.'); ?>%
                            </span>
                                </td>
                                <td class="text-center">
                                    <?php echo $rebalanced ? '<span class="badge rounded-pill bg-soft-info">Rebalanced</span>' : '<span class="text-muted small">Mantido</span>'; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($depositMade > 0): ?>
                                        <?php
                                        $depositTypeLabels = [
                                            'monthly'    => 'Aporte Periódico',
                                            'strategic'  => 'Aporte Estratégico',
                                            'smart'      => 'Aporte Direcionado ao Alvo',
                                            'selic_cash' => 'Aporte em Caixa SELIC',
                                        ];
                                        $depositLabel = $depositTypeLabels[$depositType] ?? 'Aporte';
                                        ?>
                                        <span class="badge bg-soft-success text-success small" title="<?php echo $depositLabel; ?>">
                                    <i class="bi bi-cash-coin me-1"></i>
                                    <?php echo formatCurrency($depositMade, $portfolio['output_currency']); ?>
                                </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($portfolio['simulation_type'] === 'selic_cash_deposit'): ?>
                                <td class="text-center">
                                    <?php if ($selicCashInjected > 0): ?>
                                        <span class="badge bg-soft-primary text-primary small"
                                              title="Caixa SELIC investido no rebalanceamento">
                                            <i class="bi bi-arrow-right-circle me-1"></i>
                                            <?= formatCurrency($selicCashInjected, $portfolio['output_currency']) ?>
                                            <span class="d-block" style="font-size:0.7em; opacity:0.8;">investido</span>
                                        </span>
                                    <?php elseif ($selicCashValue > 0): ?>
                                        <span class="fw-bold text-secondary small">
                                            <?= formatCurrency($selicCashValue, $portfolio['output_currency']) ?>
                                        </span>
                                        <?php if ($selicCashEarnings > 0): ?>
                                            <div class="text-success" style="font-size:0.78em;">
                                                +<?= formatCurrency($selicCashEarnings, $portfolio['output_currency']) ?>&nbsp;<abbr title="Rendimento SELIC do mês">SELIC</abbr>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-link text-decoration-none"
                                            onclick='openDetailsModal("<?= $dateLabel ?>", <?= $assetValuesJson ?>, <?= $currentValue ?>, <?= $tradesJson ?>, <?= $depositInfoJson ?>, <?= $selicCashInfoJson ?>)'>
                                        Ver Ativos <i class="bi bi-chevron-right ms-1"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php $prevValue = $currentValue; endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="background-color: var(--bg-card) !important; color: var(--text-main) !important;">
                <div class="modal-header" style="border-bottom-color: var(--border-color) !important;">
                    <h5 class="modal-title fw-bold" style="color: var(--text-main) !important;">Composição: <span id="modalDate"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--close-btn-filter, none);"></button>
                </div>
                <div class="modal-body p-0" style="background-color: var(--bg-card) !important; color: var(--text-main) !important;">
                    <div class="p-3 border-bottom bg-light-subtle" style="background-color: var(--bg-body) !important; border-bottom-color: var(--border-color) !important;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="text-muted smaller text-uppercase fw-bold d-block mb-1" style="color: var(--text-muted) !important;">Saldo Total</span>
                                    <div class="fw-bold text-primary fs-5" id="modalTotal" style="color: var(--primary) !important;"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2" id="modalDepositSection" style="display: none;">
                                    <span class="text-muted smaller text-uppercase fw-bold d-block mb-1" style="color: var(--text-muted) !important;">Aporte Realizado</span>
                                    <div class="fw-bold text-success" id="modalDeposit"></div>
                                    <div class="text-muted small" id="modalDepositType" style="color: var(--text-muted) !important;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <table class="table mb-0" style="color: var(--text-main) !important;">
                        <thead class="table-light">
                        <tr style="background-color: var(--bg-body) !important; color: var(--text-main) !important;">
                            <th class="ps-4" style="background: transparent !important; color: inherit !important; border-bottom-color: var(--border-color) !important;">Ativo</th>
                            <th class="text-end" style="background: transparent !important; color: inherit !important; border-bottom-color: var(--border-color) !important;">Alocação</th>
                            <th class="text-end pe-4" style="background: transparent !important; color: inherit !important; border-bottom-color: var(--border-color) !important;">Saldo</th>
                        </tr>
                        </thead>
                        <tbody id="modalAssetsBody" class="text-main" style="border-top: none !important;"></tbody>
                    </table>
                </div>
                <div class="modal-footer bg-light" style="background-color: var(--bg-body) !important; border-top-color: var(--border-color) !important; color: var(--text-main) !important;">
                    <div class="w-100 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small" style="color: var(--text-muted) !important;">Rebalanceamento: </span>
                            <span class="badge bg-soft-info small" id="modalRebalanceStatus"></span>
                        </div>
                        <div>
                            <strong style="color: var(--text-main) !important;">Total Ativos:</strong>
                            <strong class="text-primary ms-2" id="modalTotalAssets" style="color: var(--primary) !important;"></strong>
                        </div>
                    </div>
                </div>
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

        // Remove metadados do log de auditoria para gráficos
        const auditLog = { ...chartData.audit_log };
        delete auditLog._metadata;

        // ============================================================
        // Helpers: formatação de eixo X e tooltip de período
        // ============================================================

        /**
         * Converte data ISO (YYYY-MM-DD) para rótulo compacto MM/AA.
         * Ex.: "2026-01-31" → "01/26"
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
                'Variação: ' + prevDay + '/' + prevMm + '/' + prevYy + ' → ' + endDay + '/' + mm + '/' + yy
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
                                return `${context.dataset.label}: ${context.raw.toFixed(4)}%`;
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
                                return `Retorno: ${context.raw.toFixed(4)}%`;
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
                                return `Retorno Real: ${context.raw.toFixed(4)}%`;
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

        // Funções do Modal, Busca e CSV
        function openDetailsModal(dateLabel, assetValues, totalValue, trades, depositInfo) {
            document.getElementById('modalDate').innerText = dateLabel;
            const body = document.getElementById('modalAssetsBody');
            body.innerHTML = '';

            const isRebalanceMonth = Object.keys(trades).length > 0;
            const hasDeposit = depositInfo && depositInfo.amount > 0;
            let totalAssetsValue = 0;

            // Configura seção de aportes
            const depositSection = document.getElementById('modalDepositSection');
            if (hasDeposit) {
                depositSection.style.display = 'block';
                document.getElementById('modalDeposit').innerText =
                    new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(depositInfo.amount);
                document.getElementById('modalDepositType').innerText =
                    {
                        'monthly':    'Aporte Periódico',
                        'strategic':  'Aporte Estratégico',
                        'smart':      'Aporte Direcionado ao Alvo',
                        'selic_cash': 'Aporte em Caixa SELIC'
                    }[depositInfo.type] || 'Aporte';
            } else {
                depositSection.style.display = 'none';
            }

            // Status de rebalanceamento
            document.getElementById('modalRebalanceStatus').innerText =
                isRebalanceMonth ? 'REALIZADO' : 'NÃO REALIZADO';

            // Preenche tabela de ativos
            for (const [id, value] of Object.entries(assetValues)) {
                const name = assetNames[id] || id;
                const target = assetTargets[id] || 0;
                let finalVal = value;
                let allocationPercent = totalValue > 0 ? (value / totalValue) * 100 : 0;

                if (isRebalanceMonth && trades[id]) {
                    finalVal = trades[id].post_value;
                    allocationPercent = totalValue > 0 ? (finalVal / totalValue) * 100 : 0;
                }

                totalAssetsValue += finalVal;

                let rebalanceInfo = '';
                if (isRebalanceMonth && trades[id]) {
                    const delta = trades[id].delta;
                    const sign = delta >= 0 ? '+' : '';
                    rebalanceInfo = `<div class="text-muted small" style="color: var(--text-muted) !important;">Ajuste: ${sign}${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(delta)}</div>`;
                }

                body.innerHTML += `<tr>
                <td class="ps-4" style="border-bottom-color: var(--border-color) !important;">
                    <div class="fw-bold text-main" style="color: var(--text-main) !important;">${name}</div>
                    <div class="text-muted smaller" style="color: var(--text-muted) !important;">Meta: ${target.toFixed(2)}%</div>
                    ${rebalanceInfo}
                </td>
                <td class="text-end align-middle" style="border-bottom-color: var(--border-color) !important;">
                    <div class="fw-bold text-primary" style="color: var(--primary) !important;">${allocationPercent.toFixed(2)}%</div>
                </td>
                <td class="text-end pe-4 align-middle" style="border-bottom-color: var(--border-color) !important;">
                    <strong class="text-main" style="color: var(--text-main) !important;">${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(finalVal)}</strong>
                </td>
            </tr>`;
            }

            document.getElementById('modalTotal').innerText =
                new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(totalValue);
            document.getElementById('modalTotalAssets').innerText =
                new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(totalAssetsValue);

            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }

        document.getElementById('auditSearch').addEventListener('keyup', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#auditTable tbody tr').forEach(r => {
                const rowText = r.innerText.toLowerCase();
                r.style.display = rowText.includes(q) ? '' : 'none';
            });
        });

        function exportAuditToCSV() {
            if (!window.simulationAuditLog) {
                alert("Dados de auditoria não disponíveis.");
                return;
            }

            const auditLog = window.simulationAuditLog;
            const assetsInfo = <?php echo json_encode($assets); ?>;
            const currency = '<?php echo $portfolio['output_currency']; ?>';
            const portfolioName = '<?php echo addslashes($portfolio['name']); ?>';
            const initialCapitalValue = <?php echo $portfolio['initial_capital']; ?>;
            const assetMap = {};
            assetsInfo.forEach(a => assetMap[a.asset_id] = a.name);

            // Coletar todos os IDs de ativos únicos presentes no log
            const allAssetIds = new Set();
            Object.values(auditLog).forEach(data => {
                if (data.asset_values) {
                    Object.keys(data.asset_values).forEach(id => allAssetIds.add(id));
                }
            });
            const assetIds = Array.from(allAssetIds);

            // Metadados do Portfólio
            let csv = ["\uFEFF" + `Relatório de Auditoria: ${portfolioName}`];
            csv.push(`Moeda: ${currency}`);
            csv.push(`Capital Inicial: ${initialCapitalValue.toFixed(2).replace('.', ',')}`);
            csv.push(""); // Linha em branco

            // Cabeçalhos
            let headers = [
                "Data", 
                "Saldo Total", 
                "Variação Mensal (%)", 
                "Aporte do Mês", 
                "Aporte Acumulado",
                "Patrimônio Líquido (sem aportes)",
                "ROI Estratégia Puro (%)",
                "Câmbio (USD/BRL)",
                "Status Rebal.",
                "Caixa SELIC",
                "Rendimento SELIC do Mês",
                "Caixa SELIC Investido (Rebal.)"
            ];
            
            assetIds.forEach(id => {
                const name = assetMap[id] || id;
                headers.push(`${name} - Cotação`);
                headers.push(`${name} - Qtd`);
                headers.push(`${name} - Valor`);
                headers.push(`${name} - %`);
                headers.push(`${name} - Compra Aporte (Qtd)`);
                headers.push(`${name} - Qtd Antes Rebal.`);
                headers.push(`${name} - Qtd Depois Rebal.`);
                headers.push(`${name} - Delta Qtd Rebal.`);
                headers.push(`${name} - Valor Antes Rebal.`);
                headers.push(`${name} - Valor Depois Rebal.`);
                headers.push(`${name} - Delta Valor Rebal.`);
            });

            csv.push(headers.join(";"));

            // Ordenar datas
            const dates = Object.keys(auditLog).filter(d => d !== '_metadata').sort();

            let prevTotalValue = initialCapitalValue;

            dates.forEach(date => {
                const data = auditLog[date];
                const dateObj = new Date(date);
                const dateLabel = dateObj.toLocaleDateString('pt-BR', {month: '2-digit', year: 'numeric'});
                
                const totalValue = data.total_value.toFixed(2).replace('.', ',');
                
                // Cálculo de variação mensal real (incluindo aporte no saldo final)
                const variation = (((data.total_value / prevTotalValue) - 1) * 100).toFixed(2).replace('.', ',');
                
                const deposit = (data.deposit_made || 0).toFixed(2).replace('.', ',');
                const totalDepositsToDate = (data.total_deposits_to_date || 0).toFixed(2).replace('.', ',');
                const strategyValue = (data.strategy_value || 0).toFixed(2).replace('.', ',');
                const strategyVariation = (data.strategy_variation || 0).toFixed(2).replace('.', ',');
                const fxRate = data.fx_rate ? data.fx_rate.toFixed(4).replace('.', ',') : "-";
                const status = data.rebalanced ? "Rebalanceado" : "Mantido";
                const selicCash        = (data.selic_cash || 0).toFixed(2).replace('.', ',');
                const selicEarnings    = (data.selic_cash_earnings || 0).toFixed(2).replace('.', ',');
                const selicInjected    = (data.selic_cash_injected || 0).toFixed(2).replace('.', ',');

                let line = [
                    dateLabel, 
                    totalValue, 
                    variation, 
                    deposit, 
                    totalDepositsToDate, 
                    strategyValue, 
                    strategyVariation,
                    fxRate,
                    status,
                    selicCash,
                    selicEarnings,
                    selicInjected
                ];

                assetIds.forEach(id => {
                    const val = data.asset_values[id] || 0;
                    const price = (data.asset_prices && data.asset_prices[id]) ? data.asset_prices[id] : 0;
                    const qty = (data.asset_quantities && data.asset_quantities[id]) ? data.asset_quantities[id] : 0;
                    const percent = ((val / data.total_value) * 100).toFixed(2).replace('.', ',');
                    
                    const trade = (data.trades && data.trades[id]) ? data.trades[id] : null;
                    const purchase = (data.deposit_details && data.deposit_details[id]) ? data.deposit_details[id] : null;
                    
                    line.push(price.toFixed(4).replace('.', ','));
                    line.push(qty.toFixed(6).replace('.', ','));
                    line.push(val.toFixed(2).replace('.', ','));
                    line.push(percent);
                    
                    line.push(purchase ? purchase.quantity.toFixed(6).replace('.', ',') : "0");
                    
                    line.push(trade ? trade.pre_quantity.toFixed(6).replace('.', ',') : "-");
                    line.push(trade ? trade.post_quantity.toFixed(6).replace('.', ',') : "-");
                    line.push(trade ? trade.delta_quantity.toFixed(6).replace('.', ',') : "-");
                    
                    line.push(trade ? trade.pre_value.toFixed(2).replace('.', ',') : "-");
                    line.push(trade ? trade.post_value.toFixed(2).replace('.', ',') : "-");
                    line.push(trade ? trade.delta.toFixed(2).replace('.', ',') : "-");
                });

                csv.push(line.join(";"));
                prevTotalValue = data.total_value;
            });

            const blob = new Blob([csv.join("\n")], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `Auditoria_Completa_<?php echo $portfolio['id']; ?>_<?php echo date('Y-m-d'); ?>.csv`;
            link.click();
        }

        // 1. Define o log de auditoria globalmente para o cálculo do Beta
        window.simulationAuditLog = auditLog || {};

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
            const symbols = { 'BRL': 'R$', 'USD': '$', 'EUR': '€' };
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
                                label += context.parsed.y.toFixed(4) + '%';
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