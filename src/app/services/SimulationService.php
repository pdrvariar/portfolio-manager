<?php
require_once __DIR__ . '/../models/AssetHistory.php';
require_once __DIR__ . '/../models/ExchangeRate.php';

class SimulationService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function runSimulation($portfolioId, $userId) {
        $portfolioModel = new Portfolio();
        $portfolio = $portfolioModel->findWithAssets($portfolioId);
        
        if (!$portfolio || ($portfolio['user_id'] != $userId && !$portfolio['is_default'])) {
            throw new Exception("Portfolio not found or access denied");
        }
        
        // Gerar ID único para execução
        $executionId = uniqid('sim_', true);
        
        // Salvar status inicial
        $stmt = $this->db->prepare("
            INSERT INTO simulations 
            (portfolio_id, user_id, status, execution_id)
            VALUES (?, ?, 'RUNNING', ?)
        ");
        $stmt->execute([$portfolioId, $userId, $executionId]);
        $simulationId = $this->db->lastInsertId();
        
        // Executar simulação em background (usando task queue ou execução assíncrona)
        $this->runAsyncSimulation($simulationId, $portfolio);
        
        return [
            'execution_id' => $executionId,
            'simulation_id' => $simulationId,
            'status' => 'RUNNING'
        ];
    }
    
    private function runAsyncSimulation($simulationId, $portfolio) {
        // Em produção, usar RabbitMQ, Redis ou sistema de filas
        // Aqui usaremos execução em thread separada via exec()
        
        $scriptPath = __DIR__ . '/../../scripts/run_simulation.php';
        $command = "php " . escapeshellarg($scriptPath) . " " . $simulationId . " > /dev/null 2>&1 &";
        exec($command);
    }
    
    public function calculatePortfolioReturns($portfolio, $startDate, $endDate = null) {
        $assetHistoryModel = new AssetHistory();
        $exchangeModel = new ExchangeRate();
        
        $results = [];
        $dates = $this->getDateRange($startDate, $endDate);
        
        // Inicializar quantidades
        $quantities = [];
        $initialValues = [];
        
        foreach ($portfolio['assets'] as $asset) {
            $price = $assetHistoryModel->getPriceAtDate($asset['id'], $startDate);
            if (!$price) continue;
            
            $convertedPrice = $this->convertCurrency(
                $price, 
                $asset['currency'], 
                $portfolio['output_currency'],
                $startDate
            );
            
            $targetValue = $portfolio['initial_capital'] * $asset['allocation'];
            $quantities[$asset['id']] = $targetValue / $convertedPrice;
            $initialValues[$asset['id']] = $targetValue;
        }
        
        // Calcular para cada data
        foreach ($dates as $date) {
            $row = ['date' => $date];
            $totalValue = 0;
            
            foreach ($portfolio['assets'] as $asset) {
                $price = $assetHistoryModel->getPriceAtDate($asset['id'], $date);
                if (!$price) {
                    $row['value_' . $asset['code']] = null;
                    continue;
                }
                
                $convertedPrice = $this->convertCurrency(
                    $price,
                    $asset['currency'],
                    $portfolio['output_currency'],
                    $date
                );
                
                $value = $quantities[$asset['id']] * $convertedPrice;
                $totalValue += $value;
                
                $row['value_' . $asset['code']] = $value;
                $row['price_' . $asset['code']] = $convertedPrice;
            }
            
            $row['total_value'] = $totalValue;
            $row['return'] = ($totalValue / $portfolio['initial_capital'] - 1) * 100;
            
            $results[] = $row;
        }
        
        return $results;
    }
    
    private function getDateRange($startDate, $endDate = null) {
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }
        
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 month', $current);
        }
        
        return $dates;
    }
    
    private function convertCurrency($amount, $fromCurrency, $toCurrency, $date) {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        
        $exchangeModel = new ExchangeRate();
        $rate = $exchangeModel->getRate($fromCurrency, $toCurrency, $date);
        
        if (!$rate) {
            // Tentar conversão via USD intermediário
            $rate1 = $exchangeModel->getRate($fromCurrency, 'USD', $date);
            $rate2 = $exchangeModel->getRate('USD', $toCurrency, $date);
            
            if ($rate1 && $rate2) {
                $rate = $rate1 * $rate2;
            } else {
                throw new Exception("Exchange rate not available for $fromCurrency to $toCurrency on $date");
            }
        }
        
        return $amount * $rate;
    }
    
    public function calculateMetrics($results, $initialCapital) {
        if (empty($results)) {
            return [];
        }
        
        $returns = array_column($results, 'return');
        $values = array_column($results, 'total_value');
        
        // Retorno total
        $finalValue = end($values);
        $totalReturn = ($finalValue / $initialCapital - 1) * 100;
        
        // Retorno anualizado
        $numYears = count($results) / 12;
        $annualReturn = $numYears > 0 ? pow(1 + $totalReturn/100, 1/$numYears) - 1 : 0;
        
        // Volatilidade (mensal)
        $volatility = $this->calculateVolatility($returns);
        
        // Máximo drawdown
        $maxDrawdown = $this->calculateMaxDrawdown($values);
        
        // Sharpe ratio (assumindo risk-free = 0 por simplicidade)
        $sharpe = count($returns) > 0 ? (array_sum($returns) / count($returns)) / $volatility : 0;
        
        return [
            'total_return' => round($totalReturn, 2),
            'annual_return' => round($annualReturn * 100, 2),
            'volatility' => round($volatility, 2),
            'max_drawdown' => round($maxDrawdown, 2),
            'sharpe_ratio' => round($sharpe, 2),
            'final_value' => round($finalValue, 2)
        ];
    }
    
    private function calculateVolatility($returns) {
        if (count($returns) < 2) return 0;
        
        $mean = array_sum($returns) / count($returns);
        $variance = 0;
        
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        
        $variance /= (count($returns) - 1);
        return sqrt($variance);
    }
    
    private function calculateMaxDrawdown($values) {
        $peak = $values[0];
        $maxDrawdown = 0;
        
        foreach ($values as $value) {
            if ($value > $peak) {
                $peak = $value;
            }
            
            $drawdown = ($peak - $value) / $peak * 100;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return $maxDrawdown;
    }
}