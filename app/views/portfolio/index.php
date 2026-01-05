<?php
$title = 'Meus Portfólios';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Meus Portfólios</h1>
    <a href="/index.php?url=portfolio/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Novo Portfólio
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="portfoliosTable" class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Capital Inicial</th>
                        <th>Período</th>
                        <th>Moeda</th>
                        <th>Tipo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($portfolios as $portfolio): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($portfolio['name']); ?></strong></td>
                        <td>R$ <?php echo number_format($portfolio['initial_capital'], 2, ',', '.'); ?></td>
                        <td>
                            <small>
                                <?php echo date('d/m/Y', strtotime($portfolio['start_date'])); ?>
                                <?php if ($portfolio['end_date']): ?>
                                    - <?php echo date('d/m/Y', strtotime($portfolio['end_date'])); ?>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?php echo $portfolio['output_currency']; ?></span></td>
                        <td>
                            <?php if ($portfolio['is_system_default']): ?>
                                <span class="badge bg-info">Sistema</span>
                            <?php else: ?>
                                <span class="badge bg-success">Pessoal</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group" role="group">
                                <a href="/index.php?url=portfolio/view/<?php echo $portfolio['id']; ?>" 
                                class="btn btn-sm btn-outline-info" 
                                title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                
                                <a href="/index.php?url=portfolio/run/<?php echo $portfolio['id']; ?>" 
                                class="btn btn-sm btn-outline-success" 
                                title="Simular Backtest">
                                    <i class="bi bi-play-fill"></i>
                                </a>
                                
                                <a href="/index.php?url=portfolio/clone/<?php echo $portfolio['id']; ?>" 
                                class="btn btn-sm btn-outline-primary" 
                                title="Clonar Portfólio">
                                    <i class="bi bi-files"></i>
                                </a>
                                
                                <a href="/index.php?url=portfolio/delete/<?php echo $portfolio['id']; ?>" 
                                class="btn btn-outline-danger" 
                                title="Excluir"
                                onclick="return confirm('Tem certeza que deseja excluir este portfólio? Esta ação não pode ser desfeita.')">
                                    <i class="bi bi-trash"></i>
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
// Scripts específicos para esta página
$additional_js = '
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $("#portfoliosTable").DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            order: [[0, "asc"]],
            pageLength: 10
        });
    });
</script>';

$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>