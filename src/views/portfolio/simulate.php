<?php
require_once __DIR__ . '/../../app/controllers/PortfolioController.php';
require_once __DIR__ . '/../../app/controllers/SimulationController.php';

$portfolioId = $_GET['id'] ?? 0;
$portfolioController = new PortfolioController();
$portfolio = $portfolioController->getPortfolio($portfolioId);

if (!$portfolio) {
    header('Location: /portfolio');
    exit;
}

$simulationController = new SimulationController();
$recentSimulations = $simulationController->getPortfolioSimulations($portfolioId, 5);
?>

<?php
$pageTitle = 'Simular Portfólio: ' . htmlspecialchars($portfolio['name']);
$pageSubtitle = 'Execute simulações com diferentes parâmetros';
include __DIR__ . '/../layout/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Simulation Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configuração da Simulação</h5>
            </div>
            <div class="card-body">
                <form id="simulationForm">
                    <input type="hidden" id="portfolioId" value="<?= $portfolioId ?>">
                    
                    <!-- Basic Parameters -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Capital Inicial</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="initialCapital" 
                                           value="<?= $portfolio['initial_capital'] ?>" 
                                           min="1000" step="1000">
                                </div>
                                <div class="form-text">
                                    Capital usado na simulação
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Período da Simulação</label>
                                <select class="form-select" id="simulationPeriod">
                                    <option value="CUSTOM">Personalizado</option>
                                    <option value="1M">Último Mês</option>
                                    <option value="6M">Últimos 6 Meses</option>
                                    <option value="1Y">Último Ano</option>
                                    <option value="3Y">Últimos 3 Anos</option>
                                    <option value="5Y" selected>Últimos 5 Anos</option>
                                    <option value="10Y">Últimos 10 Anos</option>
                                    <option value="MAX">Todo o Histórico</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Date Range -->
                    <div class="row mb-4" id="customDateRange">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="startDate" 
                                       value="<?= date('Y-m-d', strtotime('-5 years')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data Final</label>
                                <input type="date" class="form-control" id="endDate" 
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Options -->
                    <div class="accordion mb-4" id="advancedOptions">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#advancedCollapse">
                                    <i class="bi bi-gear me-2"></i> Opções Avançadas
                                </button>
                            </h2>
                            <div id="advancedCollapse" class="accordion-collapse collapse" 
                                 data-bs-parent="#advancedOptions">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Frequência Rebalanceamento</label>
                                                <select class="form-select" id="rebalanceFrequency">
                                                    <option value="NEVER" <?= $portfolio['rebalance_frequency'] === 'NEVER' ? 'selected' : '' ?>>Nunca</option>
                                                    <option value="MONTHLY" <?= $portfolio['rebalance_frequency'] === 'MONTHLY' ? 'selected' : '' ?>>Mensal</option>
                                                    <option value="QUARTERLY" <?= $portfolio['rebalance_frequency'] === 'QUARTERLY' ? 'selected' : '' ?>>Trimestral</option>
                                                    <option value="SEMIANNUAL" <?= $portfolio['rebalance_frequency'] === 'SEMIANNUAL' ? 'selected' : '' ?>>Semestral</option>
                                                    <option value="ANNUAL" <?= $portfolio['rebalance_frequency'] === 'ANNUAL' ? 'selected' : '' ?>>Anual</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Moeda de Saída</label>
                                                <select class="form-select" id="outputCurrency">
                                                    <option value="BRL" <?= $portfolio['output_currency'] === 'BRL' ? 'selected' : '' ?>>Real (BRL)</option>
                                                    <option value="USD" <?= $portfolio['output_currency'] === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
                                                    <option value="EUR" <?= $portfolio['output_currency'] === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Taxas e Custos</label>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Taxa Admin.</span>
                                                    <input type="number" class="form-control" id="managementFee" 
                                                           value="0" min="0" max="5" step="0.01">
                                                    <span class="input-group-text">% a.a.</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Custo Transação</span>
                                                    <input type="number" class="form-control" id="transactionCost" 
                                                           value="0" min="0" max="1" step="0.01">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Imposto</span>
                                                    <input type="number" class="form-control" id="taxRate" 
                                                           value="15" min="0" max="30" step="0.1">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Asset Adjustments -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Ajustes de Ativos</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ativo</th>
                                        <th>Alocação Atual</th>
                                        <th>Fator Performance</th>
                                        <th>Taxa Dividendos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($portfolio['assets'] as $asset): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($asset['name']) ?></strong>
                                                <div class="text-muted small"><?= $asset['code'] ?></div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control asset-allocation" 
                                                           data-asset-id="<?= $asset['asset_id'] ?>"
                                                           value="<?= $asset['allocation_percentage'] * 100 ?>" 
                                                           min="0" max="100" step="0.01">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm asset-factor"
                                                       data-asset-id="<?= $asset['asset_id'] ?>"
                                                       value="<?= $asset['performance_factor'] ?>" 
                                                       min="0.1" max="10.0" step="0.1">
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control asset-dividend"
                                                           data-asset-id="<?= $asset['asset_id'] ?>"
                                                           value="0" min="0" max="20" step="0.1">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" id="cancelBtn">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </button>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" id="quickSimulateBtn">
                                <i class="bi bi-lightning"></i> Simulação Rápida
                            </button>
                            <button type="submit" class="btn btn-primary" id="simulateBtn">
                                <i class="bi bi-calculator"></i> Executar Simulação Completa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Portfolio Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Resumo do Portfólio</h5>
            </div>
            <div class="card-body">
                <h6><?= htmlspecialchars($portfolio['name']) ?></h6>
                <p class="text-muted small"><?= htmlspecialchars($portfolio['description'] ?? '') ?></p>
                
                <dl class="row mb-0">
                    <dt class="col-6">Ativos:</dt>
                    <dd class="col-6"><?= count($portfolio['assets']) ?></dd>
                    
                    <dt class="col-6">Capital:</dt>
                    <dd class="col-6">R$ <?= number_format($portfolio['initial_capital'], 2, ',', '.') ?></dd>
                    
                    <dt class="col-6">Rebalanceamento:</dt>
                    <dd class="col-6"><?= $portfolio['rebalance_frequency'] ?></dd>
                    
                    <dt class="col-6">Moeda:</dt>
                    <dd class="col-6"><?= $portfolio['output_currency'] ?></dd>
                </dl>
                
                <div class="mt-3">
                    <h6 class="small">Distribuição por Tipo:</h6>
                    <div id="typeDistributionChart" style="height: 120px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Recent Simulations -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Simulações Recentes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentSimulations)): ?>
                    <p class="text-muted text-center py-3">Nenhuma simulação realizada</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recentSimulations as $simulation): ?>
                            <a href="/simulation/<?= $simulation['execution_id'] ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= date('d/m/Y', strtotime($simulation['completed_at'])) ?></h6>
                                    <small class="text-muted">
                                        <?= date('H:i', strtotime($simulation['completed_at'])) ?>
                                    </small>
                                </div>
                                <p class="mb-1 small">
                                    Retorno: 
                                    <span class="<?= $simulation['total_return'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($simulation['total_return'], 2, ',', '.') ?>%
                                    </span>
                                </p>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 
                                    Duração: <?= $simulation['duration'] ?>s
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Simulation Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resultados da Simulação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="simulationResults">
                    <!-- Results will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="saveResultsBtn">
                    <i class="bi bi-save"></i> Salvar Resultados
                </button>
                <button type="button" class="btn btn-success" id="exportResultsBtn">
                    <i class="bi bi-download"></i> Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize type distribution chart
    initTypeDistributionChart();
    
    // Toggle custom date range
    $('#simulationPeriod').change(function() {
        if ($(this).val() === 'CUSTOM') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
            updateDatesForPeriod($(this).val());
        }
    }).trigger('change');
    
    // Update dates based on selected period
    function updateDatesForPeriod(period) {
        const endDate = new Date();
        let startDate = new Date();
        
        switch(period) {
            case '1M':
                startDate.setMonth(startDate.getMonth() - 1);
                break;
            case '6M':
                startDate.setMonth(startDate.getMonth() - 6);
                break;
            case '1Y':
                startDate.setFullYear(startDate.getFullYear() - 1);
                break;
            case '3Y':
                startDate.setFullYear(startDate.getFullYear() - 3);
                break;
            case '5Y':
                startDate.setFullYear(startDate.getFullYear() - 5);
                break;
            case '10Y':
                startDate.setFullYear(startDate.getFullYear() - 10);
                break;
            case 'MAX':
                // Use portfolio start date
                startDate = new Date('<?= $portfolio['start_date'] ?>');
                break;
        }
        
        $('#startDate').val(formatDate(startDate));
        $('#endDate').val(formatDate(endDate));
    }
    
    // Format date as YYYY-MM-DD
    function formatDate(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();
        
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        
        return [year, month, day].join('-');
    }
    
    // Initialize type distribution chart
    function initTypeDistributionChart() {
        // Calculate type distribution
        const typeDistribution = {};
        <?php foreach ($portfolio['assets'] as $asset): ?>
        const type = '<?= $asset['type'] ?>';
        const allocation = <?= $asset['allocation_percentage'] * 100 ?>;
        
        if (!typeDistribution[type]) {
            typeDistribution[type] = 0;
        }
        typeDistribution[type] += allocation;
        <?php endforeach; ?>
        
        // Create chart
        const ctx = document.createElement('canvas');
        $('#typeDistributionChart').html(ctx);
        
        const chart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(typeDistribution),
                datasets: [{
                    data: Object.values(typeDistribution),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#8AC926', '#1982C4'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed.toFixed(2)}%`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Cancel button
    $('#cancelBtn').click(function() {
        window.location.href = '/portfolio/<?= $portfolioId ?>';
    });
    
    // Quick simulation
    $('#quickSimulateBtn').click(function() {
        runSimulation(true);
    });
    
    // Full simulation
    $('#simulationForm').submit(function(e) {
        e.preventDefault();
        runSimulation(false);
    });
    
    // Run simulation
    function runSimulation(isQuick) {
        const portfolioId = $('#portfolioId').val();
        const simulationData = getSimulationData();
        
        // Add quick simulation flag
        simulationData.is_quick = isQuick;
        
        // Show loading
        $('#simulateBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Simulando...');
        if (!isQuick) {
            $('#quickSimulateBtn').prop('disabled', true);
        }
        
        $.ajax({
            url: '/api/portfolio/' + portfolioId + '/simulate',
            method: 'POST',
            data: JSON.stringify(simulationData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // For quick simulation, show results immediately
                    if (isQuick) {
                        showQuickResults(response.data);
                    } else {
                        // For full simulation, redirect to results page
                        window.location.href = '/simulation/' + response.data.execution_id;
                    }
                } else {
                    showError(response.error);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao executar simulação');
            },
            complete: function() {
                $('#simulateBtn').prop('disabled', false).html('<i class="bi bi-calculator"></i> Executar Simulação Completa');
                $('#quickSimulateBtn').prop('disabled', false).html('<i class="bi bi-lightning"></i> Simulação Rápida');
            }
        });
    }
    
    // Get simulation data from form
    function getSimulationData() {
        // Get asset adjustments
        const assetAdjustments = {};
        $('.asset-allocation').each(function() {
            const assetId = $(this).data('asset-id');
            assetAdjustments[assetId] = assetAdjustments[assetId] || {};
            assetAdjustments[assetId].allocation = parseFloat($(this).val()) / 100;
        });
        
        $('.asset-factor').each(function() {
            const assetId = $(this).data('asset-id');
            assetAdjustments[assetId] = assetAdjustments[assetId] || {};
            assetAdjustments[assetId].factor = parseFloat($(this).val());
        });
        
        $('.asset-dividend').each(function() {
            const assetId = $(this).data('asset-id');
            assetAdjustments[assetId] = assetAdjustments[assetId] || {};
            assetAdjustments[assetId].dividend_yield = parseFloat($(this).val()) / 100;
        });
        
        return {
            initial_capital: parseFloat($('#initialCapital').val()),
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            rebalance_frequency: $('#rebalanceFrequency').val(),
            output_currency: $('#outputCurrency').val(),
            management_fee: parseFloat($('#managementFee').val()) / 100,
            transaction_cost: parseFloat($('#transactionCost').val()) / 100,
            tax_rate: parseFloat($('#taxRate').val()) / 100,
            asset_adjustments: assetAdjustments
        };
    }
    
    // Show quick results
    function showQuickResults(data) {
        const resultsHtml = `
            <div class="text-center mb-4">
                <h4>Simulação Rápida Concluída</h4>
                <p class="text-muted">Resultados da simulação de ${data.duration.toFixed(2)} segundos</p>
            </div>
            
            <div class="row text-center mb-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="h3 ${data.total_return >= 0 ? 'text-success' : 'text-danger'}">
                                ${data.total_return.toFixed(2)}%
                            </div>
                            <div class="text-muted small">Retorno Total</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="h3 ${data.annual_return >= 0 ? 'text-success' : 'text-danger'}">
                                ${data.annual_return.toFixed(2)}%
                            </div>
                            <div class="text-muted small">Retorno Anual</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="h3">${data.volatility.toFixed(2)}%</div>
                            <div class="text-muted small">Volatilidade</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="h3">${data.sharpe_ratio.toFixed(2)}</div>
                            <div class="text-muted small">Índice Sharpe</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>Valor Final: R$ ${data.final_value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h6>
                    <h6>Capital Inicial: R$ ${data.initial_capital.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h6>
                    <h6>Período: ${data.start_date} até ${data.end_date}</h6>
                </div>
                <div class="col-md-6">
                    <h6>Máximo Drawdown: ${data.max_drawdown.toFixed(2)}%</h6>
                    <h6>Beta: ${data.beta?.toFixed(2) || 'N/A'}</h6>
                    <h6>Alpha: ${data.alpha?.toFixed(2) || 'N/A'}%</h6>
                </div>
            </div>
            
            <div class="mt-4">
                <h6>Desempenho por Ano</h6>
                <canvas id="annualPerformanceChart" height="100"></canvas>
            </div>
        `;
        
        $('#simulationResults').html(resultsHtml);
        $('#resultsModal').modal('show');
        
        // Create annual performance chart
        if (data.annual_performance) {
            createAnnualPerformanceChart(data.annual_performance);
        }
    }
    
    // Create annual performance chart
    function createAnnualPerformanceChart(annualPerformance) {
        const ctx = document.getElementById('annualPerformanceChart').getContext('2d');
        const labels = Object.keys(annualPerformance);
        const data = Object.values(annualPerformance);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Retorno Anual (%)',
                    data: data,
                    backgroundColor: data.map(value => value >= 0 ? '#28a745' : '#dc3545')
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Save results
    $('#saveResultsBtn').click(function() {
        const portfolioId = $('#portfolioId').val();
        const simulationData = getSimulationData();
        
        $.ajax({
            url: '/api/simulation/save',
            method: 'POST',
            data: JSON.stringify({
                portfolio_id: portfolioId,
                simulation_data: simulationData
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    showSuccess('Resultados salvos com sucesso!');
                    $('#resultsModal').modal('hide');
                } else {
                    showError(response.error);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao salvar resultados');
            }
        });
    });
    
    // Export results
    $('#exportResultsBtn').click(function() {
        // Get current results data
        const resultsText = $('#simulationResults').text();
        const blob = new Blob([resultsText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'simulacao_<?= $portfolio['name'] ?>_' + new Date().toISOString().split('T')[0] + '.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showSuccess('Resultados exportados com sucesso!');
    });
    
    // Helper functions
    function showSuccess(message) {
        alert('Sucesso: ' + message);
    }
    
    function showError(message) {
        alert('Erro: ' + message);
    }
});
</script>