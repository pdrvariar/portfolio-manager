<?php
class AssetController {
    private $assetModel;
    
    public function __construct() {
        $this->assetModel = new Asset();
        Session::start();
    }
    
    public function index() {
        Auth::checkAuthentication();
        
        $assets = $this->assetModel->getAll();
        
        require_once '../app/views/asset/index.php';
    }
    
    public function import() {
        Auth::checkAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['csv_file'];
                
                // Validar extensão
                $allowed = ['csv', 'txt'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                
                if (!in_array(strtolower($ext), $allowed)) {
                    Session::setFlash('error', 'Apenas arquivos CSV são permitidos.');
                    header('Location: /assets/import');
                    exit;
                }
                
                // Processar arquivo
                $result = $this->assetModel->importFromCSV($file['tmp_name']);
                
                if ($result['success']) {
                    Session::setFlash('success', $result['message']);
                } else {
                    Session::setFlash('error', $result['message']);
                }
                
                header('Location: /assets');
                exit;
            } else {
                Session::setFlash('error', 'Erro no upload do arquivo.');
                header('Location: /assets/import');
                exit;
            }
        }
        
        require_once '../app/views/asset/import.php';
    }
    
    public function historicalData($assetId) {
        Auth::checkAuthentication();
        
        $data = $this->assetModel->getHistoricalData($assetId);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
?>