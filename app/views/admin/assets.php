<?php
/**
 * @var array $assets Lista de todos os ativos do sistema
 */
$title = 'Gerenciar Ativos';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Gerenciar Ativos</h2>
    <a href="/index.php?url=assets/import" class="btn btn-primary rounded-pill px-4 shadow-sm">
        <i class="bi bi-upload me-2"></i>Importar CSV
    </a>
</div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Moeda</th>
                        <th>Tipo</th>
                        <th>Registros</th>
                        <th class="text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                    <tr>
                        <td class="ps-3"><?php echo $asset['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($asset['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($asset['name']); ?></td>
                        <td><span class="badge bg-soft-info text-info"><?php echo $asset['currency']; ?></span></td>
                        <td><?php echo $asset['asset_type']; ?></td>
                        <td>
                            <?php echo number_format($asset['data_count'] ?? 0, 0, '', '.'); ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="/index.php?url=<?php echo obfuscateUrl('assets/view/' . $asset['id']); ?>" class="btn btn-sm btn-white border px-2" title="Visualizar">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                                <a href="/index.php?url=<?php echo obfuscateUrl('assets/delete/' . $asset['id']); ?>" class="btn btn-sm btn-white border px-2" onclick="return confirm('Remover este ativo?')" title="Excluir">
                                    <i class="bi bi-trash text-danger"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
