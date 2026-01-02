<?php
class UserRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getUserStats($userId) {
        $sql = "
            SELECT 
                u.*,
                COUNT(DISTINCT p.id) as portfolio_count,
                COUNT(DISTINCT s.id) as simulation_count,
                MAX(s.completed_at) as last_simulation,
                AVG(CAST(s.metrics->>'$.total_return' AS DECIMAL)) as avg_return
            FROM users u
            LEFT JOIN portfolios p ON u.id = p.user_id
            LEFT JOIN simulations s ON u.id = s.user_id AND s.status = 'COMPLETED'
            WHERE u.id = ?
            GROUP BY u.id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getAllUsersWithStats($filters = []) {
        $sql = "
            SELECT 
                u.*,
                COUNT(DISTINCT p.id) as portfolio_count,
                COUNT(DISTINCT s.id) as simulation_count,
                MAX(s.completed_at) as last_activity
            FROM users u
            LEFT JOIN portfolios p ON u.id = p.user_id
            LEFT JOIN simulations s ON u.id = s.user_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (isset($filters['is_admin']) && $filters['is_admin'] !== null) {
            $sql .= " AND u.is_admin = ?";
            $params[] = $filters['is_admin'];
        }
        
        if (isset($filters['verified']) && $filters['verified'] !== null) {
            if ($filters['verified']) {
                $sql .= " AND u.email_verified_at IS NOT NULL";
            } else {
                $sql .= " AND u.email_verified_at IS NULL";
            }
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getUserActivity($userId, $days = 30) {
        $sql = "
            SELECT 
                DATE(s.started_at) as date,
                COUNT(*) as simulation_count,
                GROUP_CONCAT(p.name SEPARATOR ', ') as portfolio_names
            FROM simulations s
            JOIN portfolios p ON s.portfolio_id = p.id
            WHERE s.user_id = ? 
            AND s.started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(s.started_at)
            ORDER BY date DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll();
    }
    
    public function updateUserProfile($userId, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $params[] = $value;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function deleteUser($userId) {
        $this->db->beginTransaction();
        
        try {
            // Primeiro, deletar simulações
            $stmt = $this->db->prepare("DELETE FROM simulations WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Depois, portfólios
            $stmt = $this->db->prepare("DELETE FROM portfolios WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Finalmente, o usuário
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}