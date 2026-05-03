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
        
        // Verificação de limites para o Plano Starter
        if (!Auth::isPro()) {
            $userId = Auth::getCurrentUserId();
            $existingPortfolios = $this->portfolioModel->getUserPortfolios($userId, false); // false para não incluir defaults
            if (count($existingPortfolios) >= 2) {
                Session::setFlash('warning', 'O Plano Starter permite no máximo 2 portfólios. Faça upgrade para o Plano PRO para criar ilimitados.');
                header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
                exit;
            }
        }
        
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
                'strategic_deposit_percentage' => !empty($_POST['strategic_deposit_percentage']) ? $_POST['strategic_deposit_percentage'] : null,
                'deposit_inflation_adjusted' => isset($_POST['deposit_inflation_adjusted']) ? 1 : 0,
                'rebalance_type' => $_POST['rebalance_type'] ?? 'full',
                'rebalance_margin' => !empty($_POST['rebalance_margin']) ? (float)str_replace(',', '.', $_POST['rebalance_margin']) : null,
                'use_cash_assets_for_rebalance' => isset($_POST['use_cash_assets_for_rebalance']) ? 1 : 0,
                'profit_tax_rate' => !empty($_POST['profit_tax_rate']) ? (float)str_replace(',', '.', $_POST['profit_tax_rate']) : null,
                'profit_tax_rates_json' => !empty($_POST['profit_tax_rates']) ? json_encode($_POST['profit_tax_rates']) : null
            ];

            // Validação de limites do Plano Starter
            if (!Auth::isPro()) {
                // Limite de 5 anos na data de início
                $fiveYearsAgo = date('Y-m-d', strtotime('-5 years'));
                if ($data['start_date'] < $fiveYearsAgo) {
                    $data['start_date'] = $fiveYearsAgo;
                    Session::setFlash('warning', 'No Plano Starter o histórico é limitado a 5 anos. Sua data de início foi ajustada.');
                }
            }
            
            $portfolioId = $this->portfolioModel->create($data);
            if ($portfolioId) {
                if (isset($_POST['assets'])) {
                    // Verificação de limite de ativos para o Plano Starter
                    if (!Auth::isPro()) {
                        $assetsCount = count($_POST['assets']);
                        if ($assetsCount > 5) {
                            // Se exceder, pegamos apenas os 5 primeiros
                            $_POST['assets'] = array_slice($_POST['assets'], 0, 5, true);
                            Session::setFlash('warning', 'O Plano Starter permite no máximo 5 ativos por carteira. Apenas os 5 primeiros foram salvos.');
                        }
                    }
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
        $userId = $_SESSION['user_id'];

        $simulationModel = new SimulationResult();
        $monthlyCount = $simulationModel->countMonthlySimulations($userId);
        $isPro = Auth::isPro();
        $limit = $isPro ? 1000 : 20;

        if ($monthlyCount >= $limit) {
            $msg = $isPro 
                ? "Você atingiu o limite de 1000 simulações mensais do seu plano PRO."
                : "Você atingiu o limite de 20 simulações mensais do plano Starter. Faça um upgrade para o PRO para aumentar seu limite para 1000!";
            Session::setFlash('error', $msg);
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolioId));
            exit;
        }
        
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

    public function runAdvancedSimulation() {
        Auth::checkAuthentication();
        $portfolioId = $this->params['id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$portfolioId) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // PRO-only feature guard
        if (!Auth::isPro()) {
            Session::setFlash('error', 'A Simulação Avançada é exclusiva do Plano PRO. Faça upgrade para desbloquear!');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Check ownership
        $portfolio = $this->portfolioModel->findById($portfolioId);
        if (!$portfolio || $portfolio['user_id'] != $userId) {
            Session::setFlash('error', 'Portfólio não encontrado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Check monthly simulation limit (each advanced run uses up to 20 slots)
        $simulationModel = new SimulationResult();
        $monthlyCount = $simulationModel->countMonthlySimulations($userId);
        $isPro = Auth::isPro();
        $hardLimit = $isPro ? 1000 : 20;
        $scenarioCount = $isPro ? 20 : 5; // Starter gets 5 scenarios, PRO gets 20

        if ($monthlyCount + $scenarioCount > $hardLimit) {
            $remaining = max(0, $hardLimit - $monthlyCount);
            if ($remaining < 2) {
                $msg = $isPro 
                    ? "Você atingiu o limite de simulações mensais do plano PRO."
                    : "Limite de simulações mensais atingido. Faça upgrade para o Plano PRO!";
                Session::setFlash('error', $msg);
                header('Location: /index.php?url=' . obfuscateUrl('portfolio/history/' . $portfolioId));
                exit;
            }
            $scenarioCount = $remaining;
        }

        set_time_limit(300); // Up to 5 min for 20 simulations

        $backtestService = new BacktestService();
        $result = $backtestService->runAdvancedSimulation($portfolioId, $scenarioCount);

        if ($result['success']) {
            $dateStr = date('m/Y', strtotime($result['effective_end']));
            $best = $result['best'];
            $bestSharpe = number_format($best['metrics']['sharpe_ratio'], 2, ',', '.');
            $bestReturn = number_format($best['metrics']['strategy_annual_return'], 2, ',', '.');
            Session::setFlash('success', 
                "✅ Simulação Avançada concluída! {$result['count']} cenários gerados até $dateStr. " .
                "Melhor cenário: Sharpe $bestSharpe · Retorno anual da estratégia $bestReturn% " .
                "· Alocação: {$best['label']}"
            );
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/history/' . $portfolioId) . '&group=' . urlencode($result['group_id']));
        } else {
            Session::setFlash('error', $result['message']);
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/history/' . $portfolioId));
        }
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
            $metrics['strategy_annual_return'] = $latest['strategy_annual_return'] ?? 0;

            // Se não tiver strategy_annual_return no banco (simulações antigas), calcula a partir do strategy_return
            if ($metrics['strategy_annual_return'] == 0 && $metrics['strategy_return'] != 0) {
                $start = new DateTime($portfolio['start_date']);
                $end   = new DateTime($latest['simulation_date']);
                $numMonths = ($start->diff($end)->y * 12) + $start->diff($end)->m;
                if ($numMonths <= 0) $numMonths = 1;

                $strategyReturnDecimal = $metrics['strategy_return'] / 100;
                if ($numMonths >= 12) {
                    $metrics['strategy_annual_return'] = (pow(1 + $strategyReturnDecimal, 12 / $numMonths) - 1) * 100;
                } else {
                    $metrics['strategy_annual_return'] = $strategyReturnDecimal * 100;
                }
            }

            $metrics['volatility'] = $latest['volatility'] ?? 0;
            $metrics['sharpe_ratio'] = $latest['sharpe_ratio'] ?? 0;
            $metrics['max_drawdown'] = $latest['max_drawdown'] ?? 0;
            $metrics['max_monthly_gain'] = $latest['max_monthly_gain'] ?? 0;
            $metrics['max_monthly_loss'] = $latest['max_monthly_loss'] ?? 0;
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

            // Calcula o total de impostos pagos ao longo da simulação
            $totalTaxPaid = 0;
            if (isset($chartData['audit_log'])) {
                foreach ($chartData['audit_log'] as $date => $data) {
                    if ($date === '_metadata') continue;
                    
                    if (isset($data['tax_summary'])) {
                        foreach ($data['tax_summary'] as $groupInfo) {
                            $totalTaxPaid += $groupInfo['tax'] ?? 0;
                        }
                    } elseif (isset($data['tax_paid'])) {
                        $totalTaxPaid += $data['tax_paid'];
                    }
                }
            }
            $metrics['total_tax_paid'] = $totalTaxPaid;

            // === FIX: Garante que o ponto 0 (capital inicial) exista no gráfico de evolução ===
            // Aplica tanto em simulações antigas (sem ponto 0) quanto novas.
            $initialCapitalValue = (float)$portfolio['initial_capital'];
            $firstSimDate = null;
            if (isset($chartData['audit_log'])) {
                foreach ($chartData['audit_log'] as $auditDate => $auditData) {
                    if ($auditDate !== '_metadata') { $firstSimDate = $auditDate; break; }
                }
            }

            if ($firstSimDate && !empty($chartData['value_chart']['labels'])) {
                $dt0 = new \DateTime($firstSimDate);
                $dt0->modify('first day of this month');
                $dt0->modify('-1 month');
                $point0Date = $dt0->format('Y-m-d');

                // Só adiciona se ainda não estiver presente (evita duplicação em simulações novas)
                if ($chartData['value_chart']['labels'][0] !== $point0Date) {
                    array_unshift($chartData['value_chart']['labels'], $point0Date);
                    foreach ($chartData['value_chart']['datasets'] as &$ds) {
                        array_unshift($ds['data'], $initialCapitalValue);
                    }
                    unset($ds);
                }
            }

            // Gera projeção futura se tivermos um retorno positivo
            if ($metrics['strategy_annual_return'] > 0) {
                $projectionService = new ProjectionService();
                
                // Pega valor do aporte do portfólio (se houver algum configurado)
                $monthlyDeposit = 0;
                if (!empty($portfolio['deposit_amount'])) {
                    $monthlyDeposit = (float)$portfolio['deposit_amount'];
                    
                    if ($portfolio['deposit_frequency'] == 'quarterly') {
                        $monthlyDeposit /= 3;
                    } elseif ($portfolio['deposit_frequency'] == 'biannual') {
                        $monthlyDeposit /= 6;
                    } elseif ($portfolio['deposit_frequency'] == 'annual') {
                        $monthlyDeposit /= 12;
                    }
                }
                
                // Usa a data de fim da simulação como ponto de partida da projeção (não "hoje")
                $projectionRaw = $projectionService->calculateProjection(
                    $metrics['final_value'], 
                    $metrics['strategy_annual_return'], 
                    $monthlyDeposit,
                    10, // 10 anos de projeção
                    $latest['simulation_date'] // Ponto de partida = fim da simulação
                );
                
                $chartData['projection_chart'] = $projectionService->formatProjectionChart($projectionRaw);
            }
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
                'strategic_deposit_percentage' => !empty($_POST['strategic_deposit_percentage']) ? $_POST['strategic_deposit_percentage'] : null,
                'deposit_inflation_adjusted' => isset($_POST['deposit_inflation_adjusted']) ? 1 : 0,
                'rebalance_type' => $_POST['rebalance_type'] ?? 'full',
                'rebalance_margin' => !empty($_POST['rebalance_margin']) ? (float)str_replace(',', '.', $_POST['rebalance_margin']) : null,
                'use_cash_assets_for_rebalance' => isset($_POST['use_cash_assets_for_rebalance']) ? 1 : 0,
                'profit_tax_rate' => !empty($_POST['profit_tax_rate']) ? (float)str_replace(',', '.', $_POST['profit_tax_rate']) : null,
                'profit_tax_rates_json' => !empty($_POST['profit_tax_rates']) ? json_encode($_POST['profit_tax_rates']) : null
            ];

            // Validação de limites do Plano Starter no update
            if (!Auth::isPro()) {
                // Limite de 5 anos na data de início
                $fiveYearsAgo = date('Y-m-d', strtotime('-5 years'));
                if ($data['start_date'] < $fiveYearsAgo) {
                    $data['start_date'] = $fiveYearsAgo;
                    Session::setFlash('warning', 'No Plano Starter o histórico é limitado a 5 anos. Sua data de início foi ajustada.');
                }
            }
            
            // 1. Atualiza os metadados (Nome, Capital, etc)
            $this->portfolioModel->update($data);
            if (isset($_POST['assets'])) {
                // Verificação de limite de ativos para o Plano Starter no update
                if (!Auth::isPro()) {
                    $assetsCount = count($_POST['assets']);
                    if ($assetsCount > 5) {
                        $_POST['assets'] = array_slice($_POST['assets'], 0, 5, true);
                        Session::setFlash('warning', 'O Plano Starter permite no máximo 5 ativos por carteira. Apenas os 5 primeiros foram salvos.');
                    }
                }
                $this->portfolioModel->updateAssets($id, $_POST['assets']);
                // CORREÇÃO: Use Session::setFlash para o main.php mostrar o alerta
                Session::setFlash('success', 'Portfólio atualizado com sucesso!');
            }
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }
    }

    public function applySnapshot() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de segurança inválido.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $portfolioId  = $this->params['id'] ?? null;
        $simulationId = (int)($_POST['simulation_id'] ?? 0);

        if (!$portfolioId || !$simulationId) {
            Session::setFlash('error', 'Parâmetros inválidos.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Valida propriedade do portfólio
        $portfolio = $this->portfolioModel->findById($portfolioId);
        if (!$portfolio || ($portfolio['user_id'] != $_SESSION['user_id'] && !Auth::isAdmin())) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        if ($portfolio['is_system_default'] && !Auth::isAdmin()) {
            Session::setFlash('error', 'Portfólios do sistema não podem ser alterados.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/history/' . $portfolioId));
            exit;
        }

        // Carrega o snapshot
        $db       = Database::getInstance()->getConnection();
        $stmt     = $db->prepare("SELECT portfolio_config, assets_config FROM simulation_snapshots WHERE simulation_id = ?");
        $stmt->execute([$simulationId]);
        $snapshot = $stmt->fetch();

        if (!$snapshot) {
            Session::setFlash('error', 'Snapshot não encontrado para esta simulação. Execute uma nova simulação para gerar o snapshot.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/history/' . $portfolioId));
            exit;
        }

        $pc = json_decode($snapshot['portfolio_config'], true);
        $ac = json_decode($snapshot['assets_config'],    true);

        // Aplica configurações do portfólio (mantém name e description atuais — só parâmetros técnicos)
        $updateData = [
            'id'                            => $portfolioId,
            'name'                          => $portfolio['name'],
            'description'                   => $portfolio['description'],
            'initial_capital'               => $pc['initial_capital'],
            'start_date'                    => $pc['start_date'],
            'end_date'                      => $pc['end_date'] ?? null,
            'rebalance_frequency'           => $pc['rebalance_frequency'],
            'output_currency'               => $pc['output_currency'],
            'simulation_type'               => $pc['simulation_type'] ?? 'standard',
            'rebalance_type'                => $pc['rebalance_type'] ?? 'full',
            'rebalance_margin'              => $pc['rebalance_margin'] ?? null,
            'deposit_amount'                => $pc['deposit_amount'] ?? null,
            'deposit_currency'              => $pc['deposit_currency'] ?? null,
            'deposit_frequency'             => $pc['deposit_frequency'] ?? null,
            'strategic_threshold'           => $pc['strategic_threshold'] ?? null,
            'strategic_deposit_percentage'  => $pc['strategic_deposit_percentage'] ?? null,
            'deposit_inflation_adjusted'    => $pc['deposit_inflation_adjusted'] ?? 0,
            'use_cash_assets_for_rebalance' => $pc['use_cash_assets_for_rebalance'] ?? 0,
            'profit_tax_rate'               => $pc['profit_tax_rate'] ?? null,
            'profit_tax_rates_json'         => $pc['profit_tax_rates_json'] ?? null,
        ];
        $this->portfolioModel->update($updateData);

        // Aplica composição de ativos
        $assetsForUpdate = [];
        foreach ($ac as $asset) {
            $assetsForUpdate[$asset['asset_id']] = [
                'allocation'            => $asset['allocation_percentage'],
                'performance_factor'    => $asset['performance_factor'] ?? 1.0,
                'rebalance_margin_down' => $asset['rebalance_margin_down'] ?? null,
                'rebalance_margin_up'   => $asset['rebalance_margin_up']   ?? null,
            ];
        }
        $this->portfolioModel->updateAssets($portfolioId, $assetsForUpdate);

        Session::setFlash('success', 'Configuração aplicada com sucesso! Execute uma nova simulação para comparar os resultados.');
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolioId));
        exit;
    }

    /**
     * Cria um novo portfólio a partir do snapshot de uma simulação.
     */
    public function createFromSnapshot() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de segurança inválido.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $simulationId = (int)($_POST['simulation_id'] ?? 0);
        $newName      = trim($_POST['portfolio_name'] ?? '');

        if (!$simulationId || $newName === '') {
            Session::setFlash('error', 'Parâmetros inválidos. Informe um nome para o novo portfólio.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?url=' . obfuscateUrl('portfolio/simulations')));
            exit;
        }

        // Validação de limite de portfólios para Plano Starter
        if (!Auth::isPro()) {
            $userId = Auth::getCurrentUserId();
            $existing = $this->portfolioModel->getUserPortfolios($userId, false);
            if (count($existing) >= 2) {
                Session::setFlash('warning', 'O Plano Starter permite no máximo 2 portfólios. Faça upgrade para o Plano PRO para criar ilimitados.');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?url=' . obfuscateUrl('portfolio/simulations')));
                exit;
            }
        }

        // Carrega o snapshot
        $db       = Database::getInstance()->getConnection();
        $stmt     = $db->prepare("SELECT ss.portfolio_config, ss.assets_config, sr.portfolio_id FROM simulation_snapshots ss JOIN simulation_results sr ON sr.id = ss.simulation_id WHERE ss.simulation_id = ?");
        $stmt->execute([$simulationId]);
        $snapshot = $stmt->fetch();

        if (!$snapshot) {
            Session::setFlash('error', 'Snapshot não encontrado para esta simulação.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?url=' . obfuscateUrl('portfolio/simulations')));
            exit;
        }

        // Verifica se a simulação pertence a um portfólio do usuário
        $originalPortfolio = $this->portfolioModel->findById($snapshot['portfolio_id']);
        if (!$originalPortfolio || ($originalPortfolio['user_id'] != $_SESSION['user_id'] && !Auth::isAdmin())) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $pc = json_decode($snapshot['portfolio_config'], true);
        $ac = json_decode($snapshot['assets_config'],    true);

        // Cria o novo portfólio com o nome fornecido e as configs do snapshot
        $newPortfolioId = $this->portfolioModel->create([
            'user_id'                       => $_SESSION['user_id'],
            'name'                          => $newName,
            'description'                   => 'Criado a partir da simulação #' . $simulationId . ' do portfólio "' . $originalPortfolio['name'] . '".',
            'initial_capital'               => $pc['initial_capital'],
            'start_date'                    => $pc['start_date'],
            'end_date'                      => $pc['end_date'] ?? null,
            'rebalance_frequency'           => $pc['rebalance_frequency'],
            'output_currency'               => $pc['output_currency'],
            'simulation_type'               => $pc['simulation_type'] ?? 'standard',
            'rebalance_type'                => $pc['rebalance_type'] ?? 'full',
            'rebalance_margin'              => $pc['rebalance_margin'] ?? null,
            'deposit_amount'                => $pc['deposit_amount'] ?? null,
            'deposit_currency'              => $pc['deposit_currency'] ?? null,
            'deposit_frequency'             => $pc['deposit_frequency'] ?? null,
            'strategic_threshold'           => $pc['strategic_threshold'] ?? null,
            'strategic_deposit_percentage'  => $pc['strategic_deposit_percentage'] ?? null,
            'deposit_inflation_adjusted'    => $pc['deposit_inflation_adjusted'] ?? 0,
            'use_cash_assets_for_rebalance' => $pc['use_cash_assets_for_rebalance'] ?? 0,
            'profit_tax_rate'               => $pc['profit_tax_rate'] ?? null,
            'profit_tax_rates_json'         => $pc['profit_tax_rates_json'] ?? null,
            'cloned_from'                   => $snapshot['portfolio_id'],
        ]);

        if (!$newPortfolioId) {
            Session::setFlash('error', 'Erro ao criar o portfólio. Tente novamente.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?url=' . obfuscateUrl('portfolio/simulations')));
            exit;
        }

        // Aplica composição de ativos
        $assetsForUpdate = [];
        foreach ($ac as $asset) {
            $assetsForUpdate[$asset['asset_id']] = [
                'allocation'            => $asset['allocation_percentage'],
                'performance_factor'    => $asset['performance_factor'] ?? 1.0,
                'rebalance_margin_down' => $asset['rebalance_margin_down'] ?? null,
                'rebalance_margin_up'   => $asset['rebalance_margin_up']   ?? null,
            ];
        }
        $this->portfolioModel->updateAssets($newPortfolioId, $assetsForUpdate);

        Session::setFlash('success', 'Novo portfólio "' . htmlspecialchars($newName) . '" criado com sucesso! Execute uma simulação para comparar os resultados.');
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $newPortfolioId));
        exit;
    }

    public function history() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/simulations'));
            exit;
        }

        $extra = '';
        if (!empty($_GET['group'])) {
            $extra = '&group=' . urlencode($_GET['group']);
        }

        // Redireciona para a tela unificada com o filtro pré-selecionado
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/simulations') . '&portfolio_id=' . (int)$id . $extra);
        exit;
    }

    public function allSimulations() {
        Auth::checkAuthentication();
        $userId = Auth::getCurrentUserId();

        // Portfólio selecionado no filtro (0 = todos)
        $portfolioId = isset($_GET['portfolio_id']) ? (int)$_GET['portfolio_id'] : 0;

        // Busca todos os portfólios do usuário para popular o filtro
        $portfolios = $this->portfolioModel->getUserPortfolios($userId, false);

        // Garante que o portfolio_id informado pertence ao usuário
        $selectedPortfolio = null;
        if ($portfolioId) {
            foreach ($portfolios as $p) {
                if ((int)$p['id'] === $portfolioId) {
                    $selectedPortfolio = $p;
                    break;
                }
            }
            if (!$selectedPortfolio) {
                $portfolioId = 0; // reset se não for do usuário
            }
        }

        $simulationModel = new SimulationResult();
        $simulations = $simulationModel->getAllHistoryForUser($userId, $portfolioId ?: null, 200);

        // Advanced simulation group filter
        $advancedGroup = isset($_GET['group']) ? trim($_GET['group']) : null;

        // Sort portfolios alphabetically for the filter
        $sortedPortfolios = $portfolios;
        usort($sortedPortfolios, fn($a,$b) => strcasecmp($a['name'], $b['name']));

        // If a group is active, filter only those simulations for display  
        $displaySimulations = $simulations;
        if ($advancedGroup) {
            $displaySimulations = array_filter($simulations, fn($s) => ($s['advanced_simulation_group'] ?? '') === $advancedGroup);
            $displaySimulations = array_values($displaySimulations);
        }

        // Build JS data structures
        $snapshotsJs = [];
        $metricsJs   = [];
        foreach ($simulations as $sim) {
            $pc = $sim['portfolio_config'] ? json_decode($sim['portfolio_config'], true) : null;
            $ac = $sim['assets_config']    ? json_decode($sim['assets_config'],    true) : null;
            $snapshotsJs[$sim['id']] = ['portfolio' => $pc, 'assets' => $ac];
            $metricsJs[$sim['id']] = [
                'total_invested'         => $sim['total_invested']          ?? null,
                'total_deposits'         => $sim['total_deposits']          ?? null,
                'total_value'            => $sim['total_value']             ?? null,
                'interest_earned'        => $sim['interest_earned']         ?? null,
                'total_tax_paid'         => $sim['total_tax_paid']          ?? null,
                'roi'                    => $sim['roi']                     ?? null,
                'annual_return'          => $sim['annual_return']           ?? null,
                'strategy_annual_return' => $sim['strategy_annual_return']  ?? null,
                'strategy_return'        => $sim['strategy_return']         ?? null,
                'volatility'             => $sim['volatility']              ?? null,
                'sharpe_ratio'           => $sim['sharpe_ratio']            ?? null,
                'max_drawdown'           => $sim['max_drawdown']            ?? null,
                'max_monthly_gain'       => $sim['max_monthly_gain']        ?? null,
                'max_monthly_loss'       => $sim['max_monthly_loss']        ?? null,
                'portfolio_name'         => $sim['portfolio_name']          ?? null,
                'advanced_group'         => $sim['advanced_simulation_group'] ?? null,
                'allocation_label'       => $sim['allocation_label']        ?? null,
            ];
        }

        // Per-portfolio apply snapshot URLs and run URLs
        $portfolioUrlsJs = [];
        foreach ($portfolios as $p) {
            $portfolioUrlsJs[$p['id']] = [
                'apply'            => '/index.php?url=' . obfuscateUrl('portfolio/apply-snapshot/' . $p['id']),
                'create_from_snap' => '/index.php?url=' . obfuscateUrl('portfolio/create-from-snapshot'),
                'run'              => '/index.php?url=' . obfuscateUrl('portfolio/run/' . $p['id']),
                'view'             => '/index.php?url=' . obfuscateUrl('portfolio/view/' . $p['id']),
                'name'             => $p['name'],
            ];
        }

        $csrfToken     = Session::getCsrfToken();
        $csrfTokenJson = json_encode($csrfToken);
        $baseHistoryUrl = obfuscateUrl('portfolio/simulations');

        // Summary stats
        $totalCount = count($displaySimulations);
        $bestSharpe = null; $bestReturn = null;
        foreach ($displaySimulations as $s) {
            if ($bestSharpe === null || (float)$s['sharpe_ratio'] > (float)$bestSharpe['sharpe_ratio']) $bestSharpe = $s;
            if ($bestReturn === null || (float)$s['annual_return'] > (float)$bestReturn['annual_return']) $bestReturn = $s;
        }

        // Breadcrumb injetado no layout
        $breadcrumbs = [
            ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=' . obfuscateUrl('dashboard')],
            ['label' => 'Portfólios', 'url' => '/index.php?url=' . obfuscateUrl('portfolio')],
        ];
        if ($selectedPortfolio) {
            $breadcrumbs[] = ['label' => htmlspecialchars($selectedPortfolio['name']), 'url' => '/index.php?url=' . obfuscateUrl('portfolio/view/' . $selectedPortfolio['id'])];
        }
        $breadcrumbs[] = ['label' => 'Histórico de Simulações', 'url' => '#'];

        require_once __DIR__ . '/../views/portfolio/all_simulations.php';
    }

    public function compareSimulations() {
        Auth::checkAuthentication();
        $userId = Auth::getCurrentUserId();

        $rawIds = $_GET['ids'] ?? [];
        if (!is_array($rawIds)) $rawIds = explode(',', $rawIds);
        $ids = array_map('intval', array_filter($rawIds));
        $ids = array_unique(array_slice($ids, 0, 5));

        if (count($ids) < 2) {
            Session::setFlash('warning', 'Selecione pelo menos 2 simulações para comparar.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/simulations'));
            exit;
        }

        $simulationModel = new SimulationResult();
        $simulations = $simulationModel->getByIds($ids, $userId);

        if (count($simulations) < 2) {
            Session::setFlash('error', 'Simulações não encontradas ou sem permissão de acesso.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/simulations'));
            exit;
        }

        require_once __DIR__ . '/../views/portfolio/compare_simulations.php';
    }

    public function simulationDetails() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $portfolio = $this->portfolioModel->findById($id);
        if (!$portfolio) {
            Session::setFlash('error', 'Portfólio não encontrado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $assets = $this->portfolioModel->getPortfolioAssets($id);
        $simulationModel = new SimulationResult();
        $latest = $simulationModel->getLatest($id);

        if (!$latest) {
            Session::setFlash('warning', 'Nenhuma simulação encontrada para este portfólio. Execute uma simulação primeiro.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }

        $chartData = json_decode($latest['chart_data'], true);
        
        // Mapeamento de IDs para nomes de ativos para uso na view (JSON)
        $assetNames = [];
        $assetTargets = [];
        $assetMargins = [];
        $assetCurrencies = [];
        $assetTaxGroups = [];
        foreach ($assets as $asset) {
            $assetNames[$asset['asset_id']] = $asset['name'];
            $assetTargets[$asset['asset_id']] = $asset['allocation_percentage'];
            $assetMargins[$asset['asset_id']] = [
                'min' => $asset['rebalance_margin_down'],
                'max' => $asset['rebalance_margin_up']
            ];
            $assetCurrencies[$asset['asset_id']] = $asset['currency'];
            $assetTaxGroups[$asset['asset_id']] = $asset['tax_group'] ?? 'RENDA_FIXA';
        }

        require_once __DIR__ . '/../views/portfolio/simulation_details.php';
    }

    public function apiProjection() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        $years = isset($_GET['years']) ? (int)$_GET['years'] : 10;
        $initialCapitalInput = $_GET['initial_capital'] ?? null;
        if ($initialCapitalInput !== null && $initialCapitalInput !== '') {
            $initialCapital = (float)str_replace(['.', ','], ['', '.'], $initialCapitalInput);
        } else {
            $initialCapital = null;
        }
        
        // Limita os anos para evitar abusos
        $allowedYears = [5, 10, 15, 20, 25, 30];
        if (!in_array($years, $allowedYears)) {
            $years = 10;
        }

        $portfolio = $this->portfolioModel->findById($id);
        if (!$portfolio) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Portfólio não encontrado']);
            exit;
        }

        $simulationModel = new SimulationResult();
        $latest = $simulationModel->getLatest($id);
        if (!$latest) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Simulação não encontrada']);
            exit;
        }

        $metrics = $latest;
        
        // Se não foi passado capital inicial, usa o valor final da simulação
        if ($initialCapital === null) {
            $initialCapital = isset($metrics['total_value']) ? (float)$metrics['total_value'] : (float)$metrics['final_value'];
        }
        
        $monthlyDeposit = 0;
        if (!empty($portfolio['deposit_amount'])) {
            $monthlyDeposit = (float)$portfolio['deposit_amount'];
            if ($portfolio['deposit_frequency'] == 'quarterly') {
                $monthlyDeposit /= 3;
            } elseif ($portfolio['deposit_frequency'] == 'biannual') {
                $monthlyDeposit /= 6;
            } elseif ($portfolio['deposit_frequency'] == 'annual') {
                $monthlyDeposit /= 12;
            }
        }

        $projectionService = new ProjectionService();
        $projectionRaw = $projectionService->calculateProjection(
            $initialCapital, 
            $metrics['strategy_annual_return'], 
            $monthlyDeposit,
            $years,
            $latest['simulation_date'] // Ponto de partida = fim da simulação
        );
        
        $chartData = $projectionService->formatProjectionChart($projectionRaw);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'chart' => $chartData,
            'final_value' => (float)end($projectionRaw)['total_value'],
            'total_invested' => (float)end($projectionRaw)['total_invested'],
            'years' => $years
        ]);
        exit;
    }

    /**
     * POST: Processa a exclusão com lógica de permissão Admin
     */
    public function delete() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Session::setFlash('error', 'Método de requisição inválido.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de segurança inválido.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $id = $this->params['id'] ?? null;
        $portfolio = $this->portfolioModel->findById($id);

        if (!$portfolio) {
            Session::setFlash('error', 'Portfólio não encontrado.');
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
            try {
                // Sênior: Se for admin, passa null para o user_id para ignorar restrição de dono
                $deleteUserId = Auth::isAdmin() ? null : $_SESSION['user_id'];
                $this->portfolioModel->delete($id, $deleteUserId);
                Session::setFlash('success', 'Portfólio removido com sucesso.');
            } catch (Exception $e) {
                error_log("Erro ao deletar portfolio $id: " . $e->getMessage());
                Session::setFlash('error', 'Ocorreu um erro ao tentar excluir o portfólio. Verifique se ele possui dependências complexas.');
            }
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

    public function quickUpdate() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
                exit;
            }
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token de segurança inválido']);
                exit;
            }
            Session::setFlash('error', 'Token de segurança inválido');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }

        $portfolio = $this->portfolioModel->findById($id);

        // Verifica permissões
        if (!$portfolio || ($portfolio['user_id'] != $_SESSION['user_id'] && !Auth::isAdmin())) {
            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                exit;
            }
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Verifica se é portfólio do sistema (apenas admin pode editar)
        if ($portfolio['is_system_default'] && !Auth::isAdmin()) {
            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Portfólios do sistema só podem ser editados por administradores']);
                exit;
            }
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }

        // Valida as alocações
        if (!isset($_POST['assets']) || !is_array($_POST['assets'])) {
            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Dados de alocação inválidos']);
                exit;
            }
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }

        // Verificação de limite de ativos para o Plano Starter no quickUpdate
        if (!Auth::isPro()) {
            if (count($_POST['assets']) > 5) {
                if (isAjax()) {
                    if (ob_get_length()) ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'O Plano Starter permite no máximo 5 ativos por carteira.']);
                    exit;
                }
                Session::setFlash('warning', 'O Plano Starter permite no máximo 5 ativos por carteira.');
                header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
                exit;
            }
        }

        $total = 0;
        $assetsToUpdate = [];
        foreach ($_POST['assets'] as $assetId => $allocation) {
            $allocation = floatval($allocation);
            $total += $allocation;
            $assetsToUpdate[$assetId] = [
                'allocation' => $allocation,
                'performance_factor' => 1.0 // Mantém o fator padrão na edição rápida
            ];
        }

        // Valida se a soma é 100%
        if (abs($total - 100) > 0.01) {
            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'A soma das alocações deve ser 100%']);
                exit;
            }
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }

        // Atualiza as alocações
        try {
            logActivity("Iniciando quickUpdate para portfólio $id", $_SESSION['user_id'] ?? null);
            
            $this->portfolioModel->updateAssets($id, $assetsToUpdate);
            
            logActivity("Alocações atualizadas para portfólio $id. Iniciando simulação.", $_SESSION['user_id'] ?? null);
            
            if (class_exists('BacktestService')) {
                logActivity("BacktestService encontrado. Instanciando...", $_SESSION['user_id'] ?? null);
                $backtestService = new BacktestService();
                logActivity("Executando simulação para portfólio $id...", $_SESSION['user_id'] ?? null);
                $result = $backtestService->runSimulation($id);
                logActivity("Simulação concluída. Resultado: " . ($result['success'] ? 'Sucesso' : 'Falha - ' . ($result['message'] ?? 'sem mensagem')), $_SESSION['user_id'] ?? null);
                $successMsg = 'Alocações atualizadas e simulação executada!';
                $errorMsg = 'Alocações atualizadas, mas a simulação não pôde ser executada: ';
            } else {
                logActivity("BacktestService NÃO encontrado!", $_SESSION['user_id'] ?? null);
                $result = ['success' => false, 'message' => 'Serviço de simulação indisponível'];
                $successMsg = 'Alocações atualizadas!';
                $errorMsg = 'Alocações atualizadas, mas a simulação não pôde ser executada: ';
            }

            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => $successMsg,
                        'simulation_date' => $result['effective_end'] ?? null
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => $errorMsg . ($result['message'] ?? ''),
                        'warning' => true
                    ]);
                }
            } else {
                Session::setFlash('success', $result['success'] ? $successMsg : $errorMsg . ($result['message'] ?? ''));
                header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            }
        } catch (Exception $e) {
            if (isAjax()) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
            } else {
                Session::setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
                header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            }
        }
        exit;
    }
}
?>