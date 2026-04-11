<?php
/**
 * @var array $portfolio  Dados do portfólio
 * @var array $assets     Lista de ativos vinculados
 * @var array $latest     Último resultado de simulação
 * @var array $chartData  Dados da simulação decodificados
 * @var array $assetNames Mapeamento de ID para Nome
 * @var array $assetTargets Mapeamento de ID para Meta
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

                foreach ($auditLog as $date => $data):
                    if ($date === '_metadata') continue;

                    $currentValue       = $data['total_value'];
                    $totalBeforeDeposit = $data['total_before_deposit'] ?? $currentValue;
                    $variation          = $prevValue > 0
                        ? (($totalBeforeDeposit / $prevValue) - 1) * 100
                        : 0;
                    $rebalanced  = $data['rebalanced'] ?? false;
                    $depositMade = $data['deposit_made'] ?? 0;
                    $dateLabel   = date('m/Y', strtotime($date));
                ?>
                <tr data-date="<?= htmlspecialchars($date) ?>">

                    <!-- Mês/Ano — data-order usa ISO para ordenação cronológica correta -->
                    <td data-order="<?= $date ?>">
                        <span class="fw-bold"><?= $dateLabel ?></span>
                    </td>

                    <!-- Saldo Total — data-order com valor numérico -->
                    <td data-order="<?= $currentValue ?>">
                        <span class="fw-bold text-primary">
                            <?= formatCurrency($currentValue, $portfolio['output_currency']) ?>
                        </span>
                    </td>

                    <!-- Variação -->
                    <td data-order="<?= $variation ?>">
                        <span class="badge fs-6 px-2 py-1 <?= $variation >= 0 ? 'bg-soft-success' : 'bg-soft-danger' ?>">
                            <?= ($variation >= 0 ? '+' : '') . number_format($variation, 2, ',', '.') ?>%
                        </span>
                    </td>

                    <!-- Aporte -->
                    <td data-order="<?= $depositMade ?>">
                        <?php if ($depositMade > 0): ?>
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
                        <?php if ($rebalanced): ?>
                            <span class="badge rounded-pill bg-soft-info">
                                <i class="bi bi-arrow-repeat me-1"></i>Rebalanceado
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-light text-muted border">Mantido</span>
                        <?php endif; ?>
                    </td>

                    <!-- Botão expandir -->
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-secondary border-0 rounded-circle btn-expand"
                                title="Ver detalhes dos ativos em <?= $dateLabel ?>">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </td>
                </tr>
                <?php $prevValue = $currentValue; endforeach; ?>
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
const outputCurrency = <?= json_encode($portfolio['output_currency'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

/* ── Helper: formata moeda ───────────────────────────────────────────────── */
function fmtCur(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: outputCurrency,
        minimumFractionDigits: 2
    }).format(value);
}

/* ── Monta o HTML da linha expandida ─────────────────────────────────────── */
function buildChildRow(dateKey) {
    const log = window.simulationAuditLog;
    if (!log || !log[dateKey]) {
        return '<div class="child-row-wrapper text-muted p-4">Dados não disponíveis para este período.</div>';
    }

    const data         = log[dateKey];
    const assets       = data.asset_values         || {};
    const trades       = data.trades               || {};
    const assetsBefore = data.asset_values_before  || assets;
    const total        = data.total_value          || 1;
    const deposit      = data.deposit_made         || 0;
    const depositType  = data.deposit_type         || 'none';
    const isRebalance  = Object.keys(trades).length > 0;

    const depositLabels = {
        monthly:    'Aporte Periódico',
        strategic:  'Aporte Estratégico',
        smart:      'Aporte Direcionado ao Alvo',
        selic_cash: 'Aporte em Caixa SELIC'
    };

    let html = '<div class="child-row-wrapper">';

    /* ── Barra de resumo do mês ── */
    if (deposit > 0 || isRebalance) {
        html += '<div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom align-items-center" style="border-color: var(--border-color) !important;">';
        if (deposit > 0) {
            html += `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                <i class="bi bi-cash-coin me-1"></i>
                ${depositLabels[depositType] || 'Aporte'}: ${fmtCur(deposit)}
            </span>`;
        }
        if (isRebalance) {
            html += `<span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">
                <i class="bi bi-arrow-repeat me-1"></i>Rebalanceamento Executado
            </span>`;
        }
        html += '</div>';
    }

    /* ── Tabela de ativos (Similar ao Ver Ativos) ── */
    html += `
    <div class="asset-table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-borderless align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th class="ps-3 py-2 text-muted fw-semibold">Ativo</th>
                        <th class="text-end py-2 text-muted fw-semibold">Valor Final</th>
                        <th class="text-center py-2 text-muted fw-semibold">Aloc. Anterior</th>
                        <th class="text-center py-2 text-muted fw-semibold">Aloc. Atual</th>
                        <th class="text-center py-2 text-muted fw-semibold">Meta</th>
                        <th class="text-center py-2 text-muted fw-semibold">Desvio</th>
                        <th class="text-end pe-3 py-2 text-muted fw-semibold">Ajuste Rebal.</th>
                    </tr>
                </thead>
                <tbody>`;

    const totalBefore = Object.values(assetsBefore).reduce((s, v) => s + v, 0) || total;

    for (const [id, rawVal] of Object.entries(assets)) {
        const name   = assetNames[id]   || `Ativo #${id}`;
        const target = parseFloat(assetTargets[id]) || 0;
        const trade  = trades[id]       || null;

        const finalVal    = trade ? trade.post_value : rawVal;
        const allocPct    = total > 0      ? (finalVal / total) * 100      : 0;
        const prevVal     = assetsBefore[id] !== undefined ? assetsBefore[id] : rawVal;
        const allocBefore = totalBefore > 0 ? (prevVal  / totalBefore) * 100 : 0;
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
                <td class="text-end py-2 fw-semibold">${fmtCur(finalVal)}</td>
                <td class="text-center py-2 small text-muted">${allocBefore.toFixed(2)}%</td>
                <td class="text-center py-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2">${allocPct.toFixed(2)}%</span>
                </td>
                <td class="text-center py-2 small text-muted">${target.toFixed(2)}%</td>
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
        order: [[0, 'desc']],
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
