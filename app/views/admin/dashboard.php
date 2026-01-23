<?php
/**
 * @var array $stats Estatísticas globais (users, portfolios, simulations, assets)
 */
$title = 'Dashboard Administrativo';
ob_start();
?>
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Dashboard Administrativo</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center metric-card">
            <div class="card-header">Usuários</div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $stats['users']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center metric-card">
            <div class="card-header">Portfólios</div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $stats['portfolios']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center metric-card">
            <div class="card-header">Simulações</div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $stats['simulations']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center metric-card">
            <div class="card-header">Ativos</div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $stats['assets']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Ações Rápidas</div>
            <div class="card-body">
                <div class="list-group">
                    <a href="/admin/users" class="list-group-item list-group-item-action">
                        <i class="bi bi-people me-2"></i> Gerenciar Usuários
                    </a>
                    <a href="/admin/assets" class="list-group-item list-group-item-action">
                        <i class="bi bi-graph-up me-2"></i> Gerenciar Ativos
                    </a>
                    <a href="/assets/import" class="list-group-item list-group-item-action">
                        <i class="bi bi-upload me-2"></i> Importar Dados Históricos
                    </a>
                    <a href="/admin/create-default-portfolios" class="list-group-item list-group-item-action">
                        <i class="bi bi-folder-plus me-2"></i> Criar Portfólios Padrão
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Últimas Atividades</div>
            <div class="card-body">
                <?php
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("
                    SELECT 'Simulação' as type, sr.created_at as date, p.name as name, u.username as user
                    FROM simulation_results sr
                    JOIN portfolios p ON sr.portfolio_id = p.id
                    JOIN users u ON p.user_id = u.id
                    ORDER BY sr.created_at DESC LIMIT 5
                    UNION
                    SELECT 'Portfólio' as type, created_at as date, name, u.username as user
                    FROM portfolios p
                    JOIN users u ON p.user_id = u.id
                    WHERE is_system_default = FALSE
                    ORDER BY created_at DESC LIMIT 5
                    ORDER BY date DESC LIMIT 10
                ");
                $activities = $stmt->fetchAll();
                ?>
                
                <?php if (empty($activities)): ?>
                    <p class="text-muted">Nenhuma atividade recente.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($activities as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="mb-1">
                                        <strong><?php echo $activity['type']; ?>:</strong> 
                                        <?php echo htmlspecialchars($activity['name']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($activity['date'])); ?>
                                    </small>
                                </div>
                                <small class="text-muted">por <?php echo htmlspecialchars($activity['user']); ?></small>
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