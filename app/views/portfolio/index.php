<?php
/**
 * @var array $portfolios Lista de portfÃ³lios do usuÃ¡rio
 */

$title = 'Meus PortfÃ³lios';`n$meta_robots = 'noindex, nofollow';
ob_start();
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-0">Meus PortfÃ³lios</h2>
        <p class="text-muted small mb-0">Gerencie e analise suas estratÃ©gias de investimento.</p>
    </div>
    <a href="/index.php?url=<?= obfuscateUrl('portfolio/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-4 align-self-start align-self-sm-center">
        <i class="bi bi-plus-lg me-1"></i> Novo PortfÃ³lio
    </a>
</div>

<!-- â”€â”€ MOBILE: cards (visÃ­vel sÃ³ em telas < md) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="d-md-none">
    <?php if (empty($portfolios)): ?>
        <p class="text-muted text-center py-4">Nenhum portfÃ³lio encontrado.</p>
    <?php endif; ?>
    <?php foreach ($portfolios as $portfolio): ?>
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <!-- Nome + badge -->
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-bold" style="font-size:.95rem;line-height:1.2;"><?= htmlspecialchars($portfolio['name']) ?></div>
                    <div class="text-muted" style="font-size:.72rem;">
                        <i class="bi bi-arrow-repeat me-1"></i><?= ucfirst($portfolio['rebalance_frequency']) ?>
                    </div>
                </div>
                <?php if ($portfolio['is_system_default']): ?>
                    <span class="badge rounded-pill bg-soft-info text-info px-2" style="font-size:.68rem;">Sistema</span>
                <?php else: ?>
                    <span class="badge rounded-pill bg-soft-success text-success px-2" style="font-size:.68rem;">Pessoal</span>
                <?php endif; ?>
            </div>

            <!-- Capital + PerÃ­odo -->
            <div class="d-flex gap-3 mb-3 flex-wrap">
                <div>
                    <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;font-weight:700;letter-spacing:.05em;">Capital</div>
                    <div class="fw-bold text-primary" style="font-size:.9rem;"><?= formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']) ?></div>
                    <div class="text-muted" style="font-size:.65rem;"><?= $portfolio['output_currency'] ?></div>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;font-weight:700;letter-spacing:.05em;">PerÃ­odo</div>
                    <div class="fw-medium small">
                        <i class="bi bi-calendar3 text-muted me-1"></i>
                        <?= date('d/m/y', strtotime($portfolio['start_date'])) ?>
                        <span class="text-muted mx-1">â†’</span>
                        <?= $portfolio['end_date'] ? date('d/m/y', strtotime($portfolio['end_date'])) : 'Hoje' ?>
                    </div>
                </div>
            </div>

            <!-- BotÃµes de aÃ§Ã£o -->
            <div class="d-flex flex-wrap gap-2">
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>"        class="btn btn-sm btn-outline-primary rounded-pill px-3"   title="Resultados"><i class="bi bi-graph-up me-1"></i>Resultados</a>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>"         class="btn btn-sm btn-outline-success rounded-pill px-3"   title="Simular"><i class="bi bi-play-fill me-1"></i>Simular</a>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/history/' . $portfolio['id']) ?>"     class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="HistÃ³rico"><i class="bi bi-clock-history me-1"></i>HistÃ³rico</a>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/simulation-details/' . $portfolio['id']) ?>" class="btn btn-sm btn-outline-info rounded-pill px-3" title="Detalhes"><i class="bi bi-list-check me-1"></i>Detalhes</a>
                <?php if (Auth::isPro()): ?>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/run-advanced/' . $portfolio['id']) ?>"  class="btn btn-sm btn-outline-warning rounded-pill px-3" title="SimulaÃ§Ã£o AvanÃ§ada (Monte Carlo)"><i class="bi bi-stars me-1"></i>Sim. AvanÃ§ada</a>
                <?php else: ?>
                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3"
                    onclick="showPaywallModal('SimulaÃ§Ã£o AvanÃ§ada (Monte Carlo)', 'Gere automaticamente atÃ© 20 cenÃ¡rios otimizados por volatilidade e encontre a alocaÃ§Ã£o com o melhor Sharpe Ratio. Exclusivo para assinantes PRO.')"
                    title="SimulaÃ§Ã£o AvanÃ§ada â€” Recurso PRO">
                    <i class="bi bi-stars me-1"></i>Sim. AvanÃ§ada <i class="bi bi-lock-fill ms-1" style="font-size:.7rem;"></i>
                </button>
                <?php endif; ?>
                <a href="/index.php?url=<?= obfuscateUrl('portfolio/clone/' . $portfolio['id']) ?>"       class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Clonar"><i class="bi bi-files me-1"></i>Clonar</a>
                <?php if (!$portfolio['is_system_default'] || Auth::isAdmin()): ?>
                    <a href="/index.php?url=<?= obfuscateUrl('portfolio/edit/' . $portfolio['id']) ?>"   class="btn btn-sm btn-outline-warning rounded-pill px-3"   title="Editar"><i class="bi bi-pencil me-1"></i>Editar</a>
                <?php endif; ?>
                <form action="/index.php?url=<?= obfuscateUrl('portfolio/delete/' . $portfolio['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir portfÃ³lio?')">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 no-spinner" title="Excluir">
                        <i class="bi bi-trash me-1"></i>Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- â”€â”€ DESKTOP: tabela (visÃ­vel sÃ³ em md+) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="card shadow-sm border-0 rounded-3 d-none d-md-block">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="portfoliosTable" class="table table-hover align-middle mb-0" style="width:100%;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-3" style="width:25%">EstratÃ©gia</th>
                        <th style="width:20%">Capital</th>
                        <th style="width:25%">PerÃ­odo HistÃ³rico</th>
                        <th class="text-center" style="width:10%">Status</th>
                        <th class="text-end pe-3" style="width:20%">AÃ§Ãµes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($portfolios as $portfolio): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-dark" style="font-size:1rem;line-height:1.2;max-width:220px;word-wrap:break-word;">
                                <?= htmlspecialchars($portfolio['name']) ?>
                            </div>
                            <div class="text-muted" style="font-size:.7rem;">
                                <i class="bi bi-arrow-repeat me-1"></i><?= ucfirst($portfolio['rebalance_frequency']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><?= formatCurrency($portfolio['initial_capital'], $portfolio['output_currency']) ?></div>
                            <span class="text-muted" style="font-size:.65rem;font-weight:600;"><?= $portfolio['output_currency'] ?></span>
                        </td>
                        <td class="text-nowrap">
                            <div class="fw-medium text-dark small">
                                <i class="bi bi-calendar3 text-muted me-1"></i>
                                <?= date('d/m/y', strtotime($portfolio['start_date'])) ?>
                                <span class="mx-1 text-muted">â†’</span>
                                <?= $portfolio['end_date'] ? date('d/m/y', strtotime($portfolio['end_date'])) : 'Hoje' ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($portfolio['is_system_default']): ?>
                                <span class="badge rounded-pill bg-soft-info text-info px-2 py-1" style="font-size:.7rem;">Sistema</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-soft-success text-success px-2 py-1" style="font-size:.7rem;">Pessoal</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>"        class="btn btn-sm btn-white border px-2" title="Resultados"><i class="bi bi-graph-up text-primary"></i></a>
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/history/' . $portfolio['id']) ?>"     class="btn btn-sm btn-white border px-2" title="HistÃ³rico de SimulaÃ§Ãµes"><i class="bi bi-clock-history" style="color:#6f42c1;"></i></a>
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/simulation-details/' . $portfolio['id']) ?>" class="btn btn-sm btn-white border px-2" title="Detalhes da SimulaÃ§Ã£o"><i class="bi bi-list-check text-info"></i></a>
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/run/' . $portfolio['id']) ?>"         class="btn btn-sm btn-white border px-2" title="Simular"><i class="bi bi-play-fill text-success"></i></a>
                                <?php if (Auth::isPro()): ?>
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/run-advanced/' . $portfolio['id']) ?>" class="btn btn-sm btn-white border px-2" title="SimulaÃ§Ã£o AvanÃ§ada: gera atÃ© 20 cenÃ¡rios automÃ¡ticos com alocaÃ§Ãµes otimizadas por volatilidade"><i class="bi bi-stars" style="color:#fd7e14;"></i></a>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-white border px-2 position-relative"
                                    onclick="showPaywallModal('SimulaÃ§Ã£o AvanÃ§ada (Monte Carlo)', 'Gere automaticamente atÃ© 20 cenÃ¡rios otimizados por volatilidade e encontre a alocaÃ§Ã£o com o melhor Sharpe Ratio. Exclusivo para assinantes PRO.')"
                                    title="SimulaÃ§Ã£o AvanÃ§ada â€” Recurso PRO">
                                    <i class="bi bi-stars" style="color:#fd7e14;opacity:.5;"></i>
                                    <i class="bi bi-lock-fill position-absolute text-warning" style="font-size:.55rem;bottom:4px;right:4px;"></i>
                                </button>
                                <?php endif; ?>
                                <a href="/index.php?url=<?= obfuscateUrl('portfolio/clone/' . $portfolio['id']) ?>"       class="btn btn-sm btn-white border px-2" title="Clonar"><i class="bi bi-files text-secondary"></i></a>
                                <?php if (!$portfolio['is_system_default'] || Auth::isAdmin()): ?>
                                    <a href="/index.php?url=<?= obfuscateUrl('portfolio/edit/' . $portfolio['id']) ?>"   class="btn btn-sm btn-white border px-2" title="Editar"><i class="bi bi-pencil text-warning"></i></a>
                                <?php endif; ?>
                                <form action="/index.php?url=<?= obfuscateUrl('portfolio/delete/' . $portfolio['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir portfÃ³lio?')">
                                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                                    <button type="submit" class="btn btn-sm btn-white border px-2 no-spinner" title="Excluir">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </form>
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
        if ($(window).width() >= 768) {
            $("#portfoliosTable").DataTable({
                language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json" },
                order: [[0, "asc"]],
                pageLength: 10,
                autoWidth: false,
                columnDefs: [{ orderable: false, targets: 4 }],
                dom: "<\'row mb-2\'<\'col-sm-6\'l><\'col-sm-6 text-end\'f>>" +
                     "<\'row\'<\'col-sm-12\'tr>>" +
                     "<\'row mt-3\'<\'col-sm-5\'i><\'col-sm-7\'p>>"
            });
        }
    });
</script>';

$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
