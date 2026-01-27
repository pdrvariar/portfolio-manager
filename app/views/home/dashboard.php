<?php
/**
 * @var array $portfolios Lista de portfólios do usuário
 * @var array $systemPortfolios Lista de portfólios do sistema
 * @var array $stats Estatísticas de simulação do usuário
 * @var array $latestSimulations Lista das últimas simulações realizadas
 */
$title = 'Dashboard';
ob_start();
?>

<div class="dashboard-header mb-5"> <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold text-dark mb-1">Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
            <p class="text-muted mb-0">Bem-vindo de volta ao seu centro de inteligência financeira.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="/index.php?url=<?= obfuscateUrl('portfolio/create') ?>" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i>Novo Portfólio
            </a>
        </div>
    </div>
</div>

<?php if ($stats && $stats['total_simulations'] > 0): ?>
<div class="row g-4 mb-5"> <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-primary rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-cpu fs-4"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Simulações</h6>
                <h3 class="fw-bold mb-0"><?php echo $stats['total_simulations'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-success rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Retorno Médio</h6>
                <h3 class="fw-bold mb-0 <?php echo ($stats['avg_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo number_format($stats['avg_return'] ?? 0, 2, ',', '.'); ?>%
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-warning rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-activity fs-4"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Volatilidade</h6>
                <h3 class="fw-bold mb-0"><?php echo number_format($stats['avg_volatility'] ?? 0, 2, ',', '.'); ?>%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-info rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-trophy fs-4"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Top Performance</h6>
                <h3 class="fw-bold mb-0 text-info"><?php echo number_format($stats['max_return'] ?? 0, 2, ',', '.'); ?>%</h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="mb-5">
    <div class="d-flex align-items-center mb-4">
        <h5 class="fw-bold mb-0"><i class="bi bi-stars text-warning me-2"></i>Estratégias Sugeridas</h5>
        <hr class="flex-grow-1 ms-3 opacity-10">
    </div>
    
    <div class="row g-4">
        <?php foreach ($systemPortfolios as $sp): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-shadow transition">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-soft-primary p-2 rounded-3 me-3">
                            <i class="bi bi-briefcase text-primary fs-5"></i>
                        </div>
                        <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-1 smaller fw-bold">OFICIAL</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-2"><?= htmlspecialchars($sp['name']) ?></h6>
                    <p class="text-muted small mb-4" style="height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        <?= htmlspecialchars($sp['description']) ?>
                    </p>
                    <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $sp['id']) ?>" 
                       class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm transition">
                        Explorar Estratégia
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-5"> <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Portfólios Pessoais</h5>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio') ?>" class="btn btn-sm btn-link text-decoration-none fw-bold">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($portfolios)): ?>
                    <div class="text-center py-5">
                        <div class="bg-light d-inline-flex p-4 rounded-circle mb-3">
                            <i class="bi bi-folder-plus fs-1 text-muted opacity-50"></i>
                        </div>
                        <p class="text-muted fw-medium">Você ainda não possui portfólios configurados.</p>
                        <a href="/index.php?url=<?= obfuscateUrl('portfolio/create') ?>" class="btn btn-primary rounded-pill px-4 mt-2">Começar Agora</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($portfolios, 0, 5) as $portfolio): ?>
                            <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>" class="list-group-item list-group-item-action py-4 px-4 transition">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($portfolio['name']); ?></h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-muted border px-2"><?php echo ucfirst($portfolio['rebalance_frequency']); ?></span>
                                            <span class="smaller text-muted">Criado em <?php echo date('d/m/Y', strtotime($portfolio['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-auto text-end">
                                        <div class="fw-bold text-primary fs-5">
                                            <?php echo formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']); ?>
                                        </div>
                                        <span class="smaller text-muted fw-bold text-uppercase"><?php echo $portfolio['output_currency']; ?></span>
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
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold">Acesso Rápido</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="/index.php?url=<?= obfuscateUrl('assets') ?>" class="btn btn-action-quick w-100 py-3 text-center rounded-4 h-100">
                            <i class="bi bi-search d-block mb-1 fs-3 text-primary"></i>
                            <span class="small fw-bold text-dark">Ativos</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/index.php?url=<?= obfuscateUrl('profile') ?>" class="btn btn-action-quick w-100 py-3 text-center rounded-4 h-100">
                            <i class="bi bi-person-gear d-block mb-1 fs-3 text-secondary"></i>
                            <span class="small fw-bold text-dark">Meu Perfil</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold">Atividade Recente</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($latestSimulations)): ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-clock-history fs-2 text-muted opacity-25 d-block mb-2"></i>
                        <p class="text-muted smaller mb-0">Nenhuma simulação recente.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($latestSimulations, 0, 4) as $item): ?>
                            <div class="list-group-item py-3 px-4 border-0 border-bottom">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="smaller fw-bold text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($item['portfolio']['name']); ?></span>
                                    <span class="smaller text-muted"><?php echo date('d/m', strtotime($item['simulation']['simulation_date'])); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted smaller">Retorno Anualizado</span>
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