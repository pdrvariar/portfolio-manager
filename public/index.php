<?php
// public/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = dirname(__DIR__) . '/app';

$filesToLoad = [
    'core/Database.php',
    'core/Session.php',
    'core/Auth.php',
    'core/Router.php',
    'config/database.php',
    'utils/helpers.php' 
];

foreach ($filesToLoad as $file) {
    $fullPath = $baseDir . '/' . $file;
    if (file_exists($fullPath)) {
        require_once $fullPath;
    } else {
        // Se este erro aparecer, verifique se o ficheiro existe em app/config/database.php
        die("Erro: Ficheiro não encontrado: $fullPath");
    }
}

Session::start();

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
    setupRoutes($router); // Chama a função do web.php
}

// IMPORTANTE: Limpeza da URL para o Router
$url = $_GET['url'] ?? '';

// 2. Se a URL estiver vazia (usuário acessou a raiz), tentamos o fallback por REQUEST_URI
if (empty($url)) {
    $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    // Se o resultado for index.php, tratamos como página inicial (vazia)
    if ($url === 'index.php') $url = '';
}

try {
    // Agora o dispatch recebe apenas "assets" ou "portfolio/create"
    $router->dispatch($url);
} catch (Exception $e) {
    http_response_code($e->getCode() == 404 ? 404 : 500);
    echo "<h1>Erro: " . $e->getMessage() . "</h1>";
}