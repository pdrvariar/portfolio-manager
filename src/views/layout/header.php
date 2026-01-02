<?php
$currentPage = $_SERVER['REQUEST_URI'] ?? '/';
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$isAdmin = $_SESSION['is_admin'] ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Portfolio Manager' ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon.ico">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= AuthMiddleware::generateCSRFToken() ?>">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Sistema de gerenciamento e simulação de portfólios de investimentos">
    <meta name="keywords" content="portfolio, investimentos, simulação, análise, finanças">
    <meta name="author" content="Portfolio Manager">
    
    <!-- Open Graph -->
    <meta property="og:title" content="Portfolio Manager">
    <meta property="og:description" content="Simulação e análise de portfólios de investimentos">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL ?>">
    <meta property="og:image" content="/assets/img/og-image.png">
    
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#0d6efd">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            height: calc(100vh - 56px);
            position: fixed;
            width: 250px;
            transition: all 0.3s;
        }
        
        .sidebar.collapsed {
            width: 60px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--primary-color);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.15);
            border-left-color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            transition: all 0.3s;
            padding: 20px;
        }
        
        .main-content.expanded {
            margin-left: 60px;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.125);
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }
        
        .stat-card.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #f46b45 0%, #eea849 100%);
        }
        
        .stat-card.purple {
            background: linear-gradient(135deg, #654ea3 0%, #da98b4 100%);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    
    <?php if (isset($customCss)): ?>
        <style><?= $customCss ?></style>
    <?php endif; ?>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <?php if ($isLoggedIn): ?>
                <button class="navbar-toggler me-2" type="button" id="sidebarToggle">
                    <span class="navbar-toggler-icon"></span>
                </button>
            <?php endif; ?>
            
            <a class="navbar-brand" href="<?= $isLoggedIn ? '/portfolio' : '/' ?>">
                <i class="bi bi-graph-up me-2"></i>Portfolio Manager
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if ($isLoggedIn): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, '/portfolio') === 0 ? 'active' : '' ?>" 
                               href="/portfolio">
                                <i class="bi bi-pie-chart me-1"></i> Portfólios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, '/assets') === 0 ? 'active' : '' ?>" 
                               href="/assets">
                                <i class="bi bi-currency-exchange me-1"></i> Ativos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentPage, '/simulations') === 0 ? 'active' : '' ?>" 
                               href="/simulations">
                                <i class="bi bi-calculator me-1"></i> Simulações
                            </a>
                        </li>
                        <?php if ($isAdmin): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($currentPage, '/admin') === 0 ? 'active' : '' ?>" 
                                   href="/admin">
                                    <i class="bi bi-shield-lock me-1"></i> Admin
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                               id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar me-2">
                                    <?= strtoupper(substr($userName, 0, 1)) ?>
                                </div>
                                <span class="d-none d-md-inline"><?= htmlspecialchars($userName) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="/profile">
                                        <i class="bi bi-person me-2"></i> Meu Perfil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/settings">
                                        <i class="bi bi-gear me-2"></i> Configurações
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="/auth/logout">
                                        <i class="bi bi-box-arrow-right me-2"></i> Sair
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === '/auth/login' ? 'active' : '' ?>" 
                               href="/auth/login">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === '/auth/register' ? 'active' : '' ?>" 
                               href="/auth/register">
                                <i class="bi bi-person-plus me-1"></i> Cadastrar
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <?php if ($isLoggedIn): ?>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="p-3">
                <div class="d-flex align-items-center mb-4">
                    <div class="user-avatar me-3">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>
                    <div class="d-none d-md-block">
                        <h6 class="mb-0"><?= htmlspecialchars($userName) ?></h6>
                        <small class="text-muted"><?= $_SESSION['user_email'] ?? '' ?></small>
                    </div>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === '/portfolio' ? 'active' : '' ?>" 
                           href="/portfolio">
                            <i class="bi bi-house-door"></i>
                            <span class="d-none d-md-inline">Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, '/portfolio/create') === 0 ? 'active' : '' ?>" 
                           href="/portfolio/create">
                            <i class="bi bi-plus-circle"></i>
                            <span class="d-none d-md-inline">Novo Portfólio</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, '/portfolio/list') === 0 ? 'active' : '' ?>" 
                           href="/portfolio/list">
                            <i class="bi bi-list-ul"></i>
                            <span class="d-none d-md-inline">Meus Portfólios</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, '/portfolio/templates') === 0 ? 'active' : '' ?>" 
                           href="/portfolio/templates">
                            <i class="bi bi-collection"></i>
                            <span class="d-none d-md-inline">Modelos</span>
                        </a>
                    </li>
                    
                    <hr class="text-white-50">
                    
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, '/assets') === 0 ? 'active' : '' ?>" 
                           href="/assets">
                            <i class="bi bi-bar-chart"></i>
                            <span class="d-none d-md-inline">Ativos</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, '/reports') === 0 ? 'active' : '' ?>" 
                           href="/reports">
                            <i class="bi bi-file-earmark-text"></i>
                            <span class="d-none d-md-inline">Relatórios</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($currentPage, '/analytics') === 0 ? 'active' : '' ?>" 
                           href="/analytics">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span class="d-none d-md-inline">Análises</span>
                        </a>
                    </li>
                    
                    <hr class="text-white-50">
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/help">
                            <i class="bi bi-question-circle"></i>
                            <span class="d-none d-md-inline">Ajuda</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/feedback">
                            <i class="bi bi-chat-left-text"></i>
                            <span class="d-none d-md-inline">Feedback</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
    <?php else: ?>
        <!-- Public Content -->
        <div class="container py-4">
    <?php endif; ?>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['warning']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($_SESSION['info']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>
    
    <!-- Page Header -->
    <?php if (isset($pageTitle)): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
                <?php if (isset($pageSubtitle)): ?>
                    <p class="text-muted mb-0"><?= $pageSubtitle ?></p>
                <?php endif; ?>
            </div>
            <?php if (isset($pageActions)): ?>
                <div>
                    <?= $pageActions ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>