<?php
class Simulation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findByExecutionId($executionId, $userId = null) {
        $sql = "
            SELECT s.*, p.name as portfolio_name, u.name as user_name
            FROM simulations s
            JOIN portfolios p ON s.portfolio_id = p.id
            JOIN users u ON s.user_id = u.id
            WHERE s.execution_id = ?
        ";
        $params = [$executionId];
        
        if ($userId !== null) {
            $sql .= " AND s.user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function findByUser($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT s.*, p.name as portfolio_name
            FROM simulations s
            JOIN portfolios p ON s.portfolio_id = p.id
            WHERE s.user_id = ?
            ORDER BY s.started_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO simulations 
            (portfolio_id, user_id, status, execution_id, started_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['portfolio_id'],
            $data['user_id'],
            $data['status'] ?? 'PENDING',
            $data['execution_id']
        ]);
    }
    
    public function updateStatus($executionId, $status, $resultData = null, $metrics = null, $charts = null) {
        $fields = ['status = ?'];
        $params = [$status];
        
        if ($resultData !== null) {
            $fields[] = 'result_data = ?';
            $params[] = json_encode($resultData);
        }
        
        if ($metrics !== null) {
            $fields[] = 'metrics = ?';
            $params[] = json_encode($metrics);
        }
        
        if ($charts !== null) {
            $fields[] = 'charts_html = ?';
            $params[] = json_encode($charts);
        }
        
        if ($status === 'COMPLETED' || $status === 'ERROR') {
            $fields[] = 'completed_at = NOW()';
        }
        
        $params[] = $executionId;
        
        $sql = "UPDATE simulations SET " . implode(', ', $fields) . " WHERE execution_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function deleteOldSimulations($days = 30) {
        $stmt = $this->db->prepare("
            DELETE FROM simulations 
            WHERE completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        return $stmt->execute([$days]);
    }
    
    public function getStatistics($userId = null) {
        $sql = "
            SELECT 
                COUNT(*) as total_simulations,
                SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'RUNNING' THEN 1 ELSE 0 END) as running,
                SUM(CASE WHEN status = 'ERROR' THEN 1 ELSE 0 END) as errors,
                AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration
            FROM simulations
        ";
        $params = [];
        
        if ($userId !== null) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}