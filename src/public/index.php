<?php
session_start();

// Configuração de erros
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Carregar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Carregar helpers
require_once __DIR__ . '/../app/helpers/Database.php';
require_once __DIR__ . '/../app/helpers/Response.php';
require_once __DIR__ . '/../app/helpers/Validation.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');

// Definir constantes
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('APP_ROOT', dirname(__DIR__));

// Roteamento básico
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remover query string
$request = strtok($request, '?');

// API Routes
if (strpos($request, '/api/') === 0) {
    header('Content-Type: application/json');
    
    // CORS headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    
    if ($method === 'OPTIONS') {
        exit;
    }
    
    $apiPath = substr($request, 4); // Remove '/api'
    
    try {
        switch ($apiPath) {
            case '/auth/login':
                if ($method === 'POST') {
                    $controller = new AuthController();
                    $controller->apiLogin();
                }
                break;
                
            case '/auth/register':
                if ($method === 'POST') {
                    $controller = new AuthController();
                    $controller->apiRegister();
                }
                break;
                
            case '/portfolio':
                if ($method === 'GET') {
                    $controller = new PortfolioController();
                    $controller->apiIndex();
                } elseif ($method === 'POST') {
                    $controller = new PortfolioController();
                    $controller->apiCreate();
                }
                break;
                
            case preg_match('/^\/portfolio\/(\d+)$/', $apiPath, $matches) ? true : false:
                $portfolioId = $matches[1];
                $controller = new PortfolioController();
                
                if ($method === 'GET') {
                    $controller->apiShow($portfolioId);
                } elseif ($method === 'PUT') {
                    $controller->apiUpdate($portfolioId);
                } elseif ($method === 'DELETE') {
                    $controller->apiDelete($portfolioId);
                }
                break;
                
            case preg_match('/^\/portfolio\/(\d+)\/simulate$/', $apiPath, $matches) ? true : false:
                if ($method === 'POST') {
                    $portfolioId = $matches[1];
                    $controller = new PortfolioController();
                    $controller->apiSimulate($portfolioId);
                }
                break;
                
            case preg_match('/^\/simulation\/([a-zA-Z0-9_-]+)\/status$/', $apiPath, $matches) ? true : false:
                if ($method === 'GET') {
                    $executionId = $matches[1];
                    $controller = new SimulationController();
                    $controller->apiStatus($executionId);
                }
                break;
                
            case preg_match('/^\/simulation\/([a-zA-Z0-9_-]+)\/results$/', $apiPath, $matches) ? true : false:
                if ($method === 'GET') {
                    $executionId = $matches[1];
                    $controller = new SimulationController();
                    $controller->apiResults($executionId);
                }
                break;
                
            default:
                Response::error('Endpoint não encontrado', 404);
        }
    } catch (Exception $e) {
        Response::error($e->getMessage(), 500);
    }
    
    exit;
}

// Web Routes
switch ($request) {
    case '/':
    case '':
        if (isset($_SESSION['user_id'])) {
            header('Location: /portfolio');
        } else {
            header('Location: /auth/login');
        }
        exit;
        
    case '/auth/login':
        $controller = new AuthController();
        $controller->login();
        break;
        
    case '/auth/register':
        $controller = new AuthController();
        $controller->register();
        break;
        
    case '/auth/verify':
        $controller = new AuthController();
        $controller->verify();
        break;
        
    case '/auth/forgot-password':
        $controller = new AuthController();
        $controller->forgotPassword();
        break;
        
    case '/auth/reset-password':
        $controller = new AuthController();
        $controller->resetPassword();
        break;
        
    case '/auth/logout':
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case '/portfolio':
        $controller = new PortfolioController();
        $controller->index();
        break;
        
    case '/portfolio/create':
        $controller = new PortfolioController();
        $controller->create();
        break;
        
    case preg_match('/^\/portfolio\/(\d+)$/', $request, $matches) ? true : false:
        $portfolioId = $matches[1];
        $controller = new PortfolioController();
        $controller->edit($portfolioId);
        break;
        
    case preg_match('/^\/portfolio\/(\d+)\/simulate$/', $request, $matches) ? true : false:
        $portfolioId = $matches[1];
        $controller = new PortfolioController();
        $controller->simulate($portfolioId);
        break;
        
    case preg_match('/^\/portfolio\/(\d+)\/clone$/', $request, $matches) ? true : false:
        if ($method === 'POST') {
            $portfolioId = $matches[1];
            $controller = new PortfolioController();
            $controller->clone($portfolioId);
        }
        break;
        
    case preg_match('/^\/simulation\/([a-zA-Z0-9_-]+)$/', $request, $matches) ? true : false:
        $executionId = $matches[1];
        $controller = new SimulationController();
        $controller->results($executionId);
        break;
        
    case preg_match('/^\/simulation\/([a-zA-Z0-9_-]+)\/download\/(csv|json|excel)$/', $request, $matches) ? true : false:
        $executionId = $matches[1];
        $format = $matches[2];
        $controller = new SimulationController();
        $controller->download($executionId, $format);
        break;
        
    case '/admin':
        AuthMiddleware::requireAdmin();
        // Implementar dashboard admin
        echo "Admin Dashboard";
        break;
        
    default:
        http_response_code(404);
        echo "Página não encontrada";
        break;
}