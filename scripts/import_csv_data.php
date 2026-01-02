<?php
require_once __DIR__ . '/../src/app/config/database.php';
require_once __DIR__ . '/../src/app/models/Asset.php';
require_once __DIR__ . '/../src/app/models/AssetHistory.php';

if (php_sapi_name() !== 'cli') {
    die('Este script só pode ser executado via CLI');
}

class CSVImporter {
    private $assetModel;
    private $assetHistoryModel;
    
    public function __construct() {
        $this->assetModel = new Asset();
        $this->assetHistoryModel = new AssetHistory();
    }
    
    public function importDirectory($directory) {
        if (!is_dir($directory)) {
            die("Diretório não encontrado: $directory\n");
        }
        
        $files = glob($directory . '/*.csv');
        
        if (empty($files)) {
            die("Nenhum arquivo CSV encontrado\n");
        }
        
        foreach ($files as $file) {
            echo "Processando: " . basename($file) . "\n";
            $this->importFile($file);
        }
        
        echo "Importação concluída!\n";
    }
    
    private function importFile($filePath) {
        $filename = basename($filePath, '.csv');
        $metadata = $this->extractMetadata($filePath);
        
        // Criar ou obter ativo
        $asset = $this->assetModel->findByCode($filename);
        
        if (!$asset) {
            $assetData = [
                'code' => $filename,
                'name' => $metadata['name'] ?? $filename,
                'type' => $metadata['type'] ?? 'STOCK',
                'currency' => $metadata['currency'] ?? 'BRL',
                'is_default' => true
            ];
            
            $this->assetModel->create($assetData);
            $asset = $this->assetModel->findByCode($filename);
            
            echo "  Ativo criado: {$assetData['name']}\n";
        }
        
        // Importar histórico
        $historyData = $this->parseCSV($filePath);
        
        if (!empty($historyData)) {
            $this->assetHistoryModel->bulkInsert($asset['id'], $historyData);
            echo "  Importados " . count($historyData) . " registros\n";
        }
    }
    
    private function extractMetadata($filePath) {
        $metadata = [
            'name' => '',
            'type' => 'STOCK',
            'currency' => 'BRL'
        ];
        
        $handle = fopen($filePath, 'r');
        
        // Ler segunda linha
        fgets($handle); // Pular primeira linha
        $secondLine = fgets($handle);
        fclose($handle);
        
        if ($secondLine) {
            $parts = explode(',', $secondLine);
            
            if (count($parts) >= 2) {
                $metadata['name'] = trim($parts[0]);
                
                if (strpos($parts[1], ':') !== false) {
                    list($type, $currency) = explode(':', $parts[1]);
                    $metadata['type'] = strtoupper(trim($type));
                    $metadata['currency'] = strtoupper(trim($currency));
                }
            }
        }
        
        return $metadata;
    }
    
    private function parseCSV($filePath) {
        $handle = fopen($filePath, 'r');
        $history = [];
        
        // Pular duas primeiras linhas (cabeçalho e metadados)
        fgets($handle);
        fgets($handle);
        
        while (($line = fgets($handle)) !== false) {
            $data = str_getcsv($line);
            
            if (count($data) < 2) continue;
            
            $yearMonth = trim($data[0]);
            $value = trim($data[1]);
            
            // Validar formato YYYY-MM
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $yearMonth)) {
                continue;
            }
            
            // Converter para data (primeiro dia do mês)
            $date = date('Y-m-d', strtotime($yearMonth . '-01'));
            
            // Converter valor para float
            $price = floatval($value);
            
            if ($price > 0) {
                $history[] = [
                    'date' => $date,
                    'price' => $price
                ];
            }
        }
        
        fclose($handle);
        return $history;
    }
}

// Uso
if ($argc < 2) {
    die("Uso: php import_csv_data.php <diretorio_csv>\n");
}

$directory = $argv[1];
$importer = new CSVImporter();
$importer->importDirectory($directory);