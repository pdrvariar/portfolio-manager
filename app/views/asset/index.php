<?php
$title = 'Ativos - Portfolio Backtest';
ob_start();
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Ativos</h1>
        
        <?php if ($_SESSION['is_admin'] ?? false): ?>
            <div>
                <a href="index.php?url=assets/import" class="btn btn-primary">
                    <i class="bi bi-upload"></i> Importar CSV
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($assets)): ?>
                <div class="text-center py-5">
                    <h4>Nenhum ativo cadastrado</h4>
                    <p class="text-muted">Importe dados históricos para começar</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Moeda</th>
                                <th>Tipo</th>
                                <?php if ($_SESSION['is_admin'] ?? false): ?>
                                    <th>Dados</th>
                                    <th>Período</th>
                                <?php endif; ?>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($asset['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($asset['currency']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($asset['asset_type']); ?>
                                    </span>
                                </td>
                                
                                <?php if ($_SESSION['is_admin'] ?? false): ?>
                                    <td>
                                        <?php if (isset($asset['data_count'])): ?>
                                            <span class="badge bg-<?php echo $asset['data_count'] > 0 ? 'success' : 'warning'; ?>">
                                                <?php echo $asset['data_count']; ?> registros
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($asset['min_date']) && isset($asset['max_date'])): ?>
                                            <small>
                                                <?php echo date('m/Y', strtotime($asset['min_date'])); ?>
                                                até
                                                <?php echo date('m/Y', strtotime($asset['max_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/index.php?url=assets/view/<?php echo $asset['id']; ?>" 
                                        class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($_SESSION['is_admin'] ?? false): ?>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="editAsset(<?php echo $asset['id']; ?>)" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <a href="/index.php?url=assets/delete/<?php echo $asset['id']; ?>" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Remover este ativo?')" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para edição (apenas admin) -->
<?php if ($_SESSION['is_admin'] ?? false): ?>
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Ativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editAssetForm">
                    <input type="hidden" id="asset_id" name="id">
                    
                    <div class="mb-3">
                        <label for="asset_name" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="asset_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="asset_currency" class="form-label">Moeda</label>
                        <select class="form-select" id="asset_currency" name="currency" required>
                            <option value="BRL">BRL (Real)</option>
                            <option value="USD">USD (Dólar)</option>
                            <option value="EUR">EUR (Euro)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="asset_type" class="form-label">Tipo</label>
                        <select class="form-select" id="asset_type" name="asset_type" required>
                            <option value="COTACAO">Cotação</option>
                            <option value="TAXA_MENSAL">Taxa Mensal</option>
                            <option value="TAXA_ANUAL">Taxa Anual</option>
                            <option value="CAMBIO">Câmbio</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveAsset()">Salvar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function editAsset(assetId) {
    // CORREÇÃO: Usar o caminho através do roteador index.php
    fetch(`/index.php?url=api/assets/${assetId}`)
        .then(response => {
            if (!response.ok) throw new Error('Erro na rede');
            return response.json();
        })
        .then(data => {
            document.getElementById('asset_id').value = data.id;
            document.getElementById('asset_name').value = data.name;
            document.getElementById('asset_currency').value = data.currency;
            document.getElementById('asset_type').value = data.asset_type;
            
            const modal = new bootstrap.Modal(document.getElementById('editAssetModal'));
            modal.show();
        })
        .catch(error => {
            alert('Erro ao carregar dados do ativo');
            console.error(error);
        });
}

function saveAsset() {
    const formData = new FormData(document.getElementById('editAssetForm'));
    
    // CORREÇÃO: Usar o caminho através do roteador index.php
    fetch('/index.php?url=api/assets/update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erro ao salvar ativo');
        console.error(error);
    });
}

</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>