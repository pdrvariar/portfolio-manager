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

    public function getHistoryByPortfolio($portfolioId, $limit = 10) {
        $sql = "SELECT sr.id, sr.simulation_date, sr.created_at,
                       sr.total_value, sr.annual_return, sr.strategy_annual_return,
                       sr.volatility, sr.sharpe_ratio, sr.max_drawdown,
                       sr.total_invested, sr.total_deposits, sr.interest_earned, sr.roi,
                       sr.strategy_return, sr.max_monthly_gain, sr.max_monthly_loss,
                       ss.portfolio_config, ss.assets_config
                FROM simulation_results sr
                LEFT JOIN simulation_snapshots ss ON ss.simulation_id = sr.id
                WHERE sr.portfolio_id = ?
                ORDER BY sr.created_at DESC
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
                volatility, max_drawdown, sharpe_ratio, chart_data, total_deposits, 
                total_invested, interest_earned, roi, strategy_return, strategy_annual_return,
                max_monthly_gain, max_monthly_loss) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['portfolio_id'],
            $data['simulation_date'],
            $data['total_value'],
            $data['annual_return'],
            $data['volatility'],
            $data['max_drawdown'],
            $data['sharpe_ratio'],
            json_encode($data['chart_data']),
            $data['total_deposits'] ?? 0,
            $data['total_invested'] ?? 0,
            $data['interest_earned'] ?? 0,
            $data['roi'] ?? 0,
            $data['strategy_return'] ?? 0,
            $data['strategy_annual_return'] ?? 0,
            $data['max_monthly_gain'] ?? 0,
            $data['max_monthly_loss'] ?? 0
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM simulation_results WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Conta quantas simulações o usuário realizou no mês atual
     */
    public function countMonthlySimulations($userId) {
        $sql = "SELECT COUNT(*) as total 
                FROM simulation_results sr
                JOIN portfolios p ON sr.portfolio_id = p.id
                WHERE p.user_id = ? 
                AND strftime('%Y-%m', sr.created_at) = strftime('%Y-%m', 'now')";
        
        // Verifica se é SQLite ou MySQL (o projeto parece usar ambos ou estar em transição)
        // Se falhar o strftime, tenta o formato MySQL
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch (Exception $e) {
            $sql = "SELECT COUNT(*) as total 
                    FROM simulation_results sr
                    JOIN portfolios p ON sr.portfolio_id = p.id
                    WHERE p.user_id = ? 
                    AND DATE_FORMAT(sr.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return (int)$result['total'];
        }
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