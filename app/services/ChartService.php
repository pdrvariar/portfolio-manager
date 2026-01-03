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
        // Agrupar por ano
        $annualComposition = [];
        
        foreach ($results as $date => $data) {
            $year = date('Y', strtotime($date));
            
            if (!isset($annualComposition[$year])) {
                $annualComposition[$year] = [];
            }
            
            $totalValue = $data['total_value'];
            
            foreach ($assets as $asset) {
                $assetId = $asset['id'];
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
        $colors = $this->generateColors(count($assetNames));
        
        foreach ($assetNames as $index => $assetName) {
            $data = [];
            
            foreach ($years as $year) {
                $data[] = $annualComposition[$year][$assetName] ?? 0;
            }
            
            $datasets[] = [
                'label' => $assetName,
                'data' => $data,
                'backgroundColor' => $colors[$index],
                'borderColor' => $colors[$index],
                'borderWidth' => 1
            ];
        }
        
        return [
            'labels' => $years,
            'datasets' => $datasets
        ];
    }
    
    public function createAnnualReturnsChart($results) {
        // Agrupar por ano
        $annualReturns = [];
        
        $years = [];
        $currentYear = null;
        $yearStartValue = null;
        
        foreach ($results as $date => $data) {
            $year = date('Y', strtotime($date));
            
            if ($year !== $currentYear) {
                if ($currentYear !== null && $yearStartValue !== null) {
                    $yearEndValue = $data['total_value'];
                    $return = (($yearEndValue - $yearStartValue) / $yearStartValue) * 100;
                    $annualReturns[$currentYear] = $return;
                }
                
                $currentYear = $year;
                $yearStartValue = $data['total_value'];
            }
        }
        
        // Último ano
        if ($currentYear !== null && $yearStartValue !== null) {
            end($results);
            $lastData = current($results);
            $yearEndValue = $lastData['total_value'];
            $return = (($yearEndValue - $yearStartValue) / $yearStartValue) * 100;
            $annualReturns[$currentYear] = $return;
        }
        
        // Preparar dados
        $labels = array_keys($annualReturns);
        $returns = array_values($annualReturns);
        $colors = [];
        
        foreach ($returns as $return) {
            $colors[] = $return >= 0 ? '#28a745' : '#dc3545';
        }
        
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
}
?>