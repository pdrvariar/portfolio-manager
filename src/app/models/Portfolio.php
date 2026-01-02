<?php
class Portfolio {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findByUser($userId, $includeDefaults = true) {
        $sql = "
            SELECT p.*, 
                   u.name as user_name,
                   (SELECT COUNT(*) FROM portfolio_allocations WHERE portfolio_id = p.id) as asset_count
            FROM portfolios p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ? OR (p.is_default = 1 AND ? = ?)
            ORDER BY p.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $includeDefaults, $includeDefaults]);
        return $stmt->fetchAll();
    }
    
    public function findWithAssets($portfolioId) {
        // Busca portfólio com suas alocações
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   pa.asset_id, pa.allocation_percentage, pa.performance_factor,
                   a.code, a.name, a.type, a.currency
            FROM portfolios p
            LEFT JOIN portfolio_allocations pa ON p.id = pa.portfolio_id
            LEFT JOIN assets a ON pa.asset_id = a.id
            WHERE p.id = ?
            ORDER BY pa.allocation_percentage DESC
        ");
        $stmt->execute([$portfolioId]);
        
        $results = $stmt->fetchAll();
        if (empty($results)) return null;
        
        $portfolio = $results[0];
        $portfolio['assets'] = [];
        
        foreach ($results as $row) {
            if ($row['asset_id']) {
                $portfolio['assets'][] = [
                    'id' => $row['asset_id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'currency' => $row['currency'],
                    'allocation' => $row['allocation_percentage'],
                    'performance_factor' => $row['performance_factor']
                ];
            }
        }
        
        return $portfolio;
    }
    
    public function create($userId, $data) {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO portfolios 
                (user_id, name, description, initial_capital, start_date, end_date, 
                 rebalance_frequency, output_currency, is_clone, cloned_from)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $data['name'],
                $data['description'] ?? '',
                $data['initial_capital'] ?? 100000,
                $data['start_date'],
                $data['end_date'] ?? null,
                $data['rebalance_frequency'] ?? 'MONTHLY',
                $data['output_currency'] ?? 'BRL',
                $data['is_clone'] ?? false,
                $data['cloned_from'] ?? null
            ]);
            
            $portfolioId = $this->db->lastInsertId();
            
            // Inserir alocações
            foreach ($data['assets'] as $asset) {
                $stmt = $this->db->prepare("
                    INSERT INTO portfolio_allocations 
                    (portfolio_id, asset_id, allocation_percentage, performance_factor)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $portfolioId,
                    $asset['id'],
                    $asset['allocation'],
                    $asset['performance_factor'] ?? 1.0
                ]);
            }
            
            $this->db->commit();
            return $portfolioId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function clone($portfolioId, $userId, $newName) {
        $original = $this->findWithAssets($portfolioId);
        
        if (!$original) {
            throw new Exception("Portfolio not found");
        }
        
        $data = [
            'name' => $newName,
            'description' => $original['description'] . " (Clonado)",
            'initial_capital' => $original['initial_capital'],
            'start_date' => $original['start_date'],
            'end_date' => $original['end_date'],
            'rebalance_frequency' => $original['rebalance_frequency'],
            'output_currency' => $original['output_currency'],
            'is_clone' => true,
            'cloned_from' => $portfolioId,
            'assets' => []
        ];
        
        foreach ($original['assets'] as $asset) {
            $data['assets'][] = [
                'id' => $asset['id'],
                'allocation' => $asset['allocation'],
                'performance_factor' => $asset['performance_factor']
            ];
        }
        
        return $this->create($userId, $data);
    }
}