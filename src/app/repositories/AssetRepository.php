<?php
class AssetRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAssetsWithHistory($filters = []) {
        $sql = "
            SELECT 
                a.*,
                ah_min.min_date,
                ah_max.max_date,
                ah_first.price as first_price,
                ah_last.price as last_price
            FROM assets a
            LEFT JOIN (
                SELECT asset_id, MIN(date) as min_date 
                FROM asset_history 
                GROUP BY asset_id
            ) ah_min ON a.id = ah_min.asset_id
            LEFT JOIN (
                SELECT asset_id, MAX(date) as max_date 
                FROM asset_history 
                GROUP BY asset_id
            ) ah_max ON a.id = ah_max.asset_id
            LEFT JOIN asset_history ah_first ON a.id = ah_first.asset_id AND ah_first.date = ah_min.min_date
            LEFT JOIN asset_history ah_last ON a.id = ah_last.asset_id AND ah_last.date = ah_max.max_date
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['type'])) {
            $sql .= " AND a.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['currency'])) {
            $sql .= " AND a.currency = ?";
            $params[] = $filters['currency'];
        }
        
        if (isset($filters['is_default']) && $filters['is_default'] !== null) {
            $sql .= " AND a.is_default = ?";
            $params[] = $filters['is_default'];
        }
        
        $sql .= " ORDER BY a.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAssetPerformance($assetId, $startDate, $endDate = null) {
        $sql = "
            SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                MIN(price) as min_price,
                MAX(price) as max_price,
                AVG(price) as avg_price
            FROM asset_history
            WHERE asset_id = ? AND date >= ?
        ";
        $params = [$assetId, $startDate];
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAssetsCorrelation($assetIds, $startDate, $endDate = null) {
        if (count($assetIds) < 2) {
            return [];
        }
        
        // Buscar preços mensais para todos os ativos
        $prices = [];
        foreach ($assetIds as $assetId) {
            $stmt = $this->db->prepare("
                SELECT DATE_FORMAT(date, '%Y-%m') as month, AVG(price) as avg_price
                FROM asset_history
                WHERE asset_id = ? AND date >= ?
                " . ($endDate ? " AND date <= ?" : "") . "
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month ASC
            ");
            
            $params = [$assetId, $startDate];
            if ($endDate) {
                $params[] = $endDate;
            }
            
            $stmt->execute($params);
            $prices[$assetId] = $stmt->fetchAll();
        }
        
        // Calcular correlações
        $correlations = [];
        $assetIdsArray = array_values($assetIds);
        
        for ($i = 0; $i < count($assetIdsArray); $i++) {
            for ($j = $i + 1; $j < count($assetIdsArray); $j++) {
                $asset1 = $assetIdsArray[$i];
                $asset2 = $assetIdsArray[$j];
                
                $returns1 = $this->calculateReturns($prices[$asset1]);
                $returns2 = $this->calculateReturns($prices[$asset2]);
                
                $correlation = $this->calculateCorrelation($returns1, $returns2);
                
                $correlations[] = [
                    'asset1_id' => $asset1,
                    'asset2_id' => $asset2,
                    'correlation' => $correlation
                ];
            }
        }
        
        return $correlations;
    }
    
    private function calculateReturns($priceSeries) {
        $returns = [];
        
        for ($i = 1; $i < count($priceSeries); $i++) {
            if ($priceSeries[$i-1]['avg_price'] > 0) {
                $returns[] = ($priceSeries[$i]['avg_price'] - $priceSeries[$i-1]['avg_price']) / 
                            $priceSeries[$i-1]['avg_price'];
            }
        }
        
        return $returns;
    }
    
    private function calculateCorrelation($returns1, $returns2) {
        $n = min(count($returns1), count($returns2));
        
        if ($n < 2) {
            return 0;
        }
        
        // Pegar os últimos $n elementos para alinhar as séries
        $r1 = array_slice($returns1, -$n);
        $r2 = array_slice($returns2, -$n);
        
        $mean1 = array_sum($r1) / $n;
        $mean2 = array_sum($r2) / $n;
        
        $covariance = 0;
        $variance1 = 0;
        $variance2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $covariance += ($r1[$i] - $mean1) * ($r2[$i] - $mean2);
            $variance1 += pow($r1[$i] - $mean1, 2);
            $variance2 += pow($r2[$i] - $mean2, 2);
        }
        
        if ($variance1 > 0 && $variance2 > 0) {
            return $covariance / sqrt($variance1 * $variance2);
        }
        
        return 0;
    }
}