<?php
class PortfolioAsset {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByPortfolio($portfolioId) {
        $sql = "SELECT pa.*, sa.name, sa.code, sa.currency, sa.asset_type 
                FROM portfolio_assets pa
                JOIN system_assets sa ON pa.asset_id = sa.id
                WHERE pa.portfolio_id = ?
                ORDER BY pa.allocation_percentage DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId]);
        return $stmt->fetchAll();
    }
    
    public function add($portfolioId, $assetId, $allocation, $performanceFactor = 1.0) {
        $sql = "INSERT INTO portfolio_assets (portfolio_id, asset_id, allocation_percentage, performance_factor) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$portfolioId, $assetId, $allocation, $performanceFactor]);
    }
    
    public function updateAllocation($id, $allocation) {
        $sql = "UPDATE portfolio_assets SET allocation_percentage = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$allocation, $id]);
    }
    
    public function remove($id) {
        $sql = "DELETE FROM portfolio_assets WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function validateAllocationSum($portfolioId) {
        $sql = "SELECT SUM(allocation_percentage) as total 
                FROM portfolio_assets 
                WHERE portfolio_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId]);
        $result = $stmt->fetch();
        
        return abs($result['total'] - 1.0) < 0.00000001;
    }
}
?>