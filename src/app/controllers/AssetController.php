<?php
require_once __DIR__ . '/../models/Asset.php';
require_once __DIR__ . '/../models/AssetHistory.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AssetController {
    
    public function getAllAssets() {
        AuthMiddleware::requireLogin();
        
        $assetModel = new Asset();
        $assets = $assetModel->getAll();
        
        return $assets;
    }
    
    public function getAssetHistory($assetId) {
        AuthMiddleware::requireLogin();
        
        $assetHistoryModel = new AssetHistory();
        $history = $assetHistoryModel->getHistory($assetId);
        
        return $history;
    }
    
    public function importAssets() {
        AuthMiddleware::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleImport();
            return;
        }
        
        // Show import form
        include __DIR__ . '/../../views/assets/import.php';
    }
    
    private function handleImport() {
        AuthMiddleware::requireLogin();
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Por favor, selecione um arquivo CSV válido';
            header('Location: /assets/import');
            exit;
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $assetCode = $_POST['asset_code'] ?? '';
        $assetType = $_POST['asset_type'] ?? 'STOCK';
        $currency = $_POST['currency'] ?? 'BRL';
        
        if (empty($assetCode)) {
            $_SESSION['error'] = 'Código do ativo é obrigatório';
            header('Location: /assets/import');
            exit;
        }
        
        try {
            $importer = new AssetImporter();
            $result = $importer->importFromCSV($file, $assetCode, $assetType, $currency);
            
            $_SESSION['success'] = "Importados {$result['count']} registros para o ativo {$assetCode}";
            header('Location: /assets');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro na importação: ' . $e->getMessage();
            header('Location: /assets/import');
            exit;
        }
    }
    
    public function updateAssetPrice($assetId) {
        AuthMiddleware::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método não permitido', 405);
        }
        
        $price = $_POST['price'] ?? 0;
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if ($price <= 0) {
            Response::error('Preço inválido');
        }
        
        $assetHistoryModel = new AssetHistory();
        $success = $assetHistoryModel->updatePrice($assetId, $date, $price);
        
        if ($success) {
            Response::success(['message' => 'Preço atualizado com sucesso']);
        } else {
            Response::error('Erro ao atualizar preço');
        }
    }
    
    public function getAssetStatistics($assetId) {
        AuthMiddleware::requireLogin();
        
        $assetModel = new Asset();
        $asset = $assetModel->find($assetId);
        
        if (!$asset) {
            Response::error('Ativo não encontrado', 404);
        }
        
        $assetHistoryModel = new AssetHistory();
        $history = $assetHistoryModel->getHistory($assetId);
        
        if (empty($history)) {
            Response::error('Nenhum histórico disponível para este ativo');
        }
        
        // Calculate statistics
        $prices = array_column($history, 'price');
        $dates = array_column($history, 'date');
        
        $statistics = [
            'current_price' => end($prices),
            'first_price' => reset($prices),
            'min_price' => min($prices),
            'max_price' => max($prices),
            'avg_price' => array_sum($prices) / count($prices),
            'total_return' => (end($prices) - reset($prices)) / reset($prices) * 100,
            'start_date' => reset($dates),
            'end_date' => end($dates),
            'data_points' => count($history)
        ];
        
        // Calculate monthly returns
        $monthlyReturns = [];
        for ($i = 1; $i < count($prices); $i++) {
            $return = ($prices[$i] - $prices[$i-1]) / $prices[$i-1] * 100;
            $monthlyReturns[] = $return;
        }
        
        if (!empty($monthlyReturns)) {
            $statistics['avg_monthly_return'] = array_sum($monthlyReturns) / count($monthlyReturns);
            $statistics['volatility'] = $this->calculateVolatility($monthlyReturns);
            $statistics['positive_months'] = count(array_filter($monthlyReturns, function($r) { return $r > 0; }));
            $statistics['negative_months'] = count(array_filter($monthlyReturns, function($r) { return $r < 0; }));
        }
        
        Response::success([
            'asset' => $asset,
            'statistics' => $statistics,
            'history_summary' => array_slice($history, -12) // Last 12 months
        ]);
    }
    
    private function calculateVolatility($returns) {
        if (count($returns) < 2) return 0;
        
        $mean = array_sum($returns) / count($returns);
        $variance = 0;
        
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        
        $variance /= (count($returns) - 1);
        return sqrt($variance);
    }


    public function apiPreviewImport() {
        AuthMiddleware::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método não permitido', 405);
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Nenhum arquivo enviado');
        }
        
        $file = $_FILES['file']['tmp_name'];
        $hasHeader = ($_POST['has_header'] ?? '1') === '1';
        $dateFormat = $_POST['date_format'] ?? 'Y-m-d';
        $decimalSeparator = $_POST['decimal_separator'] ?? '.';
        
        try {
            $preview = $this->previewCSV($file, $hasHeader, $dateFormat, $decimalSeparator);
            Response::success($preview);
            
        } catch (Exception $e) {
            Response::error('Erro ao processar arquivo: ' . $e->getMessage());
        }
    }

    public function apiImportAsset() {
        AuthMiddleware::requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método não permitido', 405);
        }
        
        $assetCode = $_POST['asset_code'] ?? '';
        $assetName = $_POST['asset_name'] ?? $assetCode;
        $assetType = $_POST['asset_type'] ?? 'STOCK';
        $currency = $_POST['currency'] ?? 'BRL';
        $updateExisting = ($_POST['update_existing'] ?? '0') === '1';
        $skipDuplicates = ($_POST['skip_duplicates'] ?? '1') === '1';
        $validatePrices = ($_POST['validate_prices'] ?? '1') === '1';
        
        if (empty($assetCode)) {
            Response::error('Código do ativo é obrigatório');
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Arquivo CSV é obrigatório');
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $hasHeader = ($_POST['has_header'] ?? '1') === '1';
        $dateFormat = $_POST['date_format'] ?? 'Y-m-d';
        $decimalSeparator = $_POST['decimal_separator'] ?? '.';
        
        try {
            $importer = new AssetImporter();
            $result = $importer->importFromCSV($file, $assetCode, $assetType, $currency, [
                'asset_name' => $assetName,
                'update_existing' => $updateExisting,
                'skip_duplicates' => $skipDuplicates,
                'validate_prices' => $validatePrices,
                'has_header' => $hasHeader,
                'date_format' => $dateFormat,
                'decimal_separator' => $decimalSeparator
            ]);
            
            Response::success([
                'message' => 'Importação concluída com sucesso',
                'records_imported' => $result['count'],
                'asset_id' => $result['asset_id']
            ]);
            
        } catch (Exception $e) {
            Response::error('Erro na importação: ' . $e->getMessage());
        }
    }

    public function apiGetRecentImports() {
        AuthMiddleware::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $limit = $_GET['limit'] ?? 5;
        
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ai.*, a.code as asset_code, a.type as asset_type
            FROM asset_imports ai
            JOIN assets a ON ai.asset_id = a.id
            WHERE ai.user_id = ?
            ORDER BY ai.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$userId, $limit]);
        $imports = $stmt->fetchAll();
        
        Response::success($imports);
    }

    private function previewCSV($filePath, $hasHeader, $dateFormat, $decimalSeparator) {
        $previewData = [
            'total_rows' => 0,
            'columns' => [],
            'preview' => [],
            'start_date' => null,
            'end_date' => null,
            'min_value' => null,
            'max_value' => null,
            'valid' => true,
            'issues' => []
        ];
        
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new Exception('Não foi possível abrir o arquivo');
        }
        
        // Read first line for headers
        $firstLine = fgetcsv($handle);
        
        if ($hasHeader && $firstLine) {
            $previewData['columns'] = array_map('trim', $firstLine);
            $previewData['total_rows'] = -1; // Subtract header
        } else {
            // Generate column names
            $columnCount = count($firstLine);
            $previewData['columns'] = array_map(function($i) {
                return "Coluna " . ($i + 1);
            }, range(0, $columnCount - 1));
            
            // Reset file pointer
            rewind($handle);
        }
        
        // Read first 10 rows for preview
        $rowCount = 0;
        $previewRows = [];
        $dates = [];
        $values = [];
        
        while (($row = fgetcsv($handle)) !== false && $rowCount < 10) {
            $previewRows[] = array_combine($previewData['columns'], array_pad($row, count($previewData['columns']), ''));
            $rowCount++;
            
            // Try to extract date and value for validation
            if (isset($row[0]) && isset($row[1])) {
                $dateStr = trim($row[0]);
                $valueStr = trim($row[1]);
                
                // Try to parse date
                try {
                    $date = DateTime::createFromFormat($dateFormat, $dateStr);
                    if ($date) {
                        $dates[] = $date->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    $previewData['issues'][] = "Formato de data inválido na linha " . ($rowCount + ($hasHeader ? 1 : 0));
                }
                
                // Try to parse value
                $value = $this->parseNumber($valueStr, $decimalSeparator);
                if ($value !== false) {
                    $values[] = $value;
                }
            }
        }
        
        // Count total rows
        while (fgetcsv($handle) !== false) {
            $previewData['total_rows']++;
        }
        
        $previewData['total_rows'] += $rowCount;
        $previewData['preview'] = $previewRows;
        
        // Calculate statistics
        if (!empty($dates)) {
            $previewData['start_date'] = min($dates);
            $previewData['end_date'] = max($dates);
        }
        
        if (!empty($values)) {
            $previewData['min_value'] = min($values);
            $previewData['max_value'] = max($values);
        }
        
        // Validate
        if (empty($dates)) {
            $previewData['valid'] = false;
            $previewData['issues'][] = "Nenhuma data válida encontrada";
        }
        
        if (empty($values)) {
            $previewData['valid'] = false;
            $previewData['issues'][] = "Nenhum valor numérico válido encontrado";
        }
        
        fclose($handle);
        
        return $previewData;
    }

    private function parseNumber($value, $decimalSeparator) {
        $value = trim($value);
        
        if ($decimalSeparator === ',') {
            // Replace comma with dot for float conversion
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        
        // Remove currency symbols and other non-numeric characters
        $value = preg_replace('/[^\d\.\-]/', '', $value);
        
        if ($value === '' || $value === '-') {
            return false;
        }
        
        $floatValue = floatval($value);
        
        if (!is_finite($floatValue)) {
            return false;
        }
        
        return $floatValue;
    }    
}