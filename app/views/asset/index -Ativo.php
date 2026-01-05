<?php
$title = 'Biblioteca de Ativos';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Biblioteca de Ativos</h2>
        <p class="text-muted small mb-0">Ativos disponíveis para composição de portfólios.</p>
    </div>
    <?php if ($_SESSION['is_admin'] ?? false): ?>
        <a href="index.php?url=assets/import" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="bi bi-upload me-2"></i>Importar CSV
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Ativo</th>
                        <th>Moeda</th>
                        <th>Classe</th>
                        <?php if ($_SESSION['is_admin'] ?? false): ?>
                            <th>Histórico Disponível</th>
                        <?php endif; ?>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($asset['code']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($asset['name']); ?></div>
                        </td>
                        <td><span class="badge bg-soft-info text-info border-0 px-3"><?php echo htmlspecialchars($asset['currency']); ?></span></td>
                        <td><span class="badge bg-soft-secondary text-secondary border-0"><?php echo htmlspecialchars($asset['asset_type']); ?></span></td>
                        
                        <?php if ($_SESSION['is_admin'] ?? false): ?>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="small fw-bold text-<?php echo $asset['data_count'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo number_format($asset['data_count'], 0, '', '.'); ?> registros
                                        </div>
                                        <div class="text-muted smaller">
                                            <?php echo date('m/y', strtotime($asset['min_date'])); ?> ➔ <?php echo date('m/y', strtotime($asset['max_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="progress flex-grow-1 d-none d-xl-flex" style="height: 4px; max-width: 80px;">
                                        <div class="progress-bar bg-success" style="width: 100%"></div>
                                    </div>
                                </div>
                            </td>
                        <?php endif; ?>

                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="/index.php?url=assets/view/<?php echo $asset['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Visualizar Base"><i class="bi bi-eye"></i></a>
                                <?php if ($_SESSION['is_admin'] ?? false): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editAsset(<?php echo $asset['id']; ?>)" title="Editar Definição"><i class="bi bi-pencil"></i></button>
                                    <a href="/index.php?url=assets/delete/<?php echo $asset['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover este ativo e TODO o seu histórico?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($_SESSION['is_admin'] ?? false): ?>
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Propriedades do Ativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editAssetForm">
                    <input type="hidden" id="asset_id" name="id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nome do Ativo</label>
                        <input type="text" class="form-control" id="asset_name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Moeda Base</label>
                            <select class="form-select" id="asset_currency" name="currency" required>
                                <option value="BRL">BRL (Real)</option>
                                <option value="USD">USD (Dólar)</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Tipo de Cálculo</label>
                            <select class="form-select" id="asset_type" name="asset_type" required>
                                <option value="COTACAO">Cotação</option>
                                <option value="TAXA_MENSAL">Taxa Mensal</option>
                                <option value="TAXA_ANUAL">Taxa Anual</option>
                                <option value="CAMBIO">Câmbio</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveAsset()">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    // Scripts JS de editAsset e saveAsset mantidos conforme o original
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>