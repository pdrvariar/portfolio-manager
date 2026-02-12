<?php
/**
 * @var array $assets Lista de todos os ativos do sistema
 */
$title = 'Gerenciar Ativos';
$csrfToken = Session::getCsrfToken();
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
                        <th>Fonte</th>
                        <th>Registros</th>
                        <th class="text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
<?php 
    $lastClosedMonth = date('Y-m-01', strtotime('first day of this month -1 month'));
    foreach ($assets as $asset): 
        $isUpdated = ($asset['max_date'] && $asset['max_date'] >= $lastClosedMonth);
        $dateRange = '';
        if ($asset['min_date'] && $asset['max_date']) {
            $dateRange = date('m/Y', strtotime($asset['min_date'])) . ' - ' . date('m/Y', strtotime($asset['max_date']));
        }
?>
                    <tr>
                        <td class="ps-3"><?php echo $asset['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <strong class="d-block"><?php echo htmlspecialchars($asset['code']); ?>
                                        <?php if ($isUpdated): ?>
                                            <i class="bi bi-check-circle-fill text-success ms-1" title="Atualizado"></i>
                                        <?php endif; ?>
                                    </strong>
                                    <small class="text-muted"><?php echo $dateRange; ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($asset['name']); ?></td>
                        <td><span class="badge bg-soft-info text-info"><?php echo $asset['currency']; ?></span></td>
                        <td><?php echo $asset['asset_type']; ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($asset['source'] ?? 'Yahoo'); ?></span>
                        </td>
                        <td>
                            <?php echo number_format($asset['data_count'] ?? 0, 0, '', '.'); ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="/index.php?url=<?php echo obfuscateUrl('assets/view/' . $asset['id']); ?>" class="btn btn-sm btn-white border px-2" title="Visualizar">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-white border px-2 js-update-quotes" data-id="<?php echo $asset['id']; ?>" title="Atualizar Cotações">
                                    <i class="bi bi-arrow-clockwise text-success"></i>
                                </button>
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

<script>
(function(){
    const csrf = '<?php echo $csrfToken; ?>';
    const url = '/index.php?url=<?php echo obfuscateUrl('admin/assets/update-quotes'); ?>';
    document.querySelectorAll('.js-update-quotes').forEach(btn => {
        btn.addEventListener('click', function(){
            const id = this.getAttribute('data-id');
            if (!confirm('Atualizar cotações deste ativo?')) return;
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrf)}`
            }).then(r => r.json()).then(res => {
                if (res && res.requires_full_refresh) {
                    const providerStart = res.provider_start || res.yahoo_start;
                    const providerEnd = res.provider_end || res.yahoo_end;
                    if (confirm(`Divergência detectada. Dados disponíveis de ${providerStart} até ${providerEnd}. Deseja atualizar tudo?`)) {
                        fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrf)}&confirm_full=1`
                        }).then(r2 => r2.json()).then(res2 => {
                            alert(res2.message || 'Operação concluída.');
                            window.location.reload();
                        }).catch(() => alert('Falha ao confirmar atualização completa.'));
                    }
                } else {
                    alert((res && res.message) ? res.message : 'Operação concluída.');
                    window.location.reload();
                }
            }).catch(() => alert('Falha ao atualizar cotações.'));
        });
    });
})();
</script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
