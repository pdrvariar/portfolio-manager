<?php
/**
 * @var array $assets Lista de ativos disponíveis
 */
$title = 'Criar Portfólio';
ob_start();

$assetModel = new Asset();
$assets = $assetModel->getAllWithDetails();
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Criar Novo Portfólio</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('portfolio/create'); ?>" id="portfolioForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome do Portfólio *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="initial_capital" class="form-label">Capital Inicial *</label>
                                <input type="number" class="form-control" id="initial_capital" name="initial_capital" step="0.01" min="100" value="100000" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Data Início *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Data Fim (opcional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rebalance_frequency" class="form-label">Frequência Rebalanceamento *</label>
                                <select class="form-select" id="rebalance_frequency" name="rebalance_frequency" required>
                                    <option value="monthly">Mensal</option>
                                    <option value="quarterly">Trimestral</option>
                                    <option value="biannual">Semestral</option>
                                    <option value="annual">Anual</option>
                                    <option value="never">Nunca</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="output_currency" class="form-label">Moeda de Saída *</label>
                        <select class="form-select" id="output_currency" name="output_currency" required>
                            <option value="BRL">BRL (Real)</option>
                            <option value="USD">USD (Dólar)</option>
                        </select>
                    </div>
                    
                    <hr>

                    <h5 class="mb-3">Tipo de Simulação</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="simulation_type" class="form-label">Tipo de Simulação *</label>
                                <select class="form-select" id="simulation_type" name="simulation_type" required onchange="toggleSimulationFields()">
                                    <option value="standard">Padrão (sem aportes)</option>
                                    <option value="monthly_deposit">Com Aportes Periódicos</option>
                                    <option value="strategic_deposit">Com Aportes Estratégicos</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Campos para Aportes Periódicos -->
                    <div id="monthly_deposit_fields" class="simulation-fields" style="display: none;">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Configuração de Aportes Periódicos</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="deposit_amount" class="form-label">Valor do Aporte</label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="deposit_currency_label">BRL</span>
                                                <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" step="0.01" min="0" placeholder="Ex: 5000.00">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="deposit_currency" class="form-label">Moeda do Aporte</label>
                                            <select class="form-select" id="deposit_currency" name="deposit_currency" onchange="document.getElementById('deposit_currency_label').innerText = this.value">
                                                <option value="BRL">BRL (Real)</option>
                                                <option value="USD">USD (Dólar)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="deposit_frequency" class="form-label">Frequência</label>
                                            <select class="form-select" id="deposit_frequency" name="deposit_frequency">
                                                <option value="monthly">Mensal</option>
                                                <option value="bimonthly">Bimestral</option>
                                                <option value="quarterly">Trimestral</option>
                                                <option value="biannual">Semestral</option>
                                                <option value="annual">Anual</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info py-2 small mb-0">
                                    <i class="bi bi-info-circle me-1"></i> Os aportes serão realizados automaticamente na data de início de cada período.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos para Aportes Estratégicos -->
                    <div id="strategic_deposit_fields" class="simulation-fields" style="display: none;">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="bi bi-graph-down me-2"></i>Configuração de Aportes Estratégicos</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="strategic_threshold" class="form-label">Limiar de Queda para Aporte</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="strategic_threshold" name="strategic_threshold" step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Aporte será feito se o portfólio cair este percentual em um mês</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="strategic_deposit_percentage" class="form-label">Percentual do Aporte</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="strategic_deposit_percentage" name="strategic_deposit_percentage" step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Percentual do valor atual do portfólio a ser aportado</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="bi bi-lightbulb me-1"></i> Exemplo: Se cair 10% em um mês, será aportado 10% do valor atual do portfólio.
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <h5 class="mb-3">Alocação de Ativos</h5>
                    <div class="table-responsive mb-3">
                        <table class="table" id="assetsTable">
                            <thead>
                                <tr>
                                    <th>Ativo</th>
                                    <th style="width: 150px;">Alocação (%)</th>
                                    <th style="width: 150px;">Fator Performance</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="assetsBody">
                                <!-- Linhas serão adicionadas aqui -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>
                                        <select class="form-select" id="assetSelect">
                                            <option value="">+ Adicionar novo ativo...</option>
                                            <?php foreach ($assets as $asset): 
                                                $start = !empty($asset['min_date']) ? date('m/Y', strtotime($asset['min_date'])) : 'Início';
                                                $end = !empty($asset['max_date']) ? date('m/Y', strtotime($asset['max_date'])) : 'Hoje';
                                            ?>
                                                <option value="<?php echo $asset['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                                        data-min="<?php echo $asset['min_date']; ?>"
                                                        data-max="<?php echo $asset['max_date']; ?>">
                                                    <?php echo htmlspecialchars($asset['name']); ?> 
                                                    (<?php echo $start; ?> a <?php echo $end; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="assetAllocation" step="any" min="0" max="100" placeholder="%">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="assetFactor" step="0.01" min="0.1" max="10" value="1.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success" onclick="addAsset()">+</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">
                                        <strong>Total: <span id="totalAllocation">0</span>%</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning" id="allocationWarning" style="display: none;">
                        A soma da alocação deve ser 100%
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Criar Portfólio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let assets = [];
let nextId = 1;

function addAsset() {
    const assetSelect = document.getElementById('assetSelect');
    const allocationInput = document.getElementById('assetAllocation');
    const factorInput = document.getElementById('assetFactor');
    
    if (!assetSelect.value || parseFloat(allocationInput.value) <= 0) {
        alert('Selecione um ativo e informe a alocação.');
        return;
    }
    
    // Verificar se ativo já foi adicionado
    if (assets.some(a => a.asset_id == assetSelect.value)) {
        alert('Este ativo já foi adicionado ao portfólio.');
        return;
    }
    
    const opt = assetSelect.options[assetSelect.selectedIndex];
    
    const asset = {
        id: nextId++,
        asset_id: assetSelect.value,
        name: opt.getAttribute('data-name'),
        allocation: parseFloat(allocationInput.value) || 0,
        factor: parseFloat(factorInput.value) || 1.0,
        // Sênior: Metadados para validação de UEX
        min_date: opt.getAttribute('data-min'),
        max_date: opt.getAttribute('data-max')
    };
    
    assets.push(asset);
    updateAssetsTable();
    updateTotal();
    
    // Limpar campos
    assetSelect.value = '';
    allocationInput.value = '';
    factorInput.value = '1.00';
}

function removeAsset(id) {
    assets = assets.filter(a => a.id !== id);
    updateAssetsTable();
    updateTotal();
}

function updateAssetsTable() {
    const tbody = document.getElementById('assetsBody');
    tbody.innerHTML = '';
    
    assets.forEach(asset => {
        // Formatação sênior de data (AAAA-MM-DD -> MM/AAAA)
        const formatDate = (dateStr) => {
            if (!dateStr) return "-";
            const parts = dateStr.split('-');
            return parts.length >= 2 ? `${parts[1]}/${parts[0]}` : dateStr;
        };

        const row = tbody.insertRow();
        row.innerHTML = `
            <td>
                <div class="fw-bold text-dark">${asset.name}</div>
                <div class="text-muted smaller" style="font-size: 0.7rem;">
                    <i class="bi bi-calendar-check me-1"></i>
                    Histórico: ${formatDate(asset.min_date)} a ${formatDate(asset.max_date)}
                </div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm allocation-input" 
                       value="${asset.allocation}" step="any" 
                       onchange="updateAssetAllocation(${asset.id}, this.value)">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${asset.factor}" step="0.01"
                       onchange="updateAssetFactor(${asset.id}, this.value)">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0" 
                        onclick="removeAsset(${asset.id})"><i class="bi bi-trash"></i></button>
            </td>
        `;
    });
}

function updateAssetAllocation(id, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        asset.allocation = parseFloat(value) || 0;
        updateTotal();
    }
}

function updateAssetFactor(id, value) {
    const asset = assets.find(a => a.id === id);
    if (asset) {
        asset.factor = parseFloat(value) || 1.0;
    }
}

function updateTotal() {
    const total = assets.reduce((sum, asset) => sum + asset.allocation, 0);
    document.getElementById('totalAllocation').textContent = total.toFixed(5);
    
    const warning = document.getElementById('allocationWarning');
    const submitBtn = document.getElementById('submitBtn');
    
    if (Math.abs(total - 100) < 0.00001) {
        warning.style.display = 'none';
        submitBtn.disabled = false;
    } else {
        warning.style.display = 'block';
        submitBtn.disabled = true;
    }
}

document.getElementById('portfolioForm').addEventListener('submit', function(e) {
    // Adicionar ativos ao formulário
    assets.forEach((asset) => {
        const assetInput = document.createElement('input');
        assetInput.type = 'hidden';
        assetInput.name = `assets[${asset.asset_id}][asset_id]`;
        assetInput.value = asset.asset_id;
        
        const allocationInput = document.createElement('input');
        allocationInput.type = 'hidden';
        allocationInput.name = `assets[${asset.asset_id}][allocation]`;
        allocationInput.value = asset.allocation;
        
        const factorInput = document.createElement('input');
        factorInput.type = 'hidden';
        factorInput.name = `assets[${asset.asset_id}][performance_factor]`;
        factorInput.value = asset.factor;
        
        this.appendChild(assetInput);
        this.appendChild(allocationInput);
        this.appendChild(factorInput);
    });
});

// Definir data padrão (primeiro dia do mês atual)
const today = new Date();
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
document.getElementById('start_date').valueAsDate = firstDay;


// Controle dos campos de simulação
function toggleSimulationFields() {
    const type = document.getElementById('simulation_type').value;

    // Esconde todos os campos primeiro
    document.querySelectorAll('.simulation-fields').forEach(field => {
        field.style.display = 'none';
    });

    // Mostra os campos específicos
    if (type === 'monthly_deposit') {
        document.getElementById('monthly_deposit_fields').style.display = 'block';
        // Define valores padrão
        if (!document.getElementById('deposit_amount').value) {
            document.getElementById('deposit_amount').value = '1000.00';
        }
    } else if (type === 'strategic_deposit') {
        document.getElementById('strategic_deposit_fields').style.display = 'block';
        // Define valores padrão
        if (!document.getElementById('strategic_threshold').value) {
            document.getElementById('strategic_threshold').value = '10.0';
        }
        if (!document.getElementById('strategic_deposit_percentage').value) {
            document.getElementById('strategic_deposit_percentage').value = '10.0';
        }
    }
}

// Inicializa ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    toggleSimulationFields();
});

</script>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>