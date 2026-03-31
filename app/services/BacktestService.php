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

        //Historical data
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
        $portfolioCurrency = $portfolio['output_currency'];
        $rebalanceFreq = $this->parseRebalanceFrequency($portfolio['rebalance_frequency']);

        // NOVOS PARÂMETROS
        $simulationType = $portfolio['simulation_type'] ?? 'standard';
        $depositAmount = (float)($portfolio['deposit_amount'] ?? 0);
        $depositCurrency = $portfolio['deposit_currency'] ?? 'BRL';
        $depositFrequency = $portfolio['deposit_frequency'] ?? 'monthly';
        $strategicThreshold = (float)($portfolio['strategic_threshold'] ?? 0) / 100;
        $strategicDepositPercent = (float)($portfolio['strategic_deposit_percentage'] ?? 0) / 100;

        // Carrega dados de câmbio
        $fxEndDate = $portfolio['end_date'] ?? date('Y-m-d');
        $fxData = $this->getExchangeRateData($portfolio['start_date'], $fxEndDate);

        $results = [];
        $currentBalances = [];
        $dates = array_keys($historicalData);
        $lastPrices = [];
        $lastFxRate = null;

        // Variáveis para controle de aportes
        $previousMonthValue = $initialCapital;
        $totalDeposits = 0;

        // NOVO: Variáveis para cálculo do retorno real
        $portfolioWithoutDeposits = $initialCapital;
        $strategyOnlyValues = []; // Armazena valores excluindo aportes

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

                if ($asset['asset_type'] === 'TAXA_MENSAL') {
                    $monthlyReturn = ($dbValue * $factor) / 100;
                } else {
                    if (isset($lastPrices[$assetId]) && $lastPrices[$assetId] > 0) {
                        $monthlyReturn = (($dbValue / $lastPrices[$assetId]) - 1) * $factor;
                    }
                    $lastPrices[$assetId] = $dbValue;
                }

                // Aplica câmbio se necessário
                if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $lastFxRate > 0 && $currentFxRate > 0) {
                    $fxVariation = ($currentFxRate / $lastFxRate) - 1;
                    $monthlyReturn = (1 + $monthlyReturn) * (1 + $fxVariation) - 1;
                } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $lastFxRate > 0 && $currentFxRate > 0) {
                    $fxVariation = ($lastFxRate / $currentFxRate) - 1;
                    $monthlyReturn = (1 + $monthlyReturn) * (1 + $fxVariation) - 1;
                }

                $currentBalances[$assetId] *= (1 + $monthlyReturn);
                $assetValues[$assetId] = $currentBalances[$assetId];
                $totalMonthValue += $currentBalances[$assetId];
            }

            $lastFxRate = $currentFxRate;

            // Calcula o fator de retorno real dos ativos neste mês (média ponderada)
            // antes de qualquer aporte, para rastrear a performance pura da estratégia
            $monthlyReturnFactor = $previousMonthValue > 0 ? $totalMonthValue / $previousMonthValue : 1;
            $portfolioWithoutDeposits *= $monthlyReturnFactor;

            // ============================================
            // LÓGICA DE APORTES MENSAL (Tipo 1)
            // ============================================
            $depositThisMonth = 0;
            if ($simulationType === 'monthly_deposit' && $depositAmount > 0) {
                if ($this->shouldMakeDeposit($date, $portfolio['start_date'], $depositFrequency, $index)) {
                    // Converte o aporte para a moeda do portfólio se necessário
                    $depositInPortfolioCurrency = $depositAmount;

                    if ($depositCurrency !== $portfolioCurrency) {
                        if ($depositCurrency === 'USD' && $portfolioCurrency === 'BRL' && $currentFxRate) {
                            $depositInPortfolioCurrency = $depositAmount * $currentFxRate;
                        } elseif ($depositCurrency === 'BRL' && $portfolioCurrency === 'USD' && $currentFxRate) {
                            $depositInPortfolioCurrency = $depositAmount / $currentFxRate;
                        }
                    }

                    $depositThisMonth = $depositInPortfolioCurrency;
                    $totalDeposits += $depositThisMonth;

                    // Distribui o aporte proporcionalmente às alocações atuais
                    foreach ($assets as $asset) {
                        $assetId = $asset['asset_id'];
                        $currentAllocation = $currentBalances[$assetId] / max($totalMonthValue, 0.001);
                        $currentBalances[$assetId] += $depositThisMonth * $currentAllocation;
                        $assetValues[$assetId] = $currentBalances[$assetId];
                    }

                    $totalMonthValue += $depositThisMonth;
                }
            }

            // ============================================
            // LÓGICA DE APORTE ESTRATÉGICO (Tipo 2)
            // ============================================
            $strategicDepositThisMonth = 0;
            if ($simulationType === 'strategic_deposit' && $strategicThreshold > 0 && $strategicDepositPercent > 0) {
                if ($index > 0) {
                    // Calcula variação desde o mês anterior
                    $variation = ($totalMonthValue - $previousMonthValue) / max($previousMonthValue, 0.001);

                    // Se cair mais que o threshold, faz aporte
                    if ($variation <= -$strategicThreshold) {
                        $strategicDepositThisMonth = $totalMonthValue * $strategicDepositPercent;
                        $totalDeposits += $strategicDepositThisMonth;

                        // Distribui o aporte estratégico
                        foreach ($assets as $asset) {
                            $assetId = $asset['asset_id'];
                            $currentAllocation = $currentBalances[$assetId] / max($totalMonthValue, 0.001);
                            $currentBalances[$assetId] += $strategicDepositThisMonth * $currentAllocation;
                            $assetValues[$assetId] = $currentBalances[$assetId];
                        }

                        $totalMonthValue += $strategicDepositThisMonth;
                    }
                }
            }

            // Calcula variação da estratégia usando a carteira virtual (só retorno dos ativos)
            $strategyVariation = 0;
            if ($index > 0) {
                $prevStrategyValue = $results[$dates[$index - 1]]['strategy_value'];
                $strategyVariation = $prevStrategyValue > 0 ?
                    (($portfolioWithoutDeposits - $prevStrategyValue) / $prevStrategyValue) * 100 : 0;
            } else {
                // No primeiro mês, a variação é em relação ao capital inicial
                $strategyVariation = $initialCapital > 0 ?
                    (($portfolioWithoutDeposits - $initialCapital) / $initialCapital) * 100 : 0;
            }

            // Atualiza para próximo mês
            $previousMonthValue = $totalMonthValue;

            // Lógica de Rebalanceamento (existente)
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

                    $currentBalances[$assetId] = $postValue;
                    $assetValues[$assetId] = $postValue;
                }
            }

            $results[$date] = [
                'total_value' => $totalMonthValue,
                'asset_values' => $assetValues,
                'rebalanced' => $wasRebalanced,
                'trades' => $trades,
                'deposit_made' => $depositThisMonth + $strategicDepositThisMonth,
                'deposit_type' => $depositThisMonth > 0 ? 'monthly' : ($strategicDepositThisMonth > 0 ? 'strategic' : 'none'),
                'total_deposits_to_date' => $totalDeposits,
                'fx_rate' => $currentFxRate,
                // NOVO: Adiciona os valores da estratégia
                'strategy_value' => $portfolioWithoutDeposits,
                'strategy_variation' => $strategyVariation
            ];
        }

        // Adiciona informação de total de aportes ao resultado
        $results['_metadata'] = [
            'simulation_type' => $simulationType,
            'total_deposits' => $totalDeposits,
            'initial_capital' => $initialCapital
        ];

        return $results;
    }


    private function calculateMetrics($results) {
        $values = array_column($results, 'total_value');
        $initial = $values[0];
        $final = end($values);
        $numMonths = count($values);

        // Extrai metadados dos aportes
        $metadata = $results['_metadata'] ?? [];
        $totalDeposits = $metadata['total_deposits'] ?? 0;

        // Calcula ROI considerando aportes
        $totalInvested = $initial + $totalDeposits;
        $netProfit = $final - $totalInvested;
        $roi = $totalInvested > 0 ? ($netProfit / $totalInvested) * 100 : 0;

        // Retorno Total Absoluto (considerando aportes)
        $totalReturnDecimal = $initial > 0 ? ($final - $initial) / $initial : 0;

        // Cálculo de Retornos Mensais para Volatilidade (considerando aportes)
        $returns = [];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i-1] > 0) {
                $returns[] = ($values[$i] - $values[$i-1]) / $values[$i-1];
            }
        }

        // NOVO: Calcula métricas adicionais e performance real da estratégia (sem aportes)
        $valuesWithoutDeposits = [];
        $strategyReturns = [];
        $prevStrategyValue = $initial;

        foreach ($results as $date => $data) {
            if ($date !== '_metadata') {
                $currentStrategyValue = $data['strategy_value'] ?? $data['total_value'];
                $valuesWithoutDeposits[$date] = $currentStrategyValue;
                
                if ($prevStrategyValue > 0) {
                    $strategyReturns[] = ($currentStrategyValue - $prevStrategyValue) / $prevStrategyValue;
                }
                $prevStrategyValue = $currentStrategyValue;
            }
        }

        // Valor inicial e final sem aportes
        $initialWithoutDeposits = $initial;
        $finalWithoutDeposits = end($valuesWithoutDeposits);

        // Retorno real da estratégia (sem aportes)
        $strategyReturn = $initialWithoutDeposits > 0 ?
            (($finalWithoutDeposits - $initialWithoutDeposits) / $initialWithoutDeposits) * 100 : 0;
            
        // CAGR Real da Estratégia (considerando tempo, sem aportes)
        $strategyReturnDecimal = $strategyReturn / 100;
        if ($numMonths >= 12) {
            $strategyAnnualReturn = pow(1 + $strategyReturnDecimal, 12 / $numMonths) - 1;
        } else {
            $strategyAnnualReturn = $strategyReturnDecimal;
        }

        // CAGR do Portfólio (considerando aportes)
        if ($numMonths >= 12) {
            $portfolioAnnualReturn = pow(1 + $totalReturnDecimal, 12 / $numMonths) - 1;
        } else {
            $portfolioAnnualReturn = $totalReturnDecimal;
        }

        // Volatilidade da Estratégia (sem aportes)
        $vol = $this->calculateVolatility($strategyReturns);
        
        // Maior Alta e Maior Queda Mensal (da Estratégia)
        $maxMonthlyGain = !empty($strategyReturns) ? max($strategyReturns) : 0;
        $maxMonthlyLoss = !empty($strategyReturns) ? min($strategyReturns) : 0;
        
        $riskFreeRate = 0.10;
        $excessReturn = $strategyAnnualReturn - $riskFreeRate;
        $sharpe = ($vol > 0) ? ($excessReturn / $vol) : 0;

        // Juros obtidos (diferença entre valor final e total investido)
        $interestEarned = $final - $totalInvested;

        return [
            'total_return'  => $totalReturnDecimal * 100,
            'annual_return' => $portfolioAnnualReturn * 100, // Retorno anual do portfólio (com aportes)
            'strategy_annual_return' => $strategyAnnualReturn * 100, // NOVO: Retorno anual da estratégia (sem aportes)
            'volatility'    => $vol * 100,
            'sharpe_ratio'  => $sharpe,
            'is_short_period' => ($numMonths < 12),
            'initial_value' => $initial,
            'final_value'   => $final,
            'total_deposits' => $totalDeposits,
            'total_invested' => $totalInvested,
            'net_profit' => $netProfit,
            'roi' => $roi,
            'simulation_type' => $metadata['simulation_type'] ?? 'standard',
            // NOVAS MÉTRICAS
            'strategy_return' => $strategyReturn,
            'interest_earned' => $interestEarned,
            'final_without_deposits' => $finalWithoutDeposits,
            'max_monthly_gain' => $maxMonthlyGain * 100,
            'max_monthly_loss' => $maxMonthlyLoss * 100
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
            'returns_chart' => $chartService->createAnnualReturnsChart($results),
            'strategy_returns_chart' => $chartService->createAnnualStrategyReturnsChart($results),
            // NOVOS GRÁFICOS
            'strategy_performance_chart' => $chartService->createStrategyPerformanceChart($results),
            'interest_chart' => $chartService->createInterestChart($results)
        ];
    }

    private function saveResults($portfolioId, $metrics, $chartData, $endDate) {
        // CORREÇÃO: Agora salva todas as métricas, incluindo ROI e aportes
        $sql = "INSERT INTO simulation_results 
            (portfolio_id, simulation_date, total_value, annual_return, volatility, 
            max_drawdown, sharpe_ratio, chart_data, total_deposits, total_invested, 
            interest_earned, roi, strategy_return, strategy_annual_return, 
            max_monthly_gain, max_monthly_loss) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $portfolioId,
            $endDate,
            $metrics['final_value'],
            $metrics['annual_return'],
            $metrics['volatility'],
            $metrics['max_drawdown'],
            $metrics['sharpe_ratio'],
            json_encode($chartData),
            // NOVOS CAMPOS
            $metrics['total_deposits'] ?? 0,
            $metrics['total_invested'] ?? $metrics['initial_value'],
            $metrics['interest_earned'] ?? 0,
            $metrics['roi'] ?? 0,
            $metrics['strategy_return'] ?? 0,
            $metrics['strategy_annual_return'] ?? 0,
            $metrics['max_monthly_gain'] ?? 0,
            $metrics['max_monthly_loss'] ?? 0
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

    private function shouldMakeDeposit($date, $portfolioStartDate, $frequency, $currentMonthIndex) {
        $startDate = new DateTime($portfolioStartDate);
        $currentDate = new DateTime($date);

        // Calcula diferença em meses
        $interval = $startDate->diff($currentDate);
        $monthsDiff = ($interval->y * 12) + $interval->m;

        // Mapeia frequência para número de meses
        $frequencyMap = [
            'monthly' => 1,
            'bimonthly' => 2,
            'quarterly' => 3,
            'biannual' => 6,
            'annual' => 12
        ];

        $freqMonths = $frequencyMap[$frequency] ?? 1;

        // Verifica se é um mês de aporte
        return $monthsDiff % $freqMonths == 0;
    }
}