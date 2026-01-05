<?php
$title = 'Editar Portfólio';
ob_start();
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Editar Portfólio</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/index.php?url=portfolio/update/<?php echo $portfolio['id']; ?>" id="portfolioForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome do Portfólio *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($portfolio['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="initial_capital" class="form-label">Capital Inicial *</label>
                                <input type="number" class="form-control" id="initial_capital" name="initial_capital" step="0.01" min="100" value="<?php echo $portfolio['initial_capital']; ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($portfolio['description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Data Início *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $portfolio['start_date']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Data Fim (opcional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $portfolio['end_date']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rebalance_frequency" class="form-label">Frequência Rebalanceamento *</label>
                                <select class="form-select" id="rebalance_frequency" name="rebalance_frequency" required>
                                    <option value="monthly" <?php echo $portfolio['rebalance_frequency'] == 'monthly' ? 'selected' : ''; ?>>Mensal</option>
                                    <option value="quarterly" <?php echo $portfolio['rebalance_frequency'] == 'quarterly' ? 'selected' : ''; ?>>Trimestral</option>
                                    <option value="biannual" <?php echo $portfolio['rebalance_frequency'] == 'biannual' ? 'selected' : ''; ?>>Semestral</option>
                                    <option value="annual" <?php echo $portfolio['rebalance_frequency'] == 'annual' ? 'selected' : ''; ?>>Anual</option>
                                    <option value="never" <?php echo $portfolio['rebalance_frequency'] == 'never' ? 'selected' : ''; ?>>Nunca</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="output_currency" class="form-label">Moeda de Saída *</label>
                        <select class="form-select" id="output_currency" name="output_currency" required>
                            <option value="BRL" <?php echo $portfolio['output_currency'] == 'BRL' ? 'selected' : ''; ?>>BRL (Real)</option>
                            <option value="USD" <?php echo $portfolio['output_currency'] == 'USD' ? 'selected' : ''; ?>>USD (Dólar)</option>
                        </select>
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
                                <!-- Linhas serão adicionadas via JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>
                                        <select class="form-select" id="assetSelect">
                                            <option value="">Selecione um ativo</option>
                                            <?php foreach ($allAssets as $asset): 
                                                $alreadyAdded = false;
                                                foreach ($portfolioAssets as $pa) {
                                                    // CORREÇÃO: Comparar asset_id, não o id da alocação
                                                    if ($pa['asset_id'] == $asset['id']) { 
                                                        $alreadyAdded = true;
                                                        break;
                                                    }
                                                }
                                                if (!$alreadyAdded): ?>
                                                    <option value="<?php echo $asset['id']; ?>" data-name="<?php echo htmlspecialchars($asset['name']); ?>">
                                                        <?php echo htmlspecialchars($asset['name']); ?> (<?php echo $asset['currency']; ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>                                    
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="assetAllocation" step="0.01" min="0" max="100" placeholder="%" oninput="calculateLiveTotal()">
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
                        <a href="index.php?url=portfolio/view/<?php echo $portfolio['id']; ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let assets = [
    <?php foreach ($portfolioAssets as $asset): ?>
    {
        // Use asset_id para ambos se houver dúvida, para garantir consistência
        id: <?php echo $asset['asset_id'] ?? $asset['id']; ?>, 
        asset_id: <?php echo $asset['asset_id'] ?? $asset['id']; ?>,
        name: "<?php echo htmlspecialchars($asset['name']); ?>",
        allocation: <?php echo number_format($asset['allocation_percentage'], 5, '.', ''); ?>,
        factor: <?php echo (float)$asset['performance_factor']; ?>
    },
    <?php endforeach; ?>
];

let nextId = assets.length > 0 ? Math.max(...assets.map(a => a.id)) + 1 : 1;

// FUNÇÃO CRUCIAL: Executar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    updateTable(); // Nome correto da função
});

// app/views/portfolio/edit.php

function addAsset() {
    const select = document.getElementById('assetSelect');
    const allocationInput = document.getElementById('assetAllocation');
    const factorInput = document.getElementById('assetFactor');
    
    // Validação básica
    if (!select.value || !allocationInput.value) {
        alert("Selecione um ativo e informe a alocação.");
        return;
    }

    const assetId = parseInt(select.value);
    const assetName = select.options[select.selectedIndex].getAttribute('data-name');

    // Verifica se o ativo já está na lista para evitar duplicidade
    if (assets.find(a => a.asset_id === assetId)) {
        alert("Este ativo já foi adicionado.");
        return;
    }

    // Adiciona ao array JavaScript
    assets.push({
        id: nextId++,
        asset_id: assetId,
        name: assetName,
        allocation: parseFloat(allocationInput.value),
        factor: parseFloat(factorInput.value) || 1.0
    });

    // Limpa os campos de inserção
    select.value = '';
    allocationInput.value = '';
    factorInput.value = '1.00';

    // Atualiza a interface
    updateTable();
    calculateLiveTotal(); // Recalcula o total para validar o botão de salvar
}

function removeAsset(id) {
    assets = assets.filter(a => a.id !== id);
    updateTable();
}

function updateTable() {
    const tbody = document.getElementById('assetsBody');
    const totalSpan = document.getElementById('totalAllocation');
    let total = 0;
    
    tbody.innerHTML = '';
    
    assets.forEach(asset => {
        total += asset.allocation;
        tbody.innerHTML += `
            <tr>
                <td>${asset.name}</td>
                <td>
                    <input type="number" name="assets[${asset.asset_id}][allocation]" 
                        value="${asset.allocation}" step="any" class="form-control form-control-sm"
                        oninput="updateAssetData(${asset.asset_id}, 'allocation', this.value)">
                </td>
                <td>
                    <input type="number" name="assets[${asset.asset_id}][performance_factor]" 
                        value="${asset.factor}" class="form-control form-control-sm"
                        oninput="updateAssetData(${asset.asset_id}, 'factor', this.value)">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeAsset(${asset.id})">x</button>
                    <input type="hidden" name="assets[${asset.asset_id}][asset_id]" value="${asset.asset_id}">
                </td>
            </tr>
        `;
    });

    totalSpan.innerText = total.toFixed(5);
    checkTotal(total);
}

function updateAssetData(assetId, field, value) {
    const asset = assets.find(a => a.asset_id == assetId);
    if (asset) {
        if (field === 'allocation') {
            asset.allocation = parseFloat(value) || 0;
        } else if (field === 'factor') {
            asset.factor = parseFloat(value) || 0;
        }
        
        // Recalcula o total sem redesenhar a tabela toda
        let total = 0;
        assets.forEach(a => total += a.allocation);
        
        document.getElementById('totalAllocation').innerText = total.toFixed(5);
        checkTotal(total);
    }
}

function checkTotal(total) {
    const isCorrect = total.toFixed(5) === "100.00000";
    const submitBtn = document.getElementById('submitBtn');
    
    // O botão só habilita se os ativos JÁ ADICIONADOS somarem 100%
    submitBtn.disabled = !isCorrect;
}


function calculateLiveTotal() {
    // Soma o que já está na lista
    let totalInList = assets.reduce((sum, asset) => sum + asset.allocation, 0);
    
    // Pega o valor que você está digitando no campo de "novo ativo"
    const newAllocationInput = document.getElementById('assetAllocation');
    let typingValue = parseFloat(newAllocationInput.value) || 0;
    
    let finalTotal = totalInList + typingValue;
    
    // Atualiza apenas o texto na tela
    document.getElementById('totalAllocation').innerText = finalTotal.toFixed(5);
    
    // Mostra o aviso se a soma temporária não for 100
    const warning = document.getElementById('allocationWarning');
    if (warning) {
        warning.style.display = (finalTotal.toFixed(5) === "100.00000") ? 'none' : 'block';
    }
    
}



</script>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>