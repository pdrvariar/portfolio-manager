<?php
class SimulationResult {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByPortfolio($portfolioId, $limit = 10) {
        $sql = "SELECT * FROM simulation_results 
                WHERE portfolio_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId, $limit]);
        return $stmt->fetchAll();
    }

    public function getLatest($portfolioId) {
        // ALTERADO: Seleciona todas as colunas, incluindo as novas
        $sql = "SELECT * FROM simulation_results 
            WHERE portfolio_id = ? 
            ORDER BY id DESC 
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId]);
        return $stmt->fetch();
    }
    
    public function getDetails($simulationId) {
        $sql = "SELECT sad.*, sa.name, sa.code 
                FROM simulation_asset_details sad
                JOIN system_assets sa ON sad.asset_id = sa.id
                WHERE sad.simulation_id = ?
                ORDER BY sad.year, sa.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$simulationId]);
        return $stmt->fetchAll();
    }
    
    public function save($data) {
        $sql = "INSERT INTO simulation_results 
                (portfolio_id, simulation_date, total_value, annual_return, 
                volatility, max_drawdown, sharpe_ratio, chart_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['portfolio_id'],
            $data['simulation_date'],
            $data['total_value'],
            $data['annual_return'],
            $data['volatility'],
            $data['max_drawdown'],
            $data['sharpe_ratio'],
            json_encode($data['chart_data'])
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM simulation_results WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function getStatistics($userId = null) {
        $sql = "SELECT 
                COUNT(*) as total_simulations,
                AVG(annual_return) as avg_return,
                AVG(volatility) as avg_volatility,
                MIN(annual_return) as min_return,
                MAX(annual_return) as max_return
                FROM simulation_results sr";
        
        $params = [];
        
        if ($userId) {
            $sql .= " JOIN portfolios p ON sr.portfolio_id = p.id 
                     WHERE p.user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
?>