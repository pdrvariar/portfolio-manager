<?php
class Portfolio {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getUserPortfolios($userId, $includeSystemDefaults = true) {
        if ($includeSystemDefaults) {
            $sql = "SELECT * FROM portfolios 
                    WHERE user_id = ? OR is_system_default = TRUE
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql = "SELECT * FROM portfolios 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $sql = "INSERT INTO portfolios (user_id, name, description, initial_capital, 
                start_date, end_date, rebalance_frequency, output_currency, cloned_from) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['name'],
            $data['description'],
            $data['initial_capital'],
            $data['start_date'],
            $data['end_date'],
            $data['rebalance_frequency'],
            $data['output_currency'],
            $data['cloned_from'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function clone($portfolioId, $userId, $newName = null) {
        // Buscar portfólio original
        $original = $this->findById($portfolioId);
        
        if (!$original) {
            return false;
        }
        
        // Criar novo portfólio como cópia
        $newPortfolioId = $this->create([
            'user_id' => $userId,
            'name' => $newName ?? $original['name'] . ' (Cópia)',
            'description' => $original['description'],
            'initial_capital' => $original['initial_capital'],
            'start_date' => $original['start_date'],
            'end_date' => $original['end_date'],
            'rebalance_frequency' => $original['rebalance_frequency'],
            'output_currency' => $original['output_currency'],
            'cloned_from' => $portfolioId
        ]);
        
        // Copiar alocações de ativos
        $this->cloneAssets($portfolioId, $newPortfolioId);
        
        return $newPortfolioId;
    }
    
    private function cloneAssets($sourcePortfolioId, $targetPortfolioId) {
        $sql = "INSERT INTO portfolio_assets (portfolio_id, asset_id, allocation_percentage, performance_factor)
                SELECT ?, asset_id, allocation_percentage, performance_factor
                FROM portfolio_assets 
                WHERE portfolio_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$targetPortfolioId, $sourcePortfolioId]);
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM portfolios WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
        
    public function update($data) {
        $sql = "UPDATE portfolios SET 
                name = ?, 
                description = ?, 
                initial_capital = ?, 
                start_date = ?, 
                end_date = ?, 
                rebalance_frequency = ?, 
                output_currency = ? 
                WHERE id = ? AND user_id = ?";
                
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['initial_capital'],
            $data['start_date'],
            $data['end_date'],
            $data['rebalance_frequency'],
            $data['output_currency'],
            $data['id'],
            $_SESSION['user_id']
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM portfolios WHERE id = ? AND user_id = ? AND is_system_default = FALSE";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $_SESSION['user_id']]);
    }
    
    public function getPortfolioAssets($portfolioId) {
        // Adicionado sa.currency para que a view.php consiga exibir a moeda
        $sql = "SELECT sa.id as asset_id, sa.name, sa.currency, pa.allocation_percentage, pa.performance_factor, pa.id 
                FROM portfolio_assets pa
                JOIN system_assets sa ON pa.asset_id = sa.id
                WHERE pa.portfolio_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId]);
        return $stmt->fetchAll();
    }

    public function updateAssets($portfolioId, $assets) {
        // 1. Limpa as alocações antigas
        $sqlDelete = "DELETE FROM portfolio_assets WHERE portfolio_id = ?";
        $stmtDelete = $this->db->prepare($sqlDelete);
        $stmtDelete->execute([$portfolioId]);
        
        // 2. Insere todas as alocações enviadas pelo formulário (antigas e novas)
        foreach ($assets as $assetData) {
            $sql = "INSERT INTO portfolio_assets (portfolio_id, asset_id, allocation_percentage, performance_factor)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            // Converte 39% para 0.39000000 para o banco DECIMAL(10,8)
            $allocation = floatval($assetData['allocation']) / 100;
            
            $stmt->execute([
                $portfolioId,
                $assetData['asset_id'],
                $allocation,
                $assetData['performance_factor'] ?? 1.0
            ]);
        }
        return true;
    }
}
?>