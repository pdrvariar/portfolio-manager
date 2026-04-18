<?php
/**
 * @var array  $portfolio   Dados do portfólio
 * @var array  $simulations Lista das últimas 10 simulações (com snapshot)
 */

$title = 'Histórico de Simulações — ' . htmlspecialchars($portfolio['name']);
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
?>

<!-- ── Breadcrumb + Cabeçalho ────────────────────────────────────────── -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item">
                    <a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>" class="text-decoration-none text-muted">
                        <i class="bi bi-grid-1x2 me-1"></i>Portfólios
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>" class="text-decoration-none text-muted">
                        <?= htmlspecialchars($portfolio['name']) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active">Histórico</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i>
            Histórico de Simulações
        </h2>
        <p class="text-muted small mb-0 mt-1">
            Últimas simulações de <strong><?= htmlspecialchars($portfolio['name']) ?></strong>.
            Expanda cada linha para ver a configuração exata — e reproduza a melhor quando quiser.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>"
           class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-play-fill me-1"></i> Nova Simulação
        </a>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>"
           class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<?php if (empty($simulations)): ?>
<!-- ── Estado Vazio ─────────────────────────────────────────────────────── -->
<div class="card shadow-sm border-0 rounded-4 text-center py-5">
    <div class="card-body">
        <i class="bi bi-bar-chart-line text-muted mb-3 d-block" style="font-size:3.5rem;opacity:.35;"></i>
        <h5 class="fw-bold text-muted">Nenhuma simulação encontrada</h5>
        <p class="text-muted small mb-4">Execute a primeira simulação para começar a comparar resultados.</p>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>"
           class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-play-fill me-1"></i> Executar Simulação
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
    foreach ($simulations as $sim) {
        $pc = $sim['portfolio_config'] ? json_decode($sim['portfolio_config'], true) : null;
        $ac = $sim['assets_config']    ? json_decode($sim['assets_config'],    true) : null;
        $snapshotsJs[$sim['id']] = ['portfolio' => $pc, 'assets' => $ac];
    }
?>

<!-- ── Banner melhor simulação ────────────────────────────────────────── -->
<div class="alert border-0 rounded-4 mb-4 shadow-sm d-flex align-items-center gap-3"
     style="background:linear-gradient(135deg,#e8f5e9 0%,#f1f8e9 100%);">
    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:46px;height:46px;">
        <i class="bi bi-trophy-fill text-white fs-5"></i>
    </div>
    <div class="flex-grow-1">
        <div class="fw-bold text-success mb-0" style="font-size:.9rem;">Melhor Simulação · Índice Sharpe mais alto</div>
        <div class="text-muted small">
            Executada em <strong><?= date('d/m/Y H:i', strtotime($best['created_at'])) ?></strong> &nbsp;·&nbsp;
            Retorno anual: <strong><?= number_format((float)$best['annual_return'], 2, ',', '.') ?>%</strong> &nbsp;·&nbsp;
            Sharpe: <strong><?= number_format((float)$best['sharpe_ratio'], 2, ',', '.') ?></strong> &nbsp;·&nbsp;
            Drawdown máx.: <strong>-<?= number_format(abs((float)$best['max_drawdown']), 2, ',', '.') ?>%</strong>
        </div>
    </div>
    <span class="badge bg-success rounded-pill px-3 py-2 fs-6 flex-shrink-0">#<?= $best['id'] ?></span>
</div>

<!-- ── Dica de uso ───────────────────────────────────────────────────────── -->
<div class="alert border-0 rounded-3 mb-3 py-2 px-3 small d-flex align-items-center gap-2"
     style="background:#eef6fd;color:#374151;">
    <i class="bi bi-info-circle-fill flex-shrink-0" style="color:#3b82f6;"></i>
    Clique em <kbd><i class="bi bi-chevron-down"></i></kbd> ou em qualquer linha para ver a
    <strong>configuração exata</strong> que foi usada naquela simulação.
</div>

<!-- ── Tabela ────────────────────────────────────────────────────────────────── -->
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
                        <th class="text-end" style="width:9%">Ret. Anual<br><small class="fw-normal text-muted">Estratégia</small></th>
                        <th class="text-end" style="width:8%">Volatili-<br>dade</th>
                        <th class="text-end" style="width:7%">Sharpe</th>
                        <th class="text-end" style="width:9%">Drawdown<br>Máx.</th>
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
                                style="width:28px;height:28px;line-height:1;" title="Ver configuração">
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

<!-- ── Legenda ───────────────────────────────────────────────────────────── -->
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
                    <span class="text-muted" style="font-size:.75rem;">≥ 1 = excelente · 0,5–1 = bom · < 0,5 = fraco. Retorno por unidade de risco.</span>
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
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$snapshotsJsonPhp   = isset($snapshotsJs) ? json_encode($snapshotsJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) : '{}';
$applySnapshotUrl   = isset($portfolio) ? obfuscateUrl('portfolio/apply-snapshot/' . $portfolio['id']) : '';
$csrfToken          = Session::getCsrfToken();
$csrfTokenJson      = json_encode($csrfToken);
$historyUrl         = isset($portfolio) ? obfuscateUrl('portfolio/history/' . $portfolio['id']) : '';

$additional_css = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* ── Tabela ── */
    #historyTable thead th { font-size:.78rem; font-weight:700; white-space:nowrap; vertical-align:middle; }
    #historyTable tbody td { font-size:.82rem; vertical-align:middle; }
    .table-success td      { background-color:rgba(25,135,84,.06) !important; }
    tr.dt-hasChild td      { background-color:rgba(13,110,253,.04) !important; }

    /* ── Child row — light mode ── */
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

    /* Células da tabela interna de ativos */
    .child-config table td  { color: var(--text-main, #212529); }

    /* Barra de alocação */
    .asset-bar-wrap { background: var(--border-color, #e9ecef); border-radius:6px; height:8px; overflow:hidden; }
    .asset-bar      { height:8px; border-radius:6px; }

    /* ── DataTables child row background ── */
    #historyTable tbody tr.child td { background-color: var(--bg-body, #f8f9fa) !important; padding:0 !important; }
</style>';

$additional_js = <<<ENDJS
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
const SNAPSHOTS          = {$snapshotsJsonPhp};
const APPLY_SNAPSHOT_URL = "/index.php?url={$applySnapshotUrl}";
const CSRF_TOKEN         = {$csrfTokenJson};
const HISTORY_URL        = "/index.php?url={$historyUrl}";
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

function buildChildRow(simId) {
    const data = SNAPSHOTS[simId];
    if (!data || !data.portfolio) {
        return '<div class="p-3 text-muted small"><i class="bi bi-exclamation-circle me-1"></i>Configuração não disponível (simulação anterior à ativação desta funcionalidade).</div>';
    }
    const p = data.portfolio;
    const assets = data.assets || [];

    const genPills = [
        pill("bi-cash-coin",       "Capital inicial",  fmtCurrency(p.initial_capital, p.output_currency)),
        pill("bi-calendar3",       "Período",
             (p.start_date ? p.start_date.substring(0,7).split("-").reverse().join("/") : "?") +
             " > " + (p.end_date ? p.end_date.substring(0,7).split("-").reverse().join("/") : "Hoje")),
        pill("bi-currency-exchange","Moeda saída",     p.output_currency || "BRL"),
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
            dPills.push(pill("bi-calendar-check","Frequência", FREQ_LABELS[p.deposit_frequency] || p.deposit_frequency || "-"));
        }
        if (p.deposit_inflation_adjusted == 1) dPills.push('<span class="config-pill"><i class="bi bi-graph-up text-success"></i><strong class="text-success">Ajustado pela inflação</strong></span>');
        if (p.strategic_threshold)          dPills.push(pill("bi-bullseye","Gatilho estratégico", fmt(p.strategic_threshold)+"%"));
        if (p.strategic_deposit_percentage) dPills.push(pill("bi-percent","% no gatilho", fmt(p.strategic_deposit_percentage)+"%"));
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet-fill me-1"></i>Estratégia de Aportes</div><div class="d-flex flex-wrap gap-2">'+dPills.join("")+'</div></div>';
    } else {
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet me-1"></i>Aportes</div><span class="config-pill text-muted"><i class="bi bi-dash-circle"></i> Sem aportes periódicos</span></div>';
    }

    let taxHtml = "";
    if (p.profit_tax_rate) {
        taxHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-receipt me-1"></i>Imposto de Renda</div><div class="d-flex flex-wrap gap-2">'+pill("bi-percent","Alíquota geral", fmt(p.profit_tax_rate)+"%","text-danger")+'</div></div>';
    }

    let assetsRows = "";
    assets.forEach(function(a, i) {
        const pct   = parseFloat(a.allocation_percentage || 0);
        const color = BAR_COLORS[i % BAR_COLORS.length];
        const margin = (a.rebalance_margin_down || a.rebalance_margin_up)
            ? '<span>v'+fmt(a.rebalance_margin_down||0)+'% / ^'+fmt(a.rebalance_margin_up||0)+'%</span>'
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
        ? '<div class="mt-3"><div class="config-section-title"><i class="bi bi-pie-chart-fill me-1"></i>Composição dos Ativos</div>' +
          '<table class="table table-borderless mb-0" style="background:transparent;">' +
          '<thead><tr>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Ativo</th>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Moeda</th>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Alocação</th>' +
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;text-align:right;">Margem Rebal.</th>' +
          '</tr></thead><tbody>'+assetsRows+'</tbody></table></div>'
        : "";

    const applyBtn =
        '<div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3">' +
        '<div class="d-flex align-items-start gap-2">' +
        '<i class="bi bi-lightbulb-fill text-warning fs-5 flex-shrink-0 mt-1"></i>' +
        '<div>' +
        '<div class="fw-bold child-config-title" style="font-size:.85rem;">Quer repetir esta simulação?</div>' +
        '<div class="text-muted" style="font-size:.75rem;">Aplica todos os parâmetros e alocações ao portfólio atual. Depois execute uma nova simulação para comparar.</div>' +
        '</div></div>' +
        '<form method="POST" action="'+APPLY_SNAPSHOT_URL+'" onsubmit="return confirm(\'Atenção: isso irá substituir as configurações atuais do portfólio.\\n\\nCapital, período, aportes, rebalanceamento e ativos serão alterados.\\n\\nDeseja continuar?\')">' +
        '<input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'">' +
        '<input type="hidden" name="simulation_id" value="'+simId+'">' +
        '<button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm no-spinner">' +
        '<i class="bi bi-arrow-counterclockwise me-2"></i>Aplicar ao Portfólio' +
        '</button></form></div>';

    return '<div class="child-config p-3 m-2">' +
        '<div class="d-flex align-items-center gap-2 mb-3">' +
        '<i class="bi bi-clipboard-data text-primary fs-5"></i>' +
        '<span class="fw-bold child-config-title" style="font-size:.9rem;">Configuração usada nesta simulação</span>' +
        '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1" style="font-size:.68rem;">ID #'+simId+'</span>' +
        '</div>' +
        '<div class="config-section-title"><i class="bi bi-gear me-1"></i>Parâmetros Gerais</div>' +
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
            sZeroRecords:"Nenhuma simulação encontrada", sEmptyTable:"Nenhum dado disponível",
            sInfo:"Mostrando _START_ a _END_ de _TOTAL_ registros",
            sInfoEmpty:"Mostrando 0 a 0 de 0 registros",
            sInfoFiltered:"(filtrado de _MAX_ no total)",
            sSearch:"Pesquisar:", sSearchPlaceholder:"Buscar simulação...",
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
