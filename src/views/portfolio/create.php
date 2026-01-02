<?php
require_once __DIR__ . '/../../app/controllers/PortfolioController.php';
require_once __DIR__ . '/../../app/controllers/AssetController.php';

$assetController = new AssetController();
$assets = $assetController->getAllAssets();
?>

<?php
$pageTitle = 'Criar Novo Portfólio';
$pageSubtitle = 'Configure seu portfólio personalizado';
include __DIR__ . '/../layout/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Configuração do Portfólio</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/portfolio/store" id="portfolioForm">
                    <!-- Basic Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informações Básicas</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome do Portfólio *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="form-text">Ex: Portfólio Conservador, Carteira Ações, etc.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Capital and Dates -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Capital e Período</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="initial_capital" class="form-label">Capital Inicial *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="initial_capital" 
                                               name="initial_capital" value="100000" min="1000" step="1000" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Data Início *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?= date('Y-m-d', strtotime('-5 years')) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Data Final</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                           value="<?= date('Y-m-d') ?>">
                                    <div class="form-text">Deixe em branco para usar data atual</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Configurações</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rebalance_frequency" class="form-label">Frequência de Rebalanceamento</label>
                                    <select class="form-select" id="rebalance_frequency" name="rebalance_frequency">
                                        <option value="NEVER">Nunca</option>
                                        <option value="MONTHLY" selected>Mensal</option>
                                        <option value="QUARTERLY">Trimestral</option>
                                        <option value="SEMIANNUAL">Semestral</option>
                                        <option value="ANNUAL">Anual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="output_currency" class="form-label">Moeda de Saída</label>
                                    <select class="form-select" id="output_currency" name="output_currency">
                                        <option value="BRL" selected>Real (BRL)</option>
                                        <option value="USD">Dólar (USD)</option>
                                        <option value="EUR">Euro (EUR)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Asset Allocation -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Alocação de Ativos</h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="distributeEqual">
                                    <i class="bi bi-arrow-repeat"></i> Distribuir Igualmente
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="optimizeAllocation">
                                    <i class="bi bi-magic"></i> Otimizar
                                </button>
                            </div>
                        </div>
                        
                        <!-- Asset Selection -->
                        <div class="mb-3">
                            <label class="form-label">Selecionar Ativos</label>
                            <div class="row" id="assetSelection">
                                <?php foreach ($assets as $asset): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input asset-check" type="checkbox" 
                                                   value="<?= $asset['id'] ?>" 
                                                   id="asset_<?= $asset['id'] ?>"
                                                   data-code="<?= $asset['code'] ?>"
                                                   data-currency="<?= $asset['currency'] ?>"
                                                   data-type="<?= $asset['type'] ?>">
                                            <label class="form-check-label" for="asset_<?= $asset['id'] ?>">
                                                <?= htmlspecialchars($asset['name']) ?>
                                                <span class="badge bg-secondary"><?= $asset['code'] ?></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Allocation Table -->
                        <div class="table-responsive">
                            <table class="table table-sm" id="allocationTable">
                                <thead>
                                    <tr>
                                        <th width="40%">Ativo</th>
                                        <th width="30%">Alocação (%)</th>
                                        <th width="20%">Fator Perf.</th>
                                        <th width="10%">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="allocationBody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td>
                                            <span id="totalAllocation" class="badge bg-success">0.00%</span>
                                            <span id="remainingAllocation" class="badge bg-secondary ms-2">100.00%</span>
                                        </td>
                                        <td colspan="2">
                                            <div class="progress" style="height: 5px;">
                                                <div id="allocationProgress" class="progress-bar" style="width: 0%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="/portfolio" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-primary" id="saveDraft">
                                <i class="bi bi-save"></i> Salvar Rascunho
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Criar Portfólio
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Portfolio Preview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Pré-visualização</h5>
            </div>
            <div class="card-body">
                <div id="portfolioPreview">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-pie-chart display-4"></i>
                        <p class="mt-3">Configure o portfólio para ver a pré-visualização</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Tips -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Dicas Rápidas</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-lightbulb text-warning"></i>
                        <strong>Diversificação:</strong> Inclua diferentes tipos de ativos
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-lightbulb text-warning"></i>
                        <strong>Rebalanceamento:</strong> Mensal é recomendado para maior controle
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-lightbulb text-warning"></i>
                        <strong>Histórico:</strong> Use pelo menos 5 anos para simulações precisas
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-lightbulb text-warning"></i>
                        <strong>Fator Performance:</strong> Use para simular estratégias diferentes
                    </li>
                    <li>
                        <i class="bi bi-lightbulb text-warning"></i>
                        <strong>Capital:</strong> Comece com valores reais para análises práticas
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Optimize Modal -->
<div class="modal fade" id="optimizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Otimizar Alocação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Perfil de Risco</label>
                    <select class="form-select" id="riskProfile">
                        <option value="CONSERVATIVE">Conservador</option>
                        <option value="MODERATE" selected>Moderado</option>
                        <option value="AGGRESSIVE">Agressivo</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Foco</label>
                    <select class="form-select" id="optimizationFocus">
                        <option value="BALANCED" selected>Balanceado</option>
                        <option value="INCOME">Renda</option>
                        <option value="GROWTH">Crescimento</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="applyOptimization">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    let selectedAssets = [];
    let allocationData = {};
    
    // Initialize asset allocation
    function initAllocation() {
        allocationData = {};
        updateAllocationTable();
        updatePreview();
    }
    
    // Update allocation table
    function updateAllocationTable() {
        const tbody = $('#allocationBody');
        tbody.empty();
        
        let totalAllocation = 0;
        
        for (const assetId in allocationData) {
            const asset = allocationData[assetId];
            totalAllocation += parseFloat(asset.allocation) || 0;
            
            const row = `
                <tr data-asset-id="${assetId}">
                    <td>
                        <strong>${asset.name}</strong>
                        <div class="text-muted small">${asset.code} • ${asset.type}</div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control allocation-input" 
                                   value="${asset.allocation || 0}" 
                                   min="0" max="100" step="0.01"
                                   data-asset-id="${assetId}">
                            <span class="input-group-text">%</span>
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm factor-input"
                               value="${asset.factor || 1.0}"
                               min="0.1" max="10.0" step="0.1"
                               data-asset-id="${assetId}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-asset"
                                data-asset-id="${assetId}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        }
        
        // Update totals
        const remaining = 100 - totalAllocation;
        $('#totalAllocation').text(totalAllocation.toFixed(2) + '%');
        $('#remainingAllocation').text(remaining.toFixed(2) + '%');
        
        // Update progress bar
        const progressBar = $('#allocationProgress');
        progressBar.css('width', totalAllocation + '%');
        
        if (totalAllocation === 100) {
            progressBar.removeClass('bg-warning bg-danger').addClass('bg-success');
            $('#remainingAllocation').removeClass('bg-danger bg-warning').addClass('bg-success');
        } else if (totalAllocation > 100) {
            progressBar.removeClass('bg-success bg-warning').addClass('bg-danger');
            $('#remainingAllocation').removeClass('bg-success').addClass('bg-danger');
        } else if (totalAllocation > 0) {
            progressBar.removeClass('bg-success bg-danger').addClass('bg-warning');
            $('#remainingAllocation').removeClass('bg-danger').addClass('bg-warning');
        } else {
            progressBar.removeClass('bg-success bg-danger bg-warning');
            $('#remainingAllocation').removeClass('bg-danger bg-warning bg-success').addClass('bg-secondary');
        }
        
        // Update form validation
        validateForm();
    }
    
    // Update portfolio preview
    function updatePreview() {
        const preview = $('#portfolioPreview');
        const assetCount = Object.keys(allocationData).length;
        
        if (assetCount === 0) {
            preview.html(`
                <div class="text-center text-muted py-5">
                    <i class="bi bi-pie-chart display-4"></i>
                    <p class="mt-3">Selecione ativos para ver a pré-visualização</p>
                </div>
            `);
            return;
        }
        
        // Calculate asset distribution by type
        const typeDistribution = {};
        let totalAllocation = 0;
        
        for (const assetId in allocationData) {
            const asset = allocationData[assetId];
            const allocation = parseFloat(asset.allocation) || 0;
            
            if (!typeDistribution[asset.type]) {
                typeDistribution[asset.type] = 0;
            }
            typeDistribution[asset.type] += allocation;
            totalAllocation += allocation;
        }
        
        // Create preview HTML
        let html = `
            <h6>Resumo do Portfólio</h6>
            <div class="mb-3">
                <span class="badge bg-primary">${assetCount} ativos</span>
                <span class="badge bg-success">${totalAllocation.toFixed(2)}% alocado</span>
            </div>
            
            <h6 class="mt-4">Distribuição por Tipo</h6>
        `;
        
        // Add type distribution
        for (const type in typeDistribution) {
            const percentage = typeDistribution[type];
            const width = (percentage / totalAllocation) * 100;
            
            html += `
                <div class="mb-2">
                    <div class="d-flex justify-content-between small">
                        <span>${type}</span>
                        <span>${percentage.toFixed(2)}%</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar" style="width: ${width}%"></div>
                    </div>
                </div>
            `;
        }
        
        // Add estimated metrics
        html += `
            <h6 class="mt-4">Métricas Estimadas</h6>
            <div class="row text-center">
                <div class="col-6">
                    <div class="small text-muted">Diversificação</div>
                    <div class="h5">${calculateDiversificationScore().toFixed(0)}/100</div>
                </div>
                <div class="col-6">
                    <div class="small text-muted">Risco</div>
                    <div class="h5">${estimateRiskLevel()}</div>
                </div>
            </div>
        `;
        
        preview.html(html);
    }
    
    // Calculate diversification score
    function calculateDiversificationScore() {
        const assetCount = Object.keys(allocationData).length;
        if (assetCount === 0) return 0;
        
        // Count unique types
        const types = new Set();
        for (const assetId in allocationData) {
            types.add(allocationData[assetId].type);
        }
        
        // Simple score calculation
        const typeScore = (types.size / 6) * 40; // Max 6 types
        const countScore = Math.min(assetCount * 10, 60); // Max 6 assets
        
        return Math.min(typeScore + countScore, 100);
    }
    
    // Estimate risk level
    function estimateRiskLevel() {
        const riskScores = {
            'BOND': 1,
            'CURRENCY': 2,
            'COMMODITY': 3,
            'INDEX': 4,
            'STOCK': 5,
            'CRYPTO': 6
        };
        
        let totalRisk = 0;
        let totalWeight = 0;
        
        for (const assetId in allocationData) {
            const asset = allocationData[assetId];
            const weight = parseFloat(asset.allocation) || 0;
            const risk = riskScores[asset.type] || 3;
            
            totalRisk += risk * weight;
            totalWeight += weight;
        }
        
        if (totalWeight === 0) return 'Médio';
        
        const avgRisk = totalRisk / totalWeight;
        
        if (avgRisk < 2) return 'Muito Baixo';
        if (avgRisk < 3) return 'Baixo';
        if (avgRisk < 4) return 'Médio';
        if (avgRisk < 5) return 'Alto';
        return 'Muito Alto';
    }
    
    // Validate form
    function validateForm() {
        const submitBtn = $('#submitBtn');
        const totalAllocation = calculateTotalAllocation();
        
        if (totalAllocation !== 100) {
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="bi bi-x-circle"></i> Alocação deve ser 100%');
        } else if (Object.keys(allocationData).length === 0) {
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="bi bi-x-circle"></i> Selecione pelo menos 1 ativo');
        } else {
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="bi bi-check-circle"></i> Criar Portfólio');
        }
    }
    
    // Calculate total allocation
    function calculateTotalAllocation() {
        let total = 0;
        for (const assetId in allocationData) {
            total += parseFloat(allocationData[assetId].allocation) || 0;
        }
        return total;
    }
    
    // Event: Asset checkbox changed
    $('.asset-check').change(function() {
        const assetId = $(this).val();
        const isChecked = $(this).is(':checked');
        const assetCode = $(this).data('code');
        const assetType = $(this).data('type');
        const assetCurrency = $(this).data('currency');
        
        if (isChecked) {
            // Add asset to allocation
            allocationData[assetId] = {
                id: assetId,
                code: assetCode,
                name: $(this).next('label').text().trim(),
                type: assetType,
                currency: assetCurrency,
                allocation: 0,
                factor: 1.0
            };
        } else {
            // Remove asset from allocation
            delete allocationData[assetId];
        }
        
        updateAllocationTable();
        updatePreview();
    });
    
    // Event: Allocation input changed
    $(document).on('input', '.allocation-input', function() {
        const assetId = $(this).data('asset-id');
        const value = parseFloat($(this).val()) || 0;
        
        if (allocationData[assetId]) {
            allocationData[assetId].allocation = value;
            updateAllocationTable();
            updatePreview();
        }
    });
    
    // Event: Factor input changed
    $(document).on('input', '.factor-input', function() {
        const assetId = $(this).data('asset-id');
        const value = parseFloat($(this).val()) || 1.0;
        
        if (allocationData[assetId]) {
            allocationData[assetId].factor = value;
        }
    });
    
    // Event: Remove asset
    $(document).on('click', '.remove-asset', function() {
        const assetId = $(this).data('asset-id');
        
        // Uncheck the checkbox
        $(`#asset_${assetId}`).prop('checked', false);
        
        // Remove from allocation
        delete allocationData[assetId];
        
        updateAllocationTable();
        updatePreview();
    });
    
    // Event: Distribute equally
    $('#distributeEqual').click(function() {
        const assetCount = Object.keys(allocationData).length;
        if (assetCount === 0) return;
        
        const equalAllocation = 100 / assetCount;
        
        for (const assetId in allocationData) {
            allocationData[assetId].allocation = equalAllocation.toFixed(2);
        }
        
        updateAllocationTable();
        updatePreview();
    });
    
    // Event: Open optimize modal
    $('#optimizeAllocation').click(function() {
        if (Object.keys(allocationData).length === 0) {
            alert('Selecione ativos primeiro');
            return;
        }
        $('#optimizeModal').modal('show');
    });
    
    // Event: Apply optimization
    $('#applyOptimization').click(function() {
        const riskProfile = $('#riskProfile').val();
        const optimizationFocus = $('#optimizationFocus').val();
        
        // Simple optimization logic
        const allocations = optimizeAllocation(riskProfile, optimizationFocus);
        
        for (const assetId in allocationData) {
            const asset = allocationData[assetId];
            const suggestedAllocation = allocations[asset.type] || 0;
            
            // Distribute based on type
            const typeAssets = Object.values(allocationData).filter(a => a.type === asset.type);
            const perAssetAllocation = suggestedAllocation / typeAssets.length;
            
            allocationData[assetId].allocation = perAssetAllocation.toFixed(2);
        }
        
        updateAllocationTable();
        updatePreview();
        $('#optimizeModal').modal('hide');
    });
    
    // Optimization logic
    function optimizeAllocation(riskProfile, focus) {
        const allocations = {
            CONSERVATIVE: {
                BOND: 50,
                CURRENCY: 10,
                INDEX: 15,
                STOCK: 15,
                COMMODITY: 5,
                CRYPTO: 5
            },
            MODERATE: {
                BOND: 30,
                CURRENCY: 10,
                INDEX: 20,
                STOCK: 25,
                COMMODITY: 10,
                CRYPTO: 5
            },
            AGGRESSIVE: {
                BOND: 15,
                CURRENCY: 5,
                INDEX: 20,
                STOCK: 30,
                COMMODITY: 15,
                CRYPTO: 15
            }
        };
        
        let baseAllocation = allocations[riskProfile] || allocations.MODERATE;
        
        // Adjust based on focus
        if (focus === 'INCOME') {
            baseAllocation = {
                BOND: 60,
                CURRENCY: 10,
                INDEX: 15,
                STOCK: 10,
                COMMODITY: 3,
                CRYPTO: 2
            };
        } else if (focus === 'GROWTH') {
            baseAllocation = {
                BOND: 10,
                CURRENCY: 5,
                INDEX: 25,
                STOCK: 40,
                COMMODITY: 10,
                CRYPTO: 10
            };
        }
        
        return baseAllocation;
    }
    
    // Event: Form submission
    $('#portfolioForm').submit(function(e) {
        e.preventDefault();
        
        // Validate allocation
        const totalAllocation = calculateTotalAllocation();
        if (Math.abs(totalAllocation - 100) > 0.01) {
            alert(`Alocação total deve ser 100%. Atual: ${totalAllocation.toFixed(2)}%`);
            return;
        }
        
        // Prepare data
        const formData = new FormData(this);
        
        // Add assets data
        const assets = [];
        for (const assetId in allocationData) {
            const asset = allocationData[assetId];
            assets.push({
                id: assetId,
                allocation: asset.allocation,
                factor: asset.factor
            });
        }
        
        formData.append('assets', JSON.stringify(assets));
        
        // Submit form
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Criando...');
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Portfólio criado com sucesso!');
                    setTimeout(function() {
                        window.location.href = '/portfolio/' + response.data.portfolio_id;
                    }, 1000);
                } else {
                    showError(response.error);
                    $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Criar Portfólio');
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao criar portfólio');
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Criar Portfólio');
            }
        });
    });
    
    // Event: Save draft
    $('#saveDraft').click(function() {
        const formData = new FormData($('#portfolioForm')[0]);
        
        // Add assets data
        const assets = [];
        for (const assetId in allocationData) {
            const asset = allocationData[assetId];
            assets.push({
                id: assetId,
                allocation: asset.allocation,
                factor: asset.factor
            });
        }
        
        formData.append('assets', JSON.stringify(assets));
        formData.append('is_draft', 'true');
        
        $.ajax({
            url: '/portfolio/store',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Rascunho salvo com sucesso!');
                } else {
                    showError(response.error);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao salvar rascunho');
            }
        });
    });
    
    // Helper functions
    function showSuccess(message) {
        alert('Sucesso: ' + message);
    }
    
    function showError(message) {
        alert('Erro: ' + message);
    }
    
    // Initialize
    initAllocation();
});
</script>