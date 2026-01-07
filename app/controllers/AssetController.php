<?php
class AssetController {
private $assetModel;
    private $params; // Adicione esta propriedade

    public function __construct($params = []) { // Receba os parâmetros aqui
        $this->params = $params;
        Session::start();
        $this->assetModel = new Asset();
    }
    
    public function index() {
        Auth::checkAuthentication();
        
        $isAdmin = $_SESSION['is_admin'] ?? false;
        
        if ($isAdmin) {
            // CORREÇÃO: Altere de getAssetsWithData() para getAllWithDetails()
            $assets = $this->assetModel->getAllWithDetails();
        } else {
            $assets = $this->assetModel->getAll();
        }
        
        require_once __DIR__ . '/../views/asset/index.php';
    }
    
    public function import() {
        Auth::checkAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('assets/import'));
            }            

            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $result = $this->processCSV($_FILES['csv_file']['tmp_name'], $_FILES['csv_file']['name']);
                
                if ($result['success']) {
                    Session::setFlash('success', $result['message']);
                } else {
                    Session::setFlash('error', $result['message']);
                }
                
                // REDIRECIONAMENTO CORRIGIDO: Sempre use o caminho da raiz com "/"
                header('Location: /index.php?url=' . obfuscateUrl('assets'));
                exit;
            }
        }
        require_once __DIR__ . '/../views/asset/import.php';
    }

    private function processCSV($filePath, $originalName) {
        try {
            $handle = fopen($filePath, 'r');
            fgetcsv($handle); // Pula cabeçalho

            $assetCode = strtoupper(str_replace('.csv', '', $originalName));
            $asset = $this->assetModel->findByCode($assetCode);
            
            if (!$asset) {
                $this->assetModel->create(['code' => $assetCode, 'name' => $assetCode]);
                $asset = $this->assetModel->findByCode($assetCode);
            }

            $historicalData = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $historicalData[] = [
                        'date' => $row[0] . '-01', // Converte YYYY-MM para YYYY-MM-DD
                        'price' => floatval($row[1]) // Usando price em vez de value
                    ];
                }
            }
            fclose($handle);

            if (!empty($historicalData)) {
                $count = $this->assetModel->importHistoricalData($asset['id'], $historicalData);
                return ['success' => true, 'message' => "Importados $count registros para $assetCode"];
            }
            return ['success' => false, 'message' => 'Nenhum dado válido no CSV.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }    
    
    public function view() {
        Auth::checkAuthentication();
        
        // Recupere o ID dos parâmetros armazenados
        $id = $this->params['id'] ?? null;
        
        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('assets'));
            exit;
        }

        $asset = $this->assetModel->findById($id);
        if (!$asset) {
            Session::setFlash('error', 'Ativo não encontrado.');
            header('Location: /index.php?url=' . obfuscateUrl('assets'));
            exit;
        }
        
        $historicalData = $this->assetModel->getHistoricalData($id);
        require_once __DIR__ . '/../views/asset/view.php';
    }

    public function apiHistorical() {
        $assetId = $this->params['id'] ?? null;

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autorizado']);
            exit;
        }
        
        $data = $this->assetModel->getHistoricalData($assetId);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function delete() {
        Auth::checkAdmin();
        
        $id = $this->params['id'] ?? null;
        $result = $this->assetModel->delete($id);
        
        if ($result['success']) {
            Session::setFlash('success', 'Ativo removido com sucesso.');
        } else {
            Session::setFlash('error', 'Erro ao remover ativo.');
        }
        
        header('Location: /index.php?url=' . obfuscateUrl('assets'));
        exit;
    }

    public function getAssetApi() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        
        $asset = $this->assetModel->findById($id);
        
        header('Content-Type: application/json');
        if ($asset) {
            echo json_encode($asset);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Ativo não encontrado']);
        }
        exit;
    }

    public function updateApi() {
        Auth::checkAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            // QA: Validação do Token CSRF enviado via AJAX
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
                exit;
            }

            $id = $_POST['id'];
            $data = [
                'name' => sanitize($_POST['name']),
                'currency' => sanitize($_POST['currency']),
                'asset_type' => sanitize($_POST['asset_type']),
                'is_active' => 1
            ];
            
            $result = $this->assetModel->update($id, $data);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
    } 


    public function getBenchmarkData() {
        Auth::checkAuthentication();
        $assetId = $this->params['id'] ?? null;
        $startDate = $_GET['start'] ?? null;
        $endDate = $_GET['end'] ?? null;
        $baseValue = floatval($_GET['base'] ?? 100000);
        $portfolioCurrency = $_GET['currency'] ?? 'BRL';

        $asset = $this->assetModel->findById($assetId);
        $historical = $this->assetModel->getHistoricalData($assetId, $startDate, $endDate);
        
        if (!$asset || empty($historical)) {
            echo json_encode(['success' => false]); exit;
        }

        // Lógica de Câmbio: Só carrega se as moedas forem diferentes
        $fxData = [];
        if ($portfolioCurrency !== $asset['currency']) {
            $fxAsset = $this->assetModel->findByCode('USD-BRL');
            if ($fxAsset) {
                $rawFx = $this->assetModel->getHistoricalData($fxAsset['id'], $startDate, $endDate);
                foreach ($rawFx as $f) $fxData[$f['reference_date']] = floatval($f['price']);
            }
        }

        $normalizedValues = []; $returns = [];
        $accumulatedValue = $baseValue;
        $prevPrice = floatval($historical[0]['price']);
        $lastFxRate = !empty($fxData) ? ($fxData[$historical[0]['reference_date']] ?? null) : null;

        foreach ($historical as $index => $row) {
            $currentPrice = floatval($row['price']);
            $currentFxRate = $fxData[$row['reference_date']] ?? null;
            
            // 1. Retorno Mensal: Taxa Mensal (Selic) vs Variação de Preço (Ações/BTC)
            if ($asset['asset_type'] === 'TAXA_MENSAL') {
                $monthlyReturn = $currentPrice / 100;
            } else {
                $monthlyReturn = ($index > 0 && $prevPrice > 0) ? ($currentPrice / $prevPrice) - 1 : 0;
                $prevPrice = $currentPrice;
            }

            // 2. Ajuste Cambial (Se houver)
            if ($lastFxRate > 0 && $currentFxRate > 0) {
                $fxVar = ($portfolioCurrency === 'BRL' && $asset['currency'] === 'USD') 
                        ? ($currentFxRate / $lastFxRate) - 1 
                        : ($lastFxRate / $currentFxRate) - 1;
                $monthlyReturn = (1 + $monthlyReturn) * (1 + $fxVar) - 1;
            }
            $lastFxRate = $currentFxRate;

            // 3. Resultado Final
            $accumulatedValue *= (1 + $monthlyReturn);
            $normalizedValues[] = round($accumulatedValue, 2);
            $returns[] = $monthlyReturn;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'values' => $normalizedValues, 'returns' => $returns]);
        exit;
    }

}
?>