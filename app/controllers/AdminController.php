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
        
        require_once '../app/views/admin/dashboard.php';
    }
    
    public function users() {
        Auth::checkAdmin();
        
        $users = $this->userModel->getAllUsers();
        
        require_once '../app/views/admin/users.php';
    }
    
    public function assets() {
        Auth::checkAdmin();
        
        $assets = $this->assetModel->getAllWithDetails();
        
        require_once '../app/views/admin/assets.php';
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
        header('Location: /admin/dashboard');
        exit;
    }
    
    private function getStats() {
        $db = Database::getInstance()->getConnection();
        
        // Total de usuários
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $users = $stmt->fetch()['total'];
        
        // Total de portfólios
        $stmt = $db->query("SELECT COUNT(*) as total FROM portfolios");
        $portfolios = $stmt->fetch()['total'];
        
        // Total de simulações
        $stmt = $db->query("SELECT COUNT(*) as total FROM simulation_results");
        $simulations = $stmt->fetch()['total'];
        
        // Total de ativos
        $stmt = $db->query("SELECT COUNT(*) as total FROM system_assets");
        $assets = $stmt->fetch()['total'];
        
        return [
            'users' => $users,
            'portfolios' => $portfolios,
            'simulations' => $simulations,
            'assets' => $assets
        ];
    }
}
?>