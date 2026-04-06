<?php
/**
 * Script para carga inicial de dados históricos do IPCA
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/core/Env.php';
Env::load(__DIR__ . '/.env');

// Tentar forçar localhost se 'db' (Docker) não for resolvível no ambiente de execução do script
if (($_ENV['DB_HOST'] ?? '') === 'db' || (getenv('DB_HOST') ?: 'db') === 'db') {
    // Verificar se a porta 3306 está aberta no localhost
    $connection = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 2);
    if (is_resource($connection)) {
        $_ENV['DB_HOST'] = '127.0.0.1';
        fclose($connection);
    } else {
        echo "Aviso: 'db' está configurado mas 127.0.0.1:3306 não está acessível.\n";
        echo "Se estiver usando Docker, execute: docker-compose exec app php load_ipca_data.php\n";
    }
}

$baseDir = __DIR__ . '/app';
require_once "$baseDir/core/Database.php";
require_once "$baseDir/utils/helpers.php";

spl_autoload_register(function ($class) use ($baseDir) {
    $folders = ['models', 'services', 'services/quotes'];
    foreach ($folders as $folder) {
        $file = "$baseDir/$folder/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

try {
    $assetModel = new Asset();
    
    // 1. Verificar se o ativo IPCA já existe
    $ipcaAsset = $assetModel->findByCode('IPCA');
    
    if (!$ipcaAsset) {
        echo "Ativo IPCA não encontrado. Criando...\n";
        $result = $assetModel->create([
            'code' => 'IPCA',
            'name' => 'IPCA',
            'currency' => 'BRL',
            'asset_type' => 'INFLACAO',
            'is_active' => true
        ]);
        
        if ($result['success']) {
            $ipcaId = $result['id'];
            echo "Ativo IPCA criado com ID: $ipcaId\n";
            
            // Atualizar source para BCB
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE system_assets SET source = 'BCB' WHERE id = ?");
            $stmt->execute([$ipcaId]);
            
            $ipcaAsset = $assetModel->findById($ipcaId);
        } else {
            die("Erro ao criar ativo IPCA: " . $result['message'] . "\n");
        }
    } else {
        echo "Ativo IPCA já existe (ID: {$ipcaAsset['id']}).\n";
        // Garantir que a fonte está correta
        if (($ipcaAsset['source'] ?? '') !== 'BCB' || ($ipcaAsset['asset_type'] ?? '') !== 'INFLACAO') {
            echo "Atualizando configurações do ativo IPCA...\n";
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE system_assets SET source = 'BCB', asset_type = 'INFLACAO' WHERE id = ?");
            $stmt->execute([$ipcaAsset['id']]);
            $ipcaAsset = $assetModel->findById($ipcaAsset['id']);
        }
    }
    
    // 2. Executar a estratégia de importação
    echo "Iniciando busca de dados históricos do IPCA via API BCB...\n";
    $strategy = QuoteStrategyFactory::make($ipcaAsset);
    
    if (!$strategy) {
        die("Erro: Não foi possível instanciar a estratégia para o IPCA.\n");
    }
    
    // Forçar carga completa para garantir todos os dados desde 1994
    $importResult = $strategy->updateQuotes($ipcaAsset, true);
    
    if ($importResult['success']) {
        echo "Sucesso! " . $importResult['message'] . "\n";
    } else {
        echo "Falha na importação: " . $importResult['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Erro inesperado: " . $e->getMessage() . "\n";
}
