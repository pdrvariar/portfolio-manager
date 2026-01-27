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
                    <?php if ($portfolio['simulation_type'] == 'monthly_deposit'): ?>
                        <span class="badge bg-info bg-soft">Aporte Periódico</span>
                    <?php elseif ($portfolio['simulation_type'] == 'strategic_deposit'): ?>
                        <span class="badge bg-warning bg-soft">Aporte Estratégico</span>
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
                    <a href="/index.php?url=<?= obfuscateUrl('portfolio/delete/' . $portfolio['id']) ?>"
                       class="btn btn-outline-danger"
                       title="Excluir"
                       onclick="return confirm('Tem certeza que deseja excluir esta estratégia?')">
                        <i class="bi bi-trash"></i>
                    </a>
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
                <?php if ($portfolio['simulation_type'] == 'monthly_deposit'): ?>
                    <strong>Aporte Periódico:</strong>
                    <?php echo formatCurrency($portfolio['deposit_amount'], $portfolio['deposit_currency'] ?? 'BRL'); ?>
                    a cada <?php echo $portfolio['deposit_frequency']; ?>
                    <?php if ($portfolio['deposit_currency'] != $portfolio['output_currency']): ?>
                        (convertido para <?php echo $portfolio['output_currency']; ?> no momento do aporte)
                    <?php endif; ?>
                <?php elseif ($portfolio['simulation_type'] == 'strategic_deposit'): ?>
                    <strong>Aporte Estratégico:</strong>
                    Se o portfólio cair <?php echo number_format($portfolio['strategic_threshold'], 1); ?>% em um mês,
                    será aportado <?php echo number_format($portfolio['strategic_deposit_percentage'], 1); ?>% do valor atual.
                <?php endif; ?>
            </p>
        </div>
        <span class="badge bg-soft-info text-info rounded-pill px-3 py-2 smaller fw-bold ms-3">
        <?php echo strtoupper($portfolio['simulation_type']); ?>
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

    <div class="row g-3 mb-4">
        <?php
        // Adiciona métricas específicas para simulações com aportes
        $hasDeposits = isset($metrics['total_deposits']) && $metrics['total_deposits'] > 0;

        $metricsList = [
                ['label' => 'Retorno Total', 'val' => formatPercentage($metrics['total_return']), 'class' => 'border-primary', 'text' => $metrics['total_return'] >= 0 ? 'text-success' : 'text-danger'],
                [
                        'label' => ($metrics['is_short_period'] ?? false) ? 'Retorno no Período' : 'CAGR (Anual)',
                        'val'   => formatPercentage($metrics['annual_return']),
                        'class' => 'border-success',
                        'text'  => 'text-success'
                ],
                ['label' => 'Volatilidade', 'val' => formatPercentage($metrics['volatility']), 'class' => 'border-warning', 'text' => 'text-dark'],
                ['label' => 'Sharpe Ratio', 'val' => number_format($metrics['sharpe_ratio'], 2), 'class' => 'border-info', 'text' => 'text-dark']
        ];

        // Adiciona métrica de ROI se houver aportes
        if ($hasDeposits) {
            $metricsList[] = [
                    'label' => 'ROI (com aportes)',
                    'val' => formatPercentage($metrics['roi'] ?? 0),
                    'class' => 'border-success',
                    'text' => ($metrics['roi'] ?? 0) >= 0 ? 'text-success' : 'text-danger'
            ];
        }

        foreach ($metricsList as $m): ?>
            <div class="col-md-3">
                <div class="card metric-card shadow-sm h-100 border-start border-4 <?php echo $m['class']; ?>">
                    <div class="card-body">
                        <h6 class="text-muted small text-uppercase fw-bold"><?php echo $m['label']; ?></h6>
                        <h3 class="<?php echo $m['text']; ?> fw-bold mb-0"><?php echo $m['val']; ?></h3>
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
                    <h5 class="mb-0 fw-bold">Evolução do Patrimônio</h5>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($hasDeposits): ?>
                            <div class="d-flex align-items-center gap-2 border-end pe-3">
                            <span class="text-success">
                                <i class="bi bi-cash-stack me-1"></i>
                                Total Aportado: <?php echo formatCurrency($metrics['total_deposits'], $portfolio['output_currency']); ?>
                            </span>
                            </div>
                        <?php endif; ?>

                        <div id="betaContainer" style="display: none;">
                        <span class="badge bg-soft-dark text-dark border px-3 py-2 rounded-pill" title="Risco Relativo (Beta)">
                            Beta: <span id="betaValue" class="fw-bold">--</span>
                        </span>
                        </div>

                        <div class="d-flex align-items-center gap-2 border-start ps-3">
                            <label class="smaller text-muted fw-bold">Comparar com:</label>
                            <select class="form-select form-select-sm border-0 bg-light shadow-none" id="benchmarkSelector" style="width: 250px;">
                                <option value="">Nenhum</option>
                                <?php
                                $assetModel = new Asset();
                                $allAssets = $assetModel->getAllWithDetails();

                                $pStart = $portfolio['start_date'];
                                $pEnd = $latest ? $latest['simulation_date'] : ($portfolio['end_date'] ?? date('Y-m-d'));

                                foreach ($allAssets as $b):
                                    $isValid = ($b['min_date'] <= $pStart && (empty($b['max_date']) || $b['max_date'] >= $pEnd));
                                    ?>
                                    <option value="<?= $b['id'] ?>" <?= !$isValid ? 'disabled' : '' ?>>
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

    <!-- NOVO: Gráfico de Performance da Estratégia (sem aportes) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Performance Real da Estratégia</h5>
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
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Evolução dos Juros</h5>
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
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Histórico de Aportes</h5>
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
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Composição Histórica</h5></div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="compositionChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Retorno por Ano</h5></div>
                <div class="card-body"><div class="chart-container" style="height: 300px;"><canvas id="returnsChart"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Auditoria Mensal</h5>
            <div class="d-flex gap-2">
                <input type="text" id="auditSearch" class="form-control form-control-sm" placeholder="Buscar data..." style="width: 180px;">
                <button onclick="exportAuditToCSV()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive-audit">
                <table class="table table-hover align-middle mb-0" id="auditTable">
                    <thead class="sticky-top-table">
                    <tr>
                        <th class="ps-4">Mês/Ano</th>
                        <th>Saldo</th>
                        <th>Variação</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aportes</th>
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
                            $variation = (($currentValue / $prevValue) - 1) * 100;
                            $rebalanced = $data['rebalanced'] ?? false;
                            $depositMade = $data['deposit_made'] ?? 0;
                            $depositType = $data['deposit_type'] ?? 'none';

                            $dateLabel = date('m/Y', strtotime($date));
                            $assetValuesJson = htmlspecialchars(json_encode($data['asset_values']), ENT_QUOTES, 'UTF-8');
                            $tradesJson = htmlspecialchars(json_encode($data['trades'] ?? []), ENT_QUOTES, 'UTF-8');
                            $depositInfoJson = htmlspecialchars(json_encode(['amount' => $depositMade, 'type' => $depositType]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo $dateLabel; ?></td>
                                <td><?php echo formatCurrency($currentValue, $portfolio['output_currency']); ?></td>
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
                                        <span class="badge bg-soft-success text-success small" title="<?php echo $depositType == 'monthly' ? 'Aporte Periódico' : 'Aporte Estratégico'; ?>">
                                    <i class="bi bi-cash-coin me-1"></i>
                                    <?php echo formatCurrency($depositMade, $portfolio['output_currency']); ?>
                                </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-link text-decoration-none"
                                            onclick='openDetailsModal("<?= $dateLabel ?>", <?= $assetValuesJson ?>, <?= $currentValue ?>, <?= $tradesJson ?>, <?= $depositInfoJson ?>)'>
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
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Composição: <span id="modalDate"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="text-muted small">Saldo Total:</span>
                                    <div class="fw-bold text-primary fs-5" id="modalTotal"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2" id="modalDepositSection" style="display: none;">
                                    <span class="text-muted small">Aporte Realizado:</span>
                                    <div class="fw-bold text-success" id="modalDeposit"></div>
                                    <div class="text-muted small" id="modalDepositType"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <table class="table mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="ps-4">Ativo</th>
                            <th class="text-end">Alocação</th>
                            <th class="text-end pe-4">Saldo</th>
                        </tr>
                        </thead>
                        <tbody id="modalAssetsBody"></tbody>
                    </table>
                </div>
                <div class="modal-footer bg-light">
                    <div class="w-100 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small">Rebalanceamento: </span>
                            <span class="badge bg-soft-info small" id="modalRebalanceStatus"></span>
                        </div>
                        <div>
                            <strong>Total Ativos:</strong>
                            <strong class="text-primary ms-2" id="modalTotalAssets"></strong>
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

        window.valueChart = new Chart(document.getElementById('valueChart'), {
            type: 'line',
            data: chartData.value_chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Valor: ${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(ctx.raw)}`
                        }
                    }
                },
                scales: {
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
                                return `${context.dataset.label}: ${context.raw.toFixed(2)}%`;
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
                                return `Retorno: ${context.raw.toFixed(2)}%`;
                            }
                        }
                    }
                }
            }
        });

        <?php if ($hasDeposits && isset($chartData['audit_log'])): ?>
        // Gráfico de Aportes
        const depositDates = [];
        const depositAmounts = [];
        const portfolioValues = [];

        Object.entries(auditLog).forEach(([date, data]) => {
            depositDates.push(date);
            depositAmounts.push(data.deposit_made || 0);
            portfolioValues.push(data.total_value);
        });

        new Chart(document.getElementById('depositsChart'), {
            type: 'bar',
            data: {
                labels: depositDates.map(d => new Date(d).toLocaleDateString('pt-BR', {month: 'short', year: 'numeric'})),
                datasets: [
                    {
                        label: 'Valor do Portfólio',
                        data: portfolioValues,
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
                    depositInfo.type === 'monthly' ? 'Aporte Periódico' : 'Aporte Estratégico';
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
                    rebalanceInfo = `<div class="text-muted small">Ajuste: ${sign}${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(delta)}</div>`;
                }

                body.innerHTML += `<tr>
                <td class="ps-4">
                    <div class="fw-bold text-dark">${name}</div>
                    <div class="text-muted small">Meta: ${target.toFixed(2)}%</div>
                    ${rebalanceInfo}
                </td>
                <td class="text-end align-middle">
                    <div class="fw-bold text-primary">${allocationPercent.toFixed(2)}%</div>
                </td>
                <td class="text-end pe-4 align-middle">
                    <strong>${new Intl.NumberFormat('pt-BR').format(finalVal)}</strong>
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
            const headers = ["Referência", "Saldo", "Variação (%)", "Status", "Aportes"];
            let csv = [headers.join(";")];

            document.querySelectorAll("#auditTable tbody tr").forEach(row => {
                const cols = row.querySelectorAll("td");
                if (cols.length >= 5) {
                    const reference = `"${cols[0].innerText}"`;
                    const balance = `"${cols[1].innerText.replace(/[^\d,-]/g,'')}"`;
                    const variation = `"${cols[2].innerText.replace(/[^\d,-]/g,'')}"`;
                    const status = `"${cols[3].innerText}"`;
                    const deposits = `"${cols[4].innerText.replace(/[^\d,-]/g,'')}"`;
                    csv.push([reference, balance, variation, status, deposits].join(";"));
                }
            });

            const blob = new Blob(["\uFEFF" + csv.join("\n")], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `Auditoria_<?php echo $portfolio['id']; ?>_<?php echo date('Y-m-d'); ?>.csv`;
            link.click();
        }

        // 1. Define o log de auditoria globalmente para o cálculo do Beta
        window.simulationAuditLog = auditLog || {};

        document.getElementById('benchmarkSelector').addEventListener('change', function() {
            const assetId = this.value;
            const chart = window.valueChart;

            if (!chart) return;

            // Remove benchmark anterior
            if (chart.data.datasets.length > 1) {
                chart.data.datasets.pop();
                chart.update();
                document.getElementById('betaContainer').style.display = 'none';
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
                    const portfolioReturns = Object.values(window.simulationAuditLog).map((m, i, arr) => {
                        if (i === 0) return 0;
                        const prevValue = Object.values(arr)[i-1].total_value;
                        return (m.total_value / prevValue) - 1;
                    });

                    const beta = calculateBeta(portfolioReturns, res.returns);
                    document.getElementById('betaValue').innerText = beta.toFixed(2);
                    document.getElementById('betaContainer').style.display = 'block';

                    // Adiciona a linha ao gráfico
                    chart.data.datasets.push({
                        label: 'Benchmark: ' + this.options[this.selectedIndex].text,
                        data: res.values,
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
        new Chart(document.getElementById('strategyPerformanceChart'), {
            type: 'line',
            data: chartData.strategy_performance_chart,
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
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(2) + '%';
                                return label;
                            }
                        }
                    }
                },
                scales: {
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
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
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
            document.querySelectorAll('.allocation-input').forEach(input => {
                input.addEventListener('input', updateAllocationTotal);
                input.addEventListener('change', updateAllocationTotal);
            });
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