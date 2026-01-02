<?php
class ChartService {
    
    public function generatePortfolioCharts($simulationData, $metrics, $portfolio) {
        $charts = [];
        
        // Gráfico 1: Evolução do valor total
        $charts['total_value'] = $this->generateTotalValueChart($simulationData);
        
        // Gráfico 2: Composição do portfólio por ano
        $charts['composition'] = $this->generateCompositionChart($simulationData, $portfolio);
        
        // Gráfico 3: Retornos anuais
        $charts['annual_returns'] = $this->generateAnnualReturnsChart($simulationData);
        
        // Gráfico 4: Comparação de ativos
        $charts['asset_comparison'] = $this->generateAssetComparisonChart($simulationData, $portfolio);
        
        // Dashboard de métricas
        $charts['metrics_dashboard'] = $this->generateMetricsDashboard($metrics);
        
        return $charts;
    }
    
    private function generateTotalValueChart($data) {
        $dates = array_column($data, 'date');
        $values = array_column($data, 'total_value');
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => $dates,
                'datasets' => [[
                    'label' => 'Valor Total do Portfólio',
                    'data' => $values,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'fill' => true
                ]]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => ['display' => true, 'text' => 'Evolução do Valor Total']
                ]
            ]
        ];
    }
    
    private function generateCompositionChart($data, $portfolio) {
        $assets = $portfolio['assets'];
        $dates = array_unique(array_column($data, 'date'));
        
        // Agrupar por ano
        $yearlyData = [];
        foreach ($data as $row) {
            $year = date('Y', strtotime($row['date']));
            if (!isset($yearlyData[$year])) {
                $yearlyData[$year] = [];
            }
            
            foreach ($assets as $asset) {
                $key = 'value_' . $asset['code'];
                if (isset($row[$key])) {
                    $yearlyData[$year][$asset['code']] = 
                        ($yearlyData[$year][$asset['code']] ?? 0) + $row[$key];
                }
            }
        }
        
        // Preparar dados para gráfico de barras empilhadas
        $datasets = [];
        $colors = $this->generateColors(count($assets));
        
        foreach ($assets as $index => $asset) {
            $assetData = [];
            foreach ($yearlyData as $year => $values) {
                $totalYear = array_sum($values);
                $assetData[] = $totalYear > 0 ? 
                    ($values[$asset['code']] ?? 0) / $totalYear * 100 : 0;
            }
            
            $datasets[] = [
                'label' => $asset['name'],
                'data' => $assetData,
                'backgroundColor' => $colors[$index],
                'stack' => 'Stack 0'
            ];
        }
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($yearlyData),
                'datasets' => $datasets
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => ['display' => true, 'text' => 'Composição do Portfólio por Ano (%)'],
                    'tooltip' => ['mode' => 'index', 'intersect' => false]
                ],
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => ['stacked' => true, 'beginAtZero' => true]
                ]
            ]
        ];
    }
    
    private function generateAnnualReturnsChart($data) {
        // Calcular retornos anuais
        $yearlyReturns = [];
        $currentYear = null;
        $startValue = null;
        
        foreach ($data as $row) {
            $year = date('Y', strtotime($row['date']));
            
            if ($year !== $currentYear) {
                if ($currentYear !== null && $startValue !== null) {
                    $endValue = $row['total_value'];
                    $yearlyReturns[$currentYear] = ($endValue - $startValue) / $startValue * 100;
                }
                $currentYear = $year;
                $startValue = $row['total_value'];
            }
        }
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($yearlyReturns),
                'datasets' => [[
                    'label' => 'Retorno Anual (%)',
                    'data' => array_values($yearlyReturns),
                    'backgroundColor' => array_map(function($value) {
                        return $value >= 0 ? 'rgba(75, 192, 75, 0.8)' : 'rgba(255, 99, 132, 0.8)';
                    }, array_values($yearlyReturns))
                ]]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => ['display' => true, 'text' => 'Retornos Anuais']
                ]
            ]
        ];
    }
    
    private function generateMetricsDashboard($metrics) {
        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Retorno Total', 'Retorno Anual', 'Volatilidade', 'Max Drawdown', 'Sharpe'],
                'datasets' => [[
                    'label' => 'Métricas',
                    'data' => [
                        abs($metrics['total_return']),
                        abs($metrics['annual_return']),
                        $metrics['volatility'],
                        $metrics['max_drawdown'],
                        abs($metrics['sharpe_ratio'])
                    ],
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                ]]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => ['display' => true, 'text' => 'Dashboard de Métricas'],
                    'legend' => ['position' => 'right']
                ]
            ]
        ];
    }
    
    private function generateColors($count) {
        $colors = [];
        $hueStep = 360 / $count;
        
        for ($i = 0; $i < $count; $i++) {
            $hue = $i * $hueStep;
            $colors[] = "hsla($hue, 70%, 60%, 0.8)";
        }
        
        return $colors;
    }
}