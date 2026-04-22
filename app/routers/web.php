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
    $router->add('portfolio/run-advanced/{id:\d+}', ['controller' => 'portfolio', 'action' => 'runAdvancedSimulation']);
    $router->add('portfolio/quick-update/{id:\d+}', ['controller' => 'portfolio', 'action' => 'quickUpdate']);
    
    // NOVAS ROTAS OBRIGATÓRIAS:
    $router->add('portfolio/edit/{id:\d+}', ['controller' => 'portfolio', 'action' => 'edit']);
    $router->add('portfolio/update/{id:\d+}', ['controller' => 'portfolio', 'action' => 'update']);
    $router->add('portfolio/delete/{id:\d+}', ['controller' => 'portfolio', 'action' => 'delete']);
    $router->add('portfolio/clone/{id:\d+}', ['controller' => 'portfolio', 'action' => 'clone']);    
    $router->add('portfolio/simulation-details/{id:\d+}', ['controller' => 'portfolio', 'action' => 'simulationDetails']);
    $router->add('portfolio/history/{id:\d+}', ['controller' => 'portfolio', 'action' => 'history']);
    $router->add('portfolio/simulations', ['controller' => 'portfolio', 'action' => 'allSimulations']);
    $router->add('portfolio/apply-snapshot/{id:\d+}', ['controller' => 'portfolio', 'action' => 'applySnapshot']);
    $router->add('portfolio/create-from-snapshot', ['controller' => 'portfolio', 'action' => 'createFromSnapshot']);
    $router->add('portfolio/compare', ['controller' => 'portfolio', 'action' => 'compareSimulations']);

    // --- Rotas de Ativos ---
    $router->add('assets/view/{id:\d+}', ['controller' => 'asset', 'action' => 'view']);
    $router->add('assets', ['controller' => 'asset', 'action' => 'index']);
    $router->add('assets/import', ['controller' => 'asset', 'action' => 'import']);
    $router->add('api/assets/update', ['controller' => 'asset', 'action' => 'updateApi']);  
    $router->add('api/assets/{id:\d+}', ['controller' => 'asset', 'action' => 'getAssetApi']); 
    $router->add('api/assets/benchmark/{id:\d+}', ['controller' => 'asset', 'action' => 'getBenchmarkData']);
    $router->add('api/portfolio/projection/{id:\d+}', ['controller' => 'portfolio', 'action' => 'apiProjection']);
    
    
    // --- Rotas de Perfil ---
    $router->add('profile', ['controller' => 'profile', 'action' => 'index']);
    $router->add('profile/update', ['controller' => 'profile', 'action' => 'update']);
    $router->add('profile/change-password', ['controller' => 'profile', 'action' => 'changePassword']);
    $router->add('portfolio/toggle-system/{id:\d+}', ['controller' => 'portfolio', 'action' => 'toggleSystem']);    

    // --- Rotas de Assinatura ---
    $router->add('upgrade', ['controller' => 'subscription', 'action' => 'upgrade']);
    $router->add('checkout', ['controller' => 'subscription', 'action' => 'checkout']);
    $router->add('subscription-success', ['controller' => 'subscription', 'action' => 'success']);
    $router->add('subscription-failure', ['controller' => 'subscription', 'action' => 'failure']);
    $router->add('subscription-pending', ['controller' => 'subscription', 'action' => 'pending']);
    // Gestão de assinatura (self-service)
    $router->add('subscription/manage', ['controller' => 'subscription', 'action' => 'manage']);
    $router->add('subscription/cancel', ['controller' => 'subscription', 'action' => 'cancel']);
    $router->add('subscription/refund', ['controller' => 'subscription', 'action' => 'refund']);
    $router->add('subscription/upgrade-plan', ['controller' => 'subscription', 'action' => 'upgradePlan']);
    // Webhook MP (sem autenticação de sessão)
    $router->add('subscription/webhook', ['controller' => 'subscription', 'action' => 'webhook']);
    // Validação de cupom (AJAX)
    $router->add('subscription/validate-coupon', ['controller' => 'subscription', 'action' => 'validateCoupon']);

    // --- Recuperação de Senha ---
    $router->add('forgot-password', ['controller' => 'auth', 'action' => 'forgotPassword']);
    $router->add('reset-password', ['controller' => 'auth', 'action' => 'resetPassword']);
    
    // --- Legal & Institucional ---
    $router->add('terms', ['controller' => 'home', 'action' => 'terms']);
    $router->add('terms/accept', ['controller' => 'home', 'action' => 'acceptTerms']);
    $router->add('about', ['controller' => 'home', 'action' => 'about']);

    // --- Admin ---
    $router->add('admin', ['controller' => 'admin', 'action' => 'dashboard']);
    $router->add('admin/dashboard', ['controller' => 'admin', 'action' => 'dashboard']);
    $router->add('admin/users', ['controller' => 'admin', 'action' => 'users']);
    $router->add('admin/users/edit/{id:\d+}', ['controller' => 'admin', 'action' => 'editUser']);
    $router->add('admin/users/update/{id:\d+}', ['controller' => 'admin', 'action' => 'updateUser']);
    $router->add('admin/assets', ['controller' => 'admin', 'action' => 'assets']);
    $router->add('admin/assets/update-quotes', ['controller' => 'admin', 'action' => 'updateAssetQuotes']);
    $router->add('admin/create-default-portfolios', ['controller' => 'admin', 'action' => 'createDefaultPortfolios']);
    // Admin — Assinaturas
    $router->add('admin/subscriptions', ['controller' => 'admin', 'action' => 'subscriptions']);
    $router->add('admin/subscriptions/cancel/{id:\d+}', ['controller' => 'admin', 'action' => 'cancelSubscription']);
    $router->add('admin/subscriptions/refund/{id:\d+}', ['controller' => 'admin', 'action' => 'refundSubscription']);
    $router->add('admin/subscriptions/reactivate/{id:\d+}', ['controller' => 'admin', 'action' => 'reactivateSubscription']);
    // Admin — Preços
    $router->add('admin/pricing', ['controller' => 'admin', 'action' => 'pricing']);
    $router->add('admin/pricing/update', ['controller' => 'admin', 'action' => 'updatePricing']);
    // Admin — Cupons
    $router->add('admin/coupons', ['controller' => 'admin', 'action' => 'coupons']);
    $router->add('admin/coupons/create', ['controller' => 'admin', 'action' => 'createCoupon']);
    $router->add('admin/coupons/update/{id:\d+}', ['controller' => 'admin', 'action' => 'updateCoupon']);
    $router->add('admin/coupons/toggle/{id:\d+}', ['controller' => 'admin', 'action' => 'toggleCoupon']);
    $router->add('admin/coupons/delete/{id:\d+}', ['controller' => 'admin', 'action' => 'deleteCoupon']);
    $router->add('admin/coupons/check-code', ['controller' => 'admin', 'action' => 'checkCouponCode']);

    // --- Dashboard ---
    $router->add('dashboard', ['controller' => 'home', 'action' => 'index']);
}