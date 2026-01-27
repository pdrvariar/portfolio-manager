<?php
class AdminController {
    private $userModel;
    private $assetModel;
    
    private $params;
    
    public function __construct($params = []) {
        $this->userModel = new User();
        $this->assetModel = new Asset();
        $this->params = $params;
        Session::start();
    }
    
    public function dashboard() {
        Auth::checkAdmin();
        
        // Estatísticas
        $stats = $this->getStats();
        $activities = $this->getLatestActivities();
        
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }
    
    public function users() {
        Auth::checkAdmin();
        
        $users = $this->userModel->getAllUsers();
        
        require_once __DIR__ . '/../views/admin/users.php';
    }

    public function editUser() {
        Auth::checkAdmin();
        $id = $this->params['id'] ?? null;
        
        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('admin/users'));
            exit;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            Session::setFlash('error', 'Usuário não encontrado.');
            header('Location: /index.php?url=' . obfuscateUrl('admin/users'));
            exit;
        }

        require_once __DIR__ . '/../views/admin/edit_user.php';
    }

    public function updateUser() {
        Auth::checkAdmin();
        $id = $this->params['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                header('Location: /index.php?url=' . obfuscateUrl('admin/users/edit/' . $id));
                exit;
            }

            $data = [
                'full_name' => sanitize($_POST['full_name']),
                'email' => sanitize($_POST['email']),
                'status' => $_POST['status'],
                'is_admin' => isset($_POST['is_admin']) ? 1 : 0
            ];

            if ($this->userModel->adminUpdate($id, $data)) {
                Session::setFlash('success', 'Usuário atualizado com sucesso!');
            } else {
                Session::setFlash('error', 'Erro ao atualizar o usuário.');
            }

            header('Location: /index.php?url=' . obfuscateUrl('admin/users'));
            exit;
        }
    }
    
    public function assets() {
        Auth::checkAdmin();
        
        $assets = $this->assetModel->getAllWithDetails();
        
        require_once __DIR__ . '/../views/admin/assets.php';
    }
    
    public function createDefaultPortfolios() {
        Auth::checkAdmin();
        
        $portfolioModel = new Portfolio();
        $assetModel = new Asset();
        $adminId = Auth::getCurrentUserId();
        
        // 1. Portfólio Permanente (60/40 clássico adaptado)
        $permanentId = $portfolioModel->create([
            'user_id' => $adminId,
            'name' => 'Estratégia 60/40 (Global)',
            'description' => 'Modelo clássico de diversificação: 60% Ações e 40% Renda Fixa.',
            'initial_capital' => 100000,
            'start_date' => '2023-01-01',
            'end_date' => null,
            'rebalance_frequency' => 'monthly',
            'output_currency' => 'USD',
            'is_system_default' => true
        ]);
        
        // Vincular ativos se existirem
        $sp500 = $assetModel->findByCode('SP500');
        $irx = $assetModel->findByCode('IRX-RF-USA');
        
        if ($sp500 && $irx) {
            $portfolioModel->updateAssets($permanentId, [
                $sp500['id'] => ['allocation' => 60, 'performance_factor' => 1.0],
                $irx['id'] => ['allocation' => 40, 'performance_factor' => 1.0]
            ]);
        }
        
        // 2. Portfólio Conservador (Brasil)
        $conservativeId = $portfolioModel->create([
            'user_id' => $adminId,
            'name' => 'Conservador (Brasil)',
            'description' => 'Foco em preservação de capital e renda fixa local.',
            'initial_capital' => 100000,
            'start_date' => '2020-01-01',
            'end_date' => null,
            'rebalance_frequency' => 'quarterly',
            'output_currency' => 'BRL',
            'is_system_default' => true
        ]);

        $selic = $assetModel->findByCode('SELIC');
        $ifix = $assetModel->findByCode('IFIX');
        
        if ($selic && $ifix) {
            $portfolioModel->updateAssets($conservativeId, [
                $selic['id'] => ['allocation' => 80, 'performance_factor' => 1.0],
                $ifix['id'] => ['allocation' => 20, 'performance_factor' => 1.0]
            ]);
        }
        
        Session::setFlash('success', 'Portfólios oficiais gerados com sucesso e vinculados aos ativos correspondentes!');
        header('Location: /index.php?url=' . obfuscateUrl('admin/dashboard'));
        exit;
    }
    
    private function getStats() {
        $db = Database::getInstance()->getConnection();
        
        $stats = [];
        
        // Users count
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $stats['users'] = $stmt->fetch()['total'];
        
        // Portfolios count
        $stmt = $db->query("SELECT COUNT(*) as total FROM portfolios");
        $stats['portfolios'] = $stmt->fetch()['total'];
        
        // Simulations count
        $stmt = $db->query("SELECT COUNT(*) as total FROM simulation_results");
        $stats['simulations'] = $stmt->fetch()['total'];
        
        // Assets count
        $stmt = $db->query("SELECT COUNT(*) as total FROM system_assets");
        $stats['assets'] = $stmt->fetch()['total'];
        
        return $stats;
    }

    private function getLatestActivities() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT * FROM (
                SELECT 'Simulação' as type, sr.created_at as date, p.name as name, u.username as user
                FROM simulation_results sr
                JOIN portfolios p ON sr.portfolio_id = p.id
                JOIN users u ON p.user_id = u.id
                ORDER BY sr.created_at DESC LIMIT 5
            ) AS t1
            UNION
            SELECT * FROM (
                SELECT 'Portfólio' as type, p.created_at as date, p.name, u.username as user
                FROM portfolios p
                JOIN users u ON p.user_id = u.id
                WHERE p.is_system_default = FALSE
                ORDER BY p.created_at DESC LIMIT 5
            ) AS t2
            ORDER BY date DESC LIMIT 10
        ");
        return $stmt->fetchAll();
    }
}
?>