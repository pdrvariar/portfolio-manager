<?php
class HomeController {
    private $portfolioModel;
    private $simulationModel;
    
    public function __construct() {
        $this->portfolioModel = new Portfolio();
        $this->simulationModel = new SimulationResult();
        Session::start();
    }
    
    public function index() {
        if (!Auth::isLoggedIn()) {
            // Página inicial para visitantes
            require_once __DIR__ . '/../views/home/welcome.php';
            return;
        }
        
        // Dashboard do usuário logado
        $userId = Auth::getCurrentUserId();
        // Buscamos os dois grupos separadamente para a UX
        $portfolios = $this->portfolioModel->getUserPortfolios($userId, false);
        $systemPortfolios = $this->portfolioModel->getSystemPortfolios(); // NOVO
        $stats = $this->simulationModel->getStatistics($userId);        

        // Últimas simulações
        $latestSimulations = [];
        foreach ($portfolios as $portfolio) {
            $simulation = $this->simulationModel->getLatest($portfolio['id']);
            if ($simulation) {
                $latestSimulations[] = [
                    'portfolio' => $portfolio,
                    'simulation' => $simulation
                ];
            }
        }
        
        require_once __DIR__ . '/../views/home/dashboard.php';
    }
}
?>