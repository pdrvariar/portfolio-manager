<?php
// public/index.php
ob_start();

// 1. O Autoload do Composer é a prioridade zero
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Log sênior para ajudar no debug do Docker
    error_log("Composer Autoload não encontrado em: " . $autoloadPath);
    die("Erro Crítico: Bibliotecas não instaladas. Execute 'composer require' no container.");
}

// 2. Carregar o restante da aplicação
require_once __DIR__ . '/../app/core/Env.php';
Env::load(__DIR__ . '/../.env');

$isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
error_reporting($isDev ? E_ALL : 0);
ini_set('display_errors', $isDev ? 1 : 0);

// 4. Segurança de Sessão Profissional
// Em vez de session_start() puro, usamos a nossa classe Core para aplicar headers de segurança
require_once __DIR__ . '/../app/core/Session.php';
Session::start(); 

// Headers para evitar cache de dados sensíveis (SaaS Financeiro)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Carregamento do Núcleo (Core)
$baseDir = dirname(__DIR__) . '/app';
$coreFiles = [
    'core/Database.php',
    'core/Auth.php',
    'core/Router.php',
    'utils/helpers.php' 
];

foreach ($coreFiles as $file) {
    require_once "$baseDir/$file";
}

// 6. Autoloader para Classes da Aplicação (PSR-4 Simplificado)
spl_autoload_register(function ($class) use ($baseDir) {
    $folders = ['models', 'services', 'controllers'];
    foreach ($folders as $folder) {
        $file = "$baseDir/$folder/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 7. Sistema de Rotas
$router = new Router();
$routesPath = $baseDir . '/routers/web.php';

if (file_exists($routesPath)) {
    require_once $routesPath;
    setupRoutes($router);
}

// 8. Captura e Tratamento da URL
$url = $_GET['url'] ?? '';
if (empty($url)) {
    $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

// 9. Despacho da Requisição
try {
    $router->dispatch($url);
} catch (Exception $e) {
    http_response_code($e->getCode() == 404 ? 404 : 500);
    if ($isDev) {
        echo "<div style='padding:20px; border:5px solid red;'>";
        echo "<h2>Erro Sénior: " . $e->getMessage() . "</h2>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        // Em produção, mostra uma página de erro genérica e bonita
        require_once __DIR__ . '/../app/views/errors/500.php';
    }
}

ob_end_flush();