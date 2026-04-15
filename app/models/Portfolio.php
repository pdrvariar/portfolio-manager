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
            start_date, end_date, rebalance_frequency, output_currency, cloned_from,
            simulation_type, rebalance_type, rebalance_margin, deposit_amount, deposit_currency, deposit_frequency,
            strategic_threshold, strategic_deposit_percentage, deposit_inflation_adjusted, 
            use_cash_assets_for_rebalance, profit_tax_rate, profit_tax_rates_json, is_system_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
            $data['cloned_from'] ?? null,
            $data['simulation_type'] ?? 'standard',
            $data['rebalance_type'] ?? 'full',
            $data['rebalance_margin'] ?? null,
            $data['deposit_amount'] ?? null,
            $data['deposit_currency'] ?? null,
            $data['deposit_frequency'] ?? null,
            $data['strategic_threshold'] ?? null,
            $data['strategic_deposit_percentage'] ?? null,
            $data['deposit_inflation_adjusted'] ?? 0,
            $data['use_cash_assets_for_rebalance'] ?? 0,
            $data['profit_tax_rate'] ?? null,
            $data['profit_tax_rates_json'] ?? null,
            $data['is_system_default'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    public function clone($portfolioId, $userId, $newName = null) {
        // Buscar portfólio original
        $original = $this->findById($portfolioId);

        if (!$original) {
            return false;
        }

        // Criar novo portfólio como cópia (INCLUINDO OS NOVOS CAMPOS)
        $newPortfolioId = $this->create([
            'user_id' => $userId,
            'name' => $newName ?? $original['name'] . ' (Cópia)',
            'description' => $original['description'],
            'initial_capital' => $original['initial_capital'],
            'start_date' => $original['start_date'],
            'end_date' => $original['end_date'],
            'rebalance_frequency' => $original['rebalance_frequency'],
            'output_currency' => $original['output_currency'],
            'cloned_from' => $portfolioId,
            'simulation_type' => $original['simulation_type'],
            'rebalance_type' => $original['rebalance_type'] ?? 'full',
            'rebalance_margin' => $original['rebalance_margin'] ?? null,
            'deposit_amount' => $original['deposit_amount'],
            'deposit_currency' => $original['deposit_currency'],
            'deposit_frequency' => $original['deposit_frequency'],
            'strategic_threshold' => $original['strategic_threshold'],
            'strategic_deposit_percentage' => $original['strategic_deposit_percentage'],
            'deposit_inflation_adjusted' => $original['deposit_inflation_adjusted'],
            'use_cash_assets_for_rebalance' => $original['use_cash_assets_for_rebalance'] ?? 0,
            'profit_tax_rate' => $original['profit_tax_rate'] ?? null,
            'profit_tax_rates_json' => $original['profit_tax_rates_json'] ?? null
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
            output_currency = ?,
            simulation_type = ?,
            rebalance_type = ?,
            rebalance_margin = ?,
            deposit_amount = ?,
            deposit_currency = ?,
            deposit_frequency = ?,
            strategic_threshold = ?,
            strategic_deposit_percentage = ?,
            deposit_inflation_adjusted = ?,
            use_cash_assets_for_rebalance = ?,
            profit_tax_rate = ?,
            profit_tax_rates_json = ?
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['initial_capital'],
            $data['start_date'],
            $data['end_date'],
            $data['rebalance_frequency'],
            $data['output_currency'],
            $data['simulation_type'] ?? 'standard',
            $data['rebalance_type'] ?? 'full',
            $data['rebalance_margin'] ?? null,
            $data['deposit_amount'] ?? null,
            $data['deposit_currency'] ?? null,
            $data['deposit_frequency'] ?? null,
            $data['strategic_threshold'] ?? null,
            $data['strategic_deposit_percentage'] ?? null,
            $data['deposit_inflation_adjusted'] ?? 0,
            $data['use_cash_assets_for_rebalance'] ?? 0,
            $data['profit_tax_rate'] ?? null,
            $data['profit_tax_rates_json'] ?? null,
            $data['id']
        ]);
    }

    public function delete($id, $userId = null) {
        try {
            $this->db->beginTransaction();

            // 1. Apagar detalhes de simulação (Simulation Asset Details)
            // Esses dependem de simulation_results, que depende de portfolios
            $sqlDetails = "DELETE sad FROM simulation_asset_details sad 
                           JOIN simulation_results sr ON sad.simulation_id = sr.id 
                           WHERE sr.portfolio_id = ?";
            $this->db->prepare($sqlDetails)->execute([$id]);

            // 2. Apagar resultados de simulação (Simulation Results)
            $sqlSimulations = "DELETE FROM simulation_results WHERE portfolio_id = ?";
            $this->db->prepare($sqlSimulations)->execute([$id]);

            // 3. Apagar ativos do portfólio (Portfolio Assets)
            $sqlAssets = "DELETE FROM portfolio_assets WHERE portfolio_id = ?";
            $this->db->prepare($sqlAssets)->execute([$id]);

            // 4. Finalmente apagar o portfólio
            // SÊNIOR: Se userId for fornecido, restringimos a exclusão (proteção extra)
            // Se for null, permitimos (usado por Admins no controller)
            $sql = "DELETE FROM portfolios WHERE id = ?";
            $params = [$id];

            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erro ao excluir portfólio ID $id: " . $e->getMessage());
            throw $e; // Repassa para o controller tratar
        }
    }
    
    public function getPortfolioAssets($portfolioId) {
        // Adicionado sa.currency e sa.tax_group para que a view.php e simulation_details.php consigam exibir as informações
        $sql = "SELECT sa.id as asset_id, sa.name, sa.currency, sa.tax_group, 
                       pa.allocation_percentage, pa.performance_factor, pa.id,
                       pa.rebalance_margin_down, pa.rebalance_margin_up
                FROM portfolio_assets pa
                JOIN system_assets sa ON pa.asset_id = sa.id
                WHERE pa.portfolio_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$portfolioId]);
        return $stmt->fetchAll();
    }

    public function getAll($userId = null) {
        if ($userId) {
            $sql = "SELECT * FROM portfolios WHERE user_id = ? OR is_system_default = TRUE ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql = "SELECT * FROM portfolios ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }    

    public function getTotalCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM portfolios");
        return $stmt->fetch()['total'];
    }  
    
    /**
     * Busca apenas os portfólios curados pelo sistema
     */
    public function getSystemPortfolios() {
        $sql = "SELECT * FROM portfolios WHERE is_system_default = TRUE ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Altera o status de um portfólio para Sistema (Apenas Admin)
     */
    public function toggleSystemStatus($id, $status) {
        // Sênior: Garantimos que o status seja booleano (0 ou 1)
        $status = $status ? 1 : 0;
        $sql = "UPDATE portfolios SET is_system_default = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

// Adicione este método à classe Portfolio:

    public function quickUpdateAssets($portfolioId, $assets) {
        try {
            $this->db->beginTransaction();

            // Remove alocações existentes
            $sqlDelete = "DELETE FROM portfolio_assets WHERE portfolio_id = ?";
            $this->db->prepare($sqlDelete)->execute([$portfolioId]);

            // Insere novas alocações
            $sqlInsert = "INSERT INTO portfolio_assets (portfolio_id, asset_id, allocation_percentage, performance_factor, rebalance_margin_down, rebalance_margin_up) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sqlInsert);

            foreach ($assets as $asset) {
                $stmt->execute([
                    $portfolioId,
                    $asset['asset_id'],
                    $asset['allocation'],
                    $asset['performance_factor'] ?? 1.0,
                    $asset['rebalance_margin_down'] ?? null,
                    $asset['rebalance_margin_up'] ?? null
                ]);
            }

            // Atualiza a data de modificação do portfólio
            $sqlUpdate = "UPDATE portfolios SET updated_at = NOW() WHERE id = ?";
            $this->db->prepare($sqlUpdate)->execute([$portfolioId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateAssets($portfolioId, $assets) {
        try {
            $this->db->beginTransaction();

            $sqlDelete = "DELETE FROM portfolio_assets WHERE portfolio_id = ?";
            $this->db->prepare($sqlDelete)->execute([$portfolioId]);

            $sql = "INSERT INTO portfolio_assets (portfolio_id, asset_id, allocation_percentage, performance_factor, rebalance_margin_down, rebalance_margin_up)
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            foreach ($assets as $key => $assetData) {
                // SÊNIOR: Suporta dois formatos de entrada:
                // 1. [asset_id => ['allocation' => X, ...]] (Usado em update() e quickUpdate())
                // 2. [['asset_id' => ID, 'allocation' => X, ...], ...] (Antigo formato de quickUpdate())
                
                $assetId = isset($assetData['asset_id']) ? $assetData['asset_id'] : $key;
                $allocation = floatval($assetData['allocation']);

                $stmt->execute([
                    $portfolioId,
                    $assetId,
                    $allocation,
                    $assetData['performance_factor'] ?? 1.0,
                    !empty($assetData['rebalance_margin_down']) ? (float)str_replace(',', '.', $assetData['rebalance_margin_down']) : null,
                    !empty($assetData['rebalance_margin_up']) ? (float)str_replace(',', '.', $assetData['rebalance_margin_up']) : null
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>