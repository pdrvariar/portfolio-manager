<?php
require_once __DIR__ . '/../models/Portfolio.php';
require_once __DIR__ . '/../models/Asset.php';

class PortfolioService {
    
    public function validatePortfolioData($data) {
        $errors = [];
        
        // Validar nome
        if (empty($data['name']) || strlen($data['name']) < 3) {
            $errors[] = 'Nome do portfólio deve ter pelo menos 3 caracteres';
        }
        
        // Validar capital inicial
        if (!is_numeric($data['initial_capital']) || $data['initial_capital'] <= 0) {
            $errors[] = 'Capital inicial deve ser um número positivo';
        }
        
        // Validar datas
        if (!Validation::validateDate($data['start_date'])) {
            $errors[] = 'Data de início inválida';
        }
        
        if (!empty($data['end_date']) && !Validation::validateDate($data['end_date'])) {
            $errors[] = 'Data final inválida';
        }
        
        if (!empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            $errors[] = 'Data final não pode ser anterior à data de início';
        }
        
        // Validar alocações
        if (empty($data['assets']) || !is_array($data['assets'])) {
            $errors[] = 'Selecione pelo menos um ativo';
        } else {
            $allocations = array_column($data['assets'], 'allocation');
            
            if (!Validation::validateAllocationSum($allocations)) {
                $total = array_sum($allocations);
                $errors[] = "A soma das alocações deve ser 100%. Atual: " . round($total, 8) . "%";
            }
            
            // Verificar se ativos existem
            $assetModel = new Asset();
            foreach ($data['assets'] as $asset) {
                if (!$assetModel->find($asset['id'])) {
                    $errors[] = "Ativo ID {$asset['id']} não encontrado";
                }
            }
        }
        
        return $errors;
    }
    
    public function optimizeAllocation($assets, $riskProfile = 'MODERATE') {
        // Algoritmo simples de otimização baseado no perfil de risco
        $optimized = [];
        $totalWeight = 0;
        
        switch ($riskProfile) {
            case 'CONSERVATIVE':
                // Mais foco em renda fixa
                foreach ($assets as $asset) {
                    $weight = $this->calculateConservativeWeight($asset);
                    $optimized[] = [
                        'id' => $asset['id'],
                        'allocation' => $weight
                    ];
                    $totalWeight += $weight;
                }
                break;
                
            case 'AGGRESSIVE':
                // Mais foco em ações e cripto
                foreach ($assets as $asset) {
                    $weight = $this->calculateAggressiveWeight($asset);
                    $optimized[] = [
                        'id' => $asset['id'],
                        'allocation' => $weight
                    ];
                    $totalWeight += $weight;
                }
                break;
                
            case 'MODERATE':
            default:
                // Distribuição balanceada
                foreach ($assets as $asset) {
                    $weight = $this->calculateModerateWeight($asset);
                    $optimized[] = [
                        'id' => $asset['id'],
                        'allocation' => $weight
                    ];
                    $totalWeight += $weight;
                }
        }
        
        // Normalizar para somar 100%
        if ($totalWeight > 0) {
            foreach ($optimized as &$asset) {
                $asset['allocation'] = ($asset['allocation'] / $totalWeight) * 100;
            }
        }
        
        return $optimized;
    }
    
    private function calculateConservativeWeight($asset) {
        $baseWeights = [
            'BOND' => 0.5,
            'STOCK' => 0.2,
            'INDEX' => 0.15,
            'COMMODITY' => 0.1,
            'CRYPTO' => 0.05,
            'CURRENCY' => 0.0
        ];
        
        return $baseWeights[$asset['type']] ?? 0.1;
    }
    
    private function calculateModerateWeight($asset) {
        $baseWeights = [
            'STOCK' => 0.3,
            'INDEX' => 0.25,
            'BOND' => 0.2,
            'COMMODITY' => 0.15,
            'CRYPTO' => 0.05,
            'CURRENCY' => 0.05
        ];
        
        return $baseWeights[$asset['type']] ?? 0.1;
    }
    
    private function calculateAggressiveWeight($asset) {
        $baseWeights = [
            'STOCK' => 0.35,
            'INDEX' => 0.25,
            'CRYPTO' => 0.15,
            'COMMODITY' => 0.15,
            'BOND' => 0.05,
            'CURRENCY' => 0.05
        ];
        
        return $baseWeights[$asset['type']] ?? 0.1;
    }
    
    public function calculatePortfolioMetrics($portfolioId) {
        $portfolioModel = new Portfolio();
        $portfolio = $portfolioModel->findWithAssets($portfolioId);
        
        if (!$portfolio) {
            return null;
        }
        
        $metrics = [
            'asset_count' => count($portfolio['assets']),
            'total_allocation' => 0,
            'diversification_score' => 0,
            'estimated_risk' => 'MEDIUM',
            'estimated_return' => 'MEDIUM'
        ];
        
        // Calcular diversificação
        $typeCounts = [];
        foreach ($portfolio['assets'] as $asset) {
            $type = $asset['type'];
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
            $metrics['total_allocation'] += $asset['allocation'];
        }
        
        // Score de diversificação (0-100)
        $typeDiversity = count($typeCounts) / 6; // 6 tipos possíveis
        $allocationDiversity = $this->calculateAllocationDiversity($portfolio['assets']);
        $metrics['diversification_score'] = round(($typeDiversity + $allocationDiversity) / 2 * 100);
        
        // Estimar risco e retorno
        $metrics = array_merge($metrics, $this->estimateRiskReturn($portfolio['assets']));
        
        return $metrics;
    }
    
    private function calculateAllocationDiversity($assets) {
        if (count($assets) <= 1) {
            return 0;
        }
        
        $allocations = array_column($assets, 'allocation');
        $mean = array_sum($allocations) / count($allocations);
        
        $variance = 0;
        foreach ($allocations as $allocation) {
            $variance += pow($allocation - $mean, 2);
        }
        $variance /= count($allocations);
        
        // Quanto maior a variância, menor a diversificação
        $maxVariance = pow($mean, 2) * (count($assets) - 1);
        if ($maxVariance > 0) {
            return 1 - ($variance / $maxVariance);
        }
        
        return 1;
    }
    
    private function estimateRiskReturn($assets) {
        $riskScores = [
            'BOND' => 1,
            'CURRENCY' => 2,
            'COMMODITY' => 3,
            'INDEX' => 4,
            'STOCK' => 5,
            'CRYPTO' => 6
        ];
        
        $returnScores = [
            'BOND' => 1,
            'CURRENCY' => 2,
            'INDEX' => 3,
            'COMMODITY' => 4,
            'STOCK' => 5,
            'CRYPTO' => 6
        ];
        
        $totalRisk = 0;
        $totalReturn = 0;
        $totalWeight = 0;
        
        foreach ($assets as $asset) {
            $weight = $asset['allocation'];
            $risk = $riskScores[$asset['type']] ?? 3;
            $return = $returnScores[$asset['type']] ?? 3;
            
            $totalRisk += $risk * $weight;
            $totalReturn += $return * $weight;
            $totalWeight += $weight;
        }
        
        if ($totalWeight > 0) {
            $avgRisk = $totalRisk / $totalWeight;
            $avgReturn = $totalReturn / $totalWeight;
        } else {
            $avgRisk = 3;
            $avgReturn = 3;
        }
        
        $riskLevels = ['VERY_LOW', 'LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH'];
        $returnLevels = ['VERY_LOW', 'LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH'];
        
        return [
            'estimated_risk' => $riskLevels[min(4, floor($avgRisk - 1))],
            'estimated_return' => $returnLevels[min(4, floor($avgReturn - 1))]
        ];
    }
    
    public function comparePortfolios($portfolio1Id, $portfolio2Id) {
        $portfolioModel = new Portfolio();
        
        $portfolio1 = $portfolioModel->findWithAssets($portfolio1Id);
        $portfolio2 = $portfolioModel->findWithAssets($portfolio2Id);
        
        if (!$portfolio1 || !$portfolio2) {
            return null;
        }
        
        $comparison = [
            'portfolio1' => [
                'name' => $portfolio1['name'],
                'asset_count' => count($portfolio1['assets']),
                'initial_capital' => $portfolio1['initial_capital']
            ],
            'portfolio2' => [
                'name' => $portfolio2['name'],
                'asset_count' => count($portfolio2['assets']),
                'initial_capital' => $portfolio2['initial_capital']
            ],
            'differences' => []
        ];
        
        // Comparar ativos
        $assets1 = $this->groupAssetsByType($portfolio1['assets']);
        $assets2 = $this->groupAssetsByType($portfolio2['assets']);
        
        $allTypes = array_unique(array_merge(array_keys($assets1), array_keys($assets2)));
        
        foreach ($allTypes as $type) {
            $allocation1 = $assets1[$type] ?? 0;
            $allocation2 = $assets2[$type] ?? 0;
            
            if (abs($allocation1 - $allocation2) > 0.01) {
                $comparison['differences'][] = [
                    'type' => $type,
                    'portfolio1_allocation' => $allocation1,
                    'portfolio2_allocation' => $allocation2,
                    'difference' => $allocation1 - $allocation2
                ];
            }
        }
        
        return $comparison;
    }
    
    private function groupAssetsByType($assets) {
        $grouped = [];
        
        foreach ($assets as $asset) {
            $type = $asset['type'];
            $grouped[$type] = ($grouped[$type] ?? 0) + $asset['allocation'];
        }
        
        return $grouped;
    }
}