<?php
// public/index.php - CORRIGIDO
ob_start(); // Inicia buffer de output
session_start(); // ← PRIMEIRA LINHA DEPOIS DE <?php

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = dirname(__DIR__) . '/app';

$filesToLoad = [
    'core/Database.php',
    'core/Session.php',      // Este arquivo NÃO deve ter session_start()
    'core/Auth.php',
    'core/Router.php',
    'config/database.php',
    'utils/helpers.php' 
];

require_once $baseDir . '/core/Env.php';
Env::load(__DIR__ . '/../.env');

foreach ($filesToLoad as $file) {
    $fullPath = $baseDir . '/' . $file;
    if (file_exists($fullPath)) {
        require_once $fullPath;
    } else {
        die("Erro: Ficheiro não encontrado: $fullPath");
    }
}

// REMOVA ESTA LINHA: Session::start(); ← REMOVER

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

$router = new Router();
$routesPath = $baseDir . '/routers/web.php';

if (file_exists($routesPath)) {
    require_once $routesPath;
    setupRoutes($router);
}

$url = $_GET['url'] ?? '';

if (empty($url)) {
    $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    if ($url === 'index.php') $url = '';
}

try {
    $router->dispatch($url);
} catch (Exception $e) {
    http_response_code($e->getCode() == 404 ? 404 : 500);
    echo "<h1>Erro: " . $e->getMessage() . "</h1>";
}
?>
<?php ob_end_flush(); ?>