<?php
// src/app/services/AssetImporter.php

class AssetImporter {
    
    public function importFromCSV($filePath, $assetCode, $assetType, $currency, $options = []) {
        $defaultOptions = [
            'asset_name' => $assetCode,
            'update_existing' => false,
            'skip_duplicates' => true,
            'validate_prices' => true,
            'has_header' => true,
            'date_format' => 'Y-m-d',
            'decimal_separator' => '.',
            'normalize_dates' => true
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Create or get asset
        $assetModel = new Asset();
        $asset = $assetModel->findByCode($assetCode);
        
        if ($asset && !$options['update_existing']) {
            throw new Exception("Ativo com código '{$assetCode}' já existe");
        }
        
        if (!$asset) {
            // Create new asset
            $assetId = $assetModel->create([
                'code' => $assetCode,
                'name' => $options['asset_name'],
                'type' => $assetType,
                'currency' => $currency,
                'is_default' => false
            ]);
            
            $asset = $assetModel->find($assetId);
        } else {
            $assetId = $asset['id'];
        }
        
        // Parse CSV file
        $historyData = $this->parseCSV($filePath, $options);
        
        if (empty($historyData)) {
            throw new Exception("Nenhum dado válido encontrado no arquivo");
        }
        
        // Import history
        $assetHistoryModel = new AssetHistory();
        $importedCount = $assetHistoryModel->bulkInsert($assetId, $historyData, $options['skip_duplicates']);
        
        // Log import
        $this->logImport($assetId, $importedCount, $historyData);
        
        return [
            'asset_id' => $assetId,
            'count' => $importedCount
        ];
    }
    
    private function parseCSV($filePath, $options) {
        $historyData = [];
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new Exception("Não foi possível abrir o arquivo");
        }
        
        // Skip header if present
        if ($options['has_header']) {
            fgetcsv($handle);
        }
        
        $lineNumber = $options['has_header'] ? 2 : 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue; // Skip invalid rows
            }
            
            try {
                $dateStr = trim($row[0]);
                $valueStr = trim($row[1]);
                
                // Parse date
                $date = DateTime::createFromFormat($options['date_format'], $dateStr);
                if (!$date) {
                    throw new Exception("Formato de data inválido: {$dateStr}");
                }
                
                // Normalize date to first day of month if needed
                if ($options['normalize_dates'] && strlen($dateStr) <= 7) {
                    $date->modify('first day of this month');
                }
                
                $formattedDate = $date->format('Y-m-d');
                
                // Parse value
                $value = $this->parseNumber($valueStr, $options['decimal_separator']);
                if ($value === false) {
                    throw new Exception("Valor inválido: {$valueStr}");
                }
                
                // Validate price if enabled
                if ($options['validate_prices']) {
                    $this->validatePrice($value, $lineNumber);
                }
                
                // Add to history data
                $historyData[] = [
                    'date' => $formattedDate,
                    'price' => $value
                ];
                
            } catch (Exception $e) {
                error_log("Erro na linha {$lineNumber}: " . $e->getMessage());
                // Continue with next row
            }
            
            $lineNumber++;
        }
        
        fclose($handle);
        
        // Sort by date ascending
        usort($historyData, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        return $historyData;
    }
    
    private function parseNumber($value, $decimalSeparator) {
        $value = trim($value);
        
        if ($decimalSeparator === ',') {
            // Format: 1.000,50 -> 1000.50
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        
        // Remove any non-numeric characters except minus sign and dot
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
    
    private function validatePrice($price, $lineNumber) {
        if ($price <= 0) {
            throw new Exception("Preço deve ser maior que zero: {$price}");
        }
        
        if ($price > 10000000) {
            throw new Exception("Preço muito alto (máximo 10.000.000): {$price}");
        }
        
        if ($price < 0.0001) {
            throw new Exception("Preço muito baixo (mínimo 0.0001): {$price}");
        }
    }
    
    private function logImport($assetId, $recordCount, $historyData) {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? null;
        
        $stmt = $db->prepare("
            INSERT INTO asset_imports 
            (asset_id, user_id, records_imported, start_date, end_date, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $startDate = $historyData[0]['date'] ?? null;
        $endDate = $historyData[count($historyData) - 1]['date'] ?? null;
        
        $stmt->execute([$assetId, $userId, $recordCount, $startDate, $endDate]);
    }
}