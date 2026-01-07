<?php
// app/routers/web.php

function setupRoutes(Router $router) {
    // --- Rotas Públicas ---
    $router->add('', ['controller' => 'home', 'action' => 'index']);
    $router->add('login', ['controller' => 'auth', 'action' => 'login']);
    $router->add('register', ['controller' => 'auth', 'action' => 'register']);
    $router->add('logout', ['controller' => 'auth', 'action' => 'logout']);
    $router->add('verify', ['controller' => 'auth', 'action' => 'verify']);

    // --- Rotas de Autenticação Google (CORRIGIDAS) ---
    // Ajustadas para o padrão de Array compatível com o seu Router
    $router->add('google-auth', ['controller' => 'auth', 'action' => 'googleLogin']);
    $router->add('google/callback', ['controller' => 'auth', 'action' => 'googleCallback']);
    

    //Rotas de Portfólio (ADICIONAR AS QUE FALTAM) ---
    $router->add('portfolio', ['controller' => 'portfolio', 'action' => 'index']);
    $router->add('portfolio/create', ['controller' => 'portfolio', 'action' => 'create']);
    $router->add('portfolio/view/{id:\d+}', ['controller' => 'portfolio', 'action' => 'view']);
    $router->add('portfolio/run/{id:\d+}', ['controller' => 'portfolio', 'action' => 'runSimulation']);
    
    // NOVAS ROTAS OBRIGATÓRIAS:
    $router->add('portfolio/edit/{id:\d+}', ['controller' => 'portfolio', 'action' => 'edit']);
    $router->add('portfolio/update/{id:\d+}', ['controller' => 'portfolio', 'action' => 'update']);
    $router->add('portfolio/delete/{id:\d+}', ['controller' => 'portfolio', 'action' => 'delete']);
    $router->add('portfolio/clone/{id:\d+}', ['controller' => 'portfolio', 'action' => 'clone']);    
    
    // --- Rotas de Ativos ---
    $router->add('assets/view/{id:\d+}', ['controller' => 'asset', 'action' => 'view']);
    $router->add('assets', ['controller' => 'asset', 'action' => 'index']);
    $router->add('assets/import', ['controller' => 'asset', 'action' => 'import']);
    $router->add('api/assets/update', ['controller' => 'asset', 'action' => 'updateApi']);    
    
    // --- Rotas de Perfil ---
    $router->add('profile', ['controller' => 'profile', 'action' => 'index']);
    $router->add('profile/update', ['controller' => 'profile', 'action' => 'update']);
    $router->add('profile/change-password', ['controller' => 'profile', 'action' => 'changePassword']);

    // --- Recuperação de Senha ---
    $router->add('forgot-password', ['controller' => 'auth', 'action' => 'forgotPassword']);
    $router->add('reset-password', ['controller' => 'auth', 'action' => 'resetPassword']);
    
    // --- Dashboard ---
    $router->add('dashboard', ['controller' => 'home', 'action' => 'index']);
}