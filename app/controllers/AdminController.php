<?php
class AdminController {
    private $userModel;
    private $assetModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->assetModel = new Asset();
        Session::start();
    }
    
    public function dashboard() {
        Auth::checkAdmin();
        
        // Estatísticas
        $stats = $this->getStats();
        
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }
    
    public function users() {
        Auth::checkAdmin();
        
        $users = $this->userModel->getAllUsers();
        
        require_once __DIR__ . '/../views/admin/users.php';
    }
    
    public function assets() {
        Auth::checkAdmin();
        
        $assets = $this->assetModel->getAllWithDetails();
        
        require_once __DIR__ . '/../views/admin/assets.php';
    }
    
    public function createDefaultPortfolios() {
        Auth::checkAdmin();
        
        $portfolioModel = new Portfolio();
        
        // Portfólio Permanente
        $permanentId = $portfolioModel->create([
            'user_id' => 0, // Sistema
            'name' => 'Portfólio Permanente',
            'description' => 'Portfólio diversificado com ações, títulos, ouro e caixa',
            'initial_capital' => 100000,
            'start_date' => '2023-01-01',
            'end_date' => null,
            'rebalance_frequency' => 'monthly',
            'output_currency' => 'USD',
            'is_system_default' => true
        ]);
        
        // Portfólio Conservador
        $conservativeId = $portfolioModel->create([
            'user_id' => 0,
            'name' => 'Portfólio Conservador',
            'description' => 'Foco em renda fixa e ativos defensivos',
            'initial_capital' => 100000,
            'start_date' => '2020-01-01',
            'end_date' => null,
            'rebalance_frequency' => 'quarterly',
            'output_currency' => 'BRL',
            'is_system_default' => true
        ]);
        
        Session::setFlash('success', 'Portfólios padrão criados com sucesso!');
        header('Location: /index.php?url=' . obfuscateUrl('admin/dashboard'));
        exit;
    }
    
    private function getStats() {
        $db = Database::getInstance()->getConnection();
        $tables = [
            'users' => 'users',
            'portfolios' => 'portfolios',
            'simulations' => 'simulation_results',
            'assets' => 'system_assets'
        ];
        $stats = [];
        
        foreach ($tables as $key => $table) {
            // TODO: Mover esta lógica para os respectivos Models (ex: User::count())
            $stmt = $db->query("SELECT COUNT(*) as total FROM {$table}");
            $stats[$key] = $stmt->fetch()['total'];
        }
        
        return $stats;
    }
}
?>