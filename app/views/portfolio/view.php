<?php
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
<div class="collapse mb-4" id="compositionCollapse">
    <div class="card card-body shadow-sm border-0">
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
                    <span class="badge bg-light text-dark border small"><?php echo formatPercentage($asset['allocation_percentage']); ?></span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $asset['allocation_percentage']; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php 
    $metricsList = [
        ['label' => 'Retorno Total', 'val' => formatPercentage($metrics['total_return']), 'class' => 'border-primary', 'text' => $metrics['total_return'] >= 0 ? 'text-success' : 'text-danger'],
        [
            // Se for um período curto, muda o label para não enganar o investidor
            'label' => ($metrics['is_short_period'] ?? false) ? 'Retorno no Período' : 'CAGR (Anual)', 
            'val'   => formatPercentage($metrics['annual_return']), 
            'class' => 'border-success', 
            'text'  => 'text-success'
        ],
        ['label' => 'Volatilidade', 'val' => formatPercentage($metrics['volatility']), 'class' => 'border-warning', 'text' => 'text-dark'],
        ['label' => 'Sharpe Ratio', 'val' => number_format($metrics['sharpe_ratio'], 2), 'class' => 'border-info', 'text' => 'text-dark']
    ];
    foreach ($metricsList as $m): ?>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100 border-start border-4 <?php echo $m['class']; ?>">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase fw-bold"><?php echo $m['label']; ?></h6>
                <h3 class="<?php echo $m['text']; ?> fw-bold mb-0"><?php echo $m['val']; ?></h3>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Evolução do Patrimônio</h5></div>
            <div class="card-body"><div class="chart-container"><canvas id="valueChart"></canvas></div></div>
        </div>
    </div>
</div>

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
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $prevValue = $portfolio['initial_capital'];
                    if (isset($chartData['audit_log'])):
                        foreach ($chartData['audit_log'] as $date => $data): 
                            $currentValue = $data['total_value'];
                            $variation = (($currentValue / $prevValue) - 1) * 100;
                            $rebalanced = $data['rebalanced'] ?? false;
                            
                            // PREPARAÇÃO SÊNIOR: Transformamos os arrays em JSON seguro para o JS
                            $dateLabel = date('m/Y', strtotime($date));
                            $assetValuesJson = htmlspecialchars(json_encode($data['asset_values']), ENT_QUOTES, 'UTF-8');
                            $tradesJson = htmlspecialchars(json_encode($data['trades'] ?? []), ENT_QUOTES, 'UTF-8');
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
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-link text-decoration-none" 
                                    onclick='openDetailsModal("<?= $dateLabel ?>", <?= $assetValuesJson ?>, <?= $currentValue ?>, <?= $tradesJson ?>)'>
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

<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Composição: <span id="modalDate"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th class="ps-4">Ativo</th><th class="text-end">Operação</th><th class="text-end pe-4">Saldo Final</th></tr></thead>
                    <tbody id="modalAssetsBody"></tbody>
                </table>
            </div>
            <div class="modal-footer bg-light"><div class="w-100 d-flex justify-content-between"><strong>Total:</strong><strong class="text-primary" id="modalTotal"></strong></div></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Inicialização de dados e gráficos (conforme seu código original)
    const currency = '<?php echo $portfolio['output_currency']; ?>';
    const chartData = <?php echo json_encode($chartData); ?>;
    const assetNames = {<?php foreach ($assets as $a) echo '"'.$a['asset_id'].'": "'.htmlspecialchars($a['name']).'",'; ?>};
    const assetTargets = {<?php foreach ($assets as $a) echo '"'.$a['asset_id'].'": '.$a['allocation_percentage'].','; ?>};

    // Renderização dos gráficos Chart.js
    new Chart(document.getElementById('valueChart'), {
        type: 'line',
        data: chartData.value_chart,
        options: { responsive: true, maintainAspectRatio: false, plugins: { tooltip: { callbacks: { label: (ctx) => `Valor: ${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(ctx.raw)}` } } } }
    });
    new Chart(document.getElementById('compositionChart'), {
        type: 'bar',
        data: chartData.composition_chart,
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, max: 100 } } }
    });
    new Chart(document.getElementById('returnsChart'), {
        type: 'bar',
        data: chartData.returns_chart,
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Funções do Modal, Busca e CSV (Suas funções originais otimizadas)
    function openDetailsModal(dateLabel, assetValues, totalValue, trades) {
        document.getElementById('modalDate').innerText = dateLabel;
        const body = document.getElementById('modalAssetsBody');
        body.innerHTML = '';
        const isRebalanceMonth = Object.keys(trades).length > 0;
        
        for (const [id, value] of Object.entries(assetValues)) {
            const name = assetNames[id] || id;
            const target = assetTargets[id] || 0;
            let finalVal = value;
            let actionHtml = `<div class="text-muted small">${((value/totalValue)*100).toFixed(2)}%</div>`;

            if (isRebalanceMonth && trades[id]) {
                finalVal = trades[id].post_value;
                const delta = trades[id].delta;
                actionHtml = `<div class="${delta >= 0 ? 'text-success' : 'text-danger'} small">${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(delta)}</div>`;
            }

            body.innerHTML += `<tr>
                <td class="ps-4"><strong>${name}</strong>${isRebalanceMonth ? `<br><small class="text-muted">Anterior: ${new Intl.NumberFormat('pt-BR').format(trades[id].pre_value)}</small>` : ''}</td>
                <td class="text-end">${actionHtml}</td>
                <td class="text-end pe-4"><strong>${new Intl.NumberFormat('pt-BR').format(finalVal)}</strong>${isRebalanceMonth ? `<br><small class="text-muted" style="font-size:0.7rem">ALVO ${target}%</small>` : ''}</td>
            </tr>`;
        }
        document.getElementById('modalTotal').innerText = new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(totalValue);
        new bootstrap.Modal(document.getElementById('detailsModal')).show();
    }

    document.getElementById('auditSearch').addEventListener('keyup', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#auditTable tbody tr').forEach(r => r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none');
    });

    function exportAuditToCSV() {
        const headers = ["Referência", "Saldo", "Variação (%)", "Status"];
        let csv = [headers.join(";")];
        document.querySelectorAll("#auditTable tbody tr").forEach(row => {
            const cols = row.querySelectorAll("td");
            csv.push([`"${cols[0].innerText}"`, `"${cols[1].innerText.replace(/[^\d,-]/g,'')}"`, `"${cols[2].innerText.replace(/[^\d,-]/g,'')}"`, `"${cols[3].innerText}"`].join(";"));
        });
        const blob = new Blob(["\uFEFF" + csv.join("\n")], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `Auditoria_<?php echo $portfolio['id']; ?>.csv`;
        link.click();
    }
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>