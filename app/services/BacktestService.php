<?php
class BacktestService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runSimulation($portfolioId) {
        // Buscar dados do portfólio
        $portfolio = $this->getPortfolioData($portfolioId);
        $assets = $this->getPortfolioAssetsData($portfolioId);
        
        // Carregar dados históricos
        $historicalData = $this->loadHistoricalData($assets, $portfolio['start_date'], $portfolio['end_date']);
        
        // Executar backtest
        $results = $this->executeBacktest($portfolio, $assets, $historicalData);
        
        // Calcular métricas
        $metrics = $this->calculateMetrics($results);
        
        // Gerar gráficos
        $chartData = $this->generateCharts($results, $assets);
        
        // Salvar resultados
        $simulationId = $this->saveResults($portfolioId, $metrics, $chartData);
        $this->saveAssetDetails($simulationId, $results, $assets);
        
        return [
            'success' => true,
            'simulation_id' => $simulationId,
            'metrics' => $metrics,
            'chart_data' => $chartData
        ];
    }
    
    private function executeBacktest($portfolio, $assets, $historicalData) {
        $initialCapital = $portfolio['initial_capital'];
        $rebalanceFreq = $this->parseRebalanceFrequency($portfolio['rebalance_frequency']);
        $outputCurrency = $portfolio['output_currency'];
        
        // Inicializar variáveis
        $results = [];
        $quantities = [];
        $totalValue = $initialCapital;
        
        // Ordenar datas
        $dates = array_keys($historicalData);
        sort($dates);
        
        // Distribuição inicial
        foreach ($assets as $asset) {
            $price = $this->getConvertedPrice($asset['id'], $historicalData[$dates[0]], $outputCurrency);
            $allocation = $asset['allocation_percentage'];
            $targetValue = $initialCapital * $allocation;
            $quantities[$asset['id']] = $targetValue / $price;
        }
        
        // Executar para cada período
        foreach ($dates as $index => $date) {
            $monthData = $historicalData[$date];
            
            // Calcular valor atual
            $assetValues = [];
            $totalMonthValue = 0;
            
            foreach ($assets as $asset) {
                $price = $this->getConvertedPrice($asset['id'], $monthData, $outputCurrency);
                $value = $quantities[$asset['id']] * $price;
                $assetValues[$asset['id']] = $value;
                $totalMonthValue += $value;
            }
            
            // Verificar se é período de rebalanceamento
            $rebalance = false;
            if ($rebalanceFreq > 0 && $index % $rebalanceFreq == 0) {
                $rebalance = true;
                
                // Rebalancear
                foreach ($assets as $asset) {
                    $allocation = $asset['allocation_percentage'];
                    $targetValue = $totalMonthValue * $allocation;
                    $currentValue = $assetValues[$asset['id']];
                    
                    if ($currentValue > 0) {
                        $price = $this->getConvertedPrice($asset['id'], $monthData, $outputCurrency);
                        $adjustment = ($targetValue - $currentValue) / $price;
                        $quantities[$asset['id']] += $adjustment;
                    }
                }
            }
            
            // Armazenar resultados do mês
            $results[$date] = [
                'total_value' => $totalMonthValue,
                'asset_values' => $assetValues,
                'rebalance' => $rebalance,
                'quantities' => $quantities
            ];
        }
        
        return $results;
    }
    
    private function calculateMetrics($results) {
        $dates = array_keys($results);
        $values = array_column($results, 'total_value');
        
        // Calcular retornos mensais
        $returns = [];
        for ($i = 1; $i < count($values); $i++) {
            $returns[] = ($values[$i] - $values[$i-1]) / $values[$i-1];
        }
        
        // Métricas
        $initialValue = $values[0];
        $finalValue = end($values);
        $totalReturn = ($finalValue - $initialValue) / $initialValue;
        
        $numYears = (strtotime(end($dates)) - strtotime($dates[0])) / (365 * 24 * 3600);
        $annualReturn = pow(1 + $totalReturn, 1/$numYears) - 1;
        
        $volatility = $this->calculateVolatility($returns);
        $maxDrawdown = $this->calculateMaxDrawdown($values);
        $sharpeRatio = $this->calculateSharpeRatio($annualReturn, $volatility);
        
        return [
            'total_return' => $totalReturn * 100,
            'annual_return' => $annualReturn * 100,
            'volatility' => $volatility * 100,
            'max_drawdown' => $maxDrawdown * 100,
            'sharpe_ratio' => $sharpeRatio,
            'initial_value' => $initialValue,
            'final_value' => $finalValue
        ];
    }
    
    private function generateCharts($results, $assets) {
        $chartService = new ChartService();
        
        // Gráfico de evolução do valor total
        $valueChart = $chartService->createValueChart($results);
        
        // Gráfico de composição por ano
        $compositionChart = $chartService->createCompositionChart($results, $assets);
        
        // Gráfico de retornos anuais
        $returnsChart = $chartService->createAnnualReturnsChart($results);
        
        return [
            'value_chart' => $valueChart,
            'composition_chart' => $compositionChart,
            'returns_chart' => $returnsChart
        ];
    }
    
    private function saveResults($portfolioId, $metrics, $chartData) {
        $sql = "INSERT INTO simulation_results 
                (portfolio_id, simulation_date, total_value, annual_return, 
                volatility, max_drawdown, sharpe_ratio, chart_data) 
                VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $portfolioId,
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
        // Agrupar resultados por ano
        $annualResults = [];
        
        foreach ($results as $date => $data) {
            $year = date('Y', strtotime($date));
            
            if (!isset($annualResults[$year])) {
                $annualResults[$year] = [];
            }
            
            $annualResults[$year][] = $data;
        }
        
        // Calcular retorno anual por ativo
        foreach ($annualResults as $year => $monthlyData) {
            $firstMonth = reset($monthlyData);
            $lastMonth = end($monthlyData);
            
            foreach ($assets as $asset) {
                $assetId = $asset['id'];
                
                $startValue = $firstMonth['asset_values'][$assetId];
                $endValue = $lastMonth['asset_values'][$assetId];
                
                if ($startValue > 0) {
                    $annualReturn = ($endValue - $startValue) / $startValue;
                    
                    $sql = "INSERT INTO simulation_asset_details 
                            (simulation_id, asset_id, year, annual_return) 
                            VALUES (?, ?, ?, ?)";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$simulationId, $assetId, $year, $annualReturn * 100]);
                }
            }
        }
    }
    
    private function parseRebalanceFrequency($freq) {
        $map = [
            'never' => 0,
            'monthly' => 1,
            'quarterly' => 3,
            'biannual' => 6,
            'annual' => 12
        ];
        
        return $map[strtolower($freq)] ?? 1;
    }
}
?>