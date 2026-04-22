<?php
/**
 * @var array  $portfolio   Dados do portfÃ³lio
 * @var array  $simulations Lista das Ãºltimas 10 simulaÃ§Ãµes (com snapshot)
 */

$title = 'HistÃ³rico de SimulaÃ§Ãµes â€” ' . htmlspecialchars($portfolio['name']);`n$meta_robots = 'noindex, nofollow';

$breadcrumbs = [
    ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=' . obfuscateUrl('dashboard')],
    ['label' => 'PortfÃ³lios', 'url' => '/index.php?url=' . obfuscateUrl('portfolio')],
    ['label' => htmlspecialchars($portfolio['name']), 'url' => '/index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolio['id'])],
    ['label' => 'HistÃ³rico', 'url' => '#'],
];

ob_start();

$freqLabels = [
    'never'    => 'Nunca',    'monthly'  => 'Mensal',
    'quarterly'=> 'Trimestral','biannual' => 'Semestral','annual'   => 'Anual',
];
$simTypeLabels = [
    'standard'           => 'PadrÃ£o (sem aportes)',
    'monthly_deposit'    => 'Aporte periÃ³dico',
    'strategic_deposit'  => 'Aporte estratÃ©gico',
    'smart_deposit'      => 'Aporte inteligente',
    'selic_cash_deposit' => 'Reserva Selic + Aporte',
];
$rebalTypeLabels = [
    'full'    => 'Rebalanceamento total',
    'partial' => 'Rebalanceamento parcial',
    'none'    => 'Sem rebalanceamento',
];
?>

<!-- â”€â”€ CabeÃ§alho â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i>
            HistÃ³rico de SimulaÃ§Ãµes
        </h2>
        <p class="text-muted small mb-0 mt-1">
            Ãšltimas simulaÃ§Ãµes de <strong><?= htmlspecialchars($portfolio['name']) ?></strong>.
            Expanda cada linha para ver a configuraÃ§Ã£o exata â€” e reproduza a melhor quando quiser.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>"
           class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-play-fill me-1"></i> Nova SimulaÃ§Ã£o
        </a>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>"
           class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<?php if (empty($simulations)): ?>
<!-- â”€â”€ Estado Vazio â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="card shadow-sm border-0 rounded-4 text-center py-5">
    <div class="card-body">
        <i class="bi bi-bar-chart-line text-muted mb-3 d-block" style="font-size:3.5rem;opacity:.35;"></i>
        <h5 class="fw-bold text-muted">Nenhuma simulaÃ§Ã£o encontrada</h5>
        <p class="text-muted small mb-4">Execute a primeira simulaÃ§Ã£o para comeÃ§ar a comparar resultados.</p>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>"
           class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-play-fill me-1"></i> Executar SimulaÃ§Ã£o
        </a>
    </div>
</div>

<?php else:
    $best     = $simulations[0];
    foreach ($simulations as $s) {
        if ((float)$s['sharpe_ratio'] > (float)$best['sharpe_ratio']) $best = $s;
    }
    $currency = $portfolio['output_currency'] ?? 'BRL';

    $snapshotsJs = [];
    $metricsJs   = [];
    foreach ($simulations as $sim) {
        $pc = $sim['portfolio_config'] ? json_decode($sim['portfolio_config'], true) : null;
        $ac = $sim['assets_config']    ? json_decode($sim['assets_config'],    true) : null;
        $snapshotsJs[$sim['id']] = ['portfolio' => $pc, 'assets' => $ac];
        $metricsJs[$sim['id']] = [
            'total_invested'          => $sim['total_invested']          ?? null,
            'total_deposits'          => $sim['total_deposits']          ?? null,
            'total_value'             => $sim['total_value']             ?? null,
            'interest_earned'         => $sim['interest_earned']         ?? null,
            'total_tax_paid'          => $sim['total_tax_paid']          ?? null,
            'roi'                     => $sim['roi']                     ?? null,
            'annual_return'           => $sim['annual_return']           ?? null,
            'strategy_annual_return'  => $sim['strategy_annual_return']  ?? null,
            'strategy_return'         => $sim['strategy_return']         ?? null,
            'volatility'              => $sim['volatility']              ?? null,
            'sharpe_ratio'            => $sim['sharpe_ratio']            ?? null,
            'max_drawdown'            => $sim['max_drawdown']            ?? null,
            'max_monthly_gain'        => $sim['max_monthly_gain']        ?? null,
            'max_monthly_loss'        => $sim['max_monthly_loss']        ?? null,
        ];
    }
?>

<!-- â”€â”€ Banner melhor simulaÃ§Ã£o â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="alert border-0 rounded-4 mb-4 shadow-sm d-flex align-items-center gap-3"
     style="background:linear-gradient(135deg,#e8f5e9 0%,#f1f8e9 100%);">
    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:46px;height:46px;">
        <i class="bi bi-trophy-fill text-white fs-5"></i>
    </div>
    <div class="flex-grow-1">
        <div class="fw-bold text-success mb-0" style="font-size:.9rem;">Melhor SimulaÃ§Ã£o Â· Ãndice Sharpe mais alto</div>
        <div class="text-muted small">
            Executada em <strong><?= date('d/m/Y H:i', strtotime($best['created_at'])) ?></strong> &nbsp;Â·&nbsp;
            Retorno anual: <strong><?= number_format((float)$best['annual_return'], 2, ',', '.') ?>%</strong> &nbsp;Â·&nbsp;
            Sharpe: <strong><?= number_format((float)$best['sharpe_ratio'], 2, ',', '.') ?></strong> &nbsp;Â·&nbsp;
            Drawdown mÃ¡x.: <strong>-<?= number_format(abs((float)$best['max_drawdown']), 2, ',', '.') ?>%</strong>
        </div>
    </div>
    <span class="badge bg-success rounded-pill px-3 py-2 fs-6 flex-shrink-0">#<?= $best['id'] ?></span>
</div>

<!-- â”€â”€ Dica de uso â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="alert border-0 rounded-3 mb-3 py-2 px-3 small d-flex align-items-center gap-2"
     style="background:#eef6fd;color:#374151;">
    <i class="bi bi-info-circle-fill flex-shrink-0" style="color:#3b82f6;"></i>
    Clique em <kbd><i class="bi bi-chevron-down"></i></kbd> ou em qualquer linha para ver a
    <strong>configuraÃ§Ã£o exata</strong> que foi usada naquela simulaÃ§Ã£o.
</div>

<!-- â”€â”€ Tabela â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="historyTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-3" style="width:4%">&nbsp;</th>
                        <th style="width:4%">#</th>
                        <th style="width:11%">Data Simulada</th>
                        <th style="width:11%">Executada em</th>
                        <th class="text-end" style="width:11%">Valor Final</th>
                        <th class="text-end" style="width:9%">Ret. Anual<br><small class="fw-normal text-muted">Com aportes</small></th>
                        <th class="text-end" style="width:9%">Ret. Anual<br><small class="fw-normal text-muted">EstratÃ©gia</small></th>
                        <th class="text-end" style="width:8%">Volatili-<br>dade</th>
                        <th class="text-end" style="width:7%">Sharpe</th>
                        <th class="text-end" style="width:9%">Drawdown<br>MÃ¡x.</th>
                        <th class="text-end" style="width:8%">ROI</th>
                        <th class="text-end pe-3" style="width:9%">Ganho Bruto</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($simulations as $sim):
                    $isBest = ($sim['id'] === $best['id']);
                    $annRet = (float)$sim['annual_return'];
                    $strRet = (float)($sim['strategy_annual_return'] ?? 0);
                    $vol    = (float)$sim['volatility'];
                    $sharpe = (float)$sim['sharpe_ratio'];
                    $dd     = abs((float)$sim['max_drawdown']);
                    $roi    = (float)($sim['roi'] ?? 0);
                    $gain   = (float)($sim['interest_earned'] ?? 0);
                    $final  = (float)$sim['total_value'];
                ?>
                <tr class="<?= $isBest ? 'table-success' : '' ?>" data-sim-id="<?= $sim['id'] ?>">
                    <td class="ps-3 text-center">
                        <button class="btn btn-sm btn-outline-secondary border-0 btn-expand rounded-circle p-0"
                                style="width:28px;height:28px;line-height:1;" title="Ver configuraÃ§Ã£o">
                            <i class="bi bi-chevron-down" style="font-size:.75rem;"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge <?= $isBest ? 'bg-success' : 'bg-light text-muted border' ?> rounded-pill px-2"><?= $sim['id'] ?></span>
                        <?php if ($isBest): ?><i class="bi bi-trophy-fill text-success ms-1" title="Melhor Sharpe"></i><?php endif; ?>
                    </td>
                    <td><span class="fw-medium text-dark small"><?= date('d/m/Y', strtotime($sim['simulation_date'])) ?></span></td>
                    <td>
                        <span class="text-muted small">
                            <?= date('d/m/Y', strtotime($sim['created_at'])) ?><br>
                            <span style="font-size:.7rem;"><?= date('H:i', strtotime($sim['created_at'])) ?></span>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-dark small"><?= formatCurrency($final, $currency) ?></td>
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
                    <td class="text-end pe-3 small fw-bold <?= $gain >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($gain >= 0 ? '+' : '') . formatCurrency($gain, $currency) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- â”€â”€ Legenda â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="card border-0 rounded-4 bg-light shadow-sm mt-3">
    <div class="card-body py-3 px-4">
        <h6 class="fw-bold mb-2 text-muted small text-uppercase" style="letter-spacing:.05em;">
            <i class="bi bi-info-circle me-1"></i> Guia de InterpretaÃ§Ã£o
        </h6>
        <div class="row g-2">
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mt-1 text-nowrap" style="font-size:.65rem;">Ret. EstratÃ©gia</span>
                    <span class="text-muted" style="font-size:.75rem;">Performance pura dos ativos, sem influÃªncia dos aportes.</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border mt-1" style="font-size:.65rem;">Sharpe</span>
                    <span class="text-muted" style="font-size:.75rem;">â‰¥ 1 = excelente Â· 0,5â€“1 = bom Â· < 0,5 = fraco. Retorno por unidade de risco.</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border mt-1" style="font-size:.65rem;">Drawdown</span>
                    <span class="text-muted" style="font-size:.75rem;">Maior queda. Quanto menor, mais estÃ¡vel a estratÃ©gia.</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border mt-1" style="font-size:.65rem;">ROI</span>
                    <span class="text-muted" style="font-size:.75rem;">Retorno sobre todo o capital aportado (inicial + periÃ³dicos).</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$snapshotsJsonPhp   = isset($snapshotsJs) ? json_encode($snapshotsJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) : '{}';
$metricsJsonPhp     = isset($metricsJs)   ? json_encode($metricsJs,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) : '{}';
$applySnapshotUrl   = isset($portfolio) ? obfuscateUrl('portfolio/apply-snapshot/' . $portfolio['id']) : '';
$csrfToken          = Session::getCsrfToken();
$csrfTokenJson      = json_encode($csrfToken);
$historyUrl         = isset($portfolio) ? obfuscateUrl('portfolio/history/' . $portfolio['id']) : '';

$additional_css = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* â”€â”€ Tabela â”€â”€ */
    #historyTable thead th { font-size:.78rem; font-weight:700; white-space:nowrap; vertical-align:middle; }
    #historyTable tbody td { font-size:.82rem; vertical-align:middle; }
    .table-success td      { background-color:rgba(25,135,84,.06) !important; }
    tr.dt-hasChild td      { background-color:rgba(13,110,253,.04) !important; }

    /* â”€â”€ Child row â€” light mode â”€â”€ */
    .child-config {
        background: var(--bg-body, #f8f9fa);
        border-radius: 12px;
        border: 1px solid var(--border-color, #dee2e6);
    }
    .child-config .child-config-title {
        color: var(--text-main, #212529);
    }
    .config-section-title {
        font-size:.7rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.08em; color:var(--text-muted, #6c757d); margin-bottom:.5rem;
    }
    .config-pill {
        display:inline-flex; align-items:center; gap:4px;
        background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #dee2e6);
        border-radius:20px; padding:3px 10px; font-size:.75rem; white-space:nowrap;
        color: var(--text-main, #212529);
    }
    .config-pill .text-muted { color: var(--text-muted, #6c757d) !important; }
    .config-pill strong      { color: var(--text-main, #212529); }

    /* CÃ©lulas da tabela interna de ativos */
    .child-config table td  { color: var(--text-main, #212529); }

    /* Barra de alocaÃ§Ã£o */
    .asset-bar-wrap { background: var(--border-color, #e9ecef); border-radius:6px; height:8px; overflow:hidden; }
    .asset-bar      { height:8px; border-radius:6px; }

    /* â”€â”€ DataTables child row background â”€â”€ */
    #historyTable tbody tr.child td { background-color: var(--bg-body, #f8f9fa) !important; padding:0 !important; }

    /* â”€â”€ Resumo dos Resultados â€” KPI Cards â”€â”€ */
    .result-summary-block {
        background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #dee2e6);
        border-radius: 10px;
        padding: 14px 16px 10px;
    }
    .result-group-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--text-muted, #6c757d);
        margin-bottom: 8px;
    }
    .result-kpi-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .result-kpi {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-width: 110px;
        flex: 1 1 110px;
        max-width: 160px;
        padding: 10px 12px 8px;
        border-radius: 10px;
        background: var(--bg-body, #f8f9fa);
        border: 1px solid var(--border-color, #e9ecef);
        gap: 2px;
    }
    .result-kpi-icon { font-size: 1rem; margin-bottom: 3px; }
    .result-kpi-label { font-size: .63rem; color: var(--text-muted, #6c757d); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; line-height: 1.2; }
    .result-kpi-value { font-size: .88rem; font-weight: 800; margin-top: 2px; line-height: 1.1; }

    /* Color variants */
    .result-kpi.kpi-primary { border-color: rgba(13,110,253,.2);  background: rgba(13,110,253,.05); }
    .result-kpi.kpi-info    { border-color: rgba(13,202,240,.25); background: rgba(13,202,240,.06); }
    .result-kpi.kpi-success { border-color: rgba(25,135,84,.2);   background: rgba(25,135,84,.05); }
    .result-kpi.kpi-danger  { border-color: rgba(220,53,69,.2);   background: rgba(220,53,69,.05); }

    /* Separator between summary and config */
    .result-summary-block + .d-flex { padding-top: 16px; border-top: 1px solid var(--border-color, #dee2e6); margin-top: 12px; }

    /* Dark mode overrides for KPI cards */
    [data-theme="dark"] .result-summary-block { background: var(--bg-body); }
    [data-theme="dark"] .result-kpi           { background: var(--bg-card); border-color: var(--border-color); }
    [data-theme="dark"] .result-kpi.kpi-primary { background: rgba(13,110,253,.1); }
    [data-theme="dark"] .result-kpi.kpi-info    { background: rgba(13,202,240,.08); }
    [data-theme="dark"] .result-kpi.kpi-success { background: rgba(25,135,84,.1); }
    [data-theme="dark"] .result-kpi.kpi-danger  { background: rgba(220,53,69,.1); }
</style>';

$additional_js = <<<ENDJS
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
const SNAPSHOTS          = {$snapshotsJsonPhp};
const METRICS            = {$metricsJsonPhp};
const APPLY_SNAPSHOT_URL = "/index.php?url={$applySnapshotUrl}";
const CSRF_TOKEN         = {$csrfTokenJson};
const HISTORY_URL        = "/index.php?url={$historyUrl}";
const FREQ_LABELS = {never:"Nunca",monthly:"Mensal",quarterly:"Trimestral",biannual:"Semestral",annual:"Anual"};
const SIM_TYPE_LABELS = {standard:"PadrÃ£o (sem aportes)",monthly_deposit:"Aporte periÃ³dico",strategic_deposit:"Aporte estratÃ©gico",smart_deposit:"Aporte inteligente",selic_cash_deposit:"Reserva Selic + Aporte"};
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

function buildChildRow(simId) {
    const data = SNAPSHOTS[simId];
    const m    = METRICS[simId] || {};

    /* â”€â”€ SeÃ§Ã£o: Resumo dos Resultados â”€â”€ */
    const cur = (data && data.portfolio && data.portfolio.output_currency) ? data.portfolio.output_currency : "BRL";

    function kpi(icon, label, value, colorClass, bgClass) {
        return '<div class="result-kpi '+( bgClass||"")+'">'+
               '<div class="result-kpi-icon '+( colorClass||"text-primary")+'"><i class="bi '+icon+'"></i></div>'+
               '<div class="result-kpi-label">'+label+'</div>'+
               '<div class="result-kpi-value '+(colorClass||"")+'">'+value+'</div>'+
               '</div>';
    }

    function pctBadge(val, alwaysSign) {
        if (val === null || val === undefined || val === "") return '<span class="text-muted">â€”</span>';
        const v = parseFloat(val);
        const sign = v >= 0 ? "+" : "";
        const cls  = v >= 0 ? "text-success" : "text-danger";
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

    const volColor   = vol  <= 10 ? "text-success" : (vol  <= 20 ? "text-warning" : "text-danger");
    const sharpeColor= sharpe >= 1 ? "text-success" : (sharpe >= 0.5 ? "text-warning" : "text-danger");
    const ddColor    = dd   <= 10 ? "text-success" : (dd   <= 25 ? "text-warning" : "text-danger");

    const summaryHtml =
        '<div class="result-summary-block mb-3">'+

        /* â”€â”€ Grupo PatrimÃ´nio â”€â”€ */
        '<div class="result-group-label"><i class="bi bi-wallet2 me-1"></i>PatrimÃ´nio</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-bank",        "Capital Inicial",   fmtCurrency(totalInvested,  cur), "text-primary",   "kpi-primary") +
            kpi("bi-plus-circle", "Total de Aportes",  fmtCurrency(totalDeposits,  cur), "text-info",      "kpi-info") +
            kpi("bi-graph-up-arrow","PatrimÃ´nio Final", fmtCurrency(totalValue,    cur), totalValue >= totalInvested ? "text-success" : "text-danger", totalValue >= totalInvested ? "kpi-success" : "kpi-danger") +
            kpi("bi-cash-stack",  "Ganho Bruto",       (interestEarned >= 0 ? "+" : "") + fmtCurrency(interestEarned, cur), interestEarned >= 0 ? "text-success" : "text-danger", interestEarned >= 0 ? "kpi-success" : "kpi-danger") +
            (taxPaid > 0 ? kpi("bi-receipt", "Imposto Pago", "âˆ’" + fmtCurrency(taxPaid, cur), "text-danger", "kpi-danger") : "") +
        '</div>'+

        /* â”€â”€ Grupo Performance â”€â”€ */
        '<div class="result-group-label mt-3"><i class="bi bi-bar-chart-line me-1"></i>Performance</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-percent",         "Retorno Anual<br><small style=\'font-size:.6rem;opacity:.7;\'>com aportes</small>",    pctBadge(m.annual_return),              annReturn >= 0 ? "text-success" : "text-danger") +
            kpi("bi-trophy",          "Retorno EstratÃ©gia<br><small style=\'font-size:.6rem;opacity:.7;\'>puro dos ativos</small>", pctBadge(m.strategy_annual_return), strReturn >= 0 ? "text-success" : "text-danger") +
            kpi("bi-tags",            "ROI Total",          (roi >= 0 ? "+" : "") + fmt(roi) + "%",           roi >= 0 ? "text-success" : "text-danger") +
        '</div>'+

        /* â”€â”€ Grupo Risco â”€â”€ */
        '<div class="result-group-label mt-3"><i class="bi bi-shield-exclamation me-1"></i>Risco</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-activity",        "Volatilidade",        fmt(vol) + "%",   volColor) +
            kpi("bi-speedometer2",    "Ãndice Sharpe",       fmt(sharpe),      sharpeColor) +
            kpi("bi-arrow-down-circle","Drawdown MÃ¡x.",      "âˆ’" + fmt(dd) + "%", ddColor) +
            kpi("bi-arrow-up-right",  "Melhor MÃªs",         "+" + fmt(maxGain) + "%", "text-success") +
            kpi("bi-arrow-down-left", "Pior MÃªs",           "âˆ’" + fmt(maxLoss) + "%", "text-danger") +
        '</div>'+

        '</div>';

    if (!data || !data.portfolio) {
        return '<div class="child-config p-3 m-2">'+summaryHtml+
               '<div class="p-3 text-muted small"><i class="bi bi-exclamation-circle me-1"></i>ConfiguraÃ§Ã£o nÃ£o disponÃ­vel (simulaÃ§Ã£o anterior Ã  ativaÃ§Ã£o desta funcionalidade).</div></div>';
    }
    const p = data.portfolio;
    const assets = data.assets || [];

    const genPills = [
        pill("bi-cash-coin",       "Capital inicial",  fmtCurrency(p.initial_capital, p.output_currency)),
        pill("bi-calendar3",       "PerÃ­odo",
             (p.start_date ? p.start_date.substring(0,7).split("-").reverse().join("/") : "?") +
             " > " + (p.end_date ? p.end_date.substring(0,7).split("-").reverse().join("/") : "Hoje")),
        pill("bi-currency-exchange","Moeda saÃ­da",     p.output_currency || "BRL"),
        pill("bi-arrow-repeat",    "Rebalanceamento",  FREQ_LABELS[p.rebalance_frequency] || p.rebalance_frequency || "-"),
        pill("bi-sliders",         "Tipo rebal.",      REBAL_TYPE_LABELS[p.rebalance_type] || p.rebalance_type || "-"),
    ];
    if (p.rebalance_margin) genPills.push(pill("bi-arrows-expand","Margem rebal.", fmt(p.rebalance_margin)+"%"));
    if (p.use_cash_assets_for_rebalance == 1) genPills.push('<span class="config-pill"><i class="bi bi-piggy-bank text-primary"></i><strong class="text-primary">Caixa no rebalanceamento</strong></span>');

    let depositHtml = "";
    if (p.simulation_type && p.simulation_type !== "standard") {
        const dPills = [ pill("bi-wallet2","Tipo", SIM_TYPE_LABELS[p.simulation_type] || p.simulation_type) ];
        if (p.deposit_amount) {
            dPills.push(pill("bi-plus-circle","Valor aporte", fmtCurrency(p.deposit_amount, p.deposit_currency)));
            dPills.push(pill("bi-calendar-check","FrequÃªncia", FREQ_LABELS[p.deposit_frequency] || p.deposit_frequency || "-"));
        }
        if (p.deposit_inflation_adjusted == 1) dPills.push('<span class="config-pill"><i class="bi bi-graph-up text-success"></i><strong class="text-success">Ajustado pela inflaÃ§Ã£o</strong></span>');
        if (p.strategic_threshold)          dPills.push(pill("bi-bullseye","Gatilho estratÃ©gico", fmt(p.strategic_threshold)+"%"));
        if (p.strategic_deposit_percentage) dPills.push(pill("bi-percent","% no gatilho", fmt(p.strategic_deposit_percentage)+"%"));
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet-fill me-1"></i>EstratÃ©gia de Aportes</div><div class="d-flex flex-wrap gap-2">'+dPills.join("")+'</div></div>';
    } else {
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet me-1"></i>Aportes</div><span class="config-pill text-muted"><i class="bi bi-dash-circle"></i> Sem aportes periÃ³dicos</span></div>';
    }

    let taxHtml = "";
    const TAX_GROUP_LABELS = {
        CRIPTOMOEDA: "Criptomoeda",
        ETF_US: "ETF (EUA)",
        ETF_BR: "ETF (BR)",
        RENDA_FIXA: "Renda Fixa",
        FUNDO_IMOBILIARIO: "Fundo ImobiliÃ¡rio"
    };
    const TAX_GROUPS_ORDER = ["CRIPTOMOEDA","ETF_US","ETF_BR","RENDA_FIXA","FUNDO_IMOBILIARIO"];
    // Try per-group rates first (new format), fallback to legacy single rate
    let taxRates = null;
    if (p.profit_tax_rates_json) {
        try { taxRates = (typeof p.profit_tax_rates_json === "string") ? JSON.parse(p.profit_tax_rates_json) : p.profit_tax_rates_json; } catch(e) {}
    }
    if (taxRates && typeof taxRates === "object" && Object.keys(taxRates).length > 0) {
        let taxPills = TAX_GROUPS_ORDER.map(function(group) {
            const rate = taxRates[group];
            if (rate === undefined || rate === null || rate === "") return null;
            return pill("bi-percent", TAX_GROUP_LABELS[group] || group, fmt(rate)+"%", "text-danger");
        }).filter(Boolean).join("");
        if (taxPills) {
            taxHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-receipt me-1"></i>Imposto de Renda</div><div class="d-flex flex-wrap gap-2">'+taxPills+'</div></div>';
        }
    } else if (p.profit_tax_rate) {
        taxHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-receipt me-1"></i>Imposto de Renda</div><div class="d-flex flex-wrap gap-2">'+pill("bi-percent","AlÃ­quota geral", fmt(p.profit_tax_rate)+"%","text-danger")+'</div></div>';
    }

    let assetsRows = "";
    assets.forEach(function(a, i) {
        const pct   = parseFloat(a.allocation_percentage || 0);
        const color = BAR_COLORS[i % BAR_COLORS.length];
        const margin = (a.rebalance_margin_down || a.rebalance_margin_up)
            ? '<span>Entre '+fmt(a.rebalance_margin_down||0)+'% Ã  '+fmt(a.rebalance_margin_up||0)+'%</span>'
            : "-";
        assetsRows +=
            '<tr>' +
            '<td style="font-size:.8rem;padding:5px 8px;"><span class="fw-bold">'+(a.name||"-")+'</span> <span class="text-muted" style="font-size:.72rem;">'+(a.code||"")+'</span></td>' +
            '<td style="font-size:.75rem;padding:5px 8px;" class="text-muted">'+(a.currency||"-")+'</td>' +
            '<td style="padding:5px 8px;min-width:160px;"><div class="d-flex align-items-center gap-2"><div class="asset-bar-wrap flex-grow-1"><div class="asset-bar" style="width:'+pct+'%;background:'+color+';"></div></div><strong style="font-size:.8rem;min-width:44px;text-align:right;">'+fmt(pct)+'%</strong></div></td>' +
            '<td style="font-size:.72rem;padding:5px 8px;" class="text-muted text-end">'+margin+'</td>' +
            '</tr>';
    });

    const assetsHtml = assets.length > 0
        ? '<div class="mt-3"><div class="config-section-title"><i class="bi bi-pie-chart-fill me-1"></i>ComposiÃ§Ã£o dos Ativos</div>' +
          '<table class="table table-borderless mb-0" style="background:transparent;">' +
          '<thead><tr>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Ativo</th>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Moeda</th>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">AlocaÃ§Ã£o</th>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;text-align:right;">Margem Rebal.</th>' +
          '</tr></thead><tbody>'+assetsRows+'</tbody></table></div>'
        : "";

    const applyBtn =
        '<div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3">' +
        '<div class="d-flex align-items-start gap-2">' +
        '<i class="bi bi-lightbulb-fill text-warning fs-5 flex-shrink-0 mt-1"></i>' +
        '<div>' +
        '<div class="fw-bold child-config-title" style="font-size:.85rem;">Quer repetir esta simulaÃ§Ã£o?</div>' +
        '<div class="text-muted" style="font-size:.75rem;">Aplica todos os parÃ¢metros e alocaÃ§Ãµes ao portfÃ³lio atual. Depois execute uma nova simulaÃ§Ã£o para comparar.</div>' +
        '</div></div>' +
        '<form method="POST" action="'+APPLY_SNAPSHOT_URL+'" onsubmit="return confirm(\'AtenÃ§Ã£o: isso irÃ¡ substituir as configuraÃ§Ãµes atuais do portfÃ³lio.\\n\\nCapital, perÃ­odo, aportes, rebalanceamento e ativos serÃ£o alterados.\\n\\nDeseja continuar?\')">' +
        '<input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'">' +
        '<input type="hidden" name="simulation_id" value="'+simId+'">' +
        '<button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm no-spinner">' +
        '<i class="bi bi-arrow-counterclockwise me-2"></i>Aplicar ao PortfÃ³lio' +
        '</button></form></div>';

    return '<div class="child-config p-3 m-2">' +

        /* â”€â”€ SeÃ§Ã£o 1: Resumo dos Resultados â”€â”€ */
        summaryHtml +

        /* â”€â”€ SeÃ§Ã£o 2: ConfiguraÃ§Ã£o â”€â”€ */
        '<div class="d-flex align-items-center gap-2 mb-3">' +
        '<i class="bi bi-clipboard-data text-primary fs-5"></i>' +
        '<span class="fw-bold child-config-title" style="font-size:.9rem;">ConfiguraÃ§Ã£o usada nesta simulaÃ§Ã£o</span>' +
        '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1" style="font-size:.68rem;">ID #'+simId+'</span>' +
        '</div>' +
        '<div class="config-section-title"><i class="bi bi-gear me-1"></i>ParÃ¢metros Gerais</div>' +
        '<div class="d-flex flex-wrap gap-2">'+genPills.join("")+'</div>' +
        depositHtml + taxHtml + assetsHtml + applyBtn +
        '</div>';
}

$(document).ready(function () {
    const table = $("#historyTable").DataTable({
        order: [[2, "desc"]],
        pageLength: 10,
        autoWidth: false,
        columnDefs: [
            { orderable: false, searchable: false, targets: [0] },
            { className: "text-end", targets: [4,5,6,7,8,9,10,11] }
        ],
        language: {
            sProcessing:"Processando...", sLengthMenu:"Mostrar _MENU_ registros",
            sZeroRecords:"Nenhuma simulaÃ§Ã£o encontrada", sEmptyTable:"Nenhum dado disponÃ­vel",
            sInfo:"Mostrando _START_ a _END_ de _TOTAL_ registros",
            sInfoEmpty:"Mostrando 0 a 0 de 0 registros",
            sInfoFiltered:"(filtrado de _MAX_ no total)",
            sSearch:"Pesquisar:", sSearchPlaceholder:"Buscar simulaÃ§Ã£o...",
            oPaginate:{
                sFirst:'<i class="bi bi-chevron-bar-left"></i>',
                sPrevious:'<i class="bi bi-chevron-left"></i>',
                sNext:'<i class="bi bi-chevron-right"></i>',
                sLast:'<i class="bi bi-chevron-bar-right"></i>'
            }
        },
        dom:"<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-sm-end'f>><'row'<'col-12'tr>><'row mt-3 align-items-center'<'col-sm-5 text-muted small'i><'col-sm-7'p>>"
    });

    $("#historyTable tbody").on("click", "button.btn-expand", function (e) {
        e.stopPropagation();
        const btn   = $(this);
        const tr    = btn.closest("tr");
        const simId = tr.attr("data-sim-id");
        const icon  = btn.find("i");
        const row   = table.row(tr[0]);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass("dt-hasChild");
            icon.removeClass("bi-chevron-up").addClass("bi-chevron-down");
            btn.removeClass("btn-primary text-white").addClass("btn-outline-secondary");
        } else {
            row.child(buildChildRow(simId)).show();
            tr.addClass("dt-hasChild");
            icon.removeClass("bi-chevron-down").addClass("bi-chevron-up");
            btn.removeClass("btn-outline-secondary").addClass("btn-primary text-white");
        }
    });

    $("#historyTable tbody").on("click", "tr", function (e) {
        if ($(this).hasClass("child")) return;
        if ($(e.target).closest("button, a, input, select").length) return;
        $(this).find(".btn-expand").trigger("click");
    });
});
</script>
ENDJS;

$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>

