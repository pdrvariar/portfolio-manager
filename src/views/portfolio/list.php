<?php
require_once __DIR__ . '/../../app/controllers/PortfolioController.php';
$controller = new PortfolioController();
$portfolios = $controller->getUserPortfolios();
?>

<?php include __DIR__ . '/../layout/header.php'; ?>

<?php
$pageTitle = 'Meus Portfólios';
$pageSubtitle = 'Gerencie e visualize todos os seus portfólios de investimentos';
$pageActions = '
    <a href="/portfolio/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Novo Portfólio
    </a>
    <a href="/portfolio/templates" class="btn btn-outline-primary ms-2">
        <i class="bi bi-collection"></i> Modelos
    </a>
';
include __DIR__ . '/../layout/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?= count($portfolios) ?></div>
                            <div class="stat-label">Portfólios</div>
                        </div>
                        <i class="bi bi-pie-chart stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?= $controller->getActiveSimulations() ?></div>
                            <div class="stat-label">Simulações</div>
                        </div>
                        <i class="bi bi-calculator stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?= $controller->getTotalAssets() ?></div>
                            <div class="stat-label">Ativos</div>
                        </div>
                        <i class="bi bi-bar-chart stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card purple">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value">R$ <?= number_format($controller->getTotalCapital(), 2, ',', '.') ?></div>
                            <div class="stat-label">Capital Total</div>
                        </div>
                        <i class="bi bi-wallet2 stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Portfolio Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Portfólios Recentes</h5>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" id="filterAll">Todos</button>
                    <button class="btn btn-sm btn-outline-secondary" id="filterActive">Ativos</button>
                    <button class="btn btn-sm btn-outline-secondary" id="filterDefault">Modelos</button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($portfolios)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-pie-chart display-1 text-muted"></i>
                        <h4 class="mt-3">Nenhum portfólio encontrado</h4>
                        <p class="text-muted">Crie seu primeiro portfólio para começar a simular</p>
                        <a href="/portfolio/create" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Criar Portfólio
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Ativos</th>
                                    <th>Capital</th>
                                    <th>Últ. Simulação</th>
                                    <th>Retorno</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($portfolios as $portfolio): ?>
                                    <tr class="portfolio-row" data-type="<?= $portfolio['is_default'] ? 'default' : 'personal' ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($portfolio['is_default']): ?>
                                                    <span class="badge bg-info me-2">Modelo</span>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($portfolio['name']) ?></strong>
                                                    <?php if (!empty($portfolio['description'])): ?>
                                                        <div class="text-muted small"><?= htmlspecialchars(substr($portfolio['description'], 0, 50)) ?>...</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $portfolio['asset_count'] ?> ativos</span>
                                        </td>
                                        <td>
                                            R$ <?= number_format($portfolio['initial_capital'], 2, ',', '.') ?>
                                        </td>
                                        <td>
                                            <?php if ($portfolio['last_simulation']): ?>
                                                <?= date('d/m/Y', strtotime($portfolio['last_simulation'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($portfolio['last_return']): ?>
                                                <span class="<?= $portfolio['last_return'] >= 0 ? 'positive-performance' : 'negative-performance' ?>">
                                                    <?= number_format($portfolio['last_return'], 2, ',', '.') ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/portfolio/<?= $portfolio['id'] ?>" class="btn btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/portfolio/<?= $portfolio['id'] ?>/simulate" class="btn btn-outline-success">
                                                    <i class="bi bi-calculator"></i>
                                                </a>
                                                <?php if (!$portfolio['is_default']): ?>
                                                    <button class="btn btn-outline-secondary clone-portfolio" 
                                                            data-id="<?= $portfolio['id'] ?>"
                                                            data-name="<?= htmlspecialchars($portfolio['name']) ?>">
                                                        <i class="bi bi-copy"></i>
                                                    </button>
                                                    <a href="/portfolio/<?= $portfolio['id'] ?>/edit" class="btn btn-outline-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger delete-portfolio" 
                                                            data-id="<?= $portfolio['id'] ?>"
                                                            data-name="<?= htmlspecialchars($portfolio['name']) ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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
    
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Ações Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/portfolio/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Criar Novo Portfólio
                    </a>
                    <button class="btn btn-outline-primary" id="importPortfolio">
                        <i class="bi bi-upload me-2"></i> Importar Portfólio
                    </button>
                    <button class="btn btn-outline-secondary" id="exportAll">
                        <i class="bi bi-download me-2"></i> Exportar Todos
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Recent Simulations -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Simulações Recentes</h5>
            </div>
            <div class="card-body">
                <?php $simulations = $controller->getRecentSimulations(); ?>
                <?php if (empty($simulations)): ?>
                    <p class="text-muted text-center py-3">Nenhuma simulação realizada</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($simulations as $simulation): ?>
                            <a href="/simulation/<?= $simulation['execution_id'] ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($simulation['portfolio_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= date('d/m', strtotime($simulation['completed_at'])) ?>
                                    </small>
                                </div>
                                <p class="mb-1 small">
                                    Retorno: 
                                    <span class="<?= $simulation['total_return'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($simulation['total_return'], 2, ',', '.') ?>%
                                    </span>
                                </p>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 
                                    <?= date('H:i', strtotime($simulation['completed_at'])) ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Clone Portfolio Modal -->
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clonar Portfólio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cloneForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="clonePortfolioId" name="portfolio_id">
                    <div class="mb-3">
                        <label for="newName" class="form-label">Nome da Cópia</label>
                        <input type="text" class="form-control" id="newName" name="new_name" 
                               placeholder="Ex: Cópia do Meu Portfólio" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeHistory" name="include_history" checked>
                            <label class="form-check-label" for="includeHistory">
                                Incluir histórico de simulações
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Clonar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Portfolio Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Excluir Portfólio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o portfólio <strong id="deletePortfolioName"></strong>?</p>
                <p class="text-danger">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Esta ação não pode ser desfeita. Todas as simulações associadas também serão excluídas.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" id="deleteForm" style="display: inline;">
                    <input type="hidden" id="deletePortfolioId" name="portfolio_id">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    // Filter portfolios
    $('#filterAll').click(function() {
        $('.portfolio-row').show();
        $(this).addClass('active').removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#filterActive, #filterDefault').removeClass('btn-primary').addClass('btn-outline-secondary').removeClass('active');
    });
    
    $('#filterActive').click(function() {
        $('.portfolio-row').hide();
        $('.portfolio-row[data-type="personal"]').show();
        $(this).addClass('active').removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#filterAll, #filterDefault').removeClass('btn-primary').addClass('btn-outline-secondary').removeClass('active');
    });
    
    $('#filterDefault').click(function() {
        $('.portfolio-row').hide();
        $('.portfolio-row[data-type="default"]').show();
        $(this).addClass('active').removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#filterAll, #filterActive').removeClass('btn-primary').addClass('btn-outline-secondary').removeClass('active');
    });
    
    // Clone portfolio
    $('.clone-portfolio').click(function() {
        const portfolioId = $(this).data('id');
        const portfolioName = $(this).data('name');
        
        $('#clonePortfolioId').val(portfolioId);
        $('#newName').val('Cópia de ' + portfolioName);
        
        $('#cloneModal').modal('show');
    });
    
    // Delete portfolio
    $('.delete-portfolio').click(function() {
        const portfolioId = $(this).data('id');
        const portfolioName = $(this).data('name');
        
        $('#deletePortfolioId').val(portfolioId);
        $('#deletePortfolioName').text(portfolioName);
        
        $('#deleteModal').modal('show');
    });
    
    // Clone form submission
    $('#cloneForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const portfolioId = $('#clonePortfolioId').val();
        
        $.ajax({
            url: '/portfolio/' + portfolioId + '/clone',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#cloneModal .btn-primary').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Clonando...');
            },
            success: function(response) {
                if (response.success) {
                    $('#cloneModal').modal('hide');
                    showSuccess('Portfólio clonado com sucesso!');
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    showError(response.error);
                    $('#cloneModal .btn-primary').prop('disabled', false).html('Clonar');
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao clonar portfólio');
                $('#cloneModal .btn-primary').prop('disabled', false).html('Clonar');
            }
        });
    });
    
    // Delete form submission
    $('#deleteForm').submit(function(e) {
        e.preventDefault();
        
        const portfolioId = $('#deletePortfolioId').val();
        
        $.ajax({
            url: '/portfolio/' + portfolioId + '/delete',
            method: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                $('#deleteModal .btn-danger').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Excluindo...');
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    showSuccess('Portfólio excluído com sucesso!');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showError(response.error);
                    $('#deleteModal .btn-danger').prop('disabled', false).html('Excluir');
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao excluir portfólio');
                $('#deleteModal .btn-danger').prop('disabled', false).html('Excluir');
            }
        });
    });
    
    // Import portfolio
    $('#importPortfolio').click(function() {
        // Create file input
        const input = $('<input type="file" accept=".json,.csv" style="display: none;">');
        $('body').append(input);
        
        input.change(function() {
            const file = this.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            
            $.ajax({
                url: '/api/portfolios/import',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    showLoading('Importando portfólio...');
                },
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showSuccess('Portfólio importado com sucesso!');
                        setTimeout(function() {
                            window.location.href = '/portfolio/' + response.data.portfolio_id;
                        }, 1000);
                    } else {
                        showError(response.error);
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    showError(xhr.responseJSON?.error || 'Erro ao importar portfólio');
                }
            });
        });
        
        input.click();
    });
    
    // Export all portfolios
    $('#exportAll').click(function() {
        $.ajax({
            url: '/api/portfolios/export',
            method: 'GET',
            beforeSend: function() {
                showLoading('Exportando portfólios...');
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    // Create download link
                    const blob = new Blob([JSON.stringify(response.data, null, 2)], { 
                        type: 'application/json' 
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'portfolios_export_' + new Date().toISOString().split('T')[0] + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    showSuccess('Portfólios exportados com sucesso!');
                } else {
                    showError(response.error);
                }
            },
            error: function(xhr) {
                hideLoading();
                showError(xhr.responseJSON?.error || 'Erro ao exportar portfólios');
            }
        });
    });
    
    // Helper functions
    function showLoading(message) {
        // Implement loading overlay
        console.log('Loading:', message);
    }
    
    function hideLoading() {
        console.log('Hide loading');
    }
    
    function showSuccess(message) {
        alert('Sucesso: ' + message);
    }
    
    function showError(message) {
        alert('Erro: ' + message);
    }
});
</script>