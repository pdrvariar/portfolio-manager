<?php
class PortfolioRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getUserPortfoliosWithStats($userId) {
        $sql = "
            SELECT 
                p.*,
                u.name as user_name,
                COUNT(pa.id) as asset_count,
                SUM(pa.allocation_percentage) as total_allocation,
                MAX(s.completed_at) as last_simulation,
                COUNT(s.id) as simulation_count
            FROM portfolios p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN portfolio_allocations pa ON p.id = pa.portfolio_id
            LEFT JOIN simulations s ON p.id = s.portfolio_id
            WHERE p.user_id = ? OR (p.is_default = 1 AND p.user_id IS NULL)
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getPortfolioPerformance($portfolioId, $startDate = null, $endDate = null) {
        // Buscar alocações do portfólio
        $stmt = $this->db->prepare("
            SELECT pa.*, a.code, a.currency
            FROM portfolio_allocations pa
            JOIN assets a ON pa.asset_id = a.id
            WHERE pa.portfolio_id = ?
        ");
        $stmt->execute([$portfolioId]);
        $allocations = $stmt->fetchAll();
        
        if (empty($allocations)) {
            return [];
        }
        
        // Buscar preços históricos para cada ativo
        $performance = [];
        $dates = [];
        
        foreach ($allocations as $allocation) {
            $sql = "
                SELECT date, price 
                FROM asset_history 
                WHERE asset_id = ?
            ";
            $params = [$allocation['asset_id']];
            
            if ($startDate) {
                $sql .= " AND date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND date <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $prices = $stmt->fetchAll();
            
            foreach ($prices as $price) {
                $date = $price['date'];
                $dates[$date] = true;
                
                if (!isset($performance[$date])) {
                    $performance[$date] = [
                        'date' => $date,
                        'total_value' => 0,
                        'assets' => []
                    ];
                }
                
                $value = $price['price'] * $allocation['allocation_percentage'] * 1000; // Exemplo: capital base de 1000
                $performance[$date]['total_value'] += $value;
                $performance[$date]['assets'][$allocation['code']] = $value;
            }
        }
        
        // Ordenar por data
        ksort($performance);
        return array_values($performance);
    }
    
    public function getTopPortfoliosByReturn($limit = 10, $period = '1Y') {
        $dateCondition = '';
        
        switch ($period) {
            case '1M':
                $dateCondition = "AND s.completed_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case '6M':
                $dateCondition = "AND s.completed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
                break;
            case '1Y':
                $dateCondition = "AND s.completed_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            case 'ALL':
            default:
                $dateCondition = "";
        }
        
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.user_id,
                u.name as user_name,
                s.metrics->>'$.total_return' as total_return,
                s.metrics->>'$.annual_return' as annual_return,
                s.metrics->>'$.sharpe_ratio' as sharpe_ratio,
                s.completed_at
            FROM portfolios p
            JOIN users u ON p.user_id = u.id
            JOIN simulations s ON p.id = s.portfolio_id
            WHERE s.status = 'COMPLETED' 
            AND s.metrics IS NOT NULL
            $dateCondition
            ORDER BY CAST(s.metrics->>'$.total_return' AS DECIMAL) DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getPortfolioAssetDistribution($portfolioId) {
        $sql = "
            SELECT 
                a.type,
                SUM(pa.allocation_percentage) as total_allocation,
                COUNT(*) as asset_count
            FROM portfolio_allocations pa
            JOIN assets a ON pa.asset_id = a.id
            WHERE pa.portfolio_id = ?
            GROUP BY a.type
            ORDER BY total_allocation DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId]);
        return $stmt->fetchAll();
    }
}