<?php
require_once __DIR__ . '/../../app/controllers/PortfolioController.php';
require_once __DIR__ . '/../../app/controllers/AssetController.php';

$portfolioId = $_GET['id'] ?? 0;
$controller = new PortfolioController();
$portfolio = $controller->getPortfolio($portfolioId);

if (!$portfolio) {
    header('Location: /portfolio');
    exit;
}

$assetController = new AssetController();
$assets = $assetController->getAllAssets();
?>

<?php
$pageTitle = 'Editar Portfólio: ' . htmlspecialchars($portfolio['name']);
$pageSubtitle = 'Ajuste as configurações do seu portfólio';
include __DIR__ . '/../layout/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Editar Portfólio</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/portfolio/<?= $portfolioId ?>/update" id="portfolioForm">
                    <!-- Basic Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informações Básicas</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome do Portfólio *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($portfolio['name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($portfolio['description'] ?? '') ?></textarea>
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
                                               name="initial_capital" value="<?= $portfolio['initial_capital'] ?>" 
                                               min="1000" step="1000" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Data Início *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?= date('Y-m-d', strtotime($portfolio['start_date'])) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Data Final</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                           value="<?= $portfolio['end_date'] ? date('Y-m-d', strtotime($portfolio['end_date'])) : '' ?>">
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
                                    <label for="output_currency" class="form-label">Moeda de Saída</label>
                                    <select class="form-select" id="output_currency" name="output_currency">
                                        <option value="BRL" <?= $portfolio['output_currency'] === 'BRL' ? 'selected' : '' ?>>Real (BRL)</option>
                                        <option value="USD" <?= $portfolio['output_currency'] === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
                                        <option value="EUR" <?= $portfolio['output_currency'] === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
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
                                    <?php
                                    $isSelected = false;
                                    $assetAllocation = 0;
                                    $assetFactor = 1.0;
                                    
                                    foreach ($portfolio['assets'] as $portfolioAsset) {
                                        if ($portfolioAsset['asset_id'] == $asset['id']) {
                                            $isSelected = true;
                                            $assetAllocation = $portfolioAsset['allocation_percentage'] * 100;
                                            $assetFactor = $portfolioAsset['performance_factor'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input asset-check" type="checkbox" 
                                                   value="<?= $asset['id'] ?>" 
                                                   id="asset_<?= $asset['id'] ?>"
                                                   data-code="<?= $asset['code'] ?>"
                                                   data-currency="<?= $asset['currency'] ?>"
                                                   data-type="<?= $asset['type'] ?>"
                                                   <?= $isSelected ? 'checked' : '' ?>>
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
                        <a href="/portfolio/<?= $portfolioId ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-danger me-2" id="deleteBtn">
                                <i class="bi bi-trash"></i> Excluir
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Portfolio Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Estatísticas</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6">Criado em:</dt>
                    <dd class="col-6"><?= date('d/m/Y', strtotime($portfolio['created_at'])) ?></dd>
                    
                    <dt class="col-6">Última atualização:</dt>
                    <dd class="col-6"><?= date('d/m/Y', strtotime($portfolio['updated_at'])) ?></dd>
                    
                    <dt class="col-6">Simulações:</dt>
                    <dd class="col-6"><?= $portfolio['simulation_count'] ?? 0 ?></dd>
                    
                    <dt class="col-6">Última simulação:</dt>
                    <dd class="col-6">
                        <?php if ($portfolio['last_simulation']): ?>
                            <?= date('d/m/Y', strtotime($portfolio['last_simulation'])) ?>
                        <?php else: ?>
                            Nunca
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ações Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/portfolio/<?= $portfolioId ?>/simulate" class="btn btn-success">
                        <i class="bi bi-calculator me-2"></i> Simular Portfólio
                    </a>
                    <button type="button" class="btn btn-outline-primary" id="cloneBtn">
                        <i class="bi bi-copy me-2"></i> Clonar Portfólio
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="exportBtn">
                        <i class="bi bi-download me-2"></i> Exportar Configuração
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Excluir Portfólio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o portfólio <strong><?= htmlspecialchars($portfolio['name']) ?></strong>?</p>
                <p class="text-danger">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Esta ação não pode ser desfeita. Todas as simulações associadas também serão excluídas.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="/portfolio/<?= $portfolioId ?>/delete" id="deleteForm">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    let allocationData = {};
    
    // Initialize with existing portfolio data
    function initAllocation() {
        allocationData = {};
        
        <?php foreach ($portfolio['assets'] as $asset): ?>
        allocationData['<?= $asset['asset_id'] ?>'] = {
            id: '<?= $asset['asset_id'] ?>',
            code: '<?= $asset['code'] ?>',
            name: '<?= addslashes($asset['name']) ?>',
            type: '<?= $asset['type'] ?>',
            currency: '<?= $asset['currency'] ?>',
            allocation: <?= $asset['allocation_percentage'] * 100 ?>,
            factor: <?= $asset['performance_factor'] ?>
        };
        <?php endforeach; ?>
        
        updateAllocationTable();
    }
    
    // Update allocation table (same as in create.php)
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
            submitBtn.html('<i class="bi bi-check-circle"></i> Salvar Alterações');
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
    
    // Event handlers (same as in create.php)
    $('.asset-check').change(function() {
        const assetId = $(this).val();
        const isChecked = $(this).is(':checked');
        const assetCode = $(this).data('code');
        const assetType = $(this).data('type');
        const assetCurrency = $(this).data('currency');
        
        if (isChecked) {
            // Add asset to allocation
            if (!allocationData[assetId]) {
                allocationData[assetId] = {
                    id: assetId,
                    code: assetCode,
                    name: $(this).next('label').text().trim(),
                    type: assetType,
                    currency: assetCurrency,
                    allocation: 0,
                    factor: 1.0
                };
            }
        } else {
            // Remove asset from allocation
            delete allocationData[assetId];
        }
        
        updateAllocationTable();
    });
    
    $(document).on('input', '.allocation-input', function() {
        const assetId = $(this).data('asset-id');
        const value = parseFloat($(this).val()) || 0;
        
        if (allocationData[assetId]) {
            allocationData[assetId].allocation = value;
            updateAllocationTable();
        }
    });
    
    $(document).on('input', '.factor-input', function() {
        const assetId = $(this).data('asset-id');
        const value = parseFloat($(this).val()) || 1.0;
        
        if (allocationData[assetId]) {
            allocationData[assetId].factor = value;
        }
    });
    
    $(document).on('click', '.remove-asset', function() {
        const assetId = $(this).data('asset-id');
        $(`#asset_${assetId}`).prop('checked', false);
        delete allocationData[assetId];
        updateAllocationTable();
    });
    
    $('#distributeEqual').click(function() {
        const assetCount = Object.keys(allocationData).length;
        if (assetCount === 0) return;
        
        const equalAllocation = 100 / assetCount;
        for (const assetId in allocationData) {
            allocationData[assetId].allocation = equalAllocation.toFixed(2);
        }
        updateAllocationTable();
    });
    
    // Form submission
    $('#portfolioForm').submit(function(e) {
        e.preventDefault();
        
        const totalAllocation = calculateTotalAllocation();
        if (Math.abs(totalAllocation - 100) > 0.01) {
            alert(`Alocação total deve ser 100%. Atual: ${totalAllocation.toFixed(2)}%`);
            return;
        }
        
        const formData = new FormData(this);
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
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Salvando...');
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Portfólio atualizado com sucesso!');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showError(response.error);
                    $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Salvar Alterações');
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao atualizar portfólio');
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Salvar Alterações');
            }
        });
    });
    
    // Delete portfolio
    $('#deleteBtn').click(function() {
        $('#deleteModal').modal('show');
    });
    
    $('#deleteForm').submit(function(e) {
        e.preventDefault();
        
        if (!confirm('Tem certeza que deseja excluir este portfólio?')) {
            return;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            beforeSend: function() {
                $('#deleteModal .btn-danger').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Excluindo...');
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Portfólio excluído com sucesso!');
                    setTimeout(function() {
                        window.location.href = '/portfolio';
                    }, 1000);
                } else {
                    showError(response.error);
                    $('#deleteModal .btn-danger').prop('disabled', false).html('Excluir');
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao excluir portfólio');
                $('#deleteModal .btn-danger').prop('disabled', false).html('Excluir');
            }
        });
    });
    
    // Clone portfolio
    $('#cloneBtn').click(function() {
        const newName = prompt('Digite o nome para a cópia:', 'Cópia de <?= addslashes($portfolio['name']) ?>');
        
        if (newName) {
            $.ajax({
                url: '/portfolio/<?= $portfolioId ?>/clone',
                method: 'POST',
                data: { new_name: newName },
                beforeSend: function() {
                    showLoading('Clonando portfólio...');
                },
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showSuccess('Portfólio clonado com sucesso!');
                        setTimeout(function() {
                            window.location.href = '/portfolio/' + response.data.portfolio_id;
                        }, 1000);
                    } else {
                        showError(response.error);
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    showError(xhr.responseJSON?.error || 'Erro ao clonar portfólio');
                }
            });
        }
    });
    
    // Export portfolio
    $('#exportBtn').click(function() {
        $.ajax({
            url: '/api/portfolio/<?= $portfolioId ?>/export',
            method: 'GET',
            beforeSend: function() {
                showLoading('Exportando portfólio...');
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    // Create download link
                    const blob = new Blob([JSON.stringify(response.data, null, 2)], { 
                        type: 'application/json' 
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'portfolio_<?= $portfolio['name'] ?>_export.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    showSuccess('Portfólio exportado com sucesso!');
                } else {
                    showError(response.error);
                }
            },
            error: function(xhr) {
                hideLoading();
                showError(xhr.responseJSON?.error || 'Erro ao exportar portfólio');
            }
        });
    });
    
    // Helper functions
    function showLoading(message) {
        // Implement loading overlay
        console.log('Loading:', message);
    }
    
    function hideLoading() {
        console.log('Hide loading');
    }
    
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