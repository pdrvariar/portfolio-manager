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

        $historicalData = $this->loadHistoricalData($assets, $portfolio['start_date'], $portfolio['end_date']);
        
        if (empty($historicalData)) {
            return ['success' => false, 'message' => 'Dados históricos insuficientes para o período.'];
        }

        $results = $this->executeBacktest($portfolio, $assets, $historicalData);
        $metrics = $this->calculateMetrics($results);
        $chartData = $this->generateCharts($results, $assets);
        
        $simulationId = $this->saveResults($portfolioId, $metrics, $chartData);
        $this->saveAssetDetails($simulationId, $results, $assets);
        
        return [
            'success' => true,
            'simulation_id' => $simulationId,
            'metrics' => $metrics,
            'chart_data' => $chartData
        ];
    }

    private function getPortfolioData($id) {
        $sql = "SELECT * FROM portfolios WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function getPortfolioAssetsData($id) {
        // ADICIONADO sa.name à consulta
        $sql = "SELECT pa.*, sa.name, sa.currency, sa.code 
                FROM portfolio_assets pa 
                JOIN system_assets sa ON pa.asset_id = sa.id 
                WHERE pa.portfolio_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll();
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
        $initialCapital = $portfolio['initial_capital'];
        $rebalanceFreq = $this->parseRebalanceFrequency($portfolio['rebalance_frequency']);
        $results = [];
        $quantities = [];
        $dates = array_keys($historicalData);
        
        // Alocação Inicial
        foreach ($assets as $asset) {
            // Pega o preço da primeira data disponível nos dados carregados
            $price = $historicalData[$dates[0]][$asset['asset_id']] ?? 0;
            
            // Se não encontrar preço, a simulação não pode continuar para este ativo
            if ($price <= 0) {
                throw new Exception("Preço não encontrado para o ativo {$asset['code']} na data de início.");
            }
            
            $targetValue = $initialCapital * $asset['allocation_percentage'];
            $quantities[$asset['asset_id']] = $targetValue / $price;
        }

        foreach ($dates as $index => $date) {
            $monthPrices = $historicalData[$date];
            $totalMonthValue = 0;
            $assetValues = [];
            
            foreach ($assets as $asset) {
                $price = $monthPrices[$asset['asset_id']] ?? 0;
                $val = $quantities[$asset['asset_id']] * $price * ($asset['performance_factor'] ?? 1);
                $assetValues[$asset['asset_id']] = $val;
                $totalMonthValue += $val;
            }
            
            // Lógica de Rebalanceamento
            if ($rebalanceFreq > 0 && $index > 0 && $index % $rebalanceFreq == 0) {
                foreach ($assets as $asset) {
                    $price = $monthPrices[$asset['asset_id']] ?? 1;
                    $targetValue = $totalMonthValue * $asset['allocation_percentage'];
                    $quantities[$asset['asset_id']] = $targetValue / $price;
                }
            }
            
            $results[$date] = ['total_value' => $totalMonthValue, 'asset_values' => $assetValues];
        }
        return $results;
    }

    private function calculateMetrics($results) {
        $values = array_column($results, 'total_value');
        $initial = $values[0];
        $final = end($values);
        
        // CORREÇÃO: Cálculo de Retorno Total Real
        $totalReturnDecimal = ($final - $initial) / $initial;
        
        $returns = [];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i-1] > 0) {
                $returns[] = ($values[$i] - $values[$i-1]) / $values[$i-1];
            }
        }

        $numMonths = count($values);
        // CORREÇÃO: Retorno Anualizado (CAGR)
        $annualReturn = (pow(1 + $totalReturnDecimal, 12 / $numMonths) - 1);
        
        $vol = $this->calculateVolatility($returns);
        
        // CORREÇÃO: Sharpe Ratio (Considerando taxa livre de risco zero para simplificar)
        // O Sharpe deve usar o retorno ANUAL / volatilidade ANUAL
        $sharpe = ($vol > 0) ? ($annualReturn / $vol) : 0;
        
        return [
            'total_return' => $totalReturnDecimal * 100, // Agora deve mostrar o valor real
            'annual_return' => $annualReturn * 100,
            'volatility' => $vol * 100,
            'max_drawdown' => $this->calculateMaxDrawdown($values) * 100,
            'sharpe_ratio' => $sharpe,
            'initial_value' => $initial,
            'final_value' => $final
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

    private function saveResults($portfolioId, $metrics, $chartData) {
        $sql = "INSERT INTO simulation_results (portfolio_id, simulation_date, total_value, annual_return, volatility, max_drawdown, sharpe_ratio, chart_data) 
                VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId, $metrics['final_value'], $metrics['annual_return'], $metrics['volatility'], $metrics['max_drawdown'], $metrics['sharpe_ratio'], json_encode($chartData)]);
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