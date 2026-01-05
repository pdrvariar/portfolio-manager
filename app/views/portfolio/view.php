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

    <style>
        #auditTable thead th {
            border-bottom: 2px solid #dee2e6;
            box-shadow: inset 0 -1px 0 #212529; /* Reforça a linha do cabeçalho sticky */
        }
        .font-monospace {
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 0.9rem;
        }
    </style>    
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
                <a href="/index.php?url=portfolio/run/<?php echo $portfolio['id']; ?>" 
                class="btn btn-primary" id="btnRunSimulation">
                    <span class="spinner-border spinner-border-sm d-none" id="loader"></span>
                    <i class="bi bi-play-fill"></i> Executar Simulação
                </a>

                <script>
                document.getElementById('btnRunSimulation').addEventListener('click', function() {
                    this.classList.add('disabled');
                    document.getElementById('loader').classList.remove('d-none');
                });
                </script>
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
        <div class="card shadow-sm border-0 mt-5">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary fw-bold">
                    <i class="bi bi-journal-check me-2"></i>Histórico Mensal Detalhado (Auditoria)
                </h5>
                <div class="d-flex gap-2">
                    <input type="text" id="auditSearch" class="form-control form-control-sm" placeholder="Buscar data (Ex: 2024)..." style="width: 200px;">
                    <button onclick="exportAuditToCSV()" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Exportar CSV
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="auditTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">Mês/Ano</th>
                                <th>Valor Total</th>
                                <th>Variação Mensal</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $prevValue = $portfolio['initial_capital'];
                            foreach ($chartData['audit_log'] as $date => $data): 
                                $currentValue = $data['total_value'];
                                $monthlyReturn = (($currentValue / $prevValue) - 1) * 100;
                                $isRebalanced = $data['rebalanced'] ?? false;
                            ?>
                            <tr class="<?php echo $isRebalanced ? 'table-info-light' : ''; ?>">
                                <td class="ps-4">
                                    <span class="fw-bold"><?php echo date('M / Y', strtotime($date)); ?></span>
                                </td>
                                <td>
                                    <?php echo formatCurrency($currentValue, $portfolio['output_currency']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $monthlyReturn >= 0 ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger'; ?>">
                                        <?php echo ($monthlyReturn >= 0 ? '+' : '') . number_format($monthlyReturn, 2, ',', '.'); ?>%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($isRebalanced): ?>
                                        <span class="badge rounded-pill bg-info text-dark" title="Alocação resetada para o peso alvo">
                                            <i class="bi bi-arrow-repeat me-1"></i>Rebalanceado
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Mantido</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-link text-decoration-none py-0" 
                                            onclick='showMonthDetails(<?php echo json_encode($data["asset_values"]); ?>, "<?php echo $date; ?>")'>
                                        Ver Ativos <i class="bi bi-chevron-right small"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php 
                                $prevValue = $currentValue; 
                            endforeach; ?>
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



        function exportAuditToCSV() {
            let csv = [];
            const rows = document.querySelectorAll("#auditTable tr");
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (let j = 0; j < cols.length; j++) {
                    // Limpa formatação de moeda e percentual para o Excel entender como número
                    let data = cols[j].innerText
                        .replace("R$ ", "")
                        .replace(/\./g, "")
                        .replace(",", ".")
                        .replace("%", "");
                    row.push('"' + data + '"');
                }
                csv.push(row.join(";")); // Usando ponto e vírgula para compatibilidade com Excel PT-BR
            }

            const csvFile = new Blob(["\ufeff" + csv.join("\n")], { type: "text/csv;charset=utf-8;" });
            const downloadLink = document.createElement("a");
            const fileName = "audit_backtest_<?php echo $portfolio['id']; ?>.csv";

            downloadLink.download = fileName;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }        
    </script>
</body>
</html>