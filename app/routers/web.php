<?php
function setupRoutes(Router $router) {
    // Rotas públicas
    $router->add('', ['controller' => 'home', 'action' => 'index']);
    $router->add('login', ['controller' => 'auth', 'action' => 'login']);
    $router->add('register', ['controller' => 'auth', 'action' => 'register']);
    $router->add('logout', ['controller' => 'auth', 'action' => 'logout']);
    
    // Rotas de portfólio
    $router->add('portfolio', ['controller' => 'portfolio', 'action' => 'index']);
    $router->add('portfolio/create', ['controller' => 'portfolio', 'action' => 'create']);
    $router->add('portfolio/view/{id:\d+}', ['controller' => 'portfolio', 'action' => 'view']);
    $router->add('portfolio/edit/{id:\d+}', ['controller' => 'portfolio', 'action' => 'edit']);
    $router->add('portfolio/update/{id:\d+}', ['controller' => 'portfolio', 'action' => 'update']);
    $router->add('portfolio/delete/{id:\d+}', ['controller' => 'portfolio', 'action' => 'delete']);
    $router->add('portfolio/clone/{id:\d+}', ['controller' => 'portfolio', 'action' => 'clone']);
    $router->add('portfolio/run/{id:\d+}', ['controller' => 'portfolio', 'action' => 'runSimulation']);

    
    // Rotas de ativos
    $router->add('assets', ['controller' => 'asset', 'action' => 'index']);
    $router->add('assets/import', ['controller' => 'asset', 'action' => 'import']);
    $router->add('assets/view/{id:\d+}', ['controller' => 'asset', 'action' => 'view']);
    $router->add('assets/delete/{id:\d+}', ['controller' => 'asset', 'action' => 'delete']);    
    $router->add('assets/historical/{id:\d+}', ['controller' => 'asset', 'action' => 'historicalData']);
    $router->add('api/assets/{id:\d+}', ['controller' => 'asset', 'action' => 'getAssetApi']);
    $router->add('api/assets/update', ['controller' => 'asset', 'action' => 'updateApi']);    
    
    // Rotas administrativas
    $router->add('admin/dashboard', ['controller' => 'admin', 'action' => 'dashboard']);
    $router->add('dashboard', ['controller' => 'home', 'action' => 'index']);
    $router->add('admin/users', ['controller' => 'admin', 'action' => 'users']);
    $router->add('admin/assets', ['controller' => 'admin', 'action' => 'assets']);
    $router->add('admin/create-default-portfolios', ['controller' => 'admin', 'action' => 'createDefaultPortfolios']);
    
    // Rotas de perfil
    $router->add('profile', ['controller' => 'profile', 'action' => 'index']);
    $router->add('profile/update', ['controller' => 'profile', 'action' => 'update']);
    
    // API (para AJAX)
    $router->add('api/simulate', ['controller' => 'api', 'action' => 'simulate']);
    $router->add('api/portfolio/{id:\d+}/assets', ['controller' => 'api', 'action' => 'portfolioAssets']);
    $router->add('api/asset/search', ['controller' => 'api', 'action' => 'searchAssets']);
}
?>