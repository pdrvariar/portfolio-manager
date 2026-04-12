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
        // ADICIONADO sa.asset_type, sa.currency e sa.is_cash
        $sql = "SELECT pa.*, sa.name, sa.currency, sa.code, sa.asset_type, sa.is_cash 
                FROM portfolio_assets pa 
                JOIN system_assets sa ON pa.asset_id = sa.id 
                WHERE pa.portfolio_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    // Novo método para buscar os dados de câmbio
    private function getExchangeRateData($start, $end) {
        $fxData = [];
        
        // Busca taxa base (imediatamente anterior ao início)
        $sql = "SELECT reference_date, price FROM asset_historical_data ahd
                JOIN system_assets sa ON ahd.asset_id = sa.id
                WHERE sa.code = 'USD-BRL' AND reference_date < ?
                ORDER BY reference_date DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$start]);
        $baseRow = $stmt->fetch();
        if ($baseRow) {
            $fxData[$baseRow['reference_date']] = (float)$baseRow['price'];
        }

        $sql = "SELECT reference_date, price FROM asset_historical_data ahd
                JOIN system_assets sa ON ahd.asset_id = sa.id
                WHERE sa.code = 'USD-BRL' AND reference_date BETWEEN ? AND ?
                ORDER BY reference_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$start, $end]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $row) {
            $fxData[$row['reference_date']] = (float)$row['price'];
        }
        return $fxData;
    }

    private function loadHistoricalData($assets, $start, $end) {
        $data = [];
        foreach ($assets as $asset) {
            // Buscamos um registro a mais ANTES da data de início para servir de base de cálculo
            $sql = "SELECT reference_date, price FROM asset_historical_data 
                    WHERE asset_id = ? AND reference_date < ?
                    ORDER BY reference_date DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$asset['asset_id'], $start]);
            $baseRow = $stmt->fetch();
            if ($baseRow) {
                $data[$baseRow['reference_date']][$asset['asset_id']] = $baseRow['price'];
            }

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
        $rebalanceType = $portfolio['rebalance_type'] ?? 'full'; // NOVO: Captura o tipo de rebalanceamento
        $rebalanceMargin = (float)($portfolio['rebalance_margin'] ?? 0) / 100; // NOVO: Margem para rebalanceamento
        $depositAmount = (float)($portfolio['deposit_amount'] ?? 0);
        $depositCurrency = $portfolio['deposit_currency'] ?? 'BRL';
        $depositFrequency = $portfolio['deposit_frequency'] ?? 'monthly';
        $strategicThreshold = (float)($portfolio['strategic_threshold'] ?? 0) / 100;
        $strategicDepositPercent = (float)($portfolio['strategic_deposit_percentage'] ?? 0) / 100;
        $depositInflationAdjusted = (bool)($portfolio['deposit_inflation_adjusted'] ?? false);
        $useCashAssetsForRebalance = (bool)($portfolio['use_cash_assets_for_rebalance'] ?? false);

        // Carrega dados de câmbio
        $fxEndDate = $portfolio['end_date'] ?? date('Y-m-d');
        $fxData = $this->getExchangeRateData($portfolio['start_date'], $fxEndDate);

        $results = [];
        $currentBalances = [];
        $currentQuantities = []; // Rastreia quantidades de cada ativo
        $dates = array_keys($historicalData);
        $lastPrices = [];
        $lastFxRate = null;

        // Variáveis para controle de aportes
        $previousMonthValue = $initialCapital;
        $totalDeposits = 0;

        // Variáveis para Caixa SELIC (tipos smart_deposit e selic_cash_deposit)
        $selicCash = 0.0;
        $selicRates = [];
        if (in_array($simulationType, ['smart_deposit', 'selic_cash_deposit'])) {
            $selicRates = $this->loadSelicRates($portfolio['start_date'], $fxEndDate);
        }

        $ipcaRates = [];
        if ($depositInflationAdjusted && $depositAmount > 0) {
            $ipcaRates = $this->loadIpcaRates($portfolio['start_date'], $fxEndDate);
        }

        // NOVO: Variáveis para cálculo do retorno real
        $portfolioWithoutDeposits = $initialCapital;
        $strategyOnlyValues = []; // Armazena valores excluindo aportes

        // Inicialização do saldo e quantidades
        // O primeiro registro em historicalData pode ser anterior à data de início (base de cálculo)
        $firstAvailableDate = $dates[0];
        $isFirstDateBeforeStart = ($firstAvailableDate < $portfolio['start_date']);

        foreach ($assets as $asset) {
            $assetId = $asset['asset_id'];
            $initialAssetValue = $initialCapital * ($asset['allocation_percentage'] / 100);
            $currentBalances[$assetId] = $initialAssetValue;
            
            // BUSCA PREÇO INICIAL
            $initialPrice = (float)($historicalData[$firstAvailableDate][$assetId] ?? 0);
            $initialFxRate = $fxData[$firstAvailableDate] ?? null;

            // Converter o preço inicial para a moeda do portfólio para calcular a quantidade correta
            $convertedInitialPrice = $initialPrice;
            if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $initialFxRate > 0) {
                $convertedInitialPrice = $initialPrice * $initialFxRate;
            } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $initialFxRate > 0) {
                $convertedInitialPrice = $initialPrice / $initialFxRate;
            }

            $currentQuantities[$assetId] = $convertedInitialPrice > 0 ? ($initialAssetValue / $convertedInitialPrice) : 0;
            
            // Se a primeira data for a base de cálculo, já preenchemos lastPrices
            if ($isFirstDateBeforeStart) {
                $lastPrices[$assetId] = $initialPrice;
            }
        }

        if ($isFirstDateBeforeStart) {
            $lastFxRate = $fxData[$firstAvailableDate] ?? null;
            // Removemos a data base da lista de iteração da simulação e re-indexamos
            // para que $index comece em 0, garantindo que a lógica de rebalanceamento
            // e de variação da estratégia funcione corretamente.
            array_shift($dates);
        }

        $prevDateForStrategy = null; // rastreia a data anterior sem depender de índice numérico
        $adjustedDepositAmount = $depositAmount;
        $accumulatedIpca = 1.0;

        foreach ($dates as $index => $date) {
            $monthData = $historicalData[$date];
            $currentFxRate = $fxData[$date] ?? null;
            $totalMonthValue = 0;
            $assetValues = [];
            $assetPrices = [];
            $assetRawPrices = [];
            $assetQuantities = [];
            $assetPurchases = []; // Detalha o que foi comprado no mês (aporte)

            foreach ($assets as $asset) {
                $assetId = $asset['asset_id'];
                $dbValue = (float)($monthData[$assetId] ?? 0);
                $factor = (float)($asset['performance_factor'] ?? 1.0);
                $monthlyReturn = 0;


                if ($asset['asset_type'] === 'TAXA_MENSAL' || $asset['asset_type'] === 'INFLACAO') {
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
                
                // Atualiza quantidade para ativos que não são taxa mensal ou inflação (onde quantidade faz sentido)
                // Se for taxa mensal ou inflação, a "quantidade" é o próprio saldo
                if ($asset['asset_type'] !== 'TAXA_MENSAL' && $asset['asset_type'] !== 'INFLACAO') {
                    // A quantidade não muda com a valorização do preço, apenas com aportes/rebal
                    // Mas precisamos garantir que ela esteja sincronizada com o saldo se houver arredondamentos
                    // Para fins de simulação, mantemos a quantidade e atualizamos o saldo.
                } else {
                    $currentQuantities[$assetId] = $currentBalances[$assetId];
                }

                $assetValues[$assetId] = $currentBalances[$assetId];
                $assetQuantities[$assetId] = $currentQuantities[$assetId];

                // NOVO: Preço convertido para a moeda do portfólio para exibição no log
                $convertedPrice = $dbValue;
                if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                    $convertedPrice = $dbValue * $currentFxRate;
                } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                    $convertedPrice = $dbValue / $currentFxRate;
                }
                $assetPrices[$assetId] = $convertedPrice;
                $assetRawPrices[$assetId] = $dbValue;

                $totalMonthValue += $currentBalances[$assetId];
            }

            $lastFxRate = $currentFxRate;

            // Aplica rendimento SELIC ao caixa (para tipos smart_deposit e selic_cash_deposit)
            $selicCashEarnings  = 0;
            $selicCashInjected  = 0;
            if ($selicCash > 0) {
                $selicCashBeforeInterest = $selicCash;
                $selicMonthlyRate = $selicRates[$date] ?? 0;
                $selicCash *= (1 + $selicMonthlyRate);
                $selicCashEarnings = $selicCash - $selicCashBeforeInterest;
            }

            // Total incluindo caixa SELIC (para tipos com caixa; para os demais, selicCash = 0)
            $totalWithCash = $totalMonthValue + $selicCash;

            // Atualiza inflação acumulada para o próximo aporte
            if ($depositInflationAdjusted) {
                $monthlyIpca = $ipcaRates[$date] ?? 0;
                $accumulatedIpca *= (1 + $monthlyIpca);
            }

            // Calcula a rentabilidade do mês para auditoria ANTES do aporte
            // para que a variação exibida seja a performance dos ativos
            $totalBeforeDeposit = $totalWithCash;

            // Calcula o fator de retorno real dos ativos neste mês (média ponderada)
            // antes de qualquer aporte, para rastrear a performance pura da estratégia
            $monthlyReturnFactor = $previousMonthValue > 0 ? $totalWithCash / $previousMonthValue : 1;
            $portfolioWithoutDeposits *= $monthlyReturnFactor;

            // ============================================
            // LÓGICA DE APORTES MENSAL (Tipo 1)
            // ============================================
            $depositThisMonth = 0;
            if ($simulationType === 'monthly_deposit' && $depositAmount > 0) {
                if ($this->shouldMakeDeposit($date, $portfolio['start_date'], $depositFrequency, $index)) {
                    // Se for o primeiro aporte (index 0), usamos o valor original.
                    // Nos demais, usamos o valor corrigido pela inflação acumulada ATÉ O MÊS ANTERIOR ao aporte.
                    // Como acumulamos o IPCA de cada mês ao final do loop, no momento do aporte
                    // o $accumulatedIpca contém a inflação dos meses anteriores.
                    $currentDepositAmount = $depositInflationAdjusted ? ($depositAmount * $accumulatedIpca) : $depositAmount;
                    
                    // Converte o aporte para a moeda do portfólio se necessário
                    $depositInPortfolioCurrency = $currentDepositAmount;

                    if ($depositCurrency !== $portfolioCurrency) {
                        if ($depositCurrency === 'USD' && $portfolioCurrency === 'BRL' && $currentFxRate) {
                            $depositInPortfolioCurrency = $currentDepositAmount * $currentFxRate;
                        } elseif ($depositCurrency === 'BRL' && $portfolioCurrency === 'USD' && $currentFxRate) {
                            $depositInPortfolioCurrency = $currentDepositAmount / $currentFxRate;
                        }
                    }

                    $depositThisMonth = $depositInPortfolioCurrency;
                    $totalDeposits += $depositThisMonth;

                    // Adiciona o aporte ao total e rebalanceia pelos percentuais-alvo
                    $newTotalAfterDeposit = $totalMonthValue + $depositThisMonth;
                    foreach ($assets as $asset) {
                        $assetId = $asset['asset_id'];
                        $targetAllocation = (float)$asset['allocation_percentage'] / 100;
                        $newBalance = $newTotalAfterDeposit * $targetAllocation;
                        $amountToAsset = $newBalance - $currentBalances[$assetId];

                        $currentBalances[$assetId] = $newBalance;

                        // Atualiza quantidade comprada/vendida
                        if ($asset['asset_type'] !== 'TAXA_MENSAL' && $asset['asset_type'] !== 'INFLACAO') {
                            $price = (float)($monthData[$assetId] ?? 0);
                            
                            // Converte o preço para a moeda do portfólio para calcular a quantidade correta
                            $convertedPrice = $price;
                            if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                                $convertedPrice = $price * $currentFxRate;
                            } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                                $convertedPrice = $price / $currentFxRate;
                            }

                            if ($convertedPrice > 0) {
                                $newQty = $newBalance / $convertedPrice;
                                $deltaQty = $newQty - $currentQuantities[$assetId];
                                $currentQuantities[$assetId] = $newQty;
                                $assetPurchases[$assetId] = [
                                    'amount' => $amountToAsset,
                                    'quantity' => $deltaQty,
                                    'price' => $convertedPrice
                                ];
                            }
                        } else {
                            $currentQuantities[$assetId] = $newBalance;
                            $assetPurchases[$assetId] = [
                                'amount' => $amountToAsset,
                                'quantity' => $amountToAsset,
                                'price' => 1.0
                            ];
                        }

                        $assetValues[$assetId] = $newBalance;
                        $assetQuantities[$assetId] = $currentQuantities[$assetId];
                    }

                    $totalMonthValue = $newTotalAfterDeposit;
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

                        // Adiciona o aporte ao total e rebalanceia pelos percentuais-alvo
                        $newTotalAfterStrategicDeposit = $totalMonthValue + $strategicDepositThisMonth;
                        foreach ($assets as $asset) {
                            $assetId = $asset['asset_id'];
                            $targetAllocation = (float)$asset['allocation_percentage'] / 100;
                            $newBalance = $newTotalAfterStrategicDeposit * $targetAllocation;
                            $amountToAsset = $newBalance - $currentBalances[$assetId];

                            $currentBalances[$assetId] = $newBalance;

                            // Atualiza quantidade comprada/vendida
                            if ($asset['asset_type'] !== 'TAXA_MENSAL' && $asset['asset_type'] !== 'INFLACAO') {
                                $price = (float)($monthData[$assetId] ?? 0);

                                // Converte o preço para a moeda do portfólio para calcular a quantidade correta
                                $convertedPrice = $price;
                                if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                                    $convertedPrice = $price * $currentFxRate;
                                } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                                    $convertedPrice = $price / $currentFxRate;
                                }

                                if ($convertedPrice > 0) {
                                    $newQty = $newBalance / $convertedPrice;
                                    $deltaQty = $newQty - $currentQuantities[$assetId];
                                    $currentQuantities[$assetId] = $newQty;
                                    $assetPurchases[$assetId] = [
                                        'amount' => $amountToAsset,
                                        'quantity' => $deltaQty,
                                        'price' => $convertedPrice
                                    ];
                                }
                            } else {
                                $currentQuantities[$assetId] = $newBalance;
                                $assetPurchases[$assetId] = [
                                    'amount' => $amountToAsset,
                                    'quantity' => $amountToAsset,
                                    'price' => 1.0
                                ];
                            }

                            $assetValues[$assetId] = $newBalance;
                            $assetQuantities[$assetId] = $currentQuantities[$assetId];
                        }

                        $totalMonthValue = $newTotalAfterStrategicDeposit;
                    }
                }
            }

            // ============================================
            // LÓGICA DE APORTE DIRECIONADO AO ALVO (Tipo 3)
            // Compra o ativo mais abaixo do percentual-alvo; sobra vai para Caixa SELIC
            // ============================================
            $smartDepositThisMonth = 0;
            if ($simulationType === 'smart_deposit' && $depositAmount > 0) {
                if ($this->shouldMakeDeposit($date, $portfolio['start_date'], $depositFrequency, $index)) {
                    $currentDepositAmount = $depositInflationAdjusted ? ($depositAmount * $accumulatedIpca) : $depositAmount;
                    $depositInPortfolioCurrency = $currentDepositAmount;
                    if ($depositCurrency !== $portfolioCurrency) {
                        if ($depositCurrency === 'USD' && $portfolioCurrency === 'BRL' && $currentFxRate) {
                            $depositInPortfolioCurrency = $currentDepositAmount * $currentFxRate;
                        } elseif ($depositCurrency === 'BRL' && $portfolioCurrency === 'USD' && $currentFxRate) {
                            $depositInPortfolioCurrency = $currentDepositAmount / $currentFxRate;
                        }
                    }

                    $smartDepositThisMonth = $depositInPortfolioCurrency;
                    $totalDeposits += $smartDepositThisMonth;

                    // Calcula desvio relativo de cada ativo em relação ao alvo
                    // Base: total do portfólio ANTES do aporte (não inclui caixa SELIC)
                    $refTotal = $totalMonthValue;
                    $deviations = [];
                    foreach ($assets as $asset) {
                        $assetId = $asset['asset_id'];
                        $targetPct = (float)$asset['allocation_percentage'] / 100;
                        $currentPct = $refTotal > 0 ? $currentBalances[$assetId] / $refTotal : 0;
                        // Desvio relativo positivo = ativo está ABAIXO do alvo
                        $relDev = $targetPct > 0 ? ($targetPct - $currentPct) / $targetPct : 0;
                        $deficit = max(0.0, $targetPct * $refTotal - $currentBalances[$assetId]);
                        $deviations[] = [
                            'asset_id'  => $assetId,
                            'asset'     => $asset,
                            'relDev'    => $relDev,
                            'deficit'   => $deficit
                        ];
                    }
                    // Ordena: maior desvio positivo (mais abaixo do alvo) primeiro
                    usort($deviations, fn($a, $b) => $b['relDev'] <=> $a['relDev']);

                    $remaining = $smartDepositThisMonth;
                    foreach ($deviations as $devInfo) {
                        if ($remaining < 0.001) break;
                        if ($devInfo['relDev'] <= 0) break; // Ativo está no alvo ou acima → para

                        $assetId = $devInfo['asset_id'];
                        $asset   = $devInfo['asset'];
                        $buy     = min($remaining, $devInfo['deficit']);
                        if ($buy < 0.001) continue;

                        $price = (float)($monthData[$assetId] ?? 0);
                        $currentBalances[$assetId] += $buy;
                        $assetValues[$assetId] = $currentBalances[$assetId];

                        if ($asset['asset_type'] !== 'TAXA_MENSAL' && $price > 0) {
                            // Converte o preço para a moeda do portfólio para calcular a quantidade correta
                            $convertedPrice = $price;
                            if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                                $convertedPrice = $price * $currentFxRate;
                            } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                                $convertedPrice = $price / $currentFxRate;
                            }

                            $deltaQty = $convertedPrice > 0 ? $buy / $convertedPrice : 0;
                            $currentQuantities[$assetId] += $deltaQty;
                            $assetQuantities[$assetId] = $currentQuantities[$assetId];
                            $assetPurchases[$assetId] = [
                                'amount'   => $buy,
                                'quantity' => $deltaQty,
                                'price'    => $convertedPrice
                            ];
                        } else {
                            $currentQuantities[$assetId] = $currentBalances[$assetId];
                            $assetQuantities[$assetId]   = $currentQuantities[$assetId];
                            $assetPurchases[$assetId]    = ['amount' => $buy, 'quantity' => $buy, 'price' => 1.0];
                        }

                        $remaining -= $buy;
                    }

                    // Sobra do aporte vai para Caixa SELIC
                    if ($remaining > 0.001) {
                        $selicCash += $remaining;
                    }

                    // Atualiza total do portfólio (apenas o que foi investido em ativos)
                    $totalMonthValue += ($smartDepositThisMonth - $remaining);
                }
            }

            // ============================================
            // LÓGICA DE APORTE EM CAIXA SELIC (Tipo 4)
            // Todo aporte vai para Caixa SELIC; caixa é usado integralmente no rebalanceamento
            // ============================================
            $selicCashDepositThisMonth = 0;
            if ($simulationType === 'selic_cash_deposit' && $depositAmount > 0) {
                if ($this->shouldMakeDeposit($date, $portfolio['start_date'], $depositFrequency, $index)) {
                    $currentDepositAmount = $depositInflationAdjusted ? ($depositAmount * $accumulatedIpca) : $depositAmount;
                    $depositInPortfolioCurrency = $currentDepositAmount;
                    if ($depositCurrency !== $portfolioCurrency) {
                        if ($depositCurrency === 'USD' && $portfolioCurrency === 'BRL' && $currentFxRate) {
                            $depositInPortfolioCurrency = $currentDepositAmount * $currentFxRate;
                        } elseif ($depositCurrency === 'BRL' && $portfolioCurrency === 'USD' && $currentFxRate) {
                            $depositInPortfolioCurrency = $currentDepositAmount / $currentFxRate;
                        }
                    }

                    $selicCashDepositThisMonth = $depositInPortfolioCurrency;
                    $totalDeposits += $selicCashDepositThisMonth;
                    $selicCash += $selicCashDepositThisMonth;
                }
            }

            // Calcula variação da estratégia usando a carteira virtual (só retorno dos ativos)
            $strategyVariation = 0;
            if ($index > 0 || $isFirstDateBeforeStart) {
                // Se temos uma data anterior registrada em $results, usamos; caso contrário, usa capital inicial.
                if ($prevDateForStrategy !== null && isset($results[$prevDateForStrategy]['strategy_value'])) {
                    $prevStrategyValue = $results[$prevDateForStrategy]['strategy_value'];
                } else {
                    $prevStrategyValue = $initialCapital;
                }

                $strategyVariation = $prevStrategyValue > 0 ?
                    (($portfolioWithoutDeposits - $prevStrategyValue) / $prevStrategyValue) * 100 : 0;
            }

            // Atualiza para próximo mês
            // Para tipos com caixa SELIC, inclui o saldo do caixa no valor de referência
            $previousMonthValue = $totalMonthValue + $selicCash;

            // Lógica de Rebalanceamento (existente)
            $wasRebalanced = false;
            $trades = [];
            $assetValuesBefore = [];
            foreach ($assets as $asset) {
                $assetId = $asset['asset_id'];
                // Se for o primeiro mês, o valor 'before' é o aporte inicial proporcional
                if ($index === 0) {
                    $assetValuesBefore[$assetId] = $initialCapital * ($asset['allocation_percentage'] / 100);
                } else {
                    // Para meses subsequentes, pegamos o saldo final do mês anterior (após qualquer rebalanceamento anterior)
                    $assetValuesBefore[$assetId] = $results[$prevDateForStrategy]['asset_values'][$assetId] ?? $currentBalances[$assetId];
                }
            }

            if ($rebalanceFreq > 0 && $index > 0 && $index % $rebalanceFreq == 0) {
                $wasRebalanced = true;

                // Para tipos com caixa SELIC: inclui o caixa no total do rebalanceamento e o zera
                $rebalanceBase = $totalMonthValue;
                if (in_array($simulationType, ['smart_deposit', 'selic_cash_deposit']) && $selicCash > 0) {
                    $selicCashInjected = $selicCash;
                    $rebalanceBase += $selicCash;
                    $selicCash = 0; // Caixa é integralmente investido no rebalanceamento
                }

                if ($rebalanceType === 'buy_only' || $rebalanceType === 'with_margin') {
                    // ============================================
                    // LÓGICA DE REBALANCEAMENTO: APENAS COMPRAS OU COM MARGENS
                    // ============================================
                    
                    // Inicializa caixa disponível com o que foi injetado (SELIC)
                    $availableToInvest = $selicCashInjected;

                    // Se for rebalanceamento COM MARGENS, identifica excessos além da margem e vende primeiro
                    if ($rebalanceType === 'with_margin') {
                        foreach ($assets as $asset) {
                            $assetId = $asset['asset_id'];
                            $targetPct = (float)$asset['allocation_percentage'] / 100;
                            $targetValue = $rebalanceBase * $targetPct;
                            $currentValue = $currentBalances[$assetId];

                            // Margem de tolerância: Alvo * (1 + margem)
                            $toleranceLimit = $targetValue * (1 + $rebalanceMargin);

                            if ($currentValue > $toleranceLimit) {
                                // Vende apenas o que exceder o ALVO
                                $sellAmount = $currentValue - $targetValue;
                                $availableToInvest += $sellAmount;
                                $currentBalances[$assetId] = $targetValue;

                                // Atualiza quantidades
                                $price = (float)($monthData[$assetId] ?? 0);
                                if ($asset['asset_type'] !== 'TAXA_MENSAL' && $asset['asset_type'] !== 'INFLACAO' && $price > 0) {
                                    // Converte o preço para a moeda do portfólio para calcular a quantidade correta
                                    $convertedPrice = $price;
                                    if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                                        $convertedPrice = $price * $currentFxRate;
                                    } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                                        $convertedPrice = $price / $currentFxRate;
                                    }
                                    
                                    $currentQuantities[$assetId] = $convertedPrice > 0 ? $currentBalances[$assetId] / $convertedPrice : 0;
                                } else {
                                    $currentQuantities[$assetId] = $currentBalances[$assetId];
                                }

                                $trades[$assetId] = [
                                    'pre_value' => $currentValue,
                                    'post_value' => $currentBalances[$assetId],
                                    'delta' => -$sellAmount,
                                    'type' => 'margin_sell_rebalance'
                                ];
                            }
                        }
                    }

                    // Se for APENAS COMPRAS e configurado para usar ativos de caixa
                    if ($rebalanceType === 'buy_only' && $useCashAssetsForRebalance) {
                        foreach ($assets as $asset) {
                            if ($asset['is_cash']) {
                                $assetId = $asset['asset_id'];
                                $targetPct = (float)$asset['allocation_percentage'] / 100;
                                $targetValue = $rebalanceBase * $targetPct;
                                $currentValue = $currentBalances[$assetId];

                                if ($currentValue > $targetValue) {
                                    $sellAmount = $currentValue - $targetValue;
                                    $availableToInvest += $sellAmount;
                                    $currentBalances[$assetId] = $targetValue;
                                    
                                    $price = (float)($monthData[$assetId] ?? 0);
                                    if ($asset['asset_type'] !== 'TAXA_MENSAL' && $asset['asset_type'] !== 'INFLACAO' && $price > 0) {
                                        // Converte o preço para a moeda do portfólio para calcular a quantidade correta
                                        $convertedPrice = $price;
                                        if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                                            $convertedPrice = $price * $currentFxRate;
                                        } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                                            $convertedPrice = $price / $currentFxRate;
                                        }
                                        
                                        $currentQuantities[$assetId] = $convertedPrice > 0 ? $currentBalances[$assetId] / $convertedPrice : 0;
                                    } else {
                                        $currentQuantities[$assetId] = $currentBalances[$assetId];
                                    }

                                    $trades[$assetId] = [
                                        'pre_value' => $currentValue,
                                        'post_value' => $currentBalances[$assetId],
                                        'delta' => -$sellAmount,
                                        'type' => 'cash_asset_sell_rebalance'
                                    ];
                                }
                            }
                        }
                    }

                    // Investir o caixa acumulado nos ativos abaixo do alvo
                    if ($availableToInvest > 0.001) {
                        $refTotal = array_sum($currentBalances) + $availableToInvest;
                        $deviations = [];
                        foreach ($assets as $asset) {
                            $assetId = $asset['asset_id'];
                            $targetPct = (float)$asset['allocation_percentage'] / 100;
                            $targetValue = $refTotal * $targetPct;
                            $currentValue = $currentBalances[$assetId];
                            
                            $deficit = max(0.0, $targetValue - $currentValue);
                            $relDev = $targetValue > 0 ? ($targetValue - $currentValue) / $targetValue : 0;

                            $deviations[] = [
                                'asset_id' => $assetId,
                                'asset' => $asset,
                                'deficit' => $deficit,
                                'relDev' => $relDev
                            ];
                        }

                        usort($deviations, fn($a, $b) => $b['relDev'] <=> $a['relDev']);

                        $remaining = $availableToInvest;
                        foreach ($deviations as $devInfo) {
                            if ($remaining < 0.001) break;
                            if ($devInfo['deficit'] <= 0.001) continue;

                            $assetId = $devInfo['asset_id'];
                            $asset = $devInfo['asset'];
                            $buy = min($remaining, $devInfo['deficit']);
                            
                            $price = (float)($monthData[$assetId] ?? 0);
                            $currentBalances[$assetId] += $buy;
                            
                            if ($asset['asset_type'] !== 'TAXA_MENSAL' && $asset['asset_type'] !== 'INFLACAO' && $price > 0) {
                                // Converte o preço para a moeda do portfólio para calcular a quantidade correta
                                $convertedPrice = $price;
                                if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                                    $convertedPrice = $price * $currentFxRate;
                                } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                                    $convertedPrice = $price / $currentFxRate;
                                }

                                $currentQuantities[$assetId] = $convertedPrice > 0 ? $currentBalances[$assetId] / $convertedPrice : 0;
                            } else {
                                $currentQuantities[$assetId] = $currentBalances[$assetId];
                            }
                            
                            if (isset($trades[$assetId])) {
                                $trades[$assetId]['post_value'] = $currentBalances[$assetId];
                                $trades[$assetId]['delta'] += $buy;
                            } else {
                                $trades[$assetId] = [
                                    'pre_value' => $currentBalances[$assetId] - $buy,
                                    'post_value' => $currentBalances[$assetId],
                                    'delta' => $buy,
                                    'type' => $rebalanceType . '_buy'
                                ];
                            }

                            $remaining -= $buy;
                        }

                        if ($remaining > 0.001) {
                            $selicCash = $remaining;
                        }
                    }
                    $totalMonthValue = array_sum($currentBalances);
                } else if ($rebalanceType === 'full') {
                    // ============================================
                    // LÓGICA DE REBALANCEAMENTO: COMPLETO (REBALANCEAMENTO PADRÃO)
                    // Vende o que está acima e compra o que está abaixo do alvo
                    // ============================================
                    foreach ($assets as $asset) {
                        $assetId = $asset['asset_id'];
                        $preValue = $currentBalances[$assetId];
                        $preQty = $currentQuantities[$assetId];

                        $targetAllocation = (float)$asset['allocation_percentage'] / 100;
                        $postValue = $rebalanceBase * $targetAllocation;

                        // Calcula nova quantidade após rebalanceamento
                        $postQty = $preQty;
                        if ($asset['asset_type'] !== 'TAXA_MENSAL' && $asset['asset_type'] !== 'INFLACAO') {
                            $price = (float)($monthData[$assetId] ?? 0);
                            
                            // Converte o preço para a moeda do portfólio para calcular a quantidade correta
                            $convertedPrice = $price;
                            if ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD' && $currentFxRate > 0) {
                                $convertedPrice = $price * $currentFxRate;
                            } elseif ($portfolioCurrency === 'USD' && $asset['currency'] === 'BRL' && $currentFxRate > 0) {
                                $convertedPrice = $price / $currentFxRate;
                            }

                            $postQty = $convertedPrice > 0 ? ($postValue / $convertedPrice) : 0;
                        } else {
                            $postQty = $postValue;
                        }

                        $trades[$assetId] = [
                            'pre_value' => $preValue,
                            'post_value' => $postValue,
                            'pre_quantity' => $preQty,
                            'post_quantity' => $postQty,
                            'delta' => $postValue - $preValue,
                            'delta_quantity' => $postQty - $preQty
                        ];

                        $currentBalances[$assetId] = $postValue;
                        $currentQuantities[$assetId] = $postQty;
                        $assetValues[$assetId] = $postValue;
                        $assetQuantities[$assetId] = $postQty;
                    }

                    $totalMonthValue = $rebalanceBase; // Atualiza total (pode ter crescido com caixa)
                } else {
                    // Caso não caia em nenhum tipo (ex: buy_only sem caixa ou tipo inválido)
                    $wasRebalanced = false;
                }
            }

            $results[$date] = [
            'total_value' => $totalMonthValue + $selicCash,
            'total_before_deposit' => $totalBeforeDeposit,
            'asset_values' => $assetValues,
            'asset_values_before' => $assetValuesBefore, // NOVO: Valores antes do rebalanceamento
            'asset_prices' => $assetPrices,
            'asset_raw_prices' => $assetRawPrices,
            'asset_quantities' => $assetQuantities,
            'rebalanced' => $wasRebalanced,
            'trades' => $trades,
            'deposit_made' => $depositThisMonth + $strategicDepositThisMonth + $smartDepositThisMonth + $selicCashDepositThisMonth,
            'deposit_type' => $depositThisMonth > 0 ? 'monthly' : ($strategicDepositThisMonth > 0 ? 'strategic' : ($smartDepositThisMonth > 0 ? 'smart' : ($selicCashDepositThisMonth > 0 ? 'selic_cash' : 'none'))),
            'deposit_details' => $assetPurchases,
            'total_deposits_to_date' => $totalDeposits,
            'fx_rate' => $currentFxRate,
            // NOVO: Adiciona os valores da estratégia
            'strategy_value' => $portfolioWithoutDeposits,
            'strategy_variation' => $strategyVariation,
            'selic_cash' => $selicCash,
            'selic_cash_earnings' => $selicCashEarnings,
            'selic_cash_injected' => $selicCashInjected
        ];

        // Atualiza a referência da data anterior para o cálculo da variação da estratégia
        $prevDateForStrategy = $date;
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
        $final = end($values);
        $numMonths = count($values);

        // Extrai metadados dos aportes
        $metadata = $results['_metadata'] ?? [];
        $totalDeposits = $metadata['total_deposits'] ?? 0;

        // CORREÇÃO: usa o capital inicial real (do metadata), não o valor do 1º mês simulado.
        // Antes, $values[0] era o valor após o 1º mês, o que excluía o retorno do 1º mês do cálculo.
        $initial = isset($metadata['initial_capital']) && $metadata['initial_capital'] > 0
            ? (float) $metadata['initial_capital']
            : $values[0];

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
        
        // CORREÇÃO: Se houver apenas um mês (data base + 1 mês), o array strategyReturns terá 1 elemento.
        // O primeiro retorno (do mês base para o primeiro mês real) deve ser capturado corretamente.
        
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
            'interest_chart' => $chartService->createInterestChart($results),
            'audit_log' => $results // Garante que o audit_log vá para o chartData
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
        // Usamos o índice de simulação ($currentMonthIndex) em vez de diferença de datas de calendário.
        //
        // Por quê? O start_date é salvo como o PRIMEIRO dia do mês (ex: 2026-01-01),
        // mas os dados históricos ficam no ÚLTIMO dia do mês (ex: 2026-01-31).
        // Isso faz com que diff() retorne 0 meses para Jan-01 → Jan-31, tornando o
        // cálculo por monthsDiff não confiável para definir a periodicidade dos aportes.
        //
        // Lógica correta baseada em índice:
        //  - O mês base (capital inicial, ex: Dez/2025) é removido do array de datas
        //    via array_shift() ANTES do loop, portanto NUNCA chega aqui com index >= 0.
        //  - index 0 = 1º mês simulado (Jan/2026) → 1º aporte mensal.
        //  - index 1 = 2º mês simulado (Fev/2026) → 2º aporte mensal.
        //  - Para trimestral: aportes em index 0, 3, 6, 9, ...
        //  - Para semestral:  aportes em index 0, 6, 12, ...
        //  - Para anual:      aportes em index 0, 12, 24, ...

        $frequencyMap = [
            'monthly'   => 1,
            'bimonthly' => 2,
            'quarterly' => 3,
            'biannual'  => 6,
            'annual'    => 12,
        ];

        $freqMonths = $frequencyMap[$frequency] ?? 1;

        // index 0 é sempre o primeiro mês simulado (nunca o mês do capital inicial,
        // pois esse foi retirado por array_shift). Portanto aportamos quando o índice
        // é múltiplo da frequência (0 % N == 0 sempre inclui o índice 0 → 1º aporte).
        return $currentMonthIndex % $freqMonths === 0;
    }

    /**
     * Carrega as taxas mensais SELIC do banco de dados para o período informado.
     * Retorna um mapa [reference_date => taxa_decimal] (ex: 0.009 para 0.9% a.m.)
     */
    private function loadSelicRates($start, $end) {
        $rates = [];

        // 1. Tenta encontrar o ativo SELIC pelo campo source = 'Selic' e tipo TAXA_MENSAL
        $sql = "SELECT sa.id FROM system_assets sa
                WHERE sa.asset_type = 'TAXA_MENSAL' AND LOWER(COALESCE(sa.source, '')) = 'selic'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $selicAsset = $stmt->fetch();

        // 2. Fallback: qualquer ativo do tipo TAXA_MENSAL (CDI / SELIC)
        if (!$selicAsset) {
            $sql = "SELECT id FROM system_assets WHERE asset_type = 'TAXA_MENSAL' AND is_active = TRUE LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $selicAsset = $stmt->fetch();
        }

        if (!$selicAsset) {
            return $rates; // Sem dados SELIC disponíveis — caixa não rende
        }

        $selicId = $selicAsset['id'];

        // Carrega também o registro imediatamente anterior ao início (para o período base)
        $sql = "SELECT reference_date, price FROM asset_historical_data
                WHERE asset_id = ? AND reference_date < ?
                ORDER BY reference_date DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$selicId, $start]);
        $baseRow = $stmt->fetch();
        if ($baseRow) {
            $rates[$baseRow['reference_date']] = (float)$baseRow['price'] / 100;
        }

        // Carrega as taxas do período simulado
        $sql = "SELECT reference_date, price FROM asset_historical_data
                WHERE asset_id = ? AND reference_date BETWEEN ? AND ?
                ORDER BY reference_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$selicId, $start, $end]);
        foreach ($stmt->fetchAll() as $row) {
            $rates[$row['reference_date']] = (float)$row['price'] / 100;
        }

        return $rates;
    }

    /**
     * Carrega as variações mensais do IPCA do banco de dados para o período informado.
     * Retorna um mapa [reference_date => taxa_decimal] (ex: 0.001 para 0.1% a.m.)
     */
    private function loadIpcaRates($start, $end) {
        $rates = [];

        // Tenta encontrar o ativo IPCA pelo tipo INFLACAO e código IPCA
        $sql = "SELECT id FROM system_assets WHERE asset_type = 'INFLACAO' AND code = 'IPCA' AND is_active = TRUE LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $ipcaAsset = $stmt->fetch();

        if (!$ipcaAsset) {
            // Fallback: qualquer ativo do tipo INFLACAO
            $sql = "SELECT id FROM system_assets WHERE asset_type = 'INFLACAO' AND is_active = TRUE LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $ipcaAsset = $stmt->fetch();
        }

        if (!$ipcaAsset) {
            return $rates;
        }

        $ipcaId = $ipcaAsset['id'];

        // Carrega as taxas do período simulado
        $sql = "SELECT reference_date, price FROM asset_historical_data
                WHERE asset_id = ? AND reference_date BETWEEN ? AND ?
                ORDER BY reference_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ipcaId, $start, $end]);
        foreach ($stmt->fetchAll() as $row) {
            // No banco está 0.10 para indicar 0.10%
            $rates[$row['reference_date']] = (float)$row['price'] / 100;
        }

        return $rates;
    }
}