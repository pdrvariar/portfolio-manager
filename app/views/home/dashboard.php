<?php
$title = 'Dashboard';
ob_start();
?>
<div class="row">
    <div class="col-md-8">
        <h1 class="mb-4">Dashboard</h1>
        <p class="lead">Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="/portfolio/create" class="btn btn-primary">Novo Portfólio</a>
    </div>
</div>

<?php if ($stats && $stats['total_simulations'] > 0): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-header">Simulações</div>
            <div class="card-body">
                <h3 class="card-title"><?php echo $stats['total_simulations']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-header">Retorno Médio</div>
            <div class="card-body">
                <h3 class="card-title <?php echo $stats['avg_return'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo number_format($stats['avg_return'], 2); ?>%
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-header">Volatilidade Média</div>
            <div class="card-body">
                <h3 class="card-title"><?php echo number_format($stats['avg_volatility'], 2); ?>%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-header">Melhor Retorno</div>
            <div class="card-body">
                <h3 class="card-title text-success"><?php echo number_format($stats['max_return'], 2); ?>%</h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Meus Portfólios</h5>
                <a href="/portfolio" class="btn btn-sm btn-outline-primary">Ver Todos</a>
            </div>
            <div class="card-body">
                <?php if (empty($portfolios)): ?>
                    <p class="text-muted">Você ainda não criou nenhum portfólio.</p>
                    <a href="/portfolio/create" class="btn btn-primary">Criar Primeiro Portfólio</a>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach (array_slice($portfolios, 0, 5) as $portfolio): ?>
                            <a href="/portfolio/view/<?php echo $portfolio['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($portfolio['name']); ?></h6>
                                    <small><?php echo formatDate($portfolio['created_at']); ?></small>
                                </div>
                                <small class="text-muted">
                                    Capital: <?php echo formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']); ?> • 
                                    Rebalanceamento: <?php echo $portfolio['rebalance_frequency']; ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Últimas Simulações</h5>
            </div>
            <div class="card-body">
                <?php if (empty($latestSimulations)): ?>
                    <p class="text-muted">Nenhuma simulação executada ainda.</p>
                    <?php if (!empty($portfolios)): ?>
                        <a href="/portfolio/run/<?php echo $portfolios[0]['id']; ?>" class="btn btn-primary">Executar Primeira Simulação</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($latestSimulations as $item): 
                            $portfolio = $item['portfolio'];
                            $simulation = $item['simulation'];
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($portfolio['name']); ?></h6>
                                    <small><?php echo formatDate($simulation['simulation_date']); ?></small>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small>Valor Final:</small>
                                        <div><strong><?php echo formatCurrency($simulation['total_value'], $portfolio['output_currency']); ?></strong></div>
                                    </div>
                                    <div class="col-6">
                                        <small>Retorno Anual:</small>
                                        <div class="<?php echo $simulation['annual_return'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo number_format($simulation['annual_return'], 2); ?>%</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ações Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="/portfolio/create" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-plus-lg"></i> Novo Portfólio
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/assets" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="bi bi-graph-up"></i> Explorar Ativos
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/portfolio" class="btn btn-outline-success w-100 mb-2">
                            <i class="bi bi-folder"></i> Meus Portfólios
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/profile" class="btn btn-outline-info w-100 mb-2">
                            <i class="bi bi-person"></i> Meu Perfil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>