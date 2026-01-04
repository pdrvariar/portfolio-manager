<?php
// Esta view mostrará os resultados da simulação com gráficos
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($portfolio['name']); ?> - Detalhes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include_once __DIR__ . '/../layouts/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h1><?php echo htmlspecialchars($portfolio['name']); ?></h1>
                <p class="text-muted"><?php echo htmlspecialchars($portfolio['description']); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/index.php?url=portfolio/run/<?php echo $portfolio['id']; ?>" class="btn btn-success">
                    Executar Simulação
                </a>
                <a href="/index.php?url=portfolio" class="btn btn-secondary">Voltar</a>
            </div>
        </div>
        
        <!-- Métricas principais -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-header">Retorno Total</div>
                    <div class="card-body">
                        <h3 class="card-title <?php echo ($metrics['total_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($metrics['total_return'] ?? 0, 2); ?>%
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-header">Retorno Anual</div>
                    <div class="card-body">
                        <h3 class="card-title <?php echo ($metrics['annual_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($metrics['annual_return'] ?? 0, 2); ?>%
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-header">Volatilidade</div>
                    <div class="card-body">
                        <h3 class="card-title">
                            <?php echo number_format($metrics['volatility'] ?? 0, 2); ?>%
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-header">Sharpe Ratio</div>
                    <div class="card-body">
                        <h3 class="card-title">
                            <?php echo number_format($metrics['sharpe_ratio'] ?? 0, 2); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Evolução do Valor do Portfólio</div>
                    <div class="card-body" style="height: 400px;"> <canvas id="valueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Composição por Ano</div>
                    <div class="card-body">
                        <canvas id="compositionChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Retornos Anuais</div>
                    <div class="card-body">
                        <canvas id="returnsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabela de ativos -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Alocação de Ativos</div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ativo</th>
                                    <th>Alocação</th>
                                    <th>Moeda</th>
                                    <th>Fator de Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                    <td><?php echo number_format($asset['allocation_percentage'], 5); ?>%</td>
                                    <td><?php echo $asset['currency']; ?></td>
                                    <td><?php echo number_format($asset['performance_factor'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de valor
        new Chart(document.getElementById('valueChart'), {
            type: 'line',
            data: <?php echo json_encode($chartData['value_chart']); ?>,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    // Formata como moeda (ex: R$ 100.000,00)
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfico de composição
        new Chart(document.getElementById('compositionChart'), {
            type: 'bar',
            data: <?php echo json_encode($chartData['composition_chart']); ?>,
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfico de retornos
        new Chart(document.getElementById('returnsChart'), {
            type: 'bar',
            data: <?php echo json_encode($chartData['returns_chart']); ?>,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>