<?php
/**
 * @var array $stats Estatísticas globais (users, portfolios, simulations, assets)
 * @var array $activities Últimas atividades do sistema
 */
$title = 'Dashboard Administrativo';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Dashboard Administrativo</h2>
        <p class="text-muted small mb-0">Visão geral do sistema e controle de atividades.</p>
    </div>
    <div class="text-end">
        <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-2 fw-bold">MODO ADMIN</span>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-primary rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-people fs-4 text-primary"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Usuários</h6>
                <h3 class="fw-bold mb-0"><?php echo $stats['users']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-success rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-briefcase fs-4 text-success"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Portfólios</h6>
                <h3 class="fw-bold mb-0"><?php echo $stats['portfolios']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-warning rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-cpu fs-4 text-warning"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Simulações</h6>
                <h3 class="fw-bold mb-0"><?php echo $stats['simulations']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card shadow-sm rounded-4 border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-soft-info rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-layers fs-4 text-info"></i>
                </div>
                <h6 class="text-muted smaller text-uppercase fw-bold mb-1">Ativos</h6>
                <h3 class="fw-bold mb-0"><?php echo $stats['assets']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i>Ações Rápidas</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="/index.php?url=<?= obfuscateUrl('admin/users') ?>" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-2 rounded-3 me-3">
                                <i class="bi bi-people text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold small">Gerenciar Usuários</h6>
                                <p class="text-muted smaller mb-0">Ver e editar contas de investidores</p>
                            </div>
                        </div>
                    </a>
                    <a href="/index.php?url=<?= obfuscateUrl('admin/assets') ?>" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-2 rounded-3 me-3">
                                <i class="bi bi-graph-up text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold small">Gerenciar Ativos</h6>
                                <p class="text-muted smaller mb-0">Cadastrar e atualizar biblioteca de ativos</p>
                            </div>
                        </div>
                    </a>
                    <a href="/index.php?url=<?= obfuscateUrl('assets/import') ?>" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-2 rounded-3 me-3">
                                <i class="bi bi-upload text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold small">Importar Dados</h6>
                                <p class="text-muted smaller mb-0">Enviar arquivos CSV com históricos mensais</p>
                            </div>
                        </div>
                    </a>
                    <a href="/index.php?url=<?= obfuscateUrl('admin/create-default-portfolios') ?>" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-2 rounded-3 me-3">
                                <i class="bi bi-patch-check text-info"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold small">Gerar Portfólios Oficiais</h6>
                                <p class="text-muted smaller mb-0">Criar modelos sugeridos pelo sistema</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-activity me-2 text-primary"></i>Últimas Atividades</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activities)): ?>
                    <div class="p-4 text-center">
                        <p class="text-muted mb-0 small">Nenhuma atividade recente.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($activities as $activity): ?>
                            <div class="list-group-item py-3 px-4 border-0">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 small fw-bold">
                                        <span class="badge rounded-pill bg-soft-<?= $activity['type'] == 'Simulação' ? 'primary' : 'success' ?> text-<?= $activity['type'] == 'Simulação' ? 'primary' : 'success' ?> me-2 px-2 py-1" style="font-size: 0.65rem;">
                                            <?= strtoupper($activity['type']) ?>
                                        </span>
                                        <?= htmlspecialchars($activity['name']); ?>
                                    </h6>
                                    <small class="text-muted smaller">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($activity['date'])); ?>
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted smaller">por <span class="fw-bold"><?= htmlspecialchars($activity['user']); ?></span></small>
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