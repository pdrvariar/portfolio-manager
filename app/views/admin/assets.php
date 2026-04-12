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
            <table id="adminAssetsTable" class="table table-hover align-middle mb-0 table-rounded">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Moeda</th>
                        <th>Tipo</th>
                        <th>Grupo IR</th>
                        <th>Caixa?</th>
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
                            <span class="badge bg-light text-dark border small"><?php echo $asset['tax_group'] ?? 'RENDA_FIXA'; ?></span>
                        </td>
                        <td>
                            <?php if ($asset['is_cash']): ?>
                                <span class="badge bg-success">Sim</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($asset['source'] ?? 'Yahoo'); ?></span>
                        </td>
                        <td>
                            <?php echo number_format($asset['data_count'] ?? 0, 0, '', '.'); ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <button type="button" class="btn btn-sm btn-white border px-2 js-edit-asset" 
                                        data-id="<?php echo $asset['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                        data-code="<?php echo htmlspecialchars($asset['code']); ?>"
                                        data-currency="<?php echo $asset['currency']; ?>"
                                        data-type="<?php echo $asset['asset_type']; ?>"
                                        data-taxgroup="<?php echo $asset['tax_group'] ?? 'RENDA_FIXA'; ?>"
                                        data-source="<?php echo htmlspecialchars($asset['source'] ?? 'Yahoo'); ?>"
                                        data-ticker="<?php echo htmlspecialchars($asset['yahoo_ticker'] ?? ''); ?>"
                                        data-iscash="<?php echo $asset['is_cash'] ? '1' : '0'; ?>"
                                        title="Editar Ativo">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
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

<!-- Modal para Edição de Ativo -->
<div class="modal fade" id="editAssetModal" tabindex="-1" aria-labelledby="editAssetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAssetModalLabel">Editar Ativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="editAssetForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Código do Ativo</label>
                        <input type="text" id="edit-code" class="form-control" readonly disabled>
                        <small class="text-muted">O código não pode ser alterado por segurança.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Moeda</label>
                            <select name="currency" id="edit-currency" class="form-select">
                                <option value="BRL">BRL</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="asset_type" id="edit-type" class="form-select">
                                <option value="COTACAO">Cotação (Ações/ETF)</option>
                                <option value="TAXA_MENSAL">Taxa Mensal (SELIC)</option>
                                <option value="INFLACAO">Inflação (IPCA)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grupo Imposto de Renda</label>
                        <select name="tax_group" id="edit-taxgroup" class="form-select">
                            <option value="RENDA_FIXA">Renda Fixa</option>
                            <option value="ETF_BR">ETF Brasil</option>
                            <option value="CRIPTOMOEDA">Criptomoeda</option>
                            <option value="FUNDO_IMOBILIARIO">Fundo Imobiliário (FII)</option>
                            <option value="ETF_US">ETF EUA</option>
                            <option value="NAO_APLICAVEL">Não Aplicável</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fonte de Dados</label>
                        <select name="source" id="edit-source" class="form-select">
                            <option value="Yahoo">Yahoo Finance</option>
                            <option value="BCB">Banco Central (SELIC)</option>
                            <option value="IBGE">IBGE (IPCA)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yahoo Ticker (se aplicável)</label>
                        <input type="text" name="yahoo_ticker" id="edit-ticker" class="form-control">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_cash" id="edit-iscash" class="form-check-input" value="1">
                        <label class="form-check-label" for="edit-iscash">Usar como Caixa (pode ser vendido em rebalanceamento "Apenas Compras")</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$additional_js = '
<script>
(function(){
    const csrf = \'' . $csrfToken . '\';
    const updateQuotesUrl = \'/index.php?url=' . obfuscateUrl('admin/assets/update-quotes') . '\';
    const updateAssetUrl = \'/index.php?url=' . obfuscateUrl('api/assets/update') . '\';
    
    // Modal de Edição
    const editModal = new bootstrap.Modal(document.getElementById(\'editAssetModal\'));
    const editForm = document.getElementById(\'editAssetForm\');

    document.querySelectorAll(\'.js-edit-asset\').forEach(btn => {
        btn.addEventListener(\'click\', function() {
            document.getElementById(\'edit-id\').value = this.dataset.id;
            document.getElementById(\'edit-code\').value = this.dataset.code;
            document.getElementById(\'edit-name\').value = this.dataset.name;
            document.getElementById(\'edit-currency\').value = this.dataset.currency;
            document.getElementById(\'edit-type\').value = this.dataset.type;
            document.getElementById(\'edit-taxgroup\').value = this.dataset.taxgroup;
            document.getElementById(\'edit-source\').value = this.dataset.source;
            document.getElementById(\'edit-ticker\').value = this.dataset.ticker;
            document.getElementById(\'edit-iscash\').checked = this.dataset.iscash === \'1\';
            editModal.show();
        });
    });

    editForm.addEventListener(\'submit\', function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector(\'button[type="submit"]\');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...\';
        
        const formData = new FormData(this);
        
        fetch(updateAssetUrl, {
            method: \'POST\',
            body: formData
        }).then(r => r.json()).then(res => {
            if (res.success) {
                editModal.hide();
                window.location.reload();
            } else {
                alert(res.message || \'Erro ao atualizar ativo\');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }).catch(err => {
            console.error(\'Erro:\', err);
            alert(\'Falha na comunicação com o servidor\');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });

    document.querySelectorAll(\'.js-update-quotes\').forEach(btn => {
        btn.addEventListener(\'click\', function(){
            const id = this.getAttribute(\'data-id\');
            if (!confirm(\'Atualizar cotações deste ativo?\')) return;
            fetch(updateQuotesUrl, {
                method: \'POST\',
                headers: { \'Content-Type\': \'application/x-www-form-urlencoded\' },
                body: `id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrf)}`
            }).then(r => r.json()).then(res => {
                if (res && res.requires_full_refresh) {
                    const providerStart = res.provider_start || res.yahoo_start;
                    const providerEnd = res.provider_end || res.yahoo_end;
                    if (confirm(`Divergência detectada. Dados disponíveis de ${providerStart} até ${providerEnd}. Deseja atualizar tudo?`)) {
                        fetch(updateQuotesUrl, {
                            method: \'POST\',
                            headers: { \'Content-Type\': \'application/x-www-form-urlencoded\' },
                            body: `id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(csrf)}&confirm_full=1`
                        }).then(r2 => r2.json()).then(res2 => {
                            alert(res2.message || \'Operação concluída.\');
                            window.location.reload();
                        }).catch(() => alert(\'Falha ao confirmar atualização completa.\'));
                    }
                } else {
                    alert((res && res.message) ? res.message : \'Operação concluída.\');
                    window.location.reload();
                }
            }).catch(() => alert(\'Falha ao atualizar cotações.\'));
        });
    });
})();
</script>
';
include_once __DIR__ . '/../layouts/main.php';
?>
