<?php
class Asset {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Retorna todos os ativos ativos
    public function getAll() {
        $sql = "SELECT * FROM system_assets WHERE is_active = TRUE ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Retorna todos os ativos com detalhes adicionais
    public function getAllWithDetails() {
        $sql = "SELECT sa.*, 
                COUNT(ahd.id) as data_count, -- Alias alterado para coincidir com a View
                MIN(ahd.reference_date) as min_date, -- Usando novo nome de coluna e alias da View
                MAX(ahd.reference_date) as max_date  -- Usando novo nome de coluna e alias da View
                FROM system_assets sa
                LEFT JOIN asset_historical_data ahd ON sa.id = ahd.asset_id
                WHERE sa.is_active = TRUE
                GROUP BY sa.id
                ORDER BY sa.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Busca ativo por ID
    public function findById($id) {
        $sql = "SELECT * FROM system_assets WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Busca ativo por código
    public function findByCode($code) {
        $sql = "SELECT * FROM system_assets WHERE code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    // Busca ativo por código (alias para compatibilidade)
    public function getByCode($code) {
        return $this->findByCode($code);
    }
    
    // Retorna dados históricos com filtros
    public function getHistoricalData($assetId, $startDate = null, $endDate = null) {
        // Alterado para reference_date
        $sql = "SELECT * FROM asset_historical_data WHERE asset_id = ?";
        $params = [$assetId];
        
        if ($startDate) {
            $sql .= " AND reference_date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND reference_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY reference_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function importHistoricalData($assetId, $data) {
        // 1. Limpa dados antigos para evitar duplicidade
        $this->db->prepare("DELETE FROM asset_historical_data WHERE asset_id = ?")
                ->execute([$assetId]);

        // 2. Prepara a inserção com as novas colunas reference_date e price
        $sql = "INSERT INTO asset_historical_data (asset_id, reference_date, price) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        $count = 0;
        foreach ($data as $row) {
            if ($stmt->execute([$assetId, $row['date'], $row['price']])) {
                $count++;
            }
        }
        return $count;
    }    

    // Retorna dados históricos (alias para compatibilidade)
    public function getHistoricalValues($assetId) {
        return $this->getHistoricalData($assetId);
    }
    
    // Cria novo ativo
    public function create($data) {
        try {
            // Se não houver código, gera automaticamente
            if (empty($data['code']) && !empty($data['name'])) {
                $data['code'] = $this->generateAssetCode($data['name']);
            }
            
            $sql = "INSERT INTO system_assets (code, name, currency, asset_type, is_active) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['currency'] ?? 'BRL',
                $data['asset_type'] ?? 'COTACAO',
                $data['is_active'] ?? true
            ]);
            
            return [
                'success' => true,
                'id' => $this->db->lastInsertId(),
                'message' => 'Ativo criado com sucesso'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar ativo: ' . $e->getMessage()
            ];
        }
    }
    
    // Atualiza ativo existente
    public function update($id, $data) {
        try {
            $sql = "UPDATE system_assets SET 
                    name = ?, currency = ?, asset_type = ?, is_active = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $data['name'],
                $data['currency'],
                $data['asset_type'],
                $data['is_active'] ?? true,
                $id
            ]);
            
            return [
                'success' => $success,
                'message' => $success ? 'Ativo atualizado com sucesso' : 'Falha ao atualizar ativo'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar ativo: ' . $e->getMessage()
            ];
        }
    }
    
    // Remove ativo (soft delete)
    public function delete($id) {
        try {
            $sql = "UPDATE system_assets SET is_active = FALSE WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$id]);
            
            return [
                'success' => $success,
                'message' => $success ? 'Ativo removido com sucesso' : 'Falha ao remover ativo'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao remover ativo: ' . $e->getMessage()
            ];
        }
    }
    
    // Remove ativo permanentemente (hard delete)
    public function hardDelete($id) {
        try {
            // Primeiro remove dados históricos
            $this->db->prepare("DELETE FROM asset_historical_data WHERE asset_id = ?")
                     ->execute([$id]);
            
            // Depois remove o ativo
            $sql = "DELETE FROM system_assets WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$id]);
            
            return [
                'success' => $success,
                'message' => $success ? 'Ativo excluído permanentemente' : 'Falha ao excluir ativo'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir ativo: ' . $e->getMessage()
            ];
        }
    }
    
    // Adiciona ponto de dados histórico
    public function addHistoricalData($assetId, $date, $price) {
        try {
            $sql = "INSERT INTO asset_historical_data (asset_id, reference_date, price) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE price = ?";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$assetId, $date, $price, $price]);
            
            return [
                'success' => $success,
                'message' => $success ? 'Dado histórico adicionado' : 'Falha ao adicionar dado'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao adicionar dado: ' . $e->getMessage()
            ];
        }
    }
    
    // Importa dados de arquivo CSV
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
            $insertSql = "INSERT INTO asset_historical_data (asset_id, reference_date, price) 
                          VALUES (?, ?, ?)";
            $insertStmt = $this->db->prepare($insertSql);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) continue;
                
                $date = $row[0];
                $price = $row[1];
                
                // Converter data para formato MySQL
                $mysqlDate = date('Y-m-d', strtotime($date . '-01'));
                
                $insertStmt->execute([$assetId, $mysqlDate, $price]);
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
    
    // Exporta dados para CSV
    public function exportToCSV($assetId) {
        try {
            $asset = $this->findById($assetId);
            if (!$asset) {
                return ['success' => false, 'message' => 'Ativo não encontrado'];
            }
            
            $historicalData = $this->getHistoricalData($assetId);
            
            $filename = tempnam(sys_get_temp_dir(), 'asset_export_');
            $handle = fopen($filename, 'w');
            
            // Cabeçalho
            fputcsv($handle, ['Date', 'Value']);
            
            // Dados
            foreach ($historicalData as $row) {
                fputcsv($handle, [
                    date('Y-m', strtotime($row['reference_date'])),
                    $row['price']
                ]);
            }
            
            fclose($handle);
            
            return [
                'success' => true,
                'filename' => $filename,
                'asset_name' => $asset['name']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao exportar: ' . $e->getMessage()
            ];
        }
    }
    
    // Gera código do ativo automaticamente
    private function generateAssetCode($assetName) {
        $code = strtoupper($assetName);
        $code = preg_replace('/[^A-Z0-9]/', '-', $code);
        $code = preg_replace('/-+/', '-', $code);
        $code = trim($code, '-');
        
        return $code;
    }
    
    // Valida dados do ativo
    public function validateAssetData($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nome do ativo é obrigatório';
        }
        
        if (empty($data['code'])) {
            $errors[] = 'Código do ativo é obrigatório';
        } elseif (!preg_match('/^[A-Z0-9\-]+$/', $data['code'])) {
            $errors[] = 'Código inválido. Use apenas letras maiúsculas, números e hífens';
        }
        
        if (!empty($data['currency']) && strlen($data['currency']) !== 3) {
            $errors[] = 'Moeda deve ter 3 caracteres (ex: BRL, USD)';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // Busca ativos por tipo
    public function getByType($type) {
        $sql = "SELECT * FROM system_assets WHERE asset_type = ? AND is_active = TRUE ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    
    // Retorna estatísticas dos ativos
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_assets,
                SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_assets,
                asset_type,
                COUNT(*) as count_by_type
                FROM system_assets 
                GROUP BY asset_type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Verifica se código já existe
    public function codeExists($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM system_assets WHERE code = ?";
        $params = [$code];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
}
?>