<?php
$title = 'Biblioteca de Ativos';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Biblioteca de Ativos</h2>
        <p class="text-muted small mb-0">Gerencie a base de dados histórica para suas simulações.</p>
    </div>
    <?php if ($_SESSION['is_admin'] ?? false): ?>
        <a href="index.php?url=assets/import" class="btn btn-primary shadow-sm rounded-pill px-4">
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
                        <?php if ($_SESSION['is_admin'] ?? false): ?>
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
                        
                        <?php if ($_SESSION['is_admin'] ?? false): ?>
                            <td>
                                <div class="small fw-bold text-success">
                                    <?php echo number_format($asset['data_count'], 0, '', '.'); ?> registros
                                </div>
                                <div class="text-muted smaller">
                                    <?php echo date('m/y', strtotime($asset['min_date'])); ?> → <?php echo date('m/y', strtotime($asset['max_date'])); ?>
                                </div>
                            </td>
                        <?php endif; ?>

                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="/index.php?url=assets/view/<?php echo $asset['id']; ?>" class="btn btn-sm btn-white border px-2" title="Visualizar"><i class="bi bi-eye text-primary"></i></a>
                                <?php if ($_SESSION['is_admin'] ?? false): ?>
                                    <button class="btn btn-sm btn-white border px-2" onclick="editAsset(<?php echo $asset['id']; ?>)" title="Editar"><i class="bi bi-pencil text-warning"></i></button>
                                    <a href="/index.php?url=assets/delete/<?php echo $asset['id']; ?>" class="btn btn-sm btn-white border px-2" onclick="return confirm('Remover este ativo?')" title="Excluir"><i class="bi bi-trash text-danger"></i></a>
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
    <?php endif; ?>

<style>
    /* Padronização de Espaçamento e Estilo */
    #assetsTable th, #assetsTable td { padding-left: 0.4rem !important; padding-right: 0.4rem !important; }
    #assetsTable .ps-3 { padding-left: 1rem !important; }
    #assetsTable .pe-3 { padding-right: 1.5rem !important; }
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
    .bg-soft-secondary { background-color: rgba(108, 117, 125, 0.1); }
    .btn-white { background: #fff; border-color: #dee2e6 !important; }
    .btn-white:hover { background: #f8f9fa; }
    .table td { padding-top: 0.8rem !important; padding-bottom: 0.8rem !important; }
    .dataTables_wrapper { width: 100%; margin: 0 auto; }
</style>

<?php
$additional_js = '
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $("#assetsTable").DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json" },
            order: [[0, "asc"]],
            pageLength: 10,
            autoWidth: false,
            columnDefs: [{ orderable: false, targets: ' . (($_SESSION['is_admin'] ?? false) ? 4 : 3) . ' }],
            dom: "<\'row mb-2\'<\'col-sm-6\'l><\'col-sm-6 text-end\'f>>" + "<\'row\'<\'col-sm-12\'tr>>" + "<\'row mt-3\'<\'col-sm-5\'i><\'col-sm-7\'p>>"
        });
    });
</script>';

// Inclua aqui as funções JS editAsset() e saveAsset() que você já possui

$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>