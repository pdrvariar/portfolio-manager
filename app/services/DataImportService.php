<?php
class DataImportService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function importHistoricalData($filePath, $assetCode) {
        try {
            // Verificar se ativo existe
            $assetModel = new Asset();
            $asset = $assetModel->findByCode($assetCode);
            
            if (!$asset) {
                return ['success' => false, 'message' => "Ativo '$assetCode' não encontrado."];
            }
            
            // Ler arquivo CSV
            $data = $this->readCSV($filePath);
            
            if (empty($data)) {
                return ['success' => false, 'message' => 'Arquivo CSV vazio ou inválido.'];
            }
            
            // Limpar dados existentes
            $this->db->prepare("DELETE FROM asset_historical_data WHERE asset_id = ?")
                     ->execute([$asset['id']]);
            
            // Inserir novos dados
            $inserted = $this->insertHistoricalData($asset['id'], $data);
            
            return [
                'success' => true,
                'message' => "Importados $inserted registros para o ativo '{$asset['name']}'.",
                'records' => $inserted
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na importação: ' . $e->getMessage()
            ];
        }
    }
    
    private function readCSV($filePath) {
        $data = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Pular duas primeiras linhas (cabeçalho e metadados)
            fgetcsv($handle);
            fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $date = $row[0];
                    $price = $row[1];
                    
                    // Validar e converter dados
                    if ($this->isValidDate($date) && is_numeric($price)) {
                        $data[] = [
                            'date' => $this->formatDate($date),
                            'price' => (float) $price
                        ];
                    }
                }
            }
            
            fclose($handle);
        }
        
        return $data;
    }
    
    private function insertHistoricalData($assetId, $data) {
        $sql = "INSERT INTO asset_historical_data (asset_id, reference_date, price) 
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        $count = 0;
        
        foreach ($data as $record) {
            $stmt->execute([$assetId, $record['date'], $record['price']]);
            $count++;
        }
        
        return $count;
    }
    
    private function isValidDate($date) {
        $pattern = '/^\d{4}-\d{2}$/';
        return preg_match($pattern, $date);
    }
    
    private function formatDate($date) {
        // Garantir formato YYYY-MM-DD
        return date('Y-m-d', strtotime($date . '-01'));
    }
    
    public function bulkImport($folderPath) {
        $files = glob($folderPath . '/*.csv');
        $results = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            $assetCode = str_replace('.csv', '', $filename);
            
            $result = $this->importHistoricalData($file, $assetCode);
            $results[] = [
                'file' => $filename,
                'asset' => $assetCode,
                'success' => $result['success'],
                'message' => $result['message']
            ];
        }
        
        return $results;
    }
}
?>