<?php
$title = 'Resultados: ' . htmlspecialchars($portfolio['name']);
ob_start();
?>

<style>
    /* Estilos de Especialista em UX */
    .metric-card { transition: transform 0.2s; border: none; }
    .metric-card:hover { transform: translateY(-5px); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1) !important; color: #087990 !important; }
    
    /* Tabela de Auditoria Profissional */
    .sticky-top-table { top: -1px; z-index: 10; background: #f8f9fa !important; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .table-responsive-audit { max-height: 450px; overflow-y: auto; border-radius: 8px; }
    .table-responsive-audit::-webkit-scrollbar { width: 6px; }
    .table-responsive-audit::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 10px; }
    
    .rebalanced-row { background-color: rgba(13, 202, 240, 0.03); }
    .chart-container { position: relative; height: 350px; width: 100%; }
</style>

<div class="row mb-4 align-items-end">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($portfolio['name']); ?></h2>
        <p class="text-muted mb-0">
            <i class="bi bi-calendar3 me-1"></i> Período: <?php echo formatDate($portfolio['start_date']); ?>
            <?php echo $portfolio['end_date'] ? ' até ' . formatDate($portfolio['end_date']) : ' (Até o presente)'; ?>
            | <i class="bi bi-currency-exchange ms-2 me-1"></i> Moeda: <?php echo $portfolio['output_currency']; ?>
        </p>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group shadow-sm">
            <a href="/index.php?url=portfolio/run/<?php echo $portfolio['id']; ?>" class="btn btn-primary" id="btnRun">
                <i class="bi bi-play-fill me-1"></i>Simular
            </a>
            <a href="/index.php?url=portfolio/edit/<?php echo $portfolio['id']; ?>" class="btn btn-outline-secondary" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>
            <a href="/index.php?url=portfolio/clone/<?php echo $portfolio['id']; ?>" class="btn btn-outline-secondary" title="Clonar">
                <i class="bi bi-files"></i>
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase fw-bold">Retorno Total</h6>
                <h3 class="<?php echo $metrics['total_return'] >= 0 ? 'text-success' : 'text-danger'; ?> fw-bold mb-0">
                    <?php echo formatPercentage($metrics['total_return']); ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase fw-bold">CAGR (Anual)</h6>
                <h3 class="text-success fw-bold mb-0"><?php echo formatPercentage($metrics['annual_return']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase fw-bold">Volatilidade</h6>
                <h3 class="fw-bold mb-0"><?php echo formatPercentage($metrics['volatility']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase fw-bold">Sharpe Ratio</h6>
                <h3 class="fw-bold mb-0"><?php echo number_format($metrics['sharpe_ratio'], 2); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Evolução do Patrimônio</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="valueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i>Composição Histórica</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="compositionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart text-primary me-2"></i>Retorno por Ano</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="returnsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-journal-check text-primary me-2"></i>Auditoria Mensal</h5>
        <div class="d-flex gap-2">
            <input type="text" id="auditSearch" class="form-control form-control-sm" placeholder="Buscar data..." style="width: 180px;">
            <button onclick="exportAuditCSV()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive-audit">
            <table class="table table-hover align-middle mb-0" id="auditTable">
                <thead class="sticky-top-table">
                    <tr>
                        <th class="ps-4 py-3">Mês/Ano</th>
                        <th>Saldo</th>
                        <th>Variação</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Detalhes</th>
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
                    ?>
                    <tr class="<?php echo $rebalanced ? 'rebalanced-row' : ''; ?>">
                        <td class="ps-4 fw-bold"><?php echo date('m/Y', strtotime($date)); ?></td>
                        <td><?php echo formatCurrency($currentValue, $portfolio['output_currency']); ?></td>
                        <td>
                            <span class="badge <?php echo $variation >= 0 ? 'bg-success-soft' : 'bg-danger-soft'; ?>">
                                <?php echo ($variation >= 0 ? '+' : '') . number_format($variation, 2, ',', '.'); ?>%
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($rebalanced): ?>
                                <span class="badge rounded-pill bg-info-soft"><i class="bi bi-arrow-repeat me-1"></i>Rebalanced</span>
                            <?php else: ?>
                                <span class="text-muted small">Mantido</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-link text-primary text-decoration-none" 
                                    onclick='openDetailsModal("<?php echo date('m/Y', strtotime($date)); ?>", <?php echo json_encode($data["asset_values"]); ?>, <?php echo $currentValue; ?>)'>
                                Ver Ativos <i class="bi bi-chevron-right"></i>
                            </button>
                        </td>
                    </tr>
                    <?php 
                            $prevValue = $currentValue;
                        endforeach; 
                    endif; ?>
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
                    <thead class="table-light"><tr><th class="ps-4">Ativo</th><th class="text-end">Valor</th><th class="text-end pe-4">Peso</th></tr></thead>
                    <tbody id="modalAssetsBody"></tbody>
                </table>
            </div>
            <div class="modal-footer bg-light"><div class="w-100 d-flex justify-content-between"><strong>Total:</strong><strong class="text-primary" id="modalTotal"></strong></div></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const currency = '<?php echo $portfolio['output_currency']; ?>';
    const chartData = <?php echo json_encode($chartData); ?>;
    const assetNames = {<?php foreach ($assets as $a) echo '"'.$a['asset_id'].'": "'.htmlspecialchars($a['name']).'",'; ?>};

    // 1. Gráfico de Evolução (Linha)
    new Chart(document.getElementById('valueChart'), {
        type: 'line',
        data: chartData.value_chart,
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (ctx) => `Valor: ${new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(ctx.raw)}`
                    }
                }
            },
            scales: { y: { ticks: { callback: (val) => val.toLocaleString('pt-BR') } } }
        }
    });

    // 2. Gráfico de Composição (Barras Empilhadas)
    new Chart(document.getElementById('compositionChart'), {
        type: 'bar',
        data: chartData.composition_chart,
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { x: { stacked: true }, y: { stacked: true, max: 100, ticks: { callback: (v) => v + '%' } } }
        }
    });

    // 3. Gráfico de Retornos (Barras)
    new Chart(document.getElementById('returnsChart'), {
        type: 'bar',
        data: chartData.returns_chart,
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: (v) => v + '%' } } }
        }
    });

    // Lógica do Modal e Busca
    function openDetailsModal(date, assetValues, total) {
        document.getElementById('modalDate').innerText = date;
        document.getElementById('modalTotal').innerText = new Intl.NumberFormat('pt-BR', {style:'currency', currency}).format(total);
        const body = document.getElementById('modalAssetsBody');
        body.innerHTML = '';
        for (const [id, val] of Object.entries(assetValues)) {
            body.innerHTML += `<tr><td class="ps-4">${assetNames[id] || id}</td><td class="text-end">${val.toLocaleString('pt-BR', {minimumFractionDigits:2})}</td><td class="text-end pe-4 text-muted">${((val/total)*100).toFixed(2)}%</td></tr>`;
        }
        new bootstrap.Modal(document.getElementById('detailsModal')).show();
    }

    document.getElementById('auditSearch').addEventListener('keyup', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#auditTable tbody tr').forEach(r => r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none');
    });

    function exportAuditCSV() {
        let csv = ["Data,Saldo,Variacao"];
        document.querySelectorAll("#auditTable tbody tr").forEach(r => {
            const c = r.querySelectorAll("td");
            csv.push(`${c[0].innerText},${c[1].innerText.replace(/[^\d,]/g,'')},${c[2].innerText}`);
        });
        const blob = new Blob([csv.join("\n")], {type: 'text/csv'});
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `auditoria_<?php echo $portfolio['id']; ?>.csv`;
        link.click();
    }
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>