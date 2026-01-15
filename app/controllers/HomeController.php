<?php
namespace App\Controllers;

use App\Core\EntityManagerFactory;
use App\Core\Auth;
use App\Core\Session;
use App\Entities\Portfolio;
use App\Entities\User;
use App\Models\SimulationResult;

class HomeController {
    
    public function __construct() {
        Session::start();
    }
    
    public function index() {
        if (!Auth::isLoggedIn()) {
            // Página inicial para visitantes
            require_once __DIR__ . '/../views/home/welcome.php';
            return;
        }
        
        $entityManager = EntityManagerFactory::createEntityManager();
        $portfolioRepository = $entityManager->getRepository(Portfolio::class);

        // Dashboard do usuário logado
        $userId = Auth::getCurrentUserId();
        $user = $entityManager->find(User::class, $userId);

        // Buscamos os dois grupos separadamente para a UX
        $portfolioEntities = $portfolioRepository->findByUser($user);
        $portfolios = array_map(function($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'initial_capital' => $p->getInitialCapital(),
                'start_date' => $p->getStartDate()->format('Y-m-d'),
                'rebalance_frequency' => $p->getRebalanceFrequency(),
                'output_currency' => $p->getOutputCurrency(),
                'is_system_default' => $p->isSystemDefault(),
                'created_at' => $p->getCreatedAt() ? $p->getCreatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s')
            ];
        }, $portfolioEntities);

        $systemEntities = $portfolioRepository->findBy(['isSystemDefault' => true]);
        $systemPortfolios = array_map(function($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'description' => $p->getDescription(),
                'initial_capital' => $p->getInitialCapital(),
                'start_date' => $p->getStartDate()->format('Y-m-d'),
                'rebalance_frequency' => $p->getRebalanceFrequency(),
                'output_currency' => $p->getOutputCurrency(),
                'is_system_default' => $p->isSystemDefault(),
                'created_at' => $p->getCreatedAt() ? $p->getCreatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s')
            ];
        }, $systemEntities);

        $simulationModel = new SimulationResult();
        $stats = $simulationModel->getStatistics($userId);

        // Últimas simulações
        $latestSimulations = [];
        foreach ($portfolios as $portfolio) {
            $simulation = $simulationModel->getLatest($portfolio['id']);
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