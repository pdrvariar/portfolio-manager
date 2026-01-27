<?php
class ChartService {
    
    public function createValueChart($results) {
        $dates = [];
        $values = [];
        
        foreach ($results as $date => $data) {
            $dates[] = date('M Y', strtotime($date));
            $values[] = $data['total_value'];
        }
        
        return [
            'labels' => $dates,
            'datasets' => [[
                'label' => 'Valor do Portfólio',
                'data' => $values,
                'borderColor' => '#007bff',
                'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                'fill' => true,
                'tension' => 0.1
            ]]
        ];
    }
    
    public function createCompositionChart($results, $assets) {
        $annualComposition = [];
        
        foreach ($results as $date => $data) {
            $year = date('Y', strtotime($date));
            if (!isset($annualComposition[$year])) {
                $annualComposition[$year] = [];
            }
            
            $totalValue = $data['total_value'];
            
            foreach ($assets as $asset) {
                // CORREÇÃO: Use asset_id em vez de id
                $assetId = $asset['asset_id']; 
                $assetName = $asset['name'];
                
                $value = $data['asset_values'][$assetId] ?? 0;
                $percentage = $totalValue > 0 ? ($value / $totalValue) * 100 : 0;
                
                if (!isset($annualComposition[$year][$assetName])) {
                    $annualComposition[$year][$assetName] = 0;
                }
                
                $annualComposition[$year][$assetName] = $percentage;
            }
        }        
        // Preparar dados para Chart.js
        $years = array_keys($annualComposition);
        $assetNames = array_keys($annualComposition[$years[0]] ?? []);
        
        $datasets = [];
        $defaultColors = [
            '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1',
            '#20c997', '#fd7e14', '#e83e8c', '#6c757d', '#17a2b8'
        ];
        
        foreach ($assetNames as $index => $assetName) {
            $data = [];
            foreach ($years as $year) {
                $data[] = $annualComposition[$year][$assetName] ?? 0;
            }
            
            // CORREÇÃO: Tenta pegar a cor fixa, senão usa a paleta padrão
            $color = $this->getAssetColor($assetName) ?? ($defaultColors[$index % count($defaultColors)]);
            
            $datasets[] = [
                'label' => $assetName,
                'data' => $data,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'borderWidth' => 1
            ];
        }
        
        return [
            'labels' => $years,
            'datasets' => $datasets
        ];
    }    

    private function getAssetColor($assetName) {
        // Mapeamento de cores padronizadas do mercado financeiro
        $colorMap = [
            'Bitcoin' => '#F7931A',         // Laranja Bitcoin
            'BTC-USD' => '#F7931A',
            'Ibovespa' => '#004b8d',        // Azul B3
            'BVSP-IBOVESPA' => '#004b8d',
            'Taxa Selic' => '#28a745',      // Verde Selic
            'SELIC' => '#28a745',
            'S&P 500' => '#5b9bd5',         // Azul claro
            'GSPC-SP500' => '#5b9bd5',
            'Dólar' => '#85bb65',           // Verde Dólar
            'USD-BRL' => '#85bb65',
            'Ethereum' => '#627EEA'         // Roxo Ethereum
        ];

        // Se o ativo for conhecido, retorna a cor fixa, senão usa uma cor aleatória
        return $colorMap[$assetName] ?? null;
    }    

    public function createAnnualReturnsChart($results) {
        $annualReturns = [];
        $dataByYear = [];

        // Agrupa os valores mensais por ano
        foreach ($results as $date => $data) {
            $year = date('Y', strtotime($date));
            $dataByYear[$year][] = $data['total_value'];
        }

        foreach ($dataByYear as $year => $values) {
            $startValue = $values[0];
            $endValue = end($values);
            
            if ($startValue > 0) {
                $annualReturns[$year] = (($endValue - $startValue) / $startValue) * 100;
            }
        }

        $labels = array_keys($annualReturns);
        $returns = array_values($annualReturns);
        $colors = array_map(fn($r) => $r >= 0 ? '#28a745' : '#dc3545', $returns);

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Retorno Anual (%)',
                'data' => $returns,
                'backgroundColor' => $colors,
                'borderColor' => $colors,
                'borderWidth' => 1
            ]]
        ];
    }
    
    public function createPerformanceComparisonChart($portfolios) {
        $labels = [];
        $datasets = [];
        $colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'];
        
        foreach ($portfolios as $index => $portfolio) {
            $data = [];
            
            foreach ($portfolio['annual_returns'] as $year => $return) {
                if (!in_array($year, $labels)) {
                    $labels[] = $year;
                }
                $data[] = $return;
            }
            
            $datasets[] = [
                'label' => $portfolio['name'],
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => 'transparent',
                'tension' => 0.1
            ];
        }
        
        sort($labels);
        
        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
    
    private function generateColors($count) {
        $colors = [
            '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1',
            '#20c997', '#fd7e14', '#e83e8c', '#6c757d', '#17a2b8',
            '#6610f2', '#d63384', '#0d6efd', '#198754', '#ffc107'
        ];
        
        if ($count <= count($colors)) {
            return array_slice($colors, 0, $count);
        }
        
        // Gerar cores adicionais se necessário
        for ($i = count($colors); $i < $count; $i++) {
            $colors[] = '#' . substr(md5($i), 0, 6);
        }
        
        return array_slice($colors, 0, $count);
    }

    public function createDepositChart($results) {
        $dates = [];
        $values = [];
        $deposits = [];

        foreach ($results as $date => $data) {
            if ($date === '_metadata') continue;

            $dates[] = date('M Y', strtotime($date));
            $values[] = $data['total_value'];
            $deposits[] = $data['deposit_made'] ?? 0;
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Valor do Portfólio',
                    'data' => $values,
                    'borderColor' => '#007bff',
                    'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                    'fill' => true,
                    'tension' => 0.1
                ],
                [
                    'label' => 'Aportes Realizados',
                    'data' => $deposits,
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.3)',
                    'borderColor' => '#28a745',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    public function createStrategyPerformanceChart($results) {
        $dates = [];
        $strategyValues = [];
        $portfolioValues = [];

        foreach ($results as $date => $data) {
            if ($date === '_metadata') continue;

            $dates[] = date('M Y', strtotime($date));

            // Valor da estratégia (sem aportes) - base 100
            $strategyValue = $data['strategy_value'] ?? $data['total_value'];
            $strategyValues[] = $strategyValue;

            // Valor total do portfólio (com aportes) - base 100
            $portfolioValues[] = $data['total_value'];
        }

        // Normaliza para base 100 para comparação percentual
        if (!empty($strategyValues) && !empty($portfolioValues)) {
            $strategyBase = $strategyValues[0];
            $portfolioBase = $portfolioValues[0];

            $strategyValues = array_map(function($value) use ($strategyBase) {
                return $strategyBase > 0 ? (($value / $strategyBase) - 1) * 100 : 0;
            }, $strategyValues);

            $portfolioValues = array_map(function($value) use ($portfolioBase) {
                return $portfolioBase > 0 ? (($value / $portfolioBase) - 1) * 100 : 0;
            }, $portfolioValues);
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Estratégia (sem aportes)',
                    'data' => $strategyValues,
                    'borderColor' => '#6f42c1',
                    'backgroundColor' => 'rgba(111, 66, 193, 0.1)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.1
                ],
                [
                    'label' => 'Portfólio Total (com aportes)',
                    'data' => $portfolioValues,
                    'borderColor' => '#20c997',
                    'backgroundColor' => 'rgba(32, 201, 151, 0.1)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension'  => 0.1
                ]
            ]
        ];
    }

    public function createInterestChart($results) {
        $dates = [];
        $cumulativeInterest = [];
        $monthlyInterest = [];

        $previousValue = null;
        $cumulative = 0;

        foreach ($results as $date => $data) {
            if ($date === '_metadata') continue;

            $dates[] = date('M Y', strtotime($date));

            // Calcula juros do mês (variação - aportes)
            $currentValue = $data['total_value'];
            $deposits = $data['deposit_made'] ?? 0;

            if ($previousValue !== null) {
                $monthlyInterestValue = ($currentValue - $previousValue - $deposits);
                $cumulative += $monthlyInterestValue;
                $monthlyInterest[] = $monthlyInterestValue;
            } else {
                $monthlyInterest[] = 0;
            }

            $cumulativeInterest[] = $cumulative;
            $previousValue = $currentValue;
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Juros Acumulados',
                    'data' => $cumulativeInterest,
                    'borderColor' => '#198754',
                    'backgroundColor' => 'rgba(25, 135, 84, 0.1)',
                    'fill' => true,
                    'tension' => 0.1,
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'Juros Mensais',
                    'data' => $monthlyInterest,
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(255, 193, 7, 0.3)',
                    'borderColor' => '#ffc107',
                    'borderWidth' => 1,
                    'yAxisID' => 'y1'
                ]
            ]
        ];
    }

}
?>