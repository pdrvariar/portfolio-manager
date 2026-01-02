<?php
class Asset {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($includeDefaults = true) {
        $sql = "SELECT * FROM assets";
        $params = [];
        
        if ($includeDefaults) {
            $sql .= " WHERE is_default = 1";
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    public function getHistory($assetId, $startDate = null, $endDate = null) {
        $sql = "SELECT * FROM asset_history WHERE asset_id = ?";
        $params = [$assetId];
        
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
        return $stmt->fetchAll();
    }
    
    public function getPriceAtDate($assetId, $date) {
        $stmt = $this->db->prepare("
            SELECT price 
            FROM asset_history 
            WHERE asset_id = ? AND date <= ?
            ORDER BY date DESC 
            LIMIT 1
        ");
        $stmt->execute([$assetId, $date]);
        $result = $stmt->fetch();
        
        return $result ? $result['price'] : null;
    }
    
    public function getLatestPrice($assetId) {
        $stmt = $this->db->prepare("
            SELECT price 
            FROM asset_history 
            WHERE asset_id = ? 
            ORDER BY date DESC 
            LIMIT 1
        ");
        $stmt->execute([$assetId]);
        $result = $stmt->fetch();
        
        return $result ? $result['price'] : null;
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO assets (code, name, type, currency, is_default) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['type'],
            $data['currency'] ?? 'BRL',
            $data['is_default'] ?? false
        ]);
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $sql = "UPDATE assets SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM assets WHERE id = ?");
        return $stmt->execute([$id]);
    }
}