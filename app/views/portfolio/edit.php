<?php
$title = 'Editar Portfólio';
ob_start();

$assetModel = new Asset();
$allAssets = $assetModel->getAll();
$portfolioAssets = $portfolioModel->getPortfolioAssets($portfolio['id']);
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Editar Portfólio</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/portfolio/update/<?php echo $portfolio['id']; ?>" id="portfolioForm">
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
                                                    if ($pa['id'] == $asset['id']) {
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
                                        <input type="number" class="form-control" id="assetAllocation" step="0.01" min="0" max="100" placeholder="%">
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
                        <a href="/portfolio/view/<?php echo $portfolio['id']; ?>" class="btn btn-secondary">Cancelar</a>
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
        id: <?php echo $asset['id']; ?>,
        asset_id: <?php echo $asset['asset_id']; ?>,
        name: "<?php echo htmlspecialchars($asset['name']); ?>",
        allocation: <?php echo $asset['allocation_percentage'] * 100; ?>,
        factor: <?php echo $asset['performance_factor']; ?>
    },
    <?php endforeach; ?>
];

let nextId = assets.length > 0 ? Math.max(...assets.map(a => a.id)) + 1 : 1;

// Resto do JavaScript igual ao create.php...
</script>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>