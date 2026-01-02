<?php
require_once __DIR__ . '/../src/app/config/database.php';
require_once __DIR__ . '/../src/app/services/SimulationService.php';
require_once __DIR__ . '/../src/app/services/ChartService.php';
require_once __DIR__ . '/../src/app/models/Simulation.php';

if (php_sapi_name() !== 'cli') {
    die('Este script só pode ser executado via CLI');
}

if ($argc < 2) {
    die("Uso: php run_simulation.php <simulation_id>\n");
}

$simulationId = $argv[1];

try {
    $db = Database::getInstance();
    
    // Buscar simulação
    $stmt = $db->prepare("
        SELECT s.*, p.*, u.email, u.name as user_name
        FROM simulations s
        JOIN portfolios p ON s.portfolio_id = p.id
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$simulationId]);
    $simulationData = $stmt->fetch();
    
    if (!$simulationData) {
        die("Simulação não encontrada\n");
    }
    
    // Atualizar status para running
    $simulationModel = new Simulation();
    $simulationModel->updateStatus($simulationData['execution_id'], 'RUNNING');
    
    // Executar simulação
    $simulationService = new SimulationService();
    $portfolioModel = new Portfolio();
    
    $portfolio = $portfolioModel->findWithAssets($simulationData['portfolio_id']);
    
    $results = $simulationService->calculatePortfolioReturns(
        $portfolio,
        $portfolio['start_date'],
        $portfolio['end_date']
    );
    
    // Calcular métricas
    $metrics = $simulationService->calculateMetrics($results, $portfolio['initial_capital']);
    
    // Gerar gráficos
    $chartService = new ChartService();
    $charts = $chartService->generatePortfolioCharts($results, $metrics, $portfolio);
    
    // Atualizar simulação com resultados
    $simulationModel->updateStatus(
        $simulationData['execution_id'],
        'COMPLETED',
        $results,
        $metrics,
        $charts
    );
    
    // Enviar email de notificação
    $emailService = new EmailService();
    $resultsLink = BASE_URL . "/simulation/" . $simulationData['execution_id'];
    $emailService->sendSimulationCompleteEmail(
        $simulationData['email'],
        $simulationData['user_name'],
        $portfolio['name'],
        $resultsLink
    );
    
    echo "Simulação {$simulationData['execution_id']} concluída com sucesso!\n";
    
} catch (Exception $e) {
    // Atualizar status para erro
    if (isset($simulationData)) {
        $simulationModel->updateStatus($simulationData['execution_id'], 'ERROR');
    }
    
    error_log("Erro na simulação {$simulationId}: " . $e->getMessage());
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}