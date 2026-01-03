<?php
class Asset {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $sql = "SELECT * FROM system_assets WHERE is_active = TRUE ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT sa.*, 
                COUNT(ahd.id) as data_points,
                MIN(ahd.year_month) as first_date,
                MAX(ahd.year_month) as last_date
                FROM system_assets sa
                LEFT JOIN asset_historical_data ahd ON sa.id = ahd.asset_id
                GROUP BY sa.id
                ORDER BY sa.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM system_assets WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByCode($code) {
        $sql = "SELECT * FROM system_assets WHERE code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    public function getHistoricalData($assetId, $startDate = null, $endDate = null) {
        $sql = "SELECT * FROM asset_historical_data 
                WHERE asset_id = ?";
        
        $params = [$assetId];
        
        if ($startDate) {
            $sql .= " AND year_month >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND year_month <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY year_month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function importFromCSV($filePath) {
        try {
            // Ler arquivo CSV
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return ['success' => false, 'message' => 'Não foi possível abrir o arquivo.'];
            }
            
            // Ler cabeçalhos
            $headers = fgetcsv($handle);
            $secondLine = fgetcsv($handle); // Segunda linha com metadados
            
            if (!$headers || !$secondLine) {
                fclose($handle);
                return ['success' => false, 'message' => 'Formato de arquivo inválido.'];
            }
            
            // Extrair metadados da segunda linha
            $assetName = $secondLine[0] ?? 'Unknown';
            $typeCurrency = explode(':', $secondLine[1] ?? 'COTACAO:BRL');
            $assetType = $typeCurrency[0] ?? 'COTACAO';
            $currency = $typeCurrency[1] ?? 'BRL';
            
            // Gerar código do ativo
            $code = $this->generateAssetCode($assetName);
            
            // Verificar se ativo já existe
            $existingAsset = $this->findByCode($code);
            
            if ($existingAsset) {
                $assetId = $existingAsset['id'];
            } else {
                // Criar novo ativo
                $sql = "INSERT INTO system_assets (code, name, currency, asset_type) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$code, $assetName, $currency, $assetType]);
                $assetId = $this->db->lastInsertId();
            }
            
            // Limpar dados históricos existentes
            $this->db->prepare("DELETE FROM asset_historical_data WHERE asset_id = ?")
                     ->execute([$assetId]);
            
            // Importar dados históricos
            $rowCount = 0;
            $insertSql = "INSERT INTO asset_historical_data (asset_id, year_month, value) 
                          VALUES (?, ?, ?)";
            $insertStmt = $this->db->prepare($insertSql);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) continue;
                
                $date = $row[0];
                $value = $row[1];
                
                // Converter data para formato MySQL
                $mysqlDate = date('Y-m-d', strtotime($date . '-01'));
                
                $insertStmt->execute([$assetId, $mysqlDate, $value]);
                $rowCount++;
            }
            
            fclose($handle);
            
            return [
                'success' => true,
                'message' => "Ativo '$assetName' importado com sucesso. $rowCount registros adicionados.",
                'asset_id' => $assetId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao importar arquivo: ' . $e->getMessage()
            ];
        }
    }
    
    private function generateAssetCode($assetName) {
        // Converter nome para código
        $code = strtoupper($assetName);
        $code = preg_replace('/[^A-Z0-9]/', '-', $code);
        $code = preg_replace('/-+/', '-', $code);
        $code = trim($code, '-');
        
        return $code;
    }
    
    public function update($id, $data) {
        $sql = "UPDATE system_assets SET 
                name = ?, currency = ?, asset_type = ?, is_active = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['currency'],
            $data['asset_type'],
            $data['is_active'] ?? true,
            $id
        ]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM system_assets WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>