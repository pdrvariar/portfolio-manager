<?php
/**
 * @var array $asset Dados do ativo (id, code, name, currency, asset_type)
 * @var array $historicalData Lista de registros históricos (reference_date, price)
 */
$title = $asset['name'] . ' - Detalhes';
ob_start();
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><?php echo htmlspecialchars($asset['name']); ?></h1>
            <p class="text-muted mb-0">
                Código: <strong><?php echo htmlspecialchars($asset['code']); ?></strong> | 
                Moeda: <span class="badge bg-info"><?php echo $asset['currency']; ?></span> | 
                Tipo: <span class="badge bg-secondary"><?php echo $asset['asset_type']; ?></span>
            </p>
        </div>
        <div>
            <a href="/index.php?url=<?= obfuscateUrl('assets') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dados Históricos</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($historicalData)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted">Nenhum dado histórico disponível</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Valor</th>
                                        <th>Variação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $prevPrice = null;
                                    foreach ($historicalData as $index => $row):
                                        $currentPrice = $row['price'];
                                        $variation = $prevPrice !== null ? 
                                            (($currentPrice - $prevPrice) / $prevPrice) * 100 : null;
                                    ?>
                                    <tr>
                                        <td><?php echo formatMonthYear($row['reference_date']); ?></td>
                                        <td>
                                            <?php if ($asset['currency'] === 'BRL'): ?>
                                                R$ <?php echo number_format($currentPrice, 2, ',', '.'); ?>
                                            <?php else: ?>
                                                $ <?php echo number_format($currentPrice, 2, ',', '.'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($variation !== null): ?>
                                                <span class="<?php echo $variation >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo ($variation >= 0 ? '+' : '') . number_format($variation, 2, ',', '.'); ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                        $prevPrice = $currentPrice;
                                    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <p class="text-muted">
                                <small>
                                    Período: <?php echo formatMonthYear($historicalData[0]['reference_date']); ?> até <?php echo formatMonthYear(end($historicalData)['reference_date']); ?> | 
                                    Total: <?php echo count($historicalData); ?> registros
                                </small>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Estatísticas</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($historicalData)): 
                        $prices = array_column($historicalData, 'price');
                        $firstPrice = $prices[0];
                        $lastPrice = end($prices);
                        $minPrice = min($prices);
                        $maxPrice = max($prices);
                        $totalReturn = (($lastPrice - $firstPrice) / $firstPrice) * 100;
                    ?>
                    <div class="mb-3">
                        <label class="form-label">Valor Inicial</label>
                        <div class="fs-5">
                            <?php if ($asset['currency'] === 'BRL'): ?>
                                R$ <?php echo number_format($firstPrice, 2, ',', '.'); ?>
                            <?php else: ?>
                                $ <?php echo number_format($firstPrice, 2, ',', '.'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Valor Final</label>
                        <div class="fs-5">
                            <?php if ($asset['currency'] === 'BRL'): ?>
                                R$ <?php echo number_format($lastPrice, 2, ',', '.'); ?>
                            <?php else: ?>
                                $ <?php echo number_format($lastPrice, 2, ',', '.'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Retorno Total</label>
                        <div class="fs-5 <?php echo $totalReturn >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo ($totalReturn >= 0 ? '+' : '') . number_format($totalReturn, 2, ',', '.'); ?>%
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mínimo</label>
                        <div>
                            <?php if ($asset['currency'] === 'BRL'): ?>
                                R$ <?php echo number_format($minPrice, 2, ',', '.'); ?>
                            <?php else: ?>
                                $ <?php echo number_format($minPrice, 2, ',', '.'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Máximo</label>
                        <div>
                            <?php if ($asset['currency'] === 'BRL'): ?>
                                R$ <?php echo number_format($maxPrice, 2, ',', '.'); ?>
                            <?php else: ?>
                                $ <?php echo number_format($maxPrice, 2, ',', '.'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma estatística disponível</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-gear me-2"></i>Gestão do Ativo</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (Auth::isAdmin()): ?>
                            <a href="/index.php?url=assets/import" class="btn btn-primary shadow-sm">
                                <i class="bi bi-upload me-2"></i>Importar Novos Dados (CSV)
                            </a>
                            
                            <hr class="my-2">

                            <a href="/index.php?url=assets/delete/<?php echo $asset['id']; ?>" 
                            class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('ATENÇÃO: Isso excluirá permanentemente todos os registros históricos deste ativo. Confirmar?')">
                                <i class="bi bi-trash me-2"></i>Excluir Ativo e Histórico
                            </a>
                        <?php else: ?>
                            <div class="text-center p-2">
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Os dados deste ativo são atualizados mensalmente pela administração.
                                </p>
                            </div>
                        <?php endif; ?>
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