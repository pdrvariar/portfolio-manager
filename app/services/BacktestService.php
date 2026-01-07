<?php
class BacktestService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runSimulation($portfolioId) {
        $portfolio = $this->getPortfolioData($portfolioId);
        $assets = $this->getPortfolioAssetsData($portfolioId);
        
        if (!$portfolio || empty($assets)) {
            return ['success' => false, 'message' => 'Dados do portfólio não encontrados.'];
        }

        // SÊNIOR: Cálculo do horizonte comum de dados
        $effectiveDates = $this->calculateEffectiveRange($assets, $portfolio['start_date'], $portfolio['end_date']);
        
        if (!$effectiveDates['valid']) {
            return ['success' => false, 'message' => 'Nenhum dos ativos possui dados em comum no período selecionado.'];
        }

        $historicalData = $this->loadHistoricalData($assets, $effectiveDates['start'], $effectiveDates['end']); 

        if (empty($historicalData)) {
            return ['success' => false, 'message' => 'Dados históricos insuficientes para o período.'];
        }

        $results = $this->executeBacktest($portfolio, $assets, $historicalData);
        $metrics = $this->calculateMetrics($results);
        $chartData = $this->generateCharts($results, $assets);

        $chartData['audit_log'] = $results;

        // Dentro do runSimulation, onde você chama o saveResults:
        $simulationId = $this->saveResults($portfolioId, $metrics, $chartData, $effectiveDates['end']);

        $this->saveAssetDetails($simulationId, $results, $assets);
        
        return [
            'success' => true,
            'simulation_id' => $simulationId,
            'metrics' => $metrics,
            'chart_data' => $chartData,
            'effective_end' => $effectiveDates['end'] // Retornamos para informar o usuário
        ];
    }

    private function calculateEffectiveRange($assets, $requestedStart, $requestedEnd) {
        $maxStart = $requestedStart;
        $minEnd = $requestedEnd ?: date('Y-m-d');

        foreach ($assets as $asset) {
            $sql = "SELECT MIN(reference_date) as min_d, MAX(reference_date) as max_d 
                    FROM asset_historical_data WHERE asset_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$asset['asset_id']]);
            $range = $stmt->fetch();

            // O início da simulação deve ser o MAIOR dos inícios (quem começou por último)
            if ($range['min_d'] > $maxStart) $maxStart = $range['min_d'];
            
            // O fim da simulação deve ser o MENOR dos fins (quem terminou primeiro)
            if ($range['max_d'] < $minEnd) $minEnd = $range['max_d'];
        }

        return [
            'valid' => ($maxStart <= $minEnd),
            'start' => $maxStart,
            'end'   => $minEnd
        ];
    }    

    private function getPortfolioData($id) {
        $sql = "SELECT * FROM portfolios WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function getPortfolioAssetsData($id) {
        // ADICIONADO sa.asset_type e sa.currency
        $sql = "SELECT pa.*, sa.name, sa.currency, sa.code, sa.asset_type 
                FROM portfolio_assets pa 
                JOIN system_assets sa ON pa.asset_id = sa.id 
                WHERE pa.portfolio_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    // Novo método para buscar os dados de câmbio
    private function getExchangeRateData($start, $end) {
        $sql = "SELECT reference_date, price FROM asset_historical_data ahd
                JOIN system_assets sa ON ahd.asset_id = sa.id
                WHERE sa.code = 'USD-BRL' AND reference_date BETWEEN ? AND ?
                ORDER BY reference_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$start, $end]);
        $results = $stmt->fetchAll();
        
        $fxData = [];
        foreach ($results as $row) {
            $fxData[$row['reference_date']] = (float)$row['price'];
        }
        return $fxData;
    }    

    private function loadHistoricalData($assets, $start, $end) {
        $data = [];
        foreach ($assets as $asset) {
            $sql = "SELECT reference_date, price FROM asset_historical_data 
                    WHERE asset_id = ? AND reference_date BETWEEN ? AND ?
                    ORDER BY reference_date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$asset['asset_id'], $start, $end]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $data[$row['reference_date']][$asset['asset_id']] = $row['price'];
            }
        }
        ksort($data);
        return $data;
    }


    private function executeBacktest($portfolio, $assets, $historicalData) {
        $initialCapital = (float)$portfolio['initial_capital'];
        $portfolioCurrency = $portfolio['output_currency']; // BRL ou USD
        $rebalanceFreq = $this->parseRebalanceFrequency($portfolio['rebalance_frequency']);
        
        // Carrega dados de câmbio se houver mistura de moedas
        $fxData = $this->getExchangeRateData($portfolio['start_date'], $portfolio['end_date']);
        
        $results = [];
        $currentBalances = [];
        $dates = array_keys($historicalData);
        $lastPrices = [];
        $lastFxRate = null;

        // Inicialização do saldo
        foreach ($assets as $asset) {
            $currentBalances[$asset['asset_id']] = $initialCapital * ($asset['allocation_percentage'] / 100);
        }

        foreach ($dates as $index => $date) {
            $monthData = $historicalData[$date];
            $currentFxRate = $fxData[$date] ?? null;
            $totalMonthValue = 0;
            $assetValues = [];
            
            foreach ($assets as $asset) {
                $assetId = $asset['asset_id'];
                $dbValue = (float)($monthData[$assetId] ?? 0);
                $factor = (float)($asset['performance_factor'] ?? 1.0);
                $monthlyReturn = 0;

                // 1. Calcula o retorno na moeda original do ativo
                if ($asset['asset_type'] === 'TAXA_MENSAL') {
                    $monthlyReturn = ($dbValue * $factor) / 100;
                } else {
                    if (isset($lastPrices[$assetId]) && $lastPrices[$assetId] > 0) {
                        $monthlyReturn = (($dbValue / $lastPrices[$assetId]) - 1) * $factor;
                    }
                    $lastPrices[$assetId] = $dbValue;
                }

                // 2. APLICA O CÂMBIO (Se Moedas forem diferentes)
                // Se Portfólio é BRL e Ativo é USD: aplica a variação do Dólar
                if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $lastFxRate > 0 && $currentFxRate > 0) {
                    $fxVariation = ($currentFxRate / $lastFxRate) - 1;
                    // Retorno Total = (1 + retorno_ativo) * (1 + variação_cambio) - 1
                    $monthlyReturn = (1 + $monthlyReturn) * (1 + $fxVariation) - 1;
                }
                // Se Portfólio é USD e Ativo é BRL: inverte o câmbio
                elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $lastFxRate > 0 && $currentFxRate > 0) {
                    $fxVariation = ($lastFxRate / $currentFxRate) - 1;
                    $monthlyReturn = (1 + $monthlyReturn) * (1 + $fxVariation) - 1;
                }

                $currentBalances[$assetId] *= (1 + $monthlyReturn);
                $assetValues[$assetId] = $currentBalances[$assetId];
                $totalMonthValue += $currentBalances[$assetId];
            }
            
            $lastFxRate = $currentFxRate;

            // 3. Lógica de Rebalanceamento
            $wasRebalanced = false;
            $trades = [];

            if ($rebalanceFreq > 0 && $index > 0 && $index % $rebalanceFreq == 0) {
                $wasRebalanced = true;
                foreach ($assets as $asset) {
                    $assetId = $asset['asset_id'];
                    $preValue = $currentBalances[$assetId]; 
                    $targetAllocation = (float)$asset['allocation_percentage'] / 100;
                    $postValue = $totalMonthValue * $targetAllocation;
                    
                    $trades[$assetId] = [
                        'pre_value' => $preValue,
                        'post_value' => $postValue,
                        'delta' => $postValue - $preValue
                    ];
                    
                    // ATUALIZAÇÃO CRUCIAL:
                    $currentBalances[$assetId] = $postValue;
                    
                    // CORREÇÃO: Atualiza o assetValues para refletir o saldo PÓS-rebalanceamento
                    $assetValues[$assetId] = $postValue; 
                }
            }

            $results[$date] = [
                'total_value' => $totalMonthValue, 
                'asset_values' => $assetValues, // Agora levará o valor corrigido
                'rebalanced' => $wasRebalanced,
                'trades' => $trades
            ];
        }
        return $results;
    }

    private function calculateMetrics($results) {
        $values = array_column($results, 'total_value');
        $initial = $values[0];
        $final = end($values);
        $numMonths = count($values);
        
        // 1. Retorno Total Absoluto
        $totalReturnDecimal = ($final - $initial) / $initial;
        
        // 2. Cálculo de Retornos Mensais para Volatilidade
        $returns = [];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i-1] > 0) {
                $returns[] = ($values[$i] - $values[$i-1]) / $values[$i-1];
            }
        }

        // 3. CAGR (Apenas anualizamos se o período for >= 12 meses)
        // Se o período for curto, o CAGR é igual ao Retorno Total (Prática de Mercado)
        if ($numMonths >= 12) {
            $annualReturn = pow(1 + $totalReturnDecimal, 12 / $numMonths) - 1;
        } else {
            $annualReturn = $totalReturnDecimal; 
        }
        
        $vol = $this->calculateVolatility($returns);
        
        // 4. Sharpe Ratio Sênior (Exemplo com Selic fixa em 0.10 para ilustrar)
        // O ideal é buscar o valor real da SELIC no seu banco de dados
        $riskFreeRate = 0.10; 
        $excessReturn = $annualReturn - $riskFreeRate;
        $sharpe = ($vol > 0) ? ($excessReturn / $vol) : 0;
        
        return [
            'total_return'  => $totalReturnDecimal * 100,
            'annual_return' => $annualReturn * 100,
            'volatility'    => $vol * 100,
            'sharpe_ratio'  => $sharpe,
            'is_short_period' => ($numMonths < 12), // Flag para a View
            'initial_value' => $initial,
            'final_value'   => $final
        ];
    }

    private function calculateVolatility($returns) {
        if (empty($returns)) return 0;
        $mean = array_sum($returns) / count($returns);
        $variance = array_reduce($returns, fn($acc, $val) => $acc + pow($val - $mean, 2), 0) / count($returns);
        return sqrt($variance) * sqrt(12);
    }

    private function calculateMaxDrawdown($values) {
        $max = 0; $drawdown = 0;
        foreach ($values as $v) {
            if ($v > $max) $max = $v;
            $dd = ($max - $v) / $max;
            if ($dd > $drawdown) $drawdown = $dd;
        }
        return $drawdown;
    }

    private function generateCharts($results, $assets) {
        $chartService = new ChartService();
        return [
            'value_chart' => $chartService->createValueChart($results),
            'composition_chart' => $chartService->createCompositionChart($results, $assets),
            'returns_chart' => $chartService->createAnnualReturnsChart($results)
        ];
    }

    private function saveResults($portfolioId, $metrics, $chartData, $endDate) {
        $sql = "INSERT INTO simulation_results (portfolio_id, simulation_date, total_value, annual_return, volatility, max_drawdown, sharpe_ratio, chart_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // Trocamos CURDATE() por ?
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $portfolioId, 
            $endDate, // Agora salvamos a data final real da simulação
            $metrics['final_value'], 
            $metrics['annual_return'], 
            $metrics['volatility'], 
            $metrics['max_drawdown'], 
            $metrics['sharpe_ratio'], 
            json_encode($chartData)
        ]);
        return $this->db->lastInsertId();
    }

    private function saveAssetDetails($simulationId, $results, $assets) {
        $year = date('Y', strtotime(array_key_last($results)));
        foreach ($assets as $asset) {
            $sql = "INSERT INTO simulation_asset_details (simulation_id, asset_id, year, annual_return) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$simulationId, $asset['asset_id'], $year, 0]);
        }
    }

    private function parseRebalanceFrequency($freq) {
        $map = ['never' => 0, 'monthly' => 1, 'quarterly' => 3, 'biannual' => 6, 'annual' => 12];
        return $map[strtolower($freq)] ?? 0;
    }
}