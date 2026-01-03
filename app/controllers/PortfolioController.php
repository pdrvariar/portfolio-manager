<?php
class PortfolioController {
    private $portfolioModel;
    private $params; // Adicione esta propriedade
    
    public function __construct() {
        $this->portfolioModel = new Portfolio();
        
    }
    
    public function index() {
        Auth::checkAuthentication();
        
        $userId = $_SESSION['user_id'];
        $portfolios = $this->portfolioModel->getUserPortfolios($userId);
        
        require_once __DIR__ . '/../views/portfolio/index.php';
    }
    
    public function create() {
        Auth::checkAuthentication();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'initial_capital' => $_POST['initial_capital'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'] ?: null,
                'rebalance_frequency' => $_POST['rebalance_frequency'],
                'output_currency' => $_POST['output_currency']
            ];
            
            $portfolioId = $this->portfolioModel->create($data);
            
            if ($portfolioId) {
                // Adicionar ativos
                if (isset($_POST['assets'])) {
                    $this->portfolioModel->updateAssets($portfolioId, $_POST['assets']);
                }
                
                header('Location: /portfolio/view/' . $portfolioId);
                exit;
            }
        }
        
        require_once __DIR__ . '/../views/portfolio/create.php';
    }
    
    public function clone($portfolioId) {
        Auth::checkAuthentication();
        
        $newPortfolioId = $this->portfolioModel->clone($portfolioId, $_SESSION['user_id']);
        
        if ($newPortfolioId) {
            header('Location: /portfolio/view/' . $newPortfolioId);
        } else {
            header('Location: /portfolio');
        }
        exit;
    }
    
    public function runSimulation($portfolioId) {
        Auth::checkAuthentication();
        
        $backtestService = new BacktestService();
        $result = $backtestService->runSimulation($portfolioId);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Simulação executada com sucesso!';
        } else {
            $_SESSION['error_message'] = 'Erro ao executar simulação.';
        }
        
        header('Location: /portfolio/view/' . $portfolioId);
        exit;
    }

    public function view() {
        Auth::checkAuthentication();
        
        // O Router extrai o ID da URL (ex: /portfolio/view/1) e coloca-o nos parâmetros
        $id = $this->params['id'] ?? null;
        
        if (!$id) {
            header('Location: /portfolio');
            exit;
        }
        
        $portfolio = $this->portfolioModel->getById($id);
        
        // Verifica se o portfólio existe e pertence ao utilizador (ou se é padrão do sistema)
        if (!$portfolio || ($portfolio['user_id'] != $_SESSION['user_id'] && !$portfolio['is_system_default'])) {
            Session::setFlash('error', 'Portfólio não encontrado ou acesso negado.');
            header('Location: /portfolio');
            exit;
        }
        
        // Busca os ativos vinculados a este portfólio
        $assets = $this->portfolioModel->getAssets($id);
        
        // Caminho absoluto para a view
        require_once __DIR__ . '/../views/portfolio/view.php';
    }    
}
?>