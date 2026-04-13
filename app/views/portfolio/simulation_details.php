<?php
/**
 * @var array $portfolio  Dados do portfólio
 * @var array $assets     Lista de ativos vinculados
 * @var array $latest     Último resultado de simulação
 * @var array $chartData  Dados da simulação decodificados
 * @var array $assetNames Mapeamento de ID para Nome
 * @var array $assetTargets Mapeamento de ID para Meta
 * @var array $assetCurrencies Mapeamento de ID para Moeda
 */

$title = 'Detalhes da Simulação: ' . htmlspecialchars($portfolio['name']);
ob_start();
?>

<!-- ─── Cabeçalho / Breadcrumb ─────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>">Portfólios</a></li>
                <li class="breadcrumb-item"><a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>"><?= htmlspecialchars($portfolio['name']) ?></a></li>
                <li class="breadcrumb-item active">Detalhes da Simulação</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Progresso Mensal Detalhado</h2>
        <p class="text-muted small mb-0">Análise profunda da evolução de cada ativo mês a mês.</p>
    </div>
    <div>
        <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>"
           class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<!-- ─── Card principal com a DataTable ────────────────────────────────────── -->
<div class="card shadow-sm border-0 rounded-3 mb-5">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="bi bi-table me-2 text-primary"></i>Histórico de Performance
        </h5>
        <small class="text-muted">
            Clique em <i class="bi bi-chevron-down"></i> ou na linha para expandir os detalhes do mês.
        </small>
    </div>
    <div class="card-body p-3">
        <table id="detailsTable" class="table table-hover align-middle mb-0 w-100">
            <thead class="table-light">
                <tr>
                    <th>Mês/Ano</th>
                    <th>Saldo Total</th>
                    <th>Variação Mensal</th>
                    <th>Aporte</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Expandir</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $prevValue = $portfolio['initial_capital'];
                $auditLog  = $chartData['audit_log'] ?? [];
                
                // Remove metadados se existirem
                if (isset($auditLog['_metadata'])) unset($auditLog['_metadata']);
                
                // Ordena por data (chave) de forma crescente
                ksort($auditLog);

                foreach ($auditLog as $date => $data):
                    $isInitialPoint = $data['is_initial_point'] ?? false;
                    $currentValue       = $data['total_value'];
                    $totalBeforeDeposit = $data['total_before_deposit'] ?? $currentValue;
                    
                    if ($isInitialPoint) {
                        $variation = 0;
                    } else {
                        $variation = $prevValue > 0
                            ? (($totalBeforeDeposit / $prevValue) - 1) * 100
                            : 0;
                    }

                    $rebalanced  = $data['rebalanced'] ?? false;
                    $depositMade = $data['deposit_made'] ?? 0;
                    $dateLabel   = date('m/Y', strtotime($date));
                ?>
                <tr data-date="<?= htmlspecialchars($date) ?>" class="<?= $isInitialPoint ? 'tr-initial-point' : '' ?>">

                    <!-- Mês/Ano — data-order usa ISO para ordenação cronológica correta -->
                    <td data-order="<?= $date ?>">
                        <span class="fw-bold"><?= $dateLabel ?></span>
                        <?php if ($isInitialPoint): ?>
                            <br><span class="badge-initial-capital">Capital Inicial</span>
                        <?php endif; ?>
                    </td>

                    <!-- Saldo Total — data-order com valor numérico -->
                    <td data-order="<?= $currentValue ?>">
                        <span class="fw-bold text-primary">
                            <?= formatCurrency($currentValue, $portfolio['output_currency']) ?>
                        </span>
                    </td>

                    <!-- Variação -->
                    <td data-order="<?= $variation ?>">
                        <?php if ($isInitialPoint): ?>
                            <span class="text-muted small">—</span>
                        <?php else: ?>
                            <span class="badge fs-6 px-2 py-1 <?= $variation >= 0 ? 'bg-soft-success' : 'bg-soft-danger' ?>">
                                <?= ($variation >= 0 ? '+' : '') . number_format($variation, 2, ',', '.') ?>%
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Aporte -->
                    <td data-order="<?= $depositMade ?>">
                        <?php if ($isInitialPoint): ?>
                            <span class="text-muted small">—</span>
                        <?php elseif ($depositMade > 0): ?>
                            <span class="text-success small fw-semibold">
                                <i class="bi bi-plus-circle-fill me-1"></i>
                                <?= formatCurrency($depositMade, $portfolio['output_currency']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- Status -->
                    <td class="text-center">
                        <?php if ($isInitialPoint): ?>
                            <span class="badge bg-light text-dark border rounded-pill px-3">
                                <i class="bi bi-flag-fill me-1 text-primary"></i>Mantido
                            </span>
                        <?php elseif ($rebalanced): ?>
                            <span class="badge rounded-pill bg-soft-info">
                                <i class="bi bi-arrow-repeat me-1"></i>Rebalanceado
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-light text-muted border">Mantido</span>
                        <?php endif; ?>
                    </td>

                    <!-- Botão expandir -->
                    <td class="text-center">
                        <button class="btn btn-sm <?= $isInitialPoint ? 'btn-primary' : 'btn-outline-secondary' ?> border-0 rounded-circle btn-expand"
                                title="Ver detalhes dos ativos em <?= $dateLabel ?>">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </td>
                </tr>
                <?php 
                    $prevValue = $currentValue; 
                endforeach; 
                ?>
            </tbody>

            <!-- Filtros por coluna no rodapé da tabela -->
            <tfoot class="table-light">
                <tr>
                    <th>
                        <input type="search" class="form-control form-control-sm col-filter"
                               placeholder="ex: 01/2023">
                    </th>
                    <th>
                        <input type="search" class="form-control form-control-sm col-filter"
                               placeholder="Buscar saldo…">
                    </th>
                    <th>
                        <input type="search" class="form-control form-control-sm col-filter"
                               placeholder="ex: +2,5%">
                    </th>
                    <th>
                        <input type="search" class="form-control form-control-sm col-filter"
                               placeholder="Buscar aporte…">
                    </th>
                    <th class="text-center">
                        <select class="form-select form-select-sm col-filter-status">
                            <option value="">Todos</option>
                            <option value="Rebalanceado">Rebalanceado</option>
                            <option value="Mantido">Mantido</option>
                        </select>
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- ─── Estilos ───────────────────────────────────────────────────────────── -->
<style>
    /* ── Badge soft colours ── */
    .bg-soft-success { background-color: rgba(25,135,84,.12);  color: #198754; }
    .bg-soft-danger  { background-color: rgba(220,53,69,.12);  color: #dc3545; }
    .bg-soft-info    { background-color: rgba(13,202,240,.12); color: #0aa2c0; }
    .bg-soft-primary { background-color: rgba(13,110,253,.12); color: #0d6efd; }

    /* ── Child row (Expandida) ── */
    .child-row-wrapper {
        padding: 24px;
        background-color: var(--bg-body, #f8f9fa);
        border-top: 2px solid rgba(13,110,253,.15);
    }
    .asset-table-container {
        background-color: var(--bg-card, #ffffff);
        border: 1px solid var(--border-color, #dee2e6);
        border-radius: 8px;
        overflow: hidden;
    }
    .asset-table-container thead {
        background-color: rgba(0,0,0,.02);
        border-bottom: 1px solid var(--border-color, #dee2e6);
    }
    .asset-table-container th {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .asset-table-container td {
        border-bottom: 1px solid var(--border-color, #dee2e6);
    }
    .asset-table-container tbody tr:last-child td {
        border-bottom: none;
    }

    /* ── Expanded row highlight ── */
    #detailsTable tbody tr.shown > td { background-color: rgba(13,110,253,.06) !important; }

    /* ── tfoot filter cells ── */
    #detailsTable tfoot th { padding: 6px 8px; }

    /* ── DataTables Dark Mode ── */
    [data-theme="dark"] .dataTables_wrapper { color: var(--text-main); }
    [data-theme="dark"] .dataTables_wrapper .dataTables_filter input,
    [data-theme="dark"] .dataTables_wrapper .dataTables_length select,
    [data-theme="dark"] #detailsTable tfoot .form-control,
    [data-theme="dark"] #detailsTable tfoot .form-select {
        background-color: var(--bg-card) !important;
        color: var(--text-main) !important;
        border-color: var(--border-color) !important;
    }
    [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: var(--text-main) !important;
    }
    [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--bg-card) !important;
        border-color: var(--border-color) !important;
        color: var(--text-main) !important;
    }
    [data-theme="dark"] .dataTables_wrapper .dataTables_info { color: var(--text-muted); }
    [data-theme="dark"] .card-header.bg-white                { background-color: var(--bg-card) !important; }
    [data-theme="dark"] table.dataTable thead th,
    [data-theme="dark"] table.dataTable tfoot th              { border-bottom-color: var(--border-color) !important; }
    [data-theme="dark"] .child-row-wrapper                    { background-color: var(--bg-body); }
    [data-theme="dark"] .asset-table-container                { background-color: var(--bg-card); border-color: var(--border-color); }
    [data-theme="dark"] .asset-table-container thead          { background-color: rgba(255,255,255,0.03); border-bottom-color: var(--border-color); }
    [data-theme="dark"] .asset-table-container td             { border-bottom-color: var(--border-color); color: var(--text-main); }
    [data-theme="dark"] .asset-table-container th             { color: var(--text-muted) !important; }
    [data-theme="dark"] .text-dark                            { color: var(--text-main) !important; }
</style>

<?php
/* ─── Captura o conteúdo principal ────────────────────────────────────────── */
$content = ob_get_clean();

/* ─── CSS adicional (ob_start garante execução correta do PHP) ─────────────── */
ob_start();
?>
<!-- DataTables 1.13.8 (Bootstrap 5) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<?php
$additional_css = ob_get_clean();

/* ─── JS adicional (ob_start resolve o bug de PHP-dentro-de-string) ─────────
 *  IMPORTANTE: O padrão anterior usava $additional_js = '...<?php echo ... ?>...'
 *  (single-quoted string) onde o PHP NÃO executa as tags embutidas, causando
 *  output literal de "<?php echo ... ?>" e quebrando todo o JavaScript.
 *  Com ob_start()/ob_get_clean() o PHP é executado normalmente.
 * ─────────────────────────────────────────────────────────────────────────── */
ob_start();
?>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
/* ── Dados do PHP — executados corretamente via ob_start ─────────────────── */
window.simulationAuditLog = <?= json_encode(
    $chartData['audit_log'] ?? [],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const assetNames    = <?= json_encode($assetNames,   JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const assetTargets  = <?= json_encode($assetTargets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const assetCurrencies = <?= json_encode($assetCurrencies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const assetTaxGroups  = <?= json_encode($assetTaxGroups ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const outputCurrency = <?= json_encode($portfolio['output_currency'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

/* ── Helper: formata moeda ───────────────────────────────────────────────── */
function fmtCur(value, currency) {
    const targetCurrency = currency || outputCurrency;
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: targetCurrency,
        minimumFractionDigits: 2
    }).format(value);
}

/* ── Pré-cálculo do Custo Médio de Aquisição (Base Total) ───────────────
 * O Custo de Aquisição não altera com a valorização.
 * Na compra, soma-se o valor. Na venda, abate-se proporcionalmente.
 * ─────────────────────────────────────────────────────────────────────── */
(function preCalculateAcquisitionCosts() {
    const log = window.simulationAuditLog;
    if (!log) return;

    // DEBUG: Verificação dos dados iniciais
    console.log("Audit Log carregado:", Object.keys(log).length, "registros");
    console.log("Grupos de Imposto carregados:", assetTaxGroups);
    window._debug_tax_logged = false;

    // Extrai as datas ordenadas cronologicamente
    const dates = Object.keys(log).filter(k => k !== '_metadata').sort();
    const currentCosts = {};
    const accumulatedRealized = {};
    
    // Controle de prejuízos acumulados por grupo de imposto (tax_group)
    const groupTaxResults = {
        // Ex: 'ETF_BR': { accumulatedLoss: 0, accumulatedProfit: 0, taxRate: 0.15 }
    };

    // Alíquotas por grupo (Pode ser expandido conforme necessidade)
    const TAX_RATES = {
        'ETF_BR': 0.15,
        'CRIPTOMOEDA': 0.15,
        'FUNDO_IMOBILIARIO': 0.20,
        'ETF_US': 0.15,
        'RENDA_FIXA': 0 // Não aplicável regra de compensação aqui conforme pedido
    };

    dates.forEach(date => {
        const data = log[date];
        const assetsBefore = data.asset_values_before || {};
        const trades = data.trades || {};
        const assets = data.asset_values || {};

        data.realized_profits = {};
        data.accumulated_realized_profits = {};
        
        // Resultados do mês por grupo para cálculo de IR
        const monthlyGroupResults = {};

        for (const id in assets) {
            if (currentCosts[id] === undefined) currentCosts[id] = 0;
            if (accumulatedRealized[id] === undefined) accumulatedRealized[id] = 0;

            const isInitialPoint = data.is_initial_point || false;
            const trade = trades[id];
            const delta = (trade && trade.delta !== undefined) ? parseFloat(trade.delta) : 0;
            const rawTaxGroup = assetTaxGroups[id] || 'RENDA_FIXA';
            
            // Normalização de Grupos de IR conforme solicitado pelo usuário
            // ACAO_BR -> ETF_BR
            // ETF_USA -> ETF_US
            let taxGroup = rawTaxGroup;
            if (taxGroup === 'ACAO_BR') taxGroup = 'ETF_BR';
            if (taxGroup === 'ETF_USA') taxGroup = 'ETF_US';

            // DEBUG: Verificando se encontrou o grupo para este ativo
            if (date === dates[0]) {
                console.log(`Ativo #${id} (${assetNames[id] || '?'}) -> Grupo: ${taxGroup}`);
            }

            // 1. Inicialização do custo (Ponto inicial ou primeiro mês ou quando o ativo estava zerado)
            if (currentCosts[id] === 0 || isInitialPoint) {
                if (isInitialPoint) {
                    currentCosts[id] = parseFloat(assets[id] || 0);
                } else if (delta > 0) {
                    currentCosts[id] = delta;
                } else if (parseFloat(assets[id] || 0) > 0) {
                    // Tenta encontrar o valor investido inicial (aporte inicial)
                    const assetValueBefore = parseFloat(data.asset_values_before ? data.asset_values_before[id] : 0);
                    if (assetValueBefore > 0) {
                        currentCosts[id] = assetValueBefore;
                    } else {
                        currentCosts[id] = parseFloat(assets[id] || 0);
                    }
                }
            }
            // 2. Operações subsequentes (Compras e Vendas)
            else {
                if (delta > 0) {
                    // COMPRA: Aumenta o custo base investido
                    currentCosts[id] += delta;
                } else if (delta < 0) {
                    // VENDA: Apura Lucro/Prejuízo Realizado sobre o valor de mercado atualizado
                    // (O valor pré-venda é igual ao saldo final do mês + o volume que foi sacado)
                    const preTradeValue = parseFloat(assets[id] || 0) + Math.abs(delta);
                    if (preTradeValue > 0) {
                        const proportionSold = Math.min(1, Math.abs(delta) / preTradeValue);
                        const costSold = currentCosts[id] * proportionSold;
                        const realizedProfit = Math.abs(delta) - costSold;
                        
                        data.realized_profits[id] = realizedProfit;
                        accumulatedRealized[id] += realizedProfit;
                        
                        // Soma ao resultado mensal do grupo para IR
                        if (taxGroup !== 'RENDA_FIXA') {
                            if (monthlyGroupResults[taxGroup] === undefined) monthlyGroupResults[taxGroup] = 0;
                            monthlyGroupResults[taxGroup] += realizedProfit;
                        }
                        
                        currentCosts[id] = Math.max(0, currentCosts[id] - costSold);
                    } else {
                        currentCosts[id] = 0;
                    }
                }
            }
            
            data.accumulated_realized_profits[id] = accumulatedRealized[id];
        }

        // CÁLCULO DE IR POR GRUPO (Com compensação de prejuízo)
        data.tax_summary = {};
        
        // Garante que todos os grupos que já tiveram movimentação ou tem saldo acumulado apareçam
        const allGroups = new Set([...Object.keys(monthlyGroupResults), ...Object.keys(groupTaxResults)]);

    for (const group of allGroups) {
        if (!groupTaxResults[group]) {
            groupTaxResults[group] = { accumulatedLoss: 0, accumulatedResult: 0 };
        }

        const monthProfit = monthlyGroupResults[group] || 0;
        let taxableProfit = 0;
        let taxToPay = 0;
        const prevLoss = groupTaxResults[group].accumulatedLoss;
        const prevResult = groupTaxResults[group].accumulatedResult;

        // Atualiza o resultado total acumulado do grupo
        groupTaxResults[group].accumulatedResult += monthProfit;

            if (monthProfit > 0) {
                // Se teve lucro, abate do prejuízo acumulado
                if (prevLoss < 0) {
                    const absLoss = Math.abs(prevLoss);
                    if (monthProfit > absLoss) {
                        taxableProfit = monthProfit - absLoss;
                        groupTaxResults[group].accumulatedLoss = 0;
                    } else {
                        taxableProfit = 0;
                        groupTaxResults[group].accumulatedLoss += monthProfit;
                    }
                } else {
                    taxableProfit = monthProfit;
                }
                
                const rate = TAX_RATES[group] || 0.15;
                taxToPay = taxableProfit * rate;
            } else if (monthProfit < 0) {
                // Se teve prejuízo, aumenta o prejuízo acumulado
                groupTaxResults[group].accumulatedLoss += monthProfit;
                taxableProfit = 0;
                taxToPay = 0;
            } else {
                // Se não teve lucro nem prejuízo no mês (monthProfit === 0)
                taxableProfit = 0;
                taxToPay = 0;
            }

        // Só adiciona ao resumo se houver algo relevante para mostrar:
        // Lucro no mês OU prejuízo no mês OU prejuízo acumulado de meses anteriores OU saldo acumulado significativo
        const hasActivity = Math.abs(monthProfit) > 0.000001;
        const hasPrevLoss = prevLoss < -0.000001;
        const hasAccResult = Math.abs(groupTaxResults[group].accumulatedResult) > 0.000001;
        const hasAccLoss = groupTaxResults[group].accumulatedLoss < -0.000001;

        if (hasActivity || hasPrevLoss || hasAccResult || hasAccLoss) {
            data.tax_summary[group] = {
                profit: monthProfit,
                prev_loss: prevLoss,
                taxable: taxableProfit,
                tax: taxToPay,
                new_loss: groupTaxResults[group].accumulatedLoss,
                accumulated_result: groupTaxResults[group].accumulatedResult
            };
        }
    }

    // DEBUG: Log do primeiro mês com tax_summary
    if (Object.keys(data.tax_summary).length > 0 && !window._debug_tax_logged) {
        console.log("Exemplo de tax_summary no mês " + date + ":", data.tax_summary);
        window._debug_tax_logged = true;
    }

        // Injeta o custo calculado no log deste mês para a tabela renderizar
        data.asset_costs = {};
        for (const id in currentCosts) {
            if (assets[id] > 0.01) {
                data.asset_costs[id] = currentCosts[id];
            } else {
                currentCosts[id] = 0; // Zera custo se a posição for liquidada
            }
        }
    });
})();

/* ── Monta o HTML da linha expandida ─────────────────────────────────────── */
function buildChildRow(dateKey) {
    const log = window.simulationAuditLog;
    if (!log || !log[dateKey]) {
        return '<div class="child-row-wrapper text-muted p-4">Dados não disponíveis para este período.</div>';
    }

    const data         = log[dateKey];
    const assets       = data.asset_values         || {};
    const trades       = data.trades               || {};
    const total        = data.total_value          || 1;
    const assetsBefore = data.asset_values_before  || assets;
    const totalBefore  = data.total_before_deposit || total;
    const costs        = data.asset_costs          || {};
    const deposit      = data.deposit_made         || 0;
    const depositType  = data.deposit_type         || 'none';
    const taxPaid      = data.tax_paid             || 0;
    const isRebalance  = Object.keys(trades).length > 0;

    const depositLabels = {
        monthly:    'Aporte Periódico',
        strategic:  'Aporte Estratégico',
        smart:      'Aporte Direcionado ao Alvo',
        selic_cash: 'Aporte em Caixa SELIC'
    };

    let html = '<div class="child-row-wrapper">';

    /* ── Barra de resumo do mês ── */
    if (deposit > 0 || isRebalance || taxPaid > 0) {
        html += '<div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom align-items-center" style="border-color: var(--border-color) !important;">';
        if (deposit > 0) {
            html += `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                <i class="bi bi-cash-coin me-1"></i>
                ${depositLabels[depositType] || 'Aporte'}: ${fmtCur(deposit)}
            </span>`;
        }
        if (taxPaid > 0) {
            html += `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1" title="Imposto sobre o Lucro Realizado nas vendas deste mês">
                <i class="bi bi-bank me-1"></i>
                Imposto Pago: ${fmtCur(taxPaid)}
            </span>`;
        }
        if (isRebalance) {
            html += `<span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">
                <i class="bi bi-arrow-repeat me-1"></i>Rebalanceamento Executado
            </span>`;
        }
        html += '</div>';
    }

    /* ── Destaque de L/P ACUMULADO por grupo de IR ── */
    if (data.tax_summary && Object.keys(data.tax_summary).length > 0) {
        html += `<div class="mb-4 bg-light bg-opacity-50 p-3 rounded-3 border">
            <div class="row align-items-center mb-2">
                <div class="col">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-graph-up-arrow me-2 text-primary"></i>L/P ACUM. por Grupo de IR
                        <small class="ms-2 text-muted fw-normal" style="font-size: 0.75rem;">(Considera prejuízos acumulados para abatimento)</small>
                    </h6>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3">`;
        
        for (const [group, info] of Object.entries(data.tax_summary)) {
            // Exibimos o Saldo Acumulado (accumulated_result) do grupo até este mês
            const accResult = info.accumulated_result || 0;
            const isNegative = accResult < -0.01;
            const icon = isNegative ? 'bi-dash-circle' : 'bi-plus-circle';
            
            html += `
                <div class="d-flex align-items-center bg-white px-3 py-2 rounded border shadow-sm" style="min-width: 180px;">
                    <div class="me-3">
                        <i class="bi ${icon} fs-5 ${isNegative ? 'text-danger' : 'text-success'}"></i>
                    </div>
                    <div>
                        <div class="text-muted smaller text-uppercase fw-bold" style="font-size: 0.65rem;">${group.replace(/_/g, ' ')}</div>
                        <div class="fw-bold ${isNegative ? 'text-danger' : 'text-success'}">
                            ${accResult > 0.01 ? '+' : ''}${fmtCur(accResult)}
                        </div>
                    </div>
                </div>`;
        }
        
        html += `</div>
        </div>`;
    }

    /* ── Resumo de Imposto de Renda por Grupo ── */
    if (data.tax_summary && Object.keys(data.tax_summary).length > 0) {
        html += `<div class="mb-4">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center">
                <i class="bi bi-calculator me-2 text-primary"></i>Resumo de Imposto de Renda (IR) do Mês
                <small class="ms-auto text-muted fw-normal" style="font-size: 0.75rem;">Regra: Compensação de prejuízos acumulados por grupo</small>
            </h6>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">`;
        
        for (const [group, info] of Object.entries(data.tax_summary)) {
            const hasLoss = info.new_loss < -0.01;
            const statusClass = info.tax > 0 ? 'border-danger' : (hasLoss ? 'border-danger' : 'border-success');
            const bgClass = info.tax > 0 ? 'bg-danger' : (hasLoss ? 'bg-danger' : 'bg-success');
            const hexColor = info.tax > 0 ? '#dc3545' : (hasLoss ? '#dc3545' : '#198754');
            
            const accResult = info.accumulated_result || 0;
            const accColor = accResult >= 0 ? 'text-success' : 'text-danger';

            html += `
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid ${hexColor} !important;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="fw-bold text-dark small text-uppercase">${group.replace(/_/g, ' ')}</span>
                                <span class="badge ${bgClass} bg-opacity-10 ${info.tax > 0 || hasLoss ? 'text-danger' : 'text-success'} border ${statusClass} smaller">
                                    ${info.tax > 0 ? 'Imposto a Pagar' : (hasLoss ? 'Prejuízo Acumulado' : 'Sem Imposto')}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted smaller">Saldo Acumulado:</span>
                                    <span class="fw-bold smaller ${accColor}">${accResult > 0 ? '+' : ''}${fmtCur(accResult)}</span>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar ${accResult >= 0 ? 'bg-success' : 'bg-danger'}" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted smaller">L/P no Mês:</span>
                                <span class="fw-medium smaller ${info.profit >= 0 ? 'text-success' : 'text-danger'}">${info.profit > 0 ? '+' : ''}${fmtCur(info.profit)}</span>
                            </div>`;
            
            if (info.prev_loss < -0.01) {
                html += `
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted smaller">Prejuízo a Compensar:</span>
                                <span class="text-danger smaller fw-medium">${fmtCur(info.prev_loss)}</span>
                            </div>`;
            }
            
            if (info.taxable > 0) {
                html += `
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted smaller">Base Tributável:</span>
                                <span class="text-dark smaller fw-bold">${fmtCur(info.taxable)}</span>
                            </div>`;
            }

            if (info.tax > 0) {
                html += `
                            <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                <span class="fw-bold text-dark small">Imposto Estimado:</span>
                                <span class="fw-bold text-danger">${fmtCur(info.tax)}</span>
                            </div>`;
            } else if (info.new_loss < -0.01) {
                html += `
                            <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                <span class="fw-bold text-dark small">Novo Saldo Devedor:</span>
                                <span class="fw-bold text-danger">${fmtCur(Math.abs(info.new_loss))}</span>
                            </div>`;
            } else {
                html += `
                            <div class="d-flex justify-content-between mt-2 pt-2 border-top text-success small fw-medium">
                                <span>Isento / Sem lucro tributável</span>
                            </div>`;
            }

            html += `
                        </div>
                    </div>
                </div>`;
        }
        
        html += `   </div>
        </div>`;
    }

    /* ── Tabela de ativos (Similar ao Ver Ativos) ── */
    html += `
    <div class="asset-table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-borderless align-middle mb-0 w-100" style="font-size: 0.80rem;">
                <thead>
                    <tr>
                        <th class="ps-3 py-2 text-muted fw-semibold">Ativo</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Quantidade">Qtd.</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Preço do ativo na moeda original">Preço Ativo</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Cotação do Dólar (USD-BRL)">Cotação USD</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Preço do ativo convertido para a moeda do portfólio">Preço Conv.</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Preço Médio Unitário">P. Médio</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Custo Base Total">Custo Total</th>
                        <th class="text-end py-2 text-muted fw-semibold">Valor Final</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Lucro/Prejuízo Latente (Não Realizado)">L/P Latente</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Lucro/Prejuízo Realizado na Venda">L/P Venda</th>
                        <th class="text-end py-2 text-muted fw-semibold" title="Lucro/Prejuízo Realizado Acumulado">L/P Acum.</th>
                        <th class="text-center py-2 text-muted fw-semibold" title="Alocação Anterior ao Rebalanceamento">% Anterior</th>
                        <th class="text-center py-2 text-muted fw-semibold" title="Alocação Atual">% Atual</th>
                        <th class="text-center py-2 text-muted fw-semibold">Meta</th>
                        <th class="text-center py-2 text-muted fw-semibold">Desvio</th>
                        <th class="text-end pe-3 py-2 text-muted fw-semibold">Ajuste Rebal.</th>
                    </tr>
                </thead>
                <tbody>`;

    for (const [id, rawVal] of Object.entries(assets)) {
        const name   = assetNames[id]   || `Ativo #${id}`;
        const target = parseFloat(assetTargets[id]) || 0;
        const trade  = trades[id]       || null;

        // Se o backend ainda não retornou 'costs', fazemos fallback para o valor bruto
        const cost        = costs[id] !== undefined ? costs[id] : rawVal;
        // rawVal é o valor do ativo no fim do mês, refletindo a valorização correta.
        const finalVal    = rawVal;
        const profit      = finalVal - cost;
        const profitColor = Math.abs(profit) < 0.01 ? 'text-muted' : (profit > 0 ? 'text-success' : 'text-danger');
        const profitSign  = profit > 0 ? '+' : '';

        // Tenta descobrir a quantidade para exibir o Preço Médio (Custo Unitário)
        const prices     = data.asset_prices     || {};
        const rawPrices  = data.asset_raw_prices || {};
        const quantities = data.asset_quantities || {};
        const fxRate     = data.fx_rate          || 0;
        let qty = 0;
        
        // Prioridade 1: Quantidade já vinda do backend (mais preciso)
        if (quantities[id] !== undefined) {
            qty = parseFloat(quantities[id]);
        } 
        // Prioridade 2: Calcular a partir do valor final e preço (fallback)
        else if (prices[id] !== undefined && parseFloat(prices[id]) > 0) {
            // O preço já vem convertido para a moeda do portfólio no log do backend (BacktestService:292)
            qty = finalVal / parseFloat(prices[id]);
        }
        
        // Se temos quantidade, calculamos o Preço Médio unitário no frontend
        // (O backend já deve enviar o 'cost' total consolidado em preCalculateAcquisitionCosts)
        let unitCost = 0;
        if (qty > 0.00000001) {
            unitCost = cost / qty;
        }

        let qtyHtml = '<span class="text-muted smaller" title="Requer dados no backend">—</span>';
        if (qty > 0) {
            qtyHtml = `<span class="text-dark fw-medium">${qty.toLocaleString('pt-BR', { maximumFractionDigits: 6 })}</span>`;
        }

        // Novos campos solicitados
        const assetCurrency = assetCurrencies[id] || 'BRL';
        const rawPriceValue = rawPrices[id] || 0;
        const convPriceValue = prices[id] || 0;

        let unitCostHtml = '<span class="text-muted smaller" title="Requer quantidade ou preço no backend">—</span>';
        if (unitCost > 0) {
            unitCostHtml = fmtCur(unitCost);
        }

        const realized = data.realized_profits ? data.realized_profits[id] : undefined;
        const accumRealized = data.accumulated_realized_profits ? data.accumulated_realized_profits[id] : 0;

        let realizedHtml = '<span class="text-muted smaller">—</span>';
        if (realized !== undefined) {
            const rColor = Math.abs(realized) < 0.01 ? 'text-muted' : (realized > 0 ? 'text-success' : 'text-danger');
            const rSign  = realized > 0 ? '+' : '';
            realizedHtml = `<span class="${rColor} fw-semibold">${rSign}${fmtCur(realized)}</span>`;
        }

        let accumHtml = '<span class="text-muted smaller">—</span>';
        if (accumRealized !== undefined) {
            if (Math.abs(accumRealized) < 0.01) {
                accumHtml = `<span class="text-muted">${fmtCur(0)}</span>`;
            } else {
                const aColor = accumRealized > 0 ? 'text-success' : 'text-danger';
                const aSign  = accumRealized > 0 ? '+' : '';
                accumHtml = `<span class="${aColor} fw-semibold">${aSign}${fmtCur(accumRealized)}</span>`;
            }
        }

        const valBefore   = assetsBefore[id] || 0;
        const allocBefore = totalBefore > 0 ? (valBefore / totalBefore) * 100 : 0;
        const allocPct    = total > 0      ? (finalVal / total) * 100      : 0;
        const deviation   = allocPct - target;

        const devColor = Math.abs(deviation) <= 1.0
            ? 'text-success'
            : (deviation > 0 ? 'text-warning' : 'text-danger');

        let tradeHtml = '<span class="text-muted small">—</span>';
        if (trade) {
            const delta = trade.delta;
            const sign  = delta > 0 ? '+' : '';
            const cls   = delta >= 0 ? 'text-success' : 'text-danger';
            tradeHtml = `<span class="fw-bold ${cls} smaller">${sign}${fmtCur(delta)}</span>`;
        }

        html += `
            <tr>
                <td class="ps-3 py-2 fw-medium text-dark">${name}</td>
                <td class="text-end py-2">${qtyHtml}</td>
                <td class="text-end py-2 text-muted fw-medium" title="Preço do ativo na moeda original (${assetCurrency})">${fmtCur(rawPriceValue, assetCurrency)}</td>
                <td class="text-end py-2 text-muted fw-medium" title="Cotação do Dólar no período">${fxRate > 0 ? fxRate.toLocaleString('pt-BR', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) : '—'}</td>
                <td class="text-end py-2 text-muted fw-medium" title="Preço do ativo convertido para ${outputCurrency}">${fmtCur(convPriceValue)}</td>
                <td class="text-end py-2 text-muted fw-medium">${unitCostHtml}</td>
                <td class="text-end py-2 text-muted">${fmtCur(cost)}</td>
                <td class="text-end py-2 fw-semibold">${fmtCur(finalVal)}</td>
                <td class="text-end py-2 fw-semibold ${profitColor}">${profitSign}${fmtCur(profit)}</td>
                <td class="text-end py-2">${realizedHtml}</td>
                <td class="text-end py-2">${accumHtml}</td>
                <td class="text-center py-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-1">${allocBefore.toFixed(2)}%</span>
                </td>
                <td class="text-center py-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-1">${allocPct.toFixed(2)}%</span>
                </td>
                <td class="text-center py-2 text-muted">${target.toFixed(2)}%</td>
                <td class="text-center py-2 fw-medium ${devColor}">
                    ${deviation > 0 ? '+' : ''}${deviation.toFixed(2)}%
                </td>
                <td class="text-end pe-3 py-2">${tradeHtml}</td>
            </tr>`;
    }

    html += `
                </tbody>
            </table>
        </div>
    </div></div>`;
    return html;
}

/* ── Inicialização da DataTable ──────────────────────────────────────────── */
$(document).ready(function () {
    const table = $('#detailsTable').DataTable({
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            ['10 linhas', '25 linhas', '50 linhas', '100 linhas', 'Todas']
        ],
        order: [[0, 'asc']],
        autoWidth: false,

        /* ── Layout: Tamanho + Busca | Tabela | Paginação ── */
        dom:
            "<'row align-items-center mb-3'" +
                "<'col-sm-6 col-md-4'l>" +
                "<'col-sm-6 col-md-8 text-sm-end'f>" +
            ">" +
            "<'row'<'col-12'tr>>" +
            "<'row mt-3 align-items-center'" +
                "<'col-sm-5 text-muted small'i>" +
                "<'col-sm-7'p>" +
            ">",

        /* ── Tradução pt-BR inline ── */
        language: {
            sProcessing:   'Processando…',
            sLengthMenu:   'Mostrar _MENU_ registros',
            sZeroRecords:  'Nenhum registro encontrado',
            sEmptyTable:   'Nenhum dado disponível',
            sInfo:         'Mostrando _START_ a _END_ de _TOTAL_ registros',
            sInfoEmpty:    'Mostrando 0 a 0 de 0 registros',
            sInfoFiltered: '(filtrado de _MAX_ no total)',
            sSearch:       'Busca rápida:',
            sSearchPlaceholder: 'Filtrar linhas…',
            oPaginate: {
                sFirst:    '<i class="bi bi-chevron-bar-left"></i>',
                sPrevious: '<i class="bi bi-chevron-left"></i>',
                sNext:     '<i class="bi bi-chevron-right"></i>',
                sLast:     '<i class="bi bi-chevron-bar-right"></i>'
            },
            oAria: {
                sSortAscending:  ': clique para ordenar ascendente',
                sSortDescending: ': clique para ordenar descendente'
            }
        },

        /* ── Definições de coluna ── */
        columnDefs: [
            /* Coluna "Expandir" – sem ordenação nem busca */
            {
                targets: 5,
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ],

        /* ── Filtros individuais por coluna no tfoot ── */
        initComplete: function () {
            const api = this.api();

            api.columns().every(function () {
                const col    = this;
                const footer = $(col.footer());
                if (!footer.length) return;

                /* Inputs de texto – colunas 0, 1, 2, 3 */
                const input = footer.find('input.col-filter');
                if (input.length) {
                    input.on('input search', function () {
                        if (col.search() !== this.value) {
                            col.search(this.value).draw();
                        }
                    });
                }

                /* Select de Status – coluna 4 */
                const sel = footer.find('select.col-filter-status');
                if (sel.length) {
                    sel.on('change', function () {
                        const val = $(this).val();
                        col.search(val, true, false).draw();
                    });
                }
            });
        }
    });

    /* ── Expansão via botão ─────────────────────────────────────────────── */
    $('#detailsTable tbody').on('click', 'button.btn-expand', function (e) {
        e.stopPropagation();

        const btn     = $(this);
        const tr      = btn.closest('tr');
        let row       = table.row(tr[0]);
        const dateKey = tr.attr('data-date');   // attr() evita cache jQuery
        const icon    = btn.find('i');

        if (!dateKey) return;

        /* ── Fallback seguro: se table.row() falhar por alguma interação ── 
         *  Busca a linha manualmente pela API do DT comparando o data-date.
         * ─────────────────────────────────────────────────────────────── */
        if (!row || !row.length) {
            table.rows().every(function () {
                if ($(this.node()).attr('data-date') === dateKey) {
                    row = this;
                    return false; // break
                }
            });
        }

        if (!row || !row.length) return;

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            btn.removeClass('btn-primary text-white').addClass('btn-outline-secondary');
        } else {
            row.child(buildChildRow(dateKey)).show();
            tr.addClass('shown');
            icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
            btn.removeClass('btn-outline-secondary').addClass('btn-primary text-white');
        }
    });

    /* ── Clique na linha inteira também expande ── */
    $('#detailsTable tbody').on('click', 'tr', function (e) {
        if ($(this).hasClass('child')) return; // ignora se for a linha expandida (child row)
        if ($(e.target).closest('button, a, input, select').length) return;
        $(this).find('.btn-expand').trigger('click');
    });
});
</script>
<?php
$additional_js = ob_get_clean();

include_once __DIR__ . '/../layouts/main.php';
?>
