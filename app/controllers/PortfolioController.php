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
                'output_currency' => $_POST['output_currency'],
                'simulation_type' => $_POST['simulation_type'] ?? 'standard',
                'deposit_amount' => !empty($_POST['deposit_amount']) ? $_POST['deposit_amount'] : null,
                'deposit_currency' => $_POST['deposit_currency'] ?? null,
                'deposit_frequency' => $_POST['deposit_frequency'] ?? null,
                'strategic_threshold' => !empty($_POST['strategic_threshold']) ? $_POST['strategic_threshold'] : null,
                'strategic_deposit_percentage' => !empty($_POST['strategic_deposit_percentage']) ? $_POST['strategic_deposit_percentage'] : null
            ];
            
            $portfolioId = $this->portfolioModel->create($data);
            if ($portfolioId) {
                if (isset($_POST['assets'])) {
                    $this->portfolioModel->updateAssets($portfolioId, $_POST['assets']);
                }
                // Garanta que o redirecionamento passe pelo index.php e seja ofuscado
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
        
        $backtestService = new BacktestService();
        $result = $backtestService->runSimulation($portfolioId);
        
        if ($result['success']) {
            $dateStr = date('m/Y', strtotime($result['effective_end']));
            Session::setFlash('success', "Simulação concluída! Dados processados até $dateStr (limite dos ativos selecionados).");
        } else {
            Session::setFlash('error', $result['message']);
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

        // Define métricas padrão usando valores do banco ou padrões
        $metrics = $latest ?: [
            'total_return' => 0,
            'annual_return' => 0,
            'volatility' => 0,
            'sharpe_ratio' => 0,
            'max_drawdown' => 0,
            'total_deposits' => 0,
            'total_invested' => $portfolio['initial_capital'],
            'interest_earned' => 0,
            'roi' => 0,
            'strategy_return' => 0,
            'total_value' => $portfolio['initial_capital'],
            'final_value' => $portfolio['initial_capital']
        ];

        // CORREÇÃO: Calcula o Retorno Total real comparando com o capital inicial
        if ($latest) {
            $initial = $portfolio['initial_capital'];
            $final = $latest['total_value'];

            // Sempre recalcula o total_return para garantir precisão
            $metrics['total_return'] = (($final / $initial) - 1) * 100;

            // Se não tiver ROI no banco ou for zero, calcula agora
            if (empty($latest['roi'])) {
                // Calcula total de aportes se não estiver no banco
                $totalDeposits = $latest['total_deposits'] ?? 0;

                // Se total_deposits não estiver no banco, tenta calcular do chart_data
                if ($totalDeposits == 0 && isset($latest['chart_data'])) {
                    $chartData = json_decode($latest['chart_data'], true);
                    if (isset($chartData['audit_log'])) {
                        foreach ($chartData['audit_log'] as $date => $data) {
                            if ($date !== '_metadata') {
                                $totalDeposits += $data['deposit_made'] ?? 0;
                            }
                        }
                    }
                }

                $totalInvested = $initial + $totalDeposits;
                $interestEarned = $final - $totalInvested;
                $roi = $totalInvested > 0 ? ($interestEarned / $totalInvested) * 100 : 0;

                $metrics['total_deposits'] = $totalDeposits;
                $metrics['total_invested'] = $totalInvested;
                $metrics['interest_earned'] = $interestEarned;
                $metrics['roi'] = $roi;
            } else {
                // Usa os valores do banco
                $metrics['total_deposits'] = $latest['total_deposits'] ?? 0;
                $metrics['total_invested'] = $latest['total_invested'] ?? ($initial + $metrics['total_deposits']);
                $metrics['interest_earned'] = $latest['interest_earned'] ?? ($final - $metrics['total_invested']);
                $metrics['roi'] = $latest['roi'] ?? 0;
            }

            // Se não tiver strategy_return no banco, tenta calcular do chart_data
            if (empty($latest['strategy_return']) && isset($latest['chart_data'])) {
                $chartData = json_decode($latest['chart_data'], true);
                if (isset($chartData['strategy_performance_chart'])) {
                    $strategyData = $chartData['strategy_performance_chart'];
                    if (!empty($strategyData['datasets'][0]['data'])) {
                        $strategyReturns = $strategyData['datasets'][0]['data'];
                        $metrics['strategy_return'] = end($strategyReturns);
                    }
                }
            } else {
                $metrics['strategy_return'] = $latest['strategy_return'] ?? 0;
            }

            // Garante que temos os valores finais
            $metrics['final_value'] = $final;
            $metrics['total_value'] = $final;

            // Copia outras métricas do banco
            $metrics['annual_return'] = $latest['annual_return'] ?? 0;
            $metrics['volatility'] = $latest['volatility'] ?? 0;
            $metrics['sharpe_ratio'] = $latest['sharpe_ratio'] ?? 0;
            $metrics['max_drawdown'] = $latest['max_drawdown'] ?? 0;
        }

        $chartData = [
            'value_chart' => ['labels' => [], 'datasets' => []],
            'composition_chart' => ['labels' => [], 'datasets' => []],
            'returns_chart' => ['labels' => [], 'datasets' => []],
            'strategy_performance_chart' => ['labels' => [], 'datasets' => []],
            'interest_chart' => ['labels' => [], 'datasets' => []],
            'audit_log' => []
        ];

        if ($latest && isset($latest['chart_data'])) {
            $chartData = json_decode($latest['chart_data'], true);
        }

        $start = new DateTime($portfolio['start_date']);
        $end   = new DateTime($portfolio['end_date'] ?? 'now');
        $months = ($start->diff($end)->y * 12) + $start->diff($end)->m;

        $metrics['is_short_period'] = ($months < 12);

        require_once __DIR__ . '/../views/portfolio/view.php';
    }
    
    /**
     * GET: Exibe o formulário de edição com trava de segurança
     */
    public function edit() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        $portfolio = $this->portfolioModel->findById($id);

        // SEGURANÇA SÊNIOR: Bloqueia edição de portfólio de sistema por não-admins
        if ($portfolio['is_system_default'] && !Auth::isAdmin()) {
            Session::setFlash('error', 'Estratégias oficiais do sistema não podem ser editadas.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }

        // Validação de propriedade (apenas o dono ou admin edita)
        if (!$portfolio || ($portfolio['user_id'] != $_SESSION['user_id'] && !Auth::isAdmin())) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $assetModel = new Asset();
        $allAssets = $assetModel->getAllWithDetails();
        $portfolioAssets = $this->portfolioModel->getPortfolioAssets($id);
        
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
                'output_currency' => $_POST['output_currency'],
                'simulation_type' => $_POST['simulation_type'] ?? 'standard',
                'deposit_amount' => !empty($_POST['deposit_amount']) ? $_POST['deposit_amount'] : null,
                'deposit_currency' => $_POST['deposit_currency'] ?? null,
                'deposit_frequency' => $_POST['deposit_frequency'] ?? null,
                'strategic_threshold' => !empty($_POST['strategic_threshold']) ? $_POST['strategic_threshold'] : null,
                'strategic_deposit_percentage' => !empty($_POST['strategic_deposit_percentage']) ? $_POST['strategic_deposit_percentage'] : null
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

    /**
     * POST: Processa a exclusão com lógica de permissão Admin
     */
    public function delete() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        $portfolio = $this->portfolioModel->findById($id);

        if (!$portfolio) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Lógica de Permissão Sênior:
        // 1. Se for sistema: Só Admin deleta.
        // 2. Se for pessoal: Só o dono (ou Admin) deleta.
        $canDelete = false;
        if ($portfolio['is_system_default']) {
            $canDelete = Auth::isAdmin();
        } else {
            $canDelete = ($portfolio['user_id'] == $_SESSION['user_id'] || Auth::isAdmin());
        }

        if ($canDelete) {
            $this->portfolioModel->delete($id);
            Session::setFlash('success', 'Portfólio removido com sucesso.');
        } else {
            Session::setFlash('error', 'Você não tem permissão para excluir este portfólio.');
        }

        header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
        exit;
    }

    public function toggleSystem() {
        // SEGURANÇA SÊNIOR: Apenas administradores podem acessar esta rota
        Auth::checkAdmin(); 

        $id = $this->params['id'] ?? null;
        $portfolio = $this->portfolioModel->findById($id);

        if ($portfolio) {
            // Inverte o status atual
            $newStatus = $portfolio['is_system_default'] ? 0 : 1;
            $this->portfolioModel->toggleSystemStatus($id, $newStatus);
            
            $msg = $newStatus ? "Portfólio promovido ao sistema!" : "Portfólio removido do sistema.";
            Session::setFlash('success', $msg);
        }
        
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
        exit;
    }    
}
?>