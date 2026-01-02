<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Simulation.php';
require_once __DIR__ . '/../services/SimulationService.php';
require_once __DIR__ . '/../services/ChartService.php';

class SimulationController {
    
    public function results($executionId) {
        AuthMiddleware::requireLogin();
        
        $simulationModel = new Simulation();
        $simulation = $simulationModel->findByExecutionId($executionId, $_SESSION['user_id']);
        
        if (!$simulation) {
            $_SESSION['error'] = 'Simulação não encontrada';
            header('Location: /portfolio');
            exit;
        }
        
        $results = json_decode($simulation['result_data'], true);
        $metrics = json_decode($simulation['metrics'], true);
        $charts = json_decode($simulation['charts_html'], true);
        
        include __DIR__ . '/../../views/simulation/results.php';
    }
    
    public function apiStatus($executionId) {
        AuthMiddleware::requireLogin();
        
        $simulationModel = new Simulation();
        $simulation = $simulationModel->findByExecutionId($executionId, $_SESSION['user_id']);
        
        if (!$simulation) {
            Response::error('Simulação não encontrada', 404);
        }
        
        Response::success([
            'status' => $simulation['status'],
            'progress' => $this->calculateProgress($simulation),
            'started_at' => $simulation['started_at'],
            'completed_at' => $simulation['completed_at']
        ]);
    }
    
    public function apiResults($executionId) {
        AuthMiddleware::requireLogin();
        
        $simulationModel = new Simulation();
        $simulation = $simulationModel->findByExecutionId($executionId, $_SESSION['user_id']);
        
        if (!$simulation) {
            Response::error('Simulação não encontrada', 404);
        }
        
        if ($simulation['status'] !== 'COMPLETED') {
            Response::error('Simulação ainda não concluída', 202);
        }
        
        $results = json_decode($simulation['result_data'], true);
        $metrics = json_decode($simulation['metrics'], true);
        $charts = json_decode($simulation['charts_html'], true);
        
        Response::success([
            'results' => $results,
            'metrics' => $metrics,
            'charts' => $charts,
            'simulation' => $simulation
        ]);
    }
    
    public function download($executionId, $format = 'csv') {
        AuthMiddleware::requireLogin();
        
        $simulationModel = new Simulation();
        $simulation = $simulationModel->findByExecutionId($executionId, $_SESSION['user_id']);
        
        if (!$simulation || $simulation['status'] !== 'COMPLETED') {
            $_SESSION['error'] = 'Simulação não encontrada ou não concluída';
            header('Location: /portfolio');
            exit;
        }
        
        $results = json_decode($simulation['result_data'], true);
        
        if ($format === 'csv') {
            $this->downloadCSV($results, $simulation);
        } elseif ($format === 'excel') {
            $this->downloadExcel($results, $simulation);
        } else {
            $this->downloadJSON($results, $simulation);
        }
    }
    
    private function downloadCSV($results, $simulation) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="simulacao_' . $simulation['execution_id'] . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalho
        if (!empty($results)) {
            fputcsv($output, array_keys($results[0]));
            
            // Dados
            foreach ($results as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
    
    private function downloadJSON($results, $simulation) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="simulacao_' . $simulation['execution_id'] . '.json"');
        
        echo json_encode([
            'simulation' => $simulation,
            'results' => $results,
            'metrics' => json_decode($simulation['metrics'], true)
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    private function calculateProgress($simulation) {
        // Lógica para calcular progresso baseado no tempo
        $start = strtotime($simulation['started_at']);
        $now = time();
        
        if ($simulation['status'] === 'COMPLETED') {
            return 100;
        }
        
        if ($simulation['status'] === 'RUNNING') {
            // Estimar 60 segundos para simulação
            $elapsed = $now - $start;
            $progress = min(90, ($elapsed / 60) * 100);
            return round($progress, 2);
        }
        
        return 0;
    }
}