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
        // Use obfuscateUrl aqui também para manter o padrão
        ['label' => '<i class="bi bi-house-door"></i> Home', 'url' => '/index.php?url=' . obfuscateUrl('dashboard')]
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
        $url = "/index.php?url=" . obfuscateUrl($controller); // Adicionado obfuscateUrl
        $breadcrumbs[] = ['label' => $labels[$controller], 'url' => $url];
    }

    // Adiciona a ação específica (Editar, Visualizar, etc)
    if ($action === 'view') $breadcrumbs[] = ['label' => 'Detalhes', 'url' => '#'];
    if ($action === 'edit' || $action === 'editUser') $breadcrumbs[] = ['label' => 'Edição', 'url' => '#'];
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

function redirectBack($fallbackUrl) {
    $location = $_SERVER['HTTP_REFERER'] ?? $fallbackUrl;
    header('Location: ' . $location);
    exit;
}

function obfuscateUrl($url) {
    if (getenv('URL_OBFUSCATE') !== 'true') return $url;

    $key = getenv('URL_SECRET_KEY');
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($url, 'aes-256-cbc', $key, 0, $iv);
    
    // Retorna o IV + Dado criptografado em Base64 seguro para URL
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($iv . $encrypted));
}

function deobfuscateUrl($hash) {
    if (getenv('URL_OBFUSCATE') !== 'true' || empty($hash)) return $hash;

    // HEURÍSTICA DE QA: Se a URL contém "/", ela já é um caminho legível, 
    // então não tentamos descriptografar para evitar o erro de IV curto.
    if (strpos($hash, '/') !== false) return $hash;

    $key = getenv('URL_SECRET_KEY');
    
    // 1. Restaurar caracteres e preenchimento (Padding) do Base64
    $base64 = str_replace(['-', '_'], ['+', '/'], $hash);
    $remainder = strlen($base64) % 4;
    if ($remainder) {
        $base64 .= str_repeat('=', 4 - $remainder);
    }
    
    $data = base64_decode($base64, true); // O 'true' ativa validação estrita
    $ivSize = openssl_cipher_iv_length('aes-256-cbc'); // Esperado: 16 bytes

    // 2. VALIDAÇÃO CRUCIAL: O dado decodificado deve ter pelo menos 16 bytes (tamanho do IV)
    if (!$data || strlen($data) < $ivSize) {
        return $hash; // Retorna o original se não parecer um hash válido
    }

    $iv = substr($data, 0, $ivSize);
    $encrypted = substr($data, $ivSize);
    
    // 3. Tenta descriptografar
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    
    // Se a chave estiver errada ou o dado for lixo, o openssl retorna false.
    // Nesse caso, retornamos o hash original para o Router tentar processar.
    return $decrypted ?: $hash;
}

/**
 * Converte datas para o formato internacional numérico: 10/2014
 */
function formatMonthYear($date) {
    if (empty($date)) return '';
    return date('m/Y', strtotime($date));
}

function formatFullDate($date) {
    if (empty($date)) return '';
    
    $months = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    
    $timestamp = strtotime($date);
    $month = (int)date('n', $timestamp);
    $year = date('Y', $timestamp);
    
    return $months[$month] . ' de ' . $year;
}

?>