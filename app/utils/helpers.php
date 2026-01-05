<?php
// Funções auxiliares

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatCurrency($value, $currency = 'BRL') {
    $symbols = [
        'BRL' => 'R$',
        'USD' => '$',
        'EUR' => '€'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    
    return $symbol . ' ' . number_format($value, 2, ',', '.');
}

function formatPercentage($value) {
    return number_format($value, 2, ',', '.') . '%';
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function arrayToCsv($array) {
    if (count($array) == 0) {
        return null;
    }
    
    ob_start();
    $df = fopen("php://output", 'w');
    fputcsv($df, array_keys(reset($array)));
    
    foreach ($array as $row) {
        fputcsv($df, $row);
    }
    
    fclose($df);
    return ob_get_clean();
}

function logActivity($message, $userId = null) {
    $logFile = __DIR__ . '/../logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = $userId ?? ($_SESSION['user_id'] ?? 'system');
    
    $logMessage = "[$timestamp] [User: $user] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function renderBreadcrumbs($params) {
    // Início sempre no Dashboard
    $breadcrumbs = [
        ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=dashboard']
    ];

    $controller = strtolower(str_replace('Controller', '', $params['controller'] ?? ''));
    $action = $params['action'] ?? '';

    // Mapeamento de nomes amigáveis para os Controllers
    $labels = [
        'portfolio' => 'Portfólios',
        'asset'     => 'Ativos',
        'profile'   => 'Meu Perfil',
        'admin'     => 'Admin'
    ];

    if (isset($labels[$controller])) {
        $url = "/index.php?url=$controller";
        $breadcrumbs[] = ['label' => $labels[$controller], 'url' => $url];
    }

    // Adiciona a ação específica (Editar, Visualizar, etc)
    if ($action === 'view') $breadcrumbs[] = ['label' => 'Detalhes', 'url' => '#'];
    if ($action === 'edit') $breadcrumbs[] = ['label' => 'Edição', 'url' => '#'];
    if ($action === 'import') $breadcrumbs[] = ['label' => 'Importação', 'url' => '#'];

    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-4 shadow-sm p-2 bg-white rounded">';
    foreach ($breadcrumbs as $index => $crumb) {
        $active = ($index === count($breadcrumbs) - 1);
        $html .= sprintf(
            '<li class="breadcrumb-item %s">%s</li>',
            $active ? 'active text-primary fw-bold' : '',
            $active ? sanitize($crumb['label']) : '<a href="'.$crumb['url'].'" class="text-decoration-none">'.$crumb['label'].'</a>'
        );
    }
    $html .= '</ol></nav>';
    
    return $html;
}
?>