<?php
$title = 'Dashboard';
ob_start();
?>

<style>
    /* Estilos de Interface Sênior */
    .dashboard-header { margin-bottom: 2rem; }
    .metric-card { border: none; transition: transform 0.2s; }
    .metric-card:hover { transform: translateY(-3px); }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.08); color: #0d6efd; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.08); color: #198754; }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #856404; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.08); color: #087990; }
    
    .list-group-item-action { border-left: 3px solid transparent; transition: all 0.2s; }
    .list-group-item-action:hover { border-left: 3px solid #0d6efd; background-color: #f8f9fa; }
    
    .btn-action-quick { transition: all 0.3s; border: 1px dashed #dee2e6; }
    .btn-action-quick:hover { background-color: #fff; border-style: solid; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
</style>

<div class="dashboard-header d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-0">Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
        <p class="text-muted mb-0">Aqui está o resumo do seu ecossistema de investimentos.</p>
    </div>
    <div>
        <a href="/index.php?url=portfolio/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg"></i> Criar Portfólio
        </a>
    </div>
</div>

<?php if ($stats && $stats['total_simulations'] > 0): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Simulações</span>
                    <i class="bi bi-cpu bg-primary-soft p-2 rounded-3"></i>
                </div>
                <h3 class="fw-bold mb-0"><?php echo $stats['total_simulations'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Retorno Médio</span>
                    <i class="bi bi-graph-up-arrow bg-success-soft p-2 rounded-3"></i>
                </div>
                <h3 class="fw-bold mb-0 <?php echo ($stats['avg_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo number_format($stats['avg_return'] ?? 0, 2, ',', '.'); ?>%
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Volatilidade</span>
                    <i class="bi bi-activity bg-warning-soft p-2 rounded-3"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($stats['avg_volatility'] ?? 0, 2, ',', '.'); ?>%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Top Performace</span>
                    <i class="bi bi-trophy bg-info-soft p-2 rounded-3"></i>
                </div>
                <h3 class="fw-bold mb-0 text-info"><?php echo number_format($stats['max_return'] ?? 0, 2, ',', '.'); ?>%</h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase me-2 text-primary"></i>Portfólios Recentes</h5>
                <a href="/index.php?url=portfolio" class="btn btn-sm btn-link text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($portfolios)): ?>
                    <div class="text-center py-5">
                        <img src="https://cdn-icons-png.flaticon.com/512/4076/4076402.png" width="80" class="mb-3 opacity-25" alt="Empty">
                        <p class="text-muted">Você ainda não possui portfólios configurados.</p>
                        <a href="/index.php?url=portfolio/create" class="btn btn-sm btn-outline-primary">Começar Agora</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($portfolios, 0, 5) as $portfolio): ?>
                            <a href="/index.php?url=portfolio/view/<?php echo $portfolio['id']; ?>" class="list-group-item list-group-item-action py-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($portfolio['name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo $portfolio['rebalance_frequency']; ?> • 
                                            Criado em <?php echo date('d/m/Y', strtotime($portfolio['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-auto text-end">
                                        <div class="fw-bold text-dark">
                                            <?php echo formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']); ?>
                                        </div>
                                        <span class="badge bg-light text-muted border">Ativo</span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Ações Rápidas</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="index.php?url=assets" class="btn btn-action-quick w-100 py-3 text-center rounded-3">
                            <i class="bi bi-search d-block mb-1 fs-4 text-primary"></i>
                            <span class="small text-dark">Ativos</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="index.php?url=profile" class="btn btn-action-quick w-100 py-3 text-center rounded-3">
                            <i class="bi bi-person-gear d-block mb-1 fs-4 text-secondary"></i>
                            <span class="small text-dark">Perfil</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Últimos Resultados</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($latestSimulations)): ?>
                    <div class="p-4 text-center">
                        <p class="text-muted small mb-0">Nenhuma simulação recente.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($latestSimulations, 0, 3) as $item): ?>
                            <div class="list-group-item py-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-bold text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($item['portfolio']['name']); ?></span>
                                    <span class="small text-muted"><?php echo date('d/m', strtotime($item['simulation']['simulation_date'])); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted smaller">Retorno Anual:</span>
                                    <span class="<?php echo $item['simulation']['annual_return'] >= 0 ? 'text-success' : 'text-danger'; ?> fw-bold">
                                        <?php echo ($item['simulation']['annual_return'] >= 0 ? '+' : '') . number_format($item['simulation']['annual_return'], 2, ',', '.'); ?>%
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>