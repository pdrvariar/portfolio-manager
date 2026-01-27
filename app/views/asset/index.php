<?php
/**
 * @var array $assets Lista de ativos (id, code, name, currency, asset_type, etc.)
 */
$title = 'Biblioteca de Ativos';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Biblioteca de Ativos</h2>
        <p class="text-muted small mb-0">Gerencie a base de dados histórica para suas simulações.</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
        <a href="index.php?url=<?php echo obfuscateUrl('assets/import'); ?>" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="bi bi-upload me-2"></i>Importar CSV
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow-x: hidden;">
            <table id="assetsTable" class="table table-hover align-middle mb-0" style="width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-3" style="width: 25%">Ativo</th>
                        <th style="width: 15%">Moeda</th>
                        <th style="width: 15%">Classe</th>
                        <?php if (Auth::isAdmin()): ?>
                            <th style="width: 25%">Histórico</th>
                        <?php endif; ?>
                        <th class="text-end pe-3" style="width: 20%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($asset['code']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($asset['name']); ?></div>
                        </td>
                        <td><span class="badge bg-soft-info text-info border-0 px-3"><?php echo htmlspecialchars($asset['currency']); ?></span></td>
                        <td><span class="badge bg-soft-secondary text-secondary border-0"><?php echo htmlspecialchars($asset['asset_type']); ?></span></td>
                        
                        <?php if (Auth::isAdmin()): ?>
                            <td>
                                <div class="small fw-bold text-success">
                                    <?php echo number_format($asset['data_count'] ?? 0, 0, '', '.'); ?> registros
                                </div>
                                <div class="text-muted smaller">
                                    <?php echo !empty($asset['min_date']) ? date('m/y', strtotime($asset['min_date'])) : '--'; ?> 
                                    → 
                                    <?php echo !empty($asset['max_date']) ? date('m/y', strtotime($asset['max_date'])) : '--'; ?>
                                </div>
                            </td>
                        <?php endif; ?>

                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="/index.php?url=<?php echo obfuscateUrl('assets/view/' . $asset['id']); ?>" class="btn btn-sm btn-white border px-2" title="Visualizar">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                                <?php if (Auth::isAdmin()): ?>
                                    <button class="btn btn-sm btn-white border px-2" onclick="editAsset(<?php echo $asset['id']; ?>)" title="Editar">
                                        <i class="bi bi-pencil text-warning"></i>
                                    </button>
                                    <a href="/index.php?url=<?php echo obfuscateUrl('assets/delete/' . $asset['id']); ?>" class="btn btn-sm btn-white border px-2" onclick="return confirm('Remover este ativo?')" title="Excluir">
                                        <i class="bi bi-trash text-danger"></i>
                                    </a>
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

<div class="modal fade" id="editAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form id="editAssetForm">
                <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                <input type="hidden" name="id" id="editAssetId">
                
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar Ativo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nome do Ativo</label>
                        <input type="text" name="name" id="editAssetName" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Moeda</label>
                            <select name="currency" id="editAssetCurrency" class="form-select">
                                <option value="BRL">BRL</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-muted">Classe</label>
                            <select name="asset_type" id="editAssetType" class="form-select">
                                <option value="COTACAO">Cotação</option>
                                <option value="TAXA_MENSAL">Taxa Mensal</option>
                                <option value="CAMBIO">Câmbio</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" onclick="saveAsset()">
                        <i class="bi bi-check-lg me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$isAdminFlag = Auth::isAdmin() ? 4 : 3;
$additional_js = <<<JS
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $("#assetsTable").DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json" },
            order: [[0, "asc"]],
            pageLength: 10,
            autoWidth: false,
            columnDefs: [{ orderable: false, targets: $isAdminFlag }],
            dom: "<'row mb-2'<'col-sm-6'l><'col-sm-6 text-end'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row mt-3'<'col-sm-5'i><'col-sm-7'p>>"
        });
    });

    function editAsset(id) {
        $.get('/index.php?url=api/assets/' + id, function(asset) {
            $('#editAssetId').val(asset.id);
            $('#editAssetName').val(asset.name);
            $('#editAssetCurrency').val(asset.currency);
            $('#editAssetType').val(asset.asset_type);
            
            var editModal = new bootstrap.Modal(document.getElementById('editAssetModal'));
            editModal.show();
        }).fail(function() {
            alert('Erro ao carregar os dados do ativo.');
        });
    }

    function saveAsset() {
        const form = document.getElementById('editAssetForm');
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => { data[key] = value; });

        $.ajax({
            url: '/index.php?url=api/assets/update',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + (response.message || 'Falha ao atualizar.'));
                }
            },
            error: function(xhr) {
                if(xhr.status === 403) {
                    alert('Erro de Segurança: Sessão expirada ou Token inválido.');
                } else {
                    alert('Erro técnico ao salvar.');
                }
            }
        });
    }
</script>
JS;

$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>