<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Portfolio.php';
require_once __DIR__ . '/../models/Asset.php';
require_once __DIR__ . '/../services/SimulationService.php';
require_once __DIR__ . '/../services/ChartService.php';

class PortfolioController {
    
    public function index() {
        AuthMiddleware::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $portfolioModel = new Portfolio();
        $portfolios = $portfolioModel->findByUser($userId);
        
        include __DIR__ . '/../../views/portfolio/list.php';
    }
    
    public function create() {
        AuthMiddleware::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $assetModel = new Asset();
        $assets = $assetModel->getAll();
        
        include __DIR__ . '/../../views/portfolio/create.php';
    }
    
    private function handleCreate() {
        $userId = $_SESSION['user_id'];
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'initial_capital' => floatval($_POST['initial_capital']),
            'start_date' => $_POST['start_date'],
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'rebalance_frequency' => $_POST['rebalance_frequency'],
            'output_currency' => $_POST['output_currency'],
            'assets' => []
        ];
        
        // Processar ativos
        foreach ($_POST['assets'] as $assetId => $assetData) {
            if ($assetData['allocation'] > 0) {
                $data['assets'][] = [
                    'id' => $assetId,
                    'allocation' => floatval($assetData['allocation']) / 100,
                    'performance_factor' => floatval($assetData['performance_factor'])
                ];
            }
        }
        
        // Validar soma das alocações
        $totalAllocation = array_sum(array_column($data['assets'], 'allocation'));
        if (abs($totalAllocation - 1.0) > 0.00000001) {
            $_SESSION['error'] = "A soma das alocações deve ser 100%. Atual: " . ($totalAllocation * 100) . "%";
            header('Location: /portfolio/create');
            exit;
        }
        
        try {
            $portfolioModel = new Portfolio();
            $portfolioId = $portfolioModel->create($userId, $data);
            
            $_SESSION['success'] = "Portfólio criado com sucesso!";
            header("Location: /portfolio/edit/$portfolioId");
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Erro ao criar portfólio: " . $e->getMessage();
            header('Location: /portfolio/create');
            exit;
        }
    }
    
    public function edit($id) {
        AuthMiddleware::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $portfolioModel = new Portfolio();
        $portfolio = $portfolioModel->findWithAssets($id);
        
        if (!$portfolio || ($portfolio['user_id'] != $userId && !$portfolio['is_default'])) {
            $_SESSION['error'] = "Portfólio não encontrado ou acesso negado";
            header('Location: /portfolio');
            exit;
        }
        
        $assetModel = new Asset();
        $assets = $assetModel->getAll();
        
        include __DIR__ . '/../../views/portfolio/edit.php';
    }
    
    public function simulate($id) {
        AuthMiddleware::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $portfolioModel = new Portfolio();
        $portfolio = $portfolioModel->findWithAssets($id);
        
        if (!$portfolio || ($portfolio['user_id'] != $userId && !$portfolio['is_default'])) {
            Response::error("Portfólio não encontrado ou acesso negado", 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $simulationService = new SimulationService();
            
            try {
                $result = $simulationService->runSimulation($id, $userId);
                Response::success($result, "Simulação iniciada");
                
            } catch (Exception $e) {
                Response::error($e->getMessage());
            }
        }
        
        include __DIR__ . '/../../views/portfolio/simulate.php';
    }
    
    public function clone($id) {
        AuthMiddleware::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error("Método não permitido", 405);
        }
        
        $userId = $_SESSION['user_id'];
        $newName = $_POST['new_name'] ?? "Cópia do Portfólio";
        
        try {
            $portfolioModel = new Portfolio();
            $newPortfolioId = $portfolioModel->clone($id, $userId, $newName);
            
            Response::success([
                'portfolio_id' => $newPortfolioId,
                'redirect' => "/portfolio/edit/$newPortfolioId"
            ], "Portfólio clonado com sucesso");
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    public function getSimulationStatus($executionId) {
        AuthMiddleware::requireLogin();
        
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT s.*, p.name as portfolio_name 
            FROM simulations s
            JOIN portfolios p ON s.portfolio_id = p.id
            WHERE s.execution_id = ? AND s.user_id = ?
        ");
        $stmt->execute([$executionId, $_SESSION['user_id']]);
        $simulation = $stmt->fetch();
        
        if (!$simulation) {
            Response::error("Simulação não encontrada", 404);
        }
        
        Response::success($simulation);
    }
    
    public function getSimulationResults($executionId) {
        AuthMiddleware::requireLogin();
        
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT s.*, p.name as portfolio_name 
            FROM simulations s
            JOIN portfolios p ON s.portfolio_id = p.id
            WHERE s.execution_id = ? AND s.user_id = ? AND s.status = 'COMPLETED'
        ");
        $stmt->execute([$executionId, $_SESSION['user_id']]);
        $simulation = $stmt->fetch();
        
        if (!$simulation) {
            Response::error("Simulação não encontrada ou não concluída", 404);
        }
        
        $results = json_decode($simulation['result_data'], true);
        $metrics = json_decode($simulation['metrics'], true);
        $charts = json_decode($simulation['charts_html'], true);
        
        Response::success([
            'results' => $results,
            'metrics' => $metrics,
            'charts' => $charts,
            'simulation' => $simulation
        ]);
    }
}