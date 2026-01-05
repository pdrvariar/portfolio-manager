<!DOCTYPE html>
<html lang="pt-BR" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Portfolio Backtest'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <?php echo $additional_css ?? ''; ?>
    
    <style>
        body { display: flex; flex-direction: column; height: 100vh; }
        main { flex: 1 0 auto; }
        .navbar-brand { font-weight: bold; letter-spacing: -0.5px; }
        .breadcrumb { background: transparent; padding: 0; margin-bottom: 1.5rem; }
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
                        <li class="nav-item"><a class="nav-link" href="/index.php?url=dashboard">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/index.php?url=portfolio">Meus Portfólios</a></li>
                        <li class="nav-item"><a class="nav-link" href="/index.php?url=assets">Ativos</a></li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-outline-secondary btn-sm text-white ms-lg-3 px-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Usuário'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="/index.php?url=profile"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/index.php?url=logout"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/index.php?url=login">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <main class="container py-4">
        <?php if (Auth::isLoggedIn() && isset($this->params)): ?>
            <?php echo renderBreadcrumbs($this->params); ?>
        <?php endif; ?>

        <?php echo $content; ?>
    </main>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <?php 
        $types = ['success' => 'bg-success', 'error' => 'bg-danger'];
        foreach ($types as $type => $bgClass): 
            if ($message = Session::getFlash($type)): ?>
                <div class="toast align-items-center text-white <?php echo $bgClass; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi <?php echo $type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
                            <?php echo sanitize($message); ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            <?php endif; 
        endforeach; ?>
    </div>

    <footer class="footer mt-auto py-3 bg-white border-top">
        <div class="container text-center">
            <span class="text-muted small">Portfolio Backtest &copy; <?php echo date('Y'); ?></span>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php echo $additional_js ?? ''; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Auto-hide toasts após 5 segundos
        var toastElList = [].slice.call(document.querySelectorAll('.toast'))
        toastElList.map(function(toastEl) {
            return new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 }).show()
        });

        // 2. Inicializa Tooltips (balões de ajuda)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // 3. Spinner nos botões ao enviar formulários
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.classList.contains('no-spinner')) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processando...';
                }
            });
        });
    });
    </script>
</body>
</html>