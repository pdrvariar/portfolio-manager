<?php
class AssetHistory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function bulkInsert($assetId, $historyData) {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO asset_history (asset_id, date, price) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE price = VALUES(price)
            ");
            
            foreach ($historyData as $data) {
                $stmt->execute([
                    $assetId,
                    $data['date'],
                    $data['price']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getPriceSeries($assetId, $startDate, $endDate = null) {
        $sql = "
            SELECT date, price 
            FROM asset_history 
            WHERE asset_id = ? AND date >= ?
        ";
        $params = [$assetId, $startDate];
        
        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getMonthlyReturns($assetId, $startDate, $endDate = null) {
        $prices = $this->getPriceSeries($assetId, $startDate, $endDate);
        $returns = [];
        
        for ($i = 1; $i < count($prices); $i++) {
            $current = $prices[$i];
            $previous = $prices[$i - 1];
            
            if ($previous['price'] > 0) {
                $return = ($current['price'] - $previous['price']) / $previous['price'];
                $returns[] = [
                    'date' => $current['date'],
                    'return' => $return
                ];
            }
        }
        
        return $returns;
    }
    
    public function getDateRange($assetId) {
        $stmt = $this->db->prepare("
            SELECT MIN(date) as min_date, MAX(date) as max_date 
            FROM asset_history 
            WHERE asset_id = ?
        ");
        $stmt->execute([$assetId]);
        return $stmt->fetch();
    }
}