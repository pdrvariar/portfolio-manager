<?php
/**
 * @var array $simulations  Array of simulation rows (2–5 items)
 */

$title = 'Comparativo de Simulações';

$breadcrumbs = [
    ['label' => '<i class="bi bi-house-door"></i> Home',  'url' => '/index.php?url=' . obfuscateUrl('dashboard')],
    ['label' => 'Portfólios',                             'url' => '/index.php?url=' . obfuscateUrl('portfolio')],
    ['label' => 'Histórico de Simulações',                'url' => '/index.php?url=' . obfuscateUrl('portfolio/simulations')],
    ['label' => 'Comparativo',                            'url' => '#'],
];

ob_start();

$n = count($simulations);

// ── Helper: detecta moeda da simulação ─────────────────────────────────────
function simCurrency(array $sim): string {
    $pc = $sim['portfolio_config'] ? json_decode($sim['portfolio_config'], true) : null;
    return $pc['output_currency'] ?? $sim['output_currency'] ?? 'BRL';
}

// ── Monta mapa de métricas por simulação ──────────────────────────────────
$mapa = [];
foreach ($simulations as $sim) {
    $cur = simCurrency($sim);
    $mapa[] = [
        'id'              => $sim['id'],
        'portfolio_name'  => $sim['portfolio_name'],
        'sim_date'        => $sim['simulation_date'],
        'created_at'      => $sim['created_at'],
        'currency'        => $cur,
        // ── Patrimônio
        'total_invested'  => (float)($sim['total_invested']  ?? 0),
        'total_deposits'  => (float)($sim['total_deposits']  ?? 0),
        'total_value'     => (float)($sim['total_value']     ?? 0),
        'interest_earned' => (float)($sim['interest_earned'] ?? 0),
        'total_tax_paid'  => (float)($sim['total_tax_paid']  ?? 0),
        // ── Retorno
        'roi'                    => (float)($sim['roi']                    ?? 0),
        'annual_return'          => (float)($sim['annual_return']          ?? 0),
        'strategy_annual_return' => (float)($sim['strategy_annual_return'] ?? 0),
        'strategy_return'        => (float)($sim['strategy_return']        ?? 0),
        // ── Risco
        'volatility'      => (float)($sim['volatility']      ?? 0),
        'sharpe_ratio'    => (float)($sim['sharpe_ratio']    ?? 0),
        'max_drawdown'    => abs((float)($sim['max_drawdown'] ?? 0)),
        'max_monthly_gain'=> (float)($sim['max_monthly_gain'] ?? 0),
        'max_monthly_loss'=> abs((float)($sim['max_monthly_loss'] ?? 0)),
    ];
}

// ── Função para determinar o índice do "vencedor" de uma métrica ──────────
//    $dir = 'max' → maior vence  |  'min' → menor vence
function winnerIndex(array $mapa, string $key, string $dir): int {
    $best = null; $bestIdx = 0;
    foreach ($mapa as $i => $m) {
        $v = $m[$key];
        if ($best === null
            || ($dir === 'max' && $v > $best)
            || ($dir === 'min' && $v < $best)) {
            $best    = $v;
            $bestIdx = $i;
        }
    }
    return $bestIdx;
}

// ── Calcula estrelas por simulação ────────────────────────────────────────
$scoredMetrics = [
    'total_value'            => 'max',
    'interest_earned'        => 'max',
    'roi'                    => 'max',
    'annual_return'          => 'max',
    'strategy_annual_return' => 'max',
    'sharpe_ratio'           => 'max',
    'max_monthly_gain'       => 'max',
    'total_tax_paid'         => 'min',   // menos imposto = melhor
    'volatility'             => 'min',
    'max_drawdown'           => 'min',
    'max_monthly_loss'       => 'min',
];

$stars = array_fill(0, $n, 0);
foreach ($scoredMetrics as $key => $dir) {
    $wi = winnerIndex($mapa, $key, $dir);
    $stars[$wi]++;
}

$overallWinner = array_search(max($stars), $stars);

// ── Definição das seções e linhas da tabela ───────────────────────────────
$sections = [
    [
        'icon'  => 'bi-wallet2',
        'label' => 'Patrimônio',
        'color' => 'primary',
        'rows'  => [
            ['key'=>'total_invested',  'label'=>'Capital Inicial',      'type'=>'currency', 'dir'=>null,  'icon'=>'bi-bank',          'desc'=>'Valor aportado inicialmente'],
            ['key'=>'total_deposits',  'label'=>'Total de Aportes',     'type'=>'currency', 'dir'=>null,  'icon'=>'bi-plus-circle',   'desc'=>'Soma de todos os aportes periódicos'],
            ['key'=>'total_value',     'label'=>'Patrimônio Final',     'type'=>'currency', 'dir'=>'max', 'icon'=>'bi-graph-up-arrow','desc'=>'Valor total ao final do período'],
            ['key'=>'interest_earned', 'label'=>'Ganho Bruto',          'type'=>'currency_signed', 'dir'=>'max', 'icon'=>'bi-cash-stack',    'desc'=>'Rendimento total antes do imposto'],
            ['key'=>'total_tax_paid',  'label'=>'Imposto Pago',         'type'=>'currency_neg',    'dir'=>'min', 'icon'=>'bi-receipt',       'desc'=>'Menos imposto = melhor eficiência fiscal'],
        ],
    ],
    [
        'icon'  => 'bi-graph-up',
        'label' => 'Retorno',
        'color' => 'success',
        'rows'  => [
            ['key'=>'roi',                    'label'=>'ROI Total',                  'type'=>'pct_signed', 'dir'=>'max', 'icon'=>'bi-tags',      'desc'=>'Retorno sobre todo o capital investido'],
            ['key'=>'annual_return',          'label'=>'Retorno Anual (c/ aportes)', 'type'=>'pct_signed', 'dir'=>'max', 'icon'=>'bi-percent',   'desc'=>'CAGR incluindo aportes periódicos'],
            ['key'=>'strategy_annual_return', 'label'=>'Retorno Anual (estratégia)','type'=>'pct_signed', 'dir'=>'max', 'icon'=>'bi-trophy',    'desc'=>'Performance pura dos ativos, sem aportes'],
            ['key'=>'strategy_return',        'label'=>'Retorno Total (estratégia)','type'=>'pct_signed', 'dir'=>'max', 'icon'=>'bi-bar-chart', 'desc'=>'Retorno acumulado da estratégia no período'],
        ],
    ],
    [
        'icon'  => 'bi-shield-exclamation',
        'label' => 'Risco',
        'color' => 'warning',
        'rows'  => [
            ['key'=>'sharpe_ratio',    'label'=>'Índice Sharpe',   'type'=>'num2',   'dir'=>'max', 'icon'=>'bi-speedometer2',        'desc'=>'Retorno por unidade de risco. ≥1 excelente'],
            ['key'=>'volatility',      'label'=>'Volatilidade',    'type'=>'pct',    'dir'=>'min', 'icon'=>'bi-activity',            'desc'=>'Oscilação mensal do portfólio. Menor = mais estável'],
            ['key'=>'max_drawdown',    'label'=>'Drawdown Máximo', 'type'=>'pct_neg','dir'=>'min', 'icon'=>'bi-arrow-down-circle',   'desc'=>'Maior queda do pico. Menor impacto = melhor'],
            ['key'=>'max_monthly_gain','label'=>'Melhor Mês',      'type'=>'pct_pos','dir'=>'max', 'icon'=>'bi-arrow-up-right-circle','desc'=>'Maior ganho em um único mês'],
            ['key'=>'max_monthly_loss','label'=>'Pior Mês',        'type'=>'pct_neg','dir'=>'min', 'icon'=>'bi-arrow-down-left-circle','desc'=>'Maior queda em um único mês'],
        ],
    ],
];

// Format helpers
function fmtCmp(float $v, string $type, string $cur = 'BRL'): string {
    $sym = $cur === 'USD' ? 'US$' : 'R$';
    switch ($type) {
        case 'currency':
            return $sym . ' ' . number_format($v, 2, ',', '.');
        case 'currency_signed':
            return ($v >= 0 ? '+' : '') . $sym . ' ' . number_format(abs($v), 2, ',', '.');
        case 'currency_neg':
            return '−' . $sym . ' ' . number_format(abs($v), 2, ',', '.');
        case 'pct_signed':
            return ($v >= 0 ? '+' : '') . number_format($v, 2, ',', '.') . '%';
        case 'pct':
            return number_format($v, 2, ',', '.') . '%';
        case 'pct_pos':
            return '+' . number_format($v, 2, ',', '.') . '%';
        case 'pct_neg':
            return '−' . number_format(abs($v), 2, ',', '.') . '%';
        case 'num2':
            return number_format($v, 2, ',', '.');
        default:
            return (string)$v;
    }
}

function colorClass(float $v, string $type): string {
    switch ($type) {
        case 'currency':      return '';
        case 'currency_signed': return $v >= 0 ? 'text-success' : 'text-danger';
        case 'currency_neg':  return 'text-danger';
        case 'pct_signed':    return $v >= 0 ? 'text-success' : 'text-danger';
        case 'pct':           return $v <= 10 ? 'text-success' : ($v <= 20 ? 'text-warning' : 'text-danger');
        case 'pct_pos':       return 'text-success';
        case 'pct_neg':       return $v <= 10 ? 'text-success' : ($v <= 25 ? 'text-warning' : 'text-danger');
        case 'num2':          return $v >= 1 ? 'text-success fw-bold' : ($v >= 0.5 ? 'text-warning' : 'text-danger');
        default:              return '';
    }
}

$simTypeLabels = [
    'standard'           => 'Padrão',
    'monthly_deposit'    => 'Aporte Mensal',
    'strategic_deposit'  => 'Aporte Estratégico',
    'smart_deposit'      => 'Aporte Inteligente',
    'selic_cash_deposit' => 'Selic + Aporte',
];
$rebalTypeLabels = [
    'full'    => 'Total',
    'partial' => 'Parcial',
    'none'    => 'Sem rebal.',
];
$freqLabels = [
    'never'     => 'Nunca',
    'monthly'   => 'Mensal',
    'quarterly' => 'Trimestral',
    'biannual'  => 'Semestral',
    'annual'    => 'Anual',
];

// Column colors for each sim (cycle)
$colPalette = ['primary','success','danger','warning','info'];
?>

<!-- ─── Cabeçalho ──────────────────────────────────────────────────────────── -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-steps text-primary"></i>
            Comparativo de Simulações
        </h2>
        <p class="text-muted small mb-0 mt-1">
            Análise lado a lado de <?= $n ?> simulações · A <span class="fw-bold text-warning">⭐ estrela</span> indica o melhor valor em cada indicador.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/simulations') ?>"
           class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<!-- ─── Banner do vencedor geral ──────────────────────────────────────────── -->
<div class="winner-banner mb-4">
    <div class="winner-banner-inner d-flex align-items-center gap-4 flex-wrap">
        <div class="winner-trophy-wrap d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="bi bi-trophy-fill" style="font-size:2rem;color:#ffd700;"></i>
        </div>
        <div class="flex-grow-1">
            <div class="winner-headline">🏆 Melhor Simulação Geral</div>
            <div class="winner-name">
                Simulação #<?= $mapa[$overallWinner]['id'] ?>
                <span class="winner-portfolio">— <?= htmlspecialchars($mapa[$overallWinner]['portfolio_name']) ?></span>
            </div>
            <div class="winner-stars mt-1">
                <?php for ($s = 0; $s < $stars[$overallWinner]; $s++): ?>
                    <span class="winner-star">⭐</span>
                <?php endfor; ?>
                <span class="winner-star-count"><?= $stars[$overallWinner] ?> critério<?= $stars[$overallWinner] !== 1 ? 's' : '' ?> vencedor<?= $stars[$overallWinner] !== 1 ? 'es' : '' ?></span>
            </div>
        </div>
        <div class="d-flex gap-3 flex-wrap">
            <div class="winner-kpi">
                <div class="winner-kpi-label">Patrimônio Final</div>
                <div class="winner-kpi-value"><?= fmtCmp($mapa[$overallWinner]['total_value'], 'currency', $mapa[$overallWinner]['currency']) ?></div>
            </div>
            <div class="winner-kpi">
                <div class="winner-kpi-label">Retorno Anual</div>
                <div class="winner-kpi-value text-success"><?= fmtCmp($mapa[$overallWinner]['annual_return'], 'pct_signed') ?></div>
            </div>
            <div class="winner-kpi">
                <div class="winner-kpi-label">Índice Sharpe</div>
                <div class="winner-kpi-value"><?= fmtCmp($mapa[$overallWinner]['sharpe_ratio'], 'num2') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Stars summary bar ─────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php foreach ($mapa as $i => $m): ?>
    <div class="col">
        <div class="sim-score-card <?= $i === $overallWinner ? 'sim-score-card--winner' : '' ?>">
            <?php if ($i === $overallWinner): ?>
            <div class="sim-score-crown"><i class="bi bi-crown-fill"></i></div>
            <?php endif; ?>
            <div class="sim-score-header">
                <span class="sim-score-num">#<?= $m['id'] ?></span>
                <span class="sim-score-portfolio"><?= htmlspecialchars(mb_strlen($m['portfolio_name']) > 28 ? mb_substr($m['portfolio_name'], 0, 26) . '…' : $m['portfolio_name']) ?></span>
            </div>
            <div class="sim-score-stars">
                <?php for ($s = 0; $s < $stars[$i]; $s++): ?>⭐<?php endfor; ?>
                <?php if ($stars[$i] === 0): ?><span class="text-muted small">—</span><?php endif; ?>
            </div>
            <div class="sim-score-label"><?= $stars[$i] ?> estrela<?= $stars[$i] !== 1 ? 's' : '' ?></div>
            <div class="sim-score-date text-muted"><?= date('d/m/Y', strtotime($m['created_at'])) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ─── Tabela comparativa ────────────────────────────────────────────────── -->
<div class="card border-0 shadow rounded-4 mb-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table cmp-table align-middle mb-0">
            <!-- ── HEADER ── -->
            <thead>
                <tr class="cmp-header-row">
                    <th class="cmp-label-col cmp-sticky-col" style="width:220px;">
                        <div class="d-flex align-items-center gap-2 py-1">
                            <i class="bi bi-list-check text-muted"></i>
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing:.06em;">Indicador</span>
                        </div>
                    </th>
                    <?php foreach ($mapa as $i => $m):
                        $isWinner = ($i === $overallWinner);
                        $pal = $colPalette[$i % count($colPalette)];
                    ?>
                    <th class="cmp-sim-col <?= $isWinner ? 'cmp-winner-col' : '' ?>" style="min-width:170px;">
                        <?php if ($isWinner): ?>
                        <div class="cmp-winner-badge"><i class="bi bi-trophy-fill me-1"></i>Melhor</div>
                        <?php endif; ?>
                        <div class="cmp-sim-id text-<?= $pal ?>">#<?= $m['id'] ?></div>
                        <div class="cmp-sim-name"><?= htmlspecialchars($m['portfolio_name']) ?></div>
                        <div class="cmp-sim-meta">
                            <span><i class="bi bi-calendar3 me-1"></i><?= date('m/Y', strtotime($m['sim_date'])) ?></span>
                            <span class="ms-2"><i class="bi bi-currency-exchange me-1"></i><?= $m['currency'] ?></span>
                        </div>
                        <div class="cmp-stars-row mt-1">
                            <?php for ($s = 0; $s < $stars[$i]; $s++): ?>⭐<?php endfor; ?>
                            <?php if ($stars[$i] === 0): ?><span style="opacity:.4;font-size:.7rem;">sem estrelas</span><?php endif; ?>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($sections as $section): ?>
                <!-- ── Section header ── -->
                <tr class="cmp-section-row">
                    <td colspan="<?= $n + 1 ?>">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi <?= $section['icon'] ?> text-<?= $section['color'] ?>"></i>
                            <span class="fw-bold text-<?= $section['color'] ?>" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.07em;"><?= $section['label'] ?></span>
                        </div>
                    </td>
                </tr>

                <?php foreach ($section['rows'] as $row):
                    $winIdx = $row['dir'] ? winnerIndex($mapa, $row['key'], $row['dir']) : -1;
                    $allSame = true;
                    $first = $mapa[0][$row['key']];
                    foreach ($mapa as $m) { if (abs($m[$row['key']] - $first) > 0.001) { $allSame = false; break; } }
                ?>
                <tr class="cmp-data-row">
                    <td class="cmp-label-col cmp-sticky-col">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi <?= $row['icon'] ?> text-muted flex-shrink-0"></i>
                            <div>
                                <div class="cmp-row-label"><?= $row['label'] ?></div>
                                <div class="cmp-row-desc"><?= $row['desc'] ?></div>
                            </div>
                        </div>
                    </td>
                    <?php foreach ($mapa as $i => $m):
                        $val     = $m[$row['key']];
                        $isWin   = (!$allSame && $row['dir'] && $i === $winIdx);
                        $isColWin = ($i === $overallWinner);
                        $clr     = colorClass($val, $row['type']);
                        $fmt     = fmtCmp($val, $row['type'], $m['currency']);
                    ?>
                    <td class="cmp-sim-col <?= $isColWin ? 'cmp-winner-col' : '' ?> <?= $isWin ? 'cmp-cell-winner' : '' ?> text-center">
                        <div class="cmp-cell-wrap">
                            <span class="cmp-val <?= $clr ?>"><?= $fmt ?></span>
                            <?php if ($isWin): ?>
                            <span class="cmp-star" title="Melhor valor neste indicador">⭐</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <!-- ── Config section ── -->
            <tr class="cmp-section-row">
                <td colspan="<?= $n + 1 ?>">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-gear text-secondary"></i>
                        <span class="fw-bold text-secondary" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.07em;">Configuração</span>
                    </div>
                </td>
            </tr>
            <?php
            // Config rows from snapshot
            $configRows = [
                ['label'=>'Capital Inicial',     'icon'=>'bi-bank',           'fn'=>function($sim){ $pc=json_decode($sim['portfolio_config']??'{}',true); return formatCurrency((float)($pc['initial_capital']??0), $pc['output_currency']??'BRL'); }],
                ['label'=>'Período',             'icon'=>'bi-calendar3',      'fn'=>function($sim){ $pc=json_decode($sim['portfolio_config']??'{}',true); $s=$pc['start_date']??'-'; $e=$pc['end_date']??'Hoje'; return date('m/Y',strtotime($s)).' → '.($e?date('m/Y',strtotime($e)):'Hoje'); }],
                ['label'=>'Tipo de Simulação',   'icon'=>'bi-layers',         'fn'=>function($sim) use($simTypeLabels){ $pc=json_decode($sim['portfolio_config']??'{}',true); return $simTypeLabels[$pc['simulation_type']??'standard']??'Padrão'; }],
                ['label'=>'Rebalanceamento',     'icon'=>'bi-arrow-repeat',   'fn'=>function($sim) use($freqLabels,$rebalTypeLabels){ $pc=json_decode($sim['portfolio_config']??'{}',true); $f=$freqLabels[$pc['rebalance_frequency']??'never']??'-'; $t=$rebalTypeLabels[$pc['rebalance_type']??'none']??'-'; return $f.' / '.$t; }],
                ['label'=>'Valor do Aporte',     'icon'=>'bi-plus-circle',    'fn'=>function($sim){ $pc=json_decode($sim['portfolio_config']??'{}',true); if(empty($pc['deposit_amount'])) return '—'; return formatCurrency((float)$pc['deposit_amount'], $pc['deposit_currency']??'BRL'); }],
                ['label'=>'Ativos',              'icon'=>'bi-pie-chart',      'fn'=>function($sim){ $ac=json_decode($sim['assets_config']??'[]',true); if(!is_array($ac)) return '—'; return count($ac).' ativo'.( count($ac)!==1?'s':''); }],
            ];
            foreach ($configRows as $cr): ?>
            <tr class="cmp-data-row">
                <td class="cmp-label-col cmp-sticky-col">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $cr['icon'] ?> text-muted flex-shrink-0"></i>
                        <div class="cmp-row-label"><?= $cr['label'] ?></div>
                    </div>
                </td>
                <?php foreach ($simulations as $i => $sim):
                    $isColWin = ($i === $overallWinner);
                    $val = ($cr['fn'])($sim);
                ?>
                <td class="cmp-sim-col <?= $isColWin ? 'cmp-winner-col' : '' ?> text-center">
                    <span class="cmp-config-val"><?= htmlspecialchars($val) ?></span>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

<!-- ─── Guia de interpretação ─────────────────────────────────────────────── -->
<div class="card border-0 rounded-4 bg-light shadow-sm mb-4">
    <div class="card-body py-3 px-4">
        <h6 class="fw-bold mb-3 text-muted small text-uppercase" style="letter-spacing:.05em;">
            <i class="bi bi-lightbulb me-1 text-warning"></i> Como interpretar os indicadores
        </h6>
        <div class="row g-3">
            <div class="col-md-4 col-sm-6">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-speedometer2 text-primary mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold small">Índice Sharpe</div>
                        <div class="text-muted" style="font-size:.75rem;">≥ 1 = excelente · 0,5–1 = bom · &lt; 0,5 = fraco. Mede o retorno ajustado ao risco.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-arrow-down-circle text-danger mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold small">Drawdown Máximo</div>
                        <div class="text-muted" style="font-size:.75rem;">Maior queda acumulada desde o pico. Quanto menor, mais estável a estratégia.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-activity text-warning mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold small">Volatilidade</div>
                        <div class="text-muted" style="font-size:.75rem;">Desvio padrão mensal dos retornos. ≤10% = baixo · 10–20% = moderado · &gt;20% = alto.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-trophy text-success mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold small">Retorno Estratégia</div>
                        <div class="text-muted" style="font-size:.75rem;">Performance pura dos ativos, sem influência dos aportes. Compara a carteira de forma isolada.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-tags text-info mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold small">ROI Total</div>
                        <div class="text-muted" style="font-size:.75rem;">Retorno sobre todo o capital aportado (inicial + periódicos). Visão do investidor.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="d-flex gap-2 align-items-start">
                    <span class="flex-shrink-0 mt-1" style="font-size:1rem;">⭐</span>
                    <div>
                        <div class="fw-bold small">Estrela</div>
                        <div class="text-muted" style="font-size:.75rem;">Indica a simulação com o melhor valor naquele indicador. A que acumular mais estrelas é declarada vencedora.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additional_css = '
<style>
/* ══════════════════════════════════════════════════════════
   COMPARE PAGE — premium financial table
   ══════════════════════════════════════════════════════════ */

/* ── Winner banner ── */
.winner-banner {
    background: linear-gradient(135deg, #1a1f36 0%, #0d1b2a 100%);
    border-radius: 16px;
    padding: 24px 28px;
    border: 1px solid rgba(255,215,0,.2);
    box-shadow: 0 4px 24px rgba(0,0,0,.2);
}
.winner-trophy-wrap {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: rgba(255,215,0,.12);
    border: 1px solid rgba(255,215,0,.3);
    flex-shrink: 0;
}
.winner-headline {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; color: rgba(255,255,255,.5); margin-bottom: 2px;
}
.winner-name {
    font-size: 1.25rem; font-weight: 800; color: #fff; line-height: 1.2;
}
.winner-portfolio { font-size: .9rem; font-weight: 500; color: rgba(255,255,255,.65); }
.winner-star { font-size: .9rem; }
.winner-star-count {
    font-size: .75rem; font-weight: 600; color: rgba(255,255,255,.55);
    margin-left: 6px; vertical-align: middle;
}
.winner-kpi {
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
    border-radius: 10px; padding: 10px 16px; min-width: 120px;
}
.winner-kpi-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: rgba(255,255,255,.45); margin-bottom: 2px; }
.winner-kpi-value { font-size: 1rem; font-weight: 800; color: #fff; }

/* ── Sim score cards ── */
.sim-score-card {
    position: relative;
    background: var(--bg-card, #fff);
    border: 1.5px solid var(--border-color, #dee2e6);
    border-radius: 14px;
    padding: 16px;
    text-align: center;
    transition: box-shadow .2s;
}
.sim-score-card--winner {
    border-color: #ffd700;
    background: linear-gradient(145deg, #fffef0 0%, #fff9c4 100%);
    box-shadow: 0 4px 18px rgba(255,215,0,.3);
}
[data-theme="dark"] .sim-score-card--winner {
    background: linear-gradient(145deg, rgba(255,215,0,.08) 0%, rgba(255,215,0,.03) 100%);
}
.sim-score-crown {
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: #ffd700; border-radius: 50%; width: 26px; height: 26px;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; color: #7a5c00; box-shadow: 0 2px 8px rgba(255,215,0,.5);
}
.sim-score-header { margin-bottom: 6px; }
.sim-score-num { font-size: 1rem; font-weight: 800; color: var(--text-main, #212529); }
.sim-score-portfolio { display: block; font-size: .72rem; color: var(--text-muted, #6c757d); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sim-score-stars { font-size: 1.1rem; line-height: 1.4; min-height: 1.6rem; }
.sim-score-label { font-size: .7rem; font-weight: 700; color: var(--text-muted, #6c757d); }
.sim-score-date { font-size: .65rem; margin-top: 4px; }

/* ── Comparison table ── */
.cmp-table { border-collapse: separate; border-spacing: 0; }
.cmp-table thead th { background: var(--bg-card, #fff); position: sticky; top: 0; z-index: 10; border-bottom: 2px solid var(--border-color, #dee2e6); }
.cmp-table th, .cmp-table td { padding: 0; }

/* Header row */
.cmp-header-row th { padding: 16px 14px; vertical-align: top; }
.cmp-sim-id { font-size: 1.1rem; font-weight: 800; line-height: 1; margin-bottom: 2px; }
.cmp-sim-name { font-size: .82rem; font-weight: 600; color: var(--text-main, #212529); line-height: 1.3; margin-bottom: 3px; }
.cmp-sim-meta { font-size: .68rem; color: var(--text-muted, #6c757d); }
.cmp-stars-row { font-size: .85rem; min-height: 1.3rem; }

/* Winner column */
.cmp-winner-col { background: rgba(255,215,0,.05) !important; border-left: 2px solid rgba(255,215,0,.4) !important; border-right: 2px solid rgba(255,215,0,.4) !important; }
.cmp-winner-badge {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, #ffd700, #ffa000);
    color: #7a5c00; font-size: .62rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em;
    border-radius: 20px; padding: 2px 8px; margin-bottom: 4px;
    box-shadow: 0 2px 6px rgba(255,160,0,.3);
}

/* Label column (sticky left) */
.cmp-label-col { padding: 10px 16px !important; }
.cmp-sticky-col { position: sticky; left: 0; z-index: 5; background: var(--bg-card, #fff); border-right: 1px solid var(--border-color, #dee2e6); min-width: 220px; }
.cmp-row-label { font-size: .82rem; font-weight: 600; color: var(--text-main, #212529); line-height: 1.2; }
.cmp-row-desc { font-size: .68rem; color: var(--text-muted, #6c757d); line-height: 1.3; margin-top: 1px; }

/* Section header rows */
.cmp-section-row td {
    background: var(--bg-body, #f8f9fa) !important;
    padding: 8px 16px !important;
    border-top: 1px solid var(--border-color, #dee2e6);
    border-bottom: 1px solid var(--border-color, #dee2e6);
}

/* Data rows */
.cmp-data-row td { padding: 11px 14px !important; border-bottom: 1px solid var(--border-color, #dee2e6); }
.cmp-data-row:hover td { background: rgba(13,110,253,.03) !important; }
.cmp-data-row:hover .cmp-winner-col { background: rgba(255,215,0,.09) !important; }

/* Cell value */
.cmp-sim-col { text-align: center; }
.cmp-cell-wrap { display: flex; align-items: center; justify-content: center; gap: 4px; }
.cmp-val { font-size: .85rem; font-weight: 600; line-height: 1.2; }
.cmp-star { font-size: .85rem; }
.cmp-config-val { font-size: .8rem; color: var(--text-main, #212529); }

/* Winner cell highlight */
.cmp-cell-winner .cmp-val {
    font-weight: 800;
}
.cmp-cell-winner .cmp-star {
    animation: starPop .3s ease;
}
@keyframes starPop { 0%{transform:scale(.5);opacity:0} 70%{transform:scale(1.2)} 100%{transform:scale(1);opacity:1} }

/* ── Dark mode ── */
[data-theme="dark"] .cmp-sticky-col { background: var(--bg-card) !important; }
[data-theme="dark"] .cmp-section-row td { background: var(--bg-body) !important; }
[data-theme="dark"] .cmp-table thead th { background: var(--bg-card) !important; }
[data-theme="dark"] .sim-score-card { background: var(--bg-card); border-color: var(--border-color); }
[data-theme="dark"] .cmp-winner-col { background: rgba(255,215,0,.07) !important; }
[data-theme="dark"] .winner-kpi { background: rgba(255,255,255,.05); }
</style>
';

include_once __DIR__ . '/../layouts/main.php';
?>

