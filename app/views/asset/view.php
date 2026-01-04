<?php
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
            <a href="/assets" class="btn btn-secondary">Voltar</a>
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
                                        <td><?php echo date('M/Y', strtotime($row['reference_date'])); ?></td>
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
                                    Período: <?php echo date('M/Y', strtotime($historicalData[0]['reference_date'])); ?> 
                                    até <?php echo date('M/Y', strtotime(end($historicalData)['reference_date'])); ?> | 
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
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Ações</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/index.php?url=assets/import" class="btn btn-outline-primary">
                            <i class="bi bi-upload"></i> Importar CSV
                        </a>

                        <?php if ($_SESSION['is_admin'] ?? false): ?>
                            <a href="/index.php?url=assets/delete/<?php echo $asset['id']; ?>" 
                            class="btn btn-outline-danger"
                            onclick="return confirm('Excluir este ativo permanentemente?')">
                                <i class="bi bi-trash"></i> Excluir Ativo
                            </a>
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