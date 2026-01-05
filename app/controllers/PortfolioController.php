<?php
class PortfolioController {
    private $portfolioModel;
    private $params; // Adicione esta propriedade
    
    public function __construct($params = []) {
        $this->portfolioModel = new Portfolio();
        $this->params = $params;
        // ADICIONE ESTA LINHA:
        Session::start(); 
    }

    public function index() {
        Auth::checkAuthentication();
        
        $userId = Auth::getCurrentUserId();
        $portfolios = $this->portfolioModel->getUserPortfolios($userId, true);
        
        require_once __DIR__ . '/../views/portfolio/index.php';
    }
        
    public function create() {
        Auth::checkAuthentication();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('portfolio/create'));
            }           
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
                if (isset($_POST['assets'])) {
                    $this->portfolioModel->updateAssets($portfolioId, $_POST['assets']);
                }
                // CORREÇÃO: Redirecionamento absoluto
                header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolioId));
                exit;
            }
        }
        require_once __DIR__ . '/../views/portfolio/create.php';
    }
    
    public function clone() {
        Auth::checkAuthentication();
        
        $portfolioId = $this->params['id'] ?? null;
        
        if (!$portfolioId) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }
        
        $newPortfolioId = $this->portfolioModel->clone($portfolioId, $_SESSION['user_id']);
        
        if ($newPortfolioId) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $newPortfolioId));
        } else {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
        }
        exit;
    }
    
    public function runSimulation() {
        Auth::checkAuthentication();
        $portfolioId = $this->params['id'] ?? null;
        if (!$portfolioId) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }
        
        $backtestService = new BacktestService();
        $result = $backtestService->runSimulation($portfolioId);
        
        if ($result['success']) {
            Session::setFlash('success', 'Simulação executada com sucesso!');
        } else {
            // Se a simulação falhar (ex: falta de dados), agora o erro aparecerá na tela
            Session::setFlash('error', 'Erro na simulação: ' . $result['message']);
        }
        
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolioId));
        exit;
    }

    public function view() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        
        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }
        
        $portfolio = $this->portfolioModel->findById($id);
        $assets = $this->portfolioModel->getPortfolioAssets($id);

        $simulationModel = new SimulationResult();
        $latest = $simulationModel->getLatest($id);

        // Define métricas padrão
        $metrics = $latest ?: [
            'total_return' => 0,
            'annual_return' => 0,
            'volatility' => 0,
            'sharpe_ratio' => 0
        ];

        // CORREÇÃO: Calcula o Retorno Total real comparando com o capital inicial
        if ($latest) {
            $initial = $portfolio['initial_capital'];
            $final = $latest['total_value'];
            $metrics['total_return'] = (($final / $initial) - 1) * 100;
        }

        $chartData = [
            'value_chart' => ['labels' => [], 'datasets' => []],
            'composition_chart' => ['labels' => [], 'datasets' => []],
            'returns_chart' => ['labels' => [], 'datasets' => []]
        ];

        if ($latest && isset($latest['chart_data'])) {
            $chartData = json_decode($latest['chart_data'], true);
        }
        
        require_once __DIR__ . '/../views/portfolio/view.php';
    }
    
    public function edit() {
        Auth::checkAuthentication();
        
        $id = $this->params['id'] ?? null;
        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }
        
        $portfolio = $this->portfolioModel->findById($id);
        
        if (!$portfolio || $portfolio['user_id'] != $_SESSION['user_id']) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }
        
        // --- CORREÇÃO: Busque os dados que a View precisa aqui ---
        $assetModel = new Asset();
        $allAssets = $assetModel->getAllWithDetails();
        $portfolioAssets = $this->portfolioModel->getPortfolioAssets($id);
        // ---------------------------------------------------------
        
        require_once __DIR__ . '/../views/portfolio/edit.php';
    }

    public function update() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('portfolio/edit/' . $id));
            }

            $data = [
                'id' => $id,
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'initial_capital' => $_POST['initial_capital'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'] ?: null,
                'rebalance_frequency' => $_POST['rebalance_frequency'],
                'output_currency' => $_POST['output_currency']
            ];
            
            // 1. Atualiza os metadados (Nome, Capital, etc)
            $this->portfolioModel->update($data);
            if (isset($_POST['assets'])) {
                $this->portfolioModel->updateAssets($id, $_POST['assets']);
                // CORREÇÃO: Use Session::setFlash para o main.php mostrar o alerta
                Session::setFlash('success', 'Portfólio atualizado com sucesso!');
            }
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }
    }

    public function delete() {
        // 1. Garante que o usuário está logado
        Auth::checkAuthentication();
        
        // 2. Recupera o ID enviado pela rota
        $id = $this->params['id'] ?? null;
        
        if ($id) {
            // 3. Tenta excluir no Model (a regra de negócio protege contra exclusão de portfólios de outros usuários)
            if ($this->portfolioModel->delete($id)) {
                Session::setFlash('success', 'Portfólio excluído com sucesso!');
            } else {
                Session::setFlash('error', 'Não foi possível excluir o portfólio. Verifique se ele é um padrão do sistema.');
            }
        }
        
        // 4. Redireciona de volta para a listagem principal
        header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
        exit;
    }    
}
?>