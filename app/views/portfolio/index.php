<?php
/**
 * @var array $portfolios Lista de portfólios do usuário
 */

$title = 'Meus Portfólios';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Meus Portfólios</h2>
        <p class="text-muted small mb-0">Gerencie e analise suas estratégias de investimento.</p>
    </div>
    <a href="/index.php?url=<?= obfuscateUrl('portfolio/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-4">
        <i class="bi bi-plus-lg me-1"></i> Novo Portfólio
    </a>
</div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow-x: hidden;"> <table id="portfoliosTable" class="table table-hover align-middle mb-0" style="width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-3" style="width: 25%">Estratégia</th>
                        <th style="width: 20%">Capital</th>
                        <th style="width: 25%">Período Histórico</th>
                        <th class="text-center" style="width: 10%">Status</th>
                        <th class="text-end pe-3" style="width: 20%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($portfolios as $portfolio): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-dark" style="font-size: 1rem; line-height: 1.2; max-width: 220px; word-wrap: break-word;">
                                <?php echo htmlspecialchars($portfolio['name']); ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                <i class="bi bi-arrow-repeat me-1"></i><?php echo ucfirst($portfolio['rebalance_frequency']); ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">
                                <?php echo formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']); ?>
                            </div>
                            <span class="text-muted" style="font-size: 0.65rem; font-weight: 600;">
                                <?php echo $portfolio['output_currency']; ?>
                            </span>
                        </td>
                        <td class="text-nowrap"> <div class="fw-medium text-dark small">
                                <i class="bi bi-calendar3 text-muted me-1"></i>
                                <?php echo date('d/m/y', strtotime($portfolio['start_date'])); ?> 
                                <span class="mx-1 text-muted">→</span> 
                                <?php echo $portfolio['end_date'] ? date('d/m/y', strtotime($portfolio['end_date'])) : 'Hoje'; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($portfolio['is_system_default']): ?>
                                <span class="badge rounded-pill bg-soft-info text-info px-2 py-1" style="font-size: 0.7rem;">Sistema</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-soft-success text-success px-2 py-1" style="font-size: 0.7rem;">Pessoal</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>" class="btn btn-sm btn-white border px-2" title="Visualizar"><i class="bi bi-graph-up text-primary"></i></a>
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>" class="btn btn-sm btn-white border px-2" title="Simular"><i class="bi bi-play-fill text-success"></i></a>
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/clone/' . $portfolio['id']) ?>" class="btn btn-sm btn-white border px-2" title="Clonar"><i class="bi bi-files text-secondary"></i></a>
                                
                                <?php if (!$portfolio['is_system_default'] || Auth::isAdmin()): ?>
                                    <a href="/index.php?url=<?= obfuscateUrl('portfolio/edit/' . $portfolio['id']) ?>" class="btn btn-sm btn-white border px-2" title="Editar"><i class="bi bi-pencil text-warning"></i></a>
                                <?php endif; ?>

                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/delete/' . $portfolio['id']) ?>" class="btn btn-sm btn-white border px-2" title="Excluir" onclick="return confirm('Excluir portfólio?')"><i class="bi bi-trash text-danger"></i></a>
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
$additional_js = '
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $("#portfoliosTable").DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json" },
            order: [[0, "asc"]],
            pageLength: 10,
            autoWidth: false, // UEX: Impede que o DataTables calcule larguras que forçam o scroll
            columnDefs: [{ orderable: false, targets: 4 }],
            dom: "<\'row mb-2\'<\'col-sm-6\'l><\'col-sm-6 text-end\'f>>" +
                 "<\'row\'<\'col-sm-12\'tr>>" +
                 "<\'row mt-3\'<\'col-sm-5\'i><\'col-sm-7\'p>>"
        });
    });
</script>';

$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>