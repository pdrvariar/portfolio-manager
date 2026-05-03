<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
const SNAPSHOTS         = {$snapshotsJsonPhp};
const METRICS           = {$metricsJsonPhp};
const PORTFOLIO_URLS    = {$portfolioUrlsJsonPhp};
const CSRF_TOKEN        = {$csrfTokenJson};
const SHOW_PORTFOLIO_COL = {$showPortfolioCol};
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

function buildChildRow(simId, portfolioId) {
    const data = SNAPSHOTS[simId];
    const m    = METRICS[simId] || {};
    const pUrls= PORTFOLIO_URLS[portfolioId] || {};
    const cur  = (data && data.portfolio && data.portfolio.output_currency) ? data.portfolio.output_currency : "BRL";

    function kpi(icon, label, value, colorClass, bgClass) {
        return '<div class="result-kpi '+(bgClass||"")+'">'+
               '<div class="result-kpi-icon '+(colorClass||"text-primary")+'"><i class="bi '+icon+'"></i></div>'+
               '<div class="result-kpi-label">'+label+'</div>'+
               '<div class="result-kpi-value '+(colorClass||"")+'">'+value+'</div>'+
               '</div>';
    }
    function pctBadge(val) {
        if (val === null || val === undefined || val === "") return '<span class="text-muted">—</span>';
        const v = parseFloat(val); const sign = v >= 0 ? "+" : ""; const cls = v >= 0 ? "text-success" : "text-danger";
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

    const volColor   = vol   <= 10 ? "text-success" : (vol   <= 20 ? "text-warning" : "text-danger");
    const sharpeColor= sharpe >= 1 ? "text-success" : (sharpe >= 0.5 ? "text-warning" : "text-danger");
    const ddColor    = dd    <= 10 ? "text-success" : (dd    <= 25 ? "text-warning" : "text-danger");

    // Portfolio badge header
    const pNameBadge = pUrls.name
        ? '<div class="mb-3 d-flex align-items-center gap-2"><i class="bi bi-briefcase text-muted"></i><span class="text-muted" style="font-size:.8rem;">Portfólio:</span> <a href="'+pUrls.view+'" class="fw-bold text-primary text-decoration-none" style="font-size:.85rem;">'+pUrls.name+'</a></div>'
        : '';

    const summaryHtml =
        '<div class="result-summary-block mb-3">'+
        pNameBadge +
        '<div class="result-group-label"><i class="bi bi-wallet2 me-1"></i>Patrimônio</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-bank","Capital Inicial",fmtCurrency(totalInvested,cur),"text-primary","kpi-primary")+
            kpi("bi-plus-circle","Total de Aportes",fmtCurrency(totalDeposits,cur),"text-info","kpi-info")+
            kpi("bi-graph-up-arrow","Patrimônio Final",fmtCurrency(totalValue,cur),totalValue>=totalInvested?"text-success":"text-danger",totalValue>=totalInvested?"kpi-success":"kpi-danger")+
            kpi("bi-cash-stack","Ganho Bruto",(interestEarned>=0?"+":"")+fmtCurrency(interestEarned,cur),interestEarned>=0?"text-success":"text-danger",interestEarned>=0?"kpi-success":"kpi-danger")+
            (taxPaid>0?kpi("bi-receipt","Imposto Pago","−"+fmtCurrency(taxPaid,cur),"text-danger","kpi-danger"):"")+
        '</div>'+
        '<div class="result-group-label mt-3"><i class="bi bi-bar-chart-line me-1"></i>Performance</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-percent","Retorno Anual<br><small style=\'font-size:.6rem;opacity:.7;\'>com aportes</small>",pctBadge(m.annual_return),annReturn>=0?"text-success":"text-danger")+
            kpi("bi-trophy","Retorno Estratégia<br><small style=\'font-size:.6rem;opacity:.7;\'>puro dos ativos</small>",pctBadge(m.strategy_annual_return),strReturn>=0?"text-success":"text-danger")+
            kpi("bi-tags","ROI Total",(roi>=0?"+":"")+fmt(roi)+"%",roi>=0?"text-success":"text-danger")+
        '</div>'+
        '<div class="result-group-label mt-3"><i class="bi bi-shield-exclamation me-1"></i>Risco</div>'+
        '<div class="result-kpi-grid">'+
            kpi("bi-activity","Volatilidade",fmt(vol)+"%",volColor)+
            kpi("bi-speedometer2","Índice Sharpe",fmt(sharpe),sharpeColor)+
            kpi("bi-arrow-down-circle","Drawdown Máx.","−"+fmt(dd)+"%",ddColor)+
            (dd > 0 ? kpi("bi-shield-check","Calmar<br><small style='font-size:.6rem;opacity:.7;'>ret.estr./drawdown</small>",
                (function(){const c=strReturn/dd;return (c>=1?'<span class="text-success fw-bold">':'<span class="'+(c>=0.5?"text-warning":"text-danger")+'">') + fmt(c) + '</span>';})(),
                strReturn/dd >= 1 ? "text-success" : (strReturn/dd >= 0.5 ? "text-warning" : "text-danger")) : "")+
            kpi("bi-arrow-up-right","Melhor Mês","+"+fmt(maxGain)+"%","text-success")+
            kpi("bi-arrow-down-left","Pior Mês","−"+fmt(maxLoss)+"%","text-danger")+
        '</div>'+
        (m.allocation_label ? '<div class="result-group-label mt-3"><i class="bi bi-stars me-1" style="color:#fd7e14;"></i>Cenário de Alocação (Monte Carlo)</div>'+
            '<div class="d-flex flex-wrap gap-2"><span class="config-pill"><i class="bi bi-pie-chart text-warning"></i><strong>'+m.allocation_label+'</strong></span></div>' : '')+
        '</div>';

    if (!data || !data.portfolio) {
        return '<div class="child-config p-3 m-2">'+summaryHtml+
               '<div class="p-3 text-muted small"><i class="bi bi-exclamation-circle me-1"></i>Configuração não disponível (simulação anterior  ativação desta funcionalidade).</div></div>';
    }
    const p = data.portfolio;
    const assets = data.assets || [];

    const genPills = [
        pill("bi-cash-coin","Capital inicial",fmtCurrency(p.initial_capital,p.output_currency)),
        pill("bi-calendar3","Período",
             (p.start_date?p.start_date.substring(0,7).split("-").reverse().join("/"):"?")+
             " > "+(p.end_date?p.end_date.substring(0,7).split("-").reverse().join("/"):"Hoje")),
        pill("bi-currency-exchange","Moeda saída",p.output_currency||"BRL"),
        pill("bi-arrow-repeat","Rebalanceamento",FREQ_LABELS[p.rebalance_frequency]||p.rebalance_frequency||"-"),
        pill("bi-sliders","Tipo rebal.",REBAL_TYPE_LABELS[p.rebalance_type]||p.rebalance_type||"-"),
    ];
    if (p.rebalance_margin) genPills.push(pill("bi-arrows-expand","Margem rebal.",fmt(p.rebalance_margin)+"%"));
    if (p.use_cash_assets_for_rebalance==1) genPills.push('<span class="config-pill"><i class="bi bi-piggy-bank text-primary"></i><strong class="text-primary">Caixa no rebalanceamento</strong></span>');

    let depositHtml = "";
    if (p.simulation_type && p.simulation_type !== "standard") {
        const dPills = [pill("bi-wallet2","Tipo",SIM_TYPE_LABELS[p.simulation_type]||p.simulation_type)];
        if (p.deposit_amount) {
            dPills.push(pill("bi-plus-circle","Valor aporte",fmtCurrency(p.deposit_amount,p.deposit_currency)));
            dPills.push(pill("bi-calendar-check","Frequência",FREQ_LABELS[p.deposit_frequency]||p.deposit_frequency||"-"));
        }
        if (p.deposit_inflation_adjusted==1) dPills.push('<span class="config-pill"><i class="bi bi-graph-up text-success"></i><strong class="text-success">Ajustado pela inflação</strong></span>');
        if (p.strategic_threshold)           dPills.push(pill("bi-bullseye","Gatilho estratégico",fmt(p.strategic_threshold)+"%"));
        if (p.strategic_deposit_percentage)  dPills.push(pill("bi-percent","% no gatilho",fmt(p.strategic_deposit_percentage)+"%"));
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet-fill me-1"></i>Estratégia de Aportes</div><div class="d-flex flex-wrap gap-2">'+dPills.join("")+'</div></div>';
    } else {
        depositHtml = '<div class="mt-3"><div class="config-section-title"><i class="bi bi-wallet me-1"></i>Aportes</div><span class="config-pill text-muted"><i class="bi bi-dash-circle"></i> Sem aportes periódicos</span></div>';
    }

    let taxHtml = "";
    const TAX_GROUP_LABELS = {CRIPTOMOEDA:"Criptomoeda",ETF_US:"ETF (EUA)",ETF_BR:"ETF (BR)",RENDA_FIXA:"Renda Fixa",FUNDO_IMOBILIARIO:"Fundo Imobiliário"};
    const TAX_GROUPS_ORDER = ["CRIPTOMOEDA","ETF_US","ETF_BR","RENDA_FIXA","FUNDO_IMOBILIARIO"];
    let taxRates = null;
    if (p.profit_tax_rates_json) { try { taxRates = (typeof p.profit_tax_rates_json==="string")?JSON.parse(p.profit_tax_rates_json):p.profit_tax_rates_json; } catch(e){} }
    if (taxRates && typeof taxRates==="object" && Object.keys(taxRates).length>0) {
        let taxPills = TAX_GROUPS_ORDER.map(function(group){
            const rate=taxRates[group]; if(rate===undefined||rate===null||rate==="") return null;
            return pill("bi-percent",TAX_GROUP_LABELS[group]||group,fmt(rate)+"%","text-danger");
        }).filter(Boolean).join("");
        if (taxPills) taxHtml='<div class="mt-3"><div class="config-section-title"><i class="bi bi-receipt me-1"></i>Imposto de Renda</div><div class="d-flex flex-wrap gap-2">'+taxPills+'</div></div>';
    } else if (p.profit_tax_rate) {
        taxHtml='<div class="mt-3"><div class="config-section-title"><i class="bi bi-receipt me-1"></i>Imposto de Renda</div><div class="d-flex flex-wrap gap-2">'+pill("bi-percent","Alíquota geral",fmt(p.profit_tax_rate)+"%","text-danger")+'</div></div>';
    }

    let assetsRows = "";
    assets.forEach(function(a, i) {
        const pct   = parseFloat(a.allocation_percentage||0);
        const color = BAR_COLORS[i%BAR_COLORS.length];
        const margin= (a.rebalance_margin_down||a.rebalance_margin_up)
            ? '<span>Entre '+fmt(a.rebalance_margin_down||0)+'%  '+fmt(a.rebalance_margin_up||0)+'%</span>' : "-";
        assetsRows +=
            '<tr>'+
            '<td style="font-size:.8rem;padding:5px 8px;"><span class="fw-bold">'+(a.name||"-")+'</span> <span class="text-muted" style="font-size:.72rem;">'+(a.code||"")+'</span></td>'+
            '<td style="font-size:.75rem;padding:5px 8px;" class="text-muted">'+(a.currency||"-")+'</td>'+
            '<td style="padding:5px 8px;min-width:160px;"><div class="d-flex align-items-center gap-2"><div class="asset-bar-wrap flex-grow-1"><div class="asset-bar" style="width:'+pct+'%;background:'+color+';"></div></div><strong style="font-size:.8rem;min-width:44px;text-align:right;">'+fmt(pct)+'%</strong></div></td>'+
            '<td style="font-size:.72rem;padding:5px 8px;" class="text-muted text-end">'+margin+'</td>'+
            '</tr>';
    });

    const assetsHtml = assets.length>0
        ? '<div class="mt-3"><div class="config-section-title"><i class="bi bi-pie-chart-fill me-1"></i>Composição dos Ativos</div>'+
          '<table class="table table-borderless mb-0" style="background:transparent;"><thead><tr>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Ativo</th>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Moeda</th>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;">Alocação</th>'+
          '<th style="font-size:.7rem;padding:3px 8px;opacity:.7;text-align:right;">Margem Rebal.</th>'+
          '</tr></thead><tbody>'+assetsRows+'</tbody></table></div>'
        : "";

    const applyUrl       = pUrls.apply || "#";
    const createSnapUrl  = pUrls.create_from_snap || "#";
    const pDisplayName   = pUrls.name ? pUrls.name : "este portfólio";

    const applyBtn =
        '<div class="mt-4 pt-3 border-top">'+
        '<div class="d-flex align-items-center gap-2 mb-3">'+
        '<i class="bi bi-lightbulb-fill text-warning fs-5"></i>'+
        '<span class="fw-bold child-config-title" style="font-size:.88rem;">O que deseja fazer com esta configuração?</span>'+
        '</div>'+
        '<div class="row g-3">'+
        /*  Card: Aplicar ao portfólio atual  */
        '<div class="col-12 col-md-6">'+
        '<div class="action-card action-card-warning h-100">'+
        '<div class="action-card-icon"><i class="bi bi-arrow-counterclockwise"></i></div>'+
        '<div class="action-card-body">'+
        '<div class="action-card-title">Aplicar ao Portfólio Atual</div>'+
        '<div class="action-card-desc">Substitui as configurações de <strong>'+pDisplayName+'</strong> pelos parâmetros desta simulação. Ação reversível via edição.</div>'+
        '</div>'+
        '<form method="POST" action="'+applyUrl+'" onsubmit="return confirm(\'Atenção: isso irá substituir as configurações atuais do portfólio.\\n\\nCapital, período, aportes, rebalanceamento e ativos serão alterados.\\n\\nDeseja continuar?\')">'+
        '<input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'">'+
        '<input type="hidden" name="simulation_id" value="'+simId+'">'+
        '<button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold shadow-sm no-spinner w-100 mt-2">'+
        '<i class="bi bi-arrow-counterclockwise me-2"></i>Aplicar ao Portfólio'+
        '</button></form>'+
        '</div></div>'+
        /*  Card: Criar novo portfólio  */
        '<div class="col-12 col-md-6">'+
        '<div class="action-card action-card-success h-100">'+
        '<div class="action-card-icon"><i class="bi bi-plus-circle"></i></div>'+
        '<div class="action-card-body">'+
        '<div class="action-card-title">Criar Novo Portfólio</div>'+
        '<div class="action-card-desc">Gera um portfólio novo com exatamente esta configuração, sem alterar nenhum portfólio existente.</div>'+
        '</div>'+
        '<button type="button" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm w-100 mt-2"'+
        ' data-sim-id="'+simId+'" data-create-url="'+createSnapUrl+'" data-bs-toggle="modal" data-bs-target="#createFromSnapshotModal" onclick="prepareCreateFromSnapshot(this)">'+
        '<i class="bi bi-plus-lg me-2"></i>Criar Novo Portfólio'+
        '</button>'+
        '</div></div>'+
        '</div>'+ /* /row */
        '</div>'; /* /border-top */

    return '<div class="child-config p-3 m-2">'+
        summaryHtml+
        '<div class="d-flex align-items-center gap-2 mb-3">'+
        '<i class="bi bi-clipboard-data text-primary fs-5"></i>'+
        '<span class="fw-bold child-config-title" style="font-size:.9rem;">Configuração usada nesta simulação</span>'+
        '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1" style="font-size:.68rem;">ID #'+simId+'</span>'+
        '</div>'+
        '<div class="config-section-title"><i class="bi bi-gear me-1"></i>Parâmetros Gerais</div>'+
        '<div class="d-flex flex-wrap gap-2">'+genPills.join("")+'</div>'+
        depositHtml + taxHtml + assetsHtml + applyBtn +
        '</div>';
}

const COMPARE_URL_BASE = "{$compareUrlBase}";

$(document).ready(function () {
    // Ativar tooltips Bootstrap nos ícones (i) dos cabeçalhos
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el, { html: false });
    });

    // Column indexes: now there's an extra checkbox col at index 0
    // cols: 0=checkbox, 1=expand, 2=#id, [3=portfolio?], 3/4=simDate, 4/5=createdAt, ...
    const colOffset = SHOW_PORTFOLIO_COL ? 1 : 0;
    // text-end columns (value/return/etc.) — shifted by 1 due to new checkbox col
    const textEndCols = SHOW_PORTFOLIO_COL
        ? [6,7,8,9,10,11,12,13]
        : [5,6,7,8,9,10,11,12];

    const table = $("#allHistoryTable").DataTable({
        order: [[4 + colOffset, "desc"]],
        pageLength: 25,
        autoWidth: false,
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 1] },
            { className: "text-end", targets: textEndCols }
        ],
        language: {
            sProcessing:"Processando...", sLengthMenu:"Mostrar _MENU_ registros",
            sZeroRecords:"Nenhuma simulação encontrada", sEmptyTable:"Nenhum dado disponível",
            sInfo:"Mostrando _START_ a _END_ de _TOTAL_ registros",
            sInfoEmpty:"Mostrando 0 a 0 de 0 registros",
            sInfoFiltered:"(filtrado de _MAX_ no total)",
            sSearch:"Pesquisar:", sSearchPlaceholder:"Buscar simulação ou portfólio...",
            oPaginate:{
                sFirst:'<i class="bi bi-chevron-bar-left"></i>',
                sPrevious:'<i class="bi bi-chevron-left"></i>',
                sNext:'<i class="bi bi-chevron-right"></i>',
                sLast:'<i class="bi bi-chevron-bar-right"></i>'
            }
        },
        dom:"<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-sm-end'f>><'row'<'col-12'tr>><'row mt-3 align-items-center'<'col-sm-5 text-muted small'i><'col-sm-7'p>>"
    });

    //  Expand button 
    $("#allHistoryTable tbody").on("click", "button.btn-expand", function (e) {
        e.stopPropagation();
        const btn        = $(this);
        const tr         = btn.closest("tr");
        const simId      = tr.attr("data-sim-id");
        const portfolioId= tr.attr("data-portfolio-id");
        const icon       = btn.find("i");
        const row        = table.row(tr[0]);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass("dt-hasChild");
            icon.removeClass("bi-chevron-up").addClass("bi-chevron-down");
            btn.removeClass("btn-primary text-white").addClass("btn-outline-secondary");
        } else {
            row.child(buildChildRow(simId, portfolioId)).show();
            tr.addClass("dt-hasChild");
            icon.removeClass("bi-chevron-down").addClass("bi-chevron-up");
            btn.removeClass("btn-outline-secondary").addClass("btn-primary text-white");
        }
    });

    $("#allHistoryTable tbody").on("click", "tr", function (e) {
        if ($(this).hasClass("child")) return;
        if ($(e.target).closest("button, a, input, select, .form-check").length) return;
        $(this).find(".btn-expand").trigger("click");
    });

    //  Compare selection 
    const MAX_SELECT = 5;
    let selectedSims = {}; // {id: label}

    function updateCompareBar() {
        const count = Object.keys(selectedSims).length;
        const bar   = $("#compareBar");
        const chips = $("#compareChips");
        const btn   = $("#compareBtn");

        if (count === 0) {
            bar.addClass("d-none");
            return;
        }
        bar.removeClass("d-none");
        $("#compareCount").text(count + (count === 1 ? " selecionada" : " selecionadas") + " (mín. 2 para comparar)");

        chips.empty();
        Object.entries(selectedSims).forEach(([id, label]) => {
            chips.append(
                '<span class="compare-chip">' +
                '<i class="bi bi-bar-chart-line" style="font-size:.7rem;opacity:.7;"></i>' +
                label +
                '<button class="chip-remove" data-id="' + id + '" title="Remover">' +
                '<i class="bi bi-x-lg" style="font-size:.65rem;"></i></button></span>'
            );
        });

        if (count >= 2) {
            const ids = Object.keys(selectedSims).map(function(id){ return 'ids[]=' + id; }).join('&');
            btn.attr("href", COMPARE_URL_BASE + '&' + ids).removeClass("disabled");
        } else {
            btn.attr("href", "#").addClass("disabled");
        }
    }

    // Checkbox change
    $("#allHistoryTable tbody").on("change", ".sim-checkbox", function () {
        const cb    = $(this);
        const id    = cb.val();
        const label = cb.data("label");
        const tr    = cb.closest("tr");

        if (cb.is(":checked")) {
            if (Object.keys(selectedSims).length >= MAX_SELECT) {
                cb.prop("checked", false);
                // Flash the bar
                $("#compareBar").addClass("shake");
                setTimeout(() => $("#compareBar").removeClass("shake"), 500);
                return;
            }
            selectedSims[id] = label;
            tr.addClass("sim-selected");
        } else {
            delete selectedSims[id];
            tr.removeClass("sim-selected");
        }
        updateCompareBar();
    });

    // Chip remove
    $("#compareChips").on("click", ".chip-remove", function () {
        const id = $(this).data("id");
        delete selectedSims[id];
        $(`.sim-checkbox[value="` + id + `"]`).prop("checked", false).closest("tr").removeClass("sim-selected");
        updateCompareBar();
    });

    // Clear all
    $("#clearCompare").on("click", function () {
        selectedSims = {};
        $(".sim-checkbox").prop("checked", false);
        $("tr.sim-selected").removeClass("sim-selected");
        $("#selectAllSims").prop("checked", false);
        updateCompareBar();
    });

    // Select all (only visible page rows, up to MAX)
    $("#selectAllSims").on("change", function () {
        const checked = $(this).is(":checked");
        if (!checked) {
            // Uncheck all
            $(".sim-checkbox:checked").each(function () {
                $(this).prop("checked", false).trigger("change");
            });
        } else {
            // Check up to MAX visible rows
            table.rows({ page: 'current' }).nodes().each(function () {
                const cb = $(this).find(".sim-checkbox");
                if (!cb.is(":checked") && Object.keys(selectedSims).length < MAX_SELECT) {
                    cb.prop("checked", true).trigger("change");
                }
            });
        }
    });

    //  Advanced Metric Filters 

    // Toggle panel
    const advToggle = document.getElementById("advFilterToggle");
    const advBody   = document.getElementById("advFilterBody");
    const advChevron = advToggle.querySelector(".adv-filter-chevron");
    const bsCollapse = new bootstrap.Collapse(advBody, { toggle: false });

    advToggle.addEventListener("click", function () {
        bsCollapse.toggle();
        const expanded = advToggle.getAttribute("aria-expanded") === "true";
        advToggle.setAttribute("aria-expanded", String(!expanded));
    });
    advBody.addEventListener("shown.bs.collapse",  () => { advToggle.setAttribute("aria-expanded","true"); });
    advBody.addEventListener("hidden.bs.collapse", () => { advToggle.setAttribute("aria-expanded","false"); });

    // Read filter values from inputs
    function getAdvFilters() {
        const filters = {};
        document.querySelectorAll(".adv-filter-input").forEach(function(inp) {
            const field = inp.dataset.field;
            const bound = inp.dataset.bound;
            const val   = inp.value.trim();
            if (!filters[field]) filters[field] = {};
            if (val !== "") filters[field][bound] = parseFloat(val);
        });
        return filters;
    }

    // Count active (filled) filter inputs
    function countActiveFilters(filters) {
        let n = 0;
        Object.values(filters).forEach(function(bounds) {
            if (bounds.min !== undefined) n++;
            if (bounds.max !== undefined) n++;
        });
        return n;
    }

    // Update badge + clear button
    function updateAdvFilterUI(activeCount) {
        const badge  = document.getElementById("advFilterBadge");
        const clearBtn = document.getElementById("advFilterClear");
        if (activeCount > 0) {
            badge.textContent = activeCount;
            badge.classList.remove("d-none");
            clearBtn.classList.remove("d-none");
        } else {
            badge.classList.add("d-none");
            clearBtn.classList.add("d-none");
        }
        // Highlight groups that have values
        document.querySelectorAll(".adv-filter-group").forEach(function(grp) {
            const inputs = grp.querySelectorAll(".adv-filter-input");
            const anyFilled = Array.from(inputs).some(i => i.value.trim() !== "");
            grp.classList.toggle("has-value", anyFilled);
        });
    }

    // Get metric value for a sim row (calmar computed on-the-fly)
    function getMetricValue(simId, field) {
        const m = METRICS[simId] || {};
        if (field === "calmar") {
            const dd  = Math.abs(parseFloat(m.max_drawdown || 0));
            const str = parseFloat(m.strategy_annual_return || 0);
            return dd > 0 ? str / dd : null;
        }
        if (field === "max_drawdown")   return Math.abs(parseFloat(m.max_drawdown   || 0));
        if (field === "max_monthly_loss") return Math.abs(parseFloat(m.max_monthly_loss || 0));
        const val = m[field];
        return (val === null || val === undefined || val === "") ? null : parseFloat(val);
    }

    // Custom DataTable search function
    let advFilters = {};
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
        if (settings.nTable.id !== "allHistoryTable") return true;
        if (Object.keys(advFilters).length === 0) return true;

        // Get sim ID from the actual DOM row
        const tr    = table.row(dataIndex).node();
        if (!tr) return true;
        const simId = $(tr).attr("data-sim-id");
        if (!simId) return true;

        for (const [field, bounds] of Object.entries(advFilters)) {
            const val = getMetricValue(simId, field);
            if (val === null) continue; // skip null values (don't exclude)
            if (bounds.min !== undefined && val < bounds.min) return false;
            if (bounds.max !== undefined && val > bounds.max) return false;
        }
        return true;
    });

    function applyAdvFilters() {
        advFilters = getAdvFilters();
        // Remove empty filter groups
        Object.keys(advFilters).forEach(k => {
            if (Object.keys(advFilters[k]).length === 0) delete advFilters[k];
        });
        const activeCount = countActiveFilters(advFilters);
        updateAdvFilterUI(activeCount);

        table.draw();

        // Update result count
        const resultCount = document.getElementById("advFilterResultCount");
        if (activeCount > 0) {
            const shown = table.rows({ search: 'applied' }).count();
            const total = table.rows().count();
            resultCount.textContent = shown + " de " + total + " simulações";
            resultCount.classList.remove("d-none");
        } else {
            resultCount.classList.add("d-none");
        }
    }

    // Apply on button click
    document.getElementById("advFilterApply").addEventListener("click", applyAdvFilters);

    // Also apply on Enter key in any filter input
    document.querySelectorAll(".adv-filter-input").forEach(function(inp) {
        inp.addEventListener("keydown", function(e) {
            if (e.key === "Enter") applyAdvFilters();
        });
        // Live highlight of group
        inp.addEventListener("input", function() {
            const grp = inp.closest(".adv-filter-group");
            const anyFilled = Array.from(grp.querySelectorAll(".adv-filter-input")).some(i => i.value.trim() !== "");
            grp.classList.toggle("has-value", anyFilled);
        });
    });

    // Clear filters
    document.getElementById("advFilterClear").addEventListener("click", function() {
        document.querySelectorAll(".adv-filter-input").forEach(i => { i.value = ""; });
        advFilters = {};
        updateAdvFilterUI(0);
        document.getElementById("advFilterResultCount").classList.add("d-none");
        table.draw();
    });

    // Auto-open filter panel if any filter is active on page load
    // (useful for back-navigation persistence — no server state needed here)
});

//  Modal: Criar Novo Portfólio a partir do Snapshot 
function prepareCreateFromSnapshot(btn) {
    const simId     = btn.getAttribute('data-sim-id');
    const createUrl = btn.getAttribute('data-create-url');
    document.getElementById('cfsSimId').value      = simId;
    document.getElementById('cfsFormAction').action = createUrl;
    // Sugestão automática de nome
    const snap = SNAPSHOTS[simId];
    const dateStr = new Date().toLocaleDateString('pt-BR', {day:'2-digit',month:'2-digit',year:'numeric'});
    const periodStr = snap && snap.portfolio && snap.portfolio.start_date
        ? snap.portfolio.start_date.substring(0,7).split('-').reverse().join('/')
        : '';
    const suggested = 'Portfólio — Simulação #' + simId + (periodStr ? ' (' + periodStr + ')' : '');
    const nameInput = document.getElementById('cfsPortfolioName');
    nameInput.value = suggested;
    setTimeout(() => { nameInput.focus(); nameInput.select(); }, 400);
}
</script>
