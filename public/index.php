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
$envPath = __DIR__ . '/../app/core/Env.php';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../app/Core/Env.php';
}
require_once $envPath;
Env::load(__DIR__ . '/../.env');

$isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
error_reporting($isDev ? E_ALL : 0);
ini_set('display_errors', $isDev ? 1 : 0);

// 4. Segurança de Sessão Profissional
// Em vez de session_start() puro, usamos a nossa classe Core para aplicar headers de segurança
$sessionPath = __DIR__ . '/../app/core/Session.php';
if (!file_exists($sessionPath)) {
    $sessionPath = __DIR__ . '/../app/Core/Session.php';
}
require_once $sessionPath;
\App\Core\Session::start(); 

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
        'core/EntityManagerFactory.php', 
        'utils/helpers.php' 
    ];

    foreach ($coreFiles as $file) {
        $filePath = "$baseDir/$file";
        if (file_exists($filePath)) {
            require_once $filePath;
        } else {
            // Tenta carregar com a primeira letra maiúscula (Core, Utils)
            $parts = explode('/', $file);
            $parts[0] = ucfirst($parts[0]);
            $upperPath = $baseDir . '/' . implode('/', $parts);
            if (file_exists($upperPath)) {
                require_once $upperPath;
            }
        }
    }

// Aliases para manter compatibilidade com código legado e views
class_alias(\App\Core\Auth::class, 'Auth');
class_alias(\App\Core\Session::class, 'Session');

// 6. Autoloader para Classes da Aplicação (PSR-4 Simplificado)
spl_autoload_register(function ($class) use ($baseDir) {
    // SÊNIOR: Normaliza o nome da classe para PSR-4 real
    $class = ltrim($class, '\\');

    // Primeiro tenta resolver via namespace App
    if (strpos($class, 'App\\') === 0) {
        $relativeClass = substr($class, 4);
        // SÊNIOR: Em sistemas Linux (Hostinger), a capitalização do diretório importa.
        // Se o namespace é App\Controllers, ele busca app/Controllers/Nome.php
        // No entanto, se a pasta física for app/controllers, o file_exists falha.
        // Vamos tentar resolver de forma insensível a maiúsculas para o caminho base.
        $file = $baseDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }

        // Tenta converter o primeiro segmento para minúsculo (ex: Controllers -> controllers)
        $parts = explode('\\', $relativeClass);
        if (count($parts) > 1) {
            $parts[0] = strtolower($parts[0]);
            $fileLower = $baseDir . '/' . implode('/', $parts) . '.php';
            if (file_exists($fileLower)) {
                require_once $fileLower;
                return;
            }
        }
    }

    // Fallback para modelos legados ou controllers sem namespace carregados pelo Router
    $folders = ['models', 'services', 'controllers', 'Entities', 'core', 'Models', 'Services', 'Controllers', 'Core'];
    foreach ($folders as $folder) {
        $file = "$baseDir/$folder/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 7. Sistema de Rotas
$router = new \App\Core\Router();
$routesPath = $baseDir . '/routers/web.php';

if (!file_exists($routesPath)) {
    $routesPath = $baseDir . '/Routers/web.php';
}

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