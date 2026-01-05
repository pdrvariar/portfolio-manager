<?php
$title = 'Importar Dados - Portfolio Backtest';
ob_start();
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Importar Dados Históricos</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> Instruções:</h5>
                        <ul class="mb-0">
                            <li>Use arquivos CSV no formato: <code>YYYY-MM,VALOR</code></li>
                            <li>O nome do arquivo será usado como código do ativo (ex: <code>BTC-USD.csv</code>)</li>
                            <li>Primeira linha deve ser o cabeçalho (será ignorada pelo sistema)</li>
                            <li>Valores devem estar no formato americano (ponto decimal, ex: 100.50)</li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="index.php?url=assets/import" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">Arquivo CSV</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" 
                                   accept=".csv" required>
                            <div class="form-text">
                                Formatos suportados: CSV com dados mensais.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Exemplo de formato esperado:</label>
                            <pre class="bg-light p-3 rounded">Data,Preco
2020-01,100.50
2020-02,105.30
2020-03,98.70</pre>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/index.php?url=assets" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Importar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Ativos Disponíveis para Importação</h5>
                </div>
                <div class="card-body">
                    <p>Use estes nomes de arquivo para importação automática (o sistema criará o ativo se ele não existir):</p>
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li><code>BTC-USD.csv</code> - Bitcoin</li>
                                <li><code>ETH-USD.csv</code> - Ethereum</li>
                                <li><code>BVSP-IBOVESPA.csv</code> - Ibovespa</li>
                                <li><code>GSPC-SP500.csv</code> - S&P 500</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li><code>SELIC.csv</code> - Taxa Selic</li>
                                <li><code>IRX-RF-USA.csv</code> - Tesouro EUA</li>
                                <li><code>USD-BRL.csv</code> - Dólar</li>
                                <li><code>IFIX.csv</code> - Fundos Imobiliários</li>
                            </ul>
                        </div>
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