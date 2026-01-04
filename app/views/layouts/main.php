<!DOCTYPE html>
<html lang="pt-BR" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Portfolio Backtest'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <?php echo $additional_css ?? ''; ?>
    
    <style>
        /* Sticky footer fix */
        body { display: flex; flex-direction: column; height: 100vh; }
        main { flex: 1 0 auto; }
        .navbar-brand { font-weight: bold; letter-spacing: -0.5px; }
        .nav-link.active { font-weight: 600; color: #fff !important; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/index.php?url=">
                <i class="bi bi-graph-up-arrow me-2"></i>Portfolio Backtest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/index.php?url=dashboard">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/index.php?url=portfolio">Meus Portfólios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/index.php?url=assets">Ativos</a>
                        </li>
                        <?php if (Auth::isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    Administração
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/index.php?url=admin/dashboard">Resumo</a></li>
                                    <li><a class="dropdown-item" href="/index.php?url=admin/users">Usuários</a></li>
                                    <li><a class="dropdown-item" href="/index.php?url=admin/assets">Gerir Ativos</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-outline-secondary btn-sm text-white ms-lg-3 px-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="/index.php?url=profile"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/index.php?url=logout"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/index.php?url=login">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <main class="container py-4">
        <?php if (Session::hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo Session::getFlash('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (Session::hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo Session::getFlash('error'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php echo $content; ?>
    </main>
    
    <footer class="footer mt-auto py-3 bg-white border-top">
        <div class="container text-center">
            <span class="text-muted small">Portfolio Backtest &copy; <?php echo date('Y'); ?> - Sistema de Auditoria Financeira</span>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php echo $additional_js ?? ''; ?>
</body>
</html>