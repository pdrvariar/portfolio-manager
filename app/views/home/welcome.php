<?php
$title = 'Portfolio Backtest - Sistema de Simulação de Investimentos';
ob_start();
?>
<div class="jumbotron bg-light p-5 rounded-3">
    <div class="container">
        <h1 class="display-4">Portfolio Backtest</h1>
        <p class="lead">Sistema completo para simulação e análise de portfólios de investimentos com dados históricos.</p>
        <hr class="my-4">
        <p>Teste diferentes estratégias de alocação, visualize resultados com gráficos interativos e tome decisões informadas.</p>
        <div class="mt-4">
            <a href="/login" class="btn btn-primary btn-lg me-2">Fazer Login</a>
            <a href="/register" class="btn btn-outline-primary btn-lg">Criar Conta</a>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h3 class="card-title">📈 Backtest Completo</h3>
                <p class="card-text">Simule portfólios com dados históricos reais e veja como teriam performado.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h3 class="card-title">💰 Diversificação</h3>
                <p class="card-text">Teste diferentes alocações entre ações, renda fixa, cripto e outros ativos.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h3 class="card-title">📊 Análise Detalhada</h3>
                <p class="card-text">Métricas de risco, gráficos interativos e relatórios completos.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Funcionalidades Principais</h4>
            </div>
            <div class="card-body">
                <ul>
                    <li>Controle de usuários com login seguro</li>
                    <li>CRUD completo de portfólios pessoais</li>
                    <li>Clone de portfólios para estudos comparativos</li>
                    <li>Importação de dados históricos via CSV</li>
                    <li>Gráficos interativos de performance e composição</li>
                    <li>Cálculo automático de métricas de risco (Sharpe, drawdown, volatilidade)</li>
                    <li>Portfólios padrão do sistema</li>
                    <li>Interface responsiva e moderna</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>