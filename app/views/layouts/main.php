<!DOCTYPE html>
<html lang="pt-BR" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Portfolio Backtest'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    

    <link rel="stylesheet" href="/css/style.css">

    <?php echo $additional_css ?? ''; ?>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/index.php?url=<?= obfuscateUrl('dashboard') ?>">
                <i class="bi bi-graph-up-arrow me-2" style="-webkit-text-fill-color: #0d6efd;"></i>
                PORTFOLIO<span class="fw-light">BACKTEST</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto"> <?php if (Auth::isLoggedIn()): ?>
                        <?php 
                            $current_url = $_GET['url'] ?? ''; 
                            $is_active = fn($path) => strpos($current_url, $path) !== false ? 'active' : '';
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_active('dashboard'); ?>" href="/index.php?url=<?= obfuscateUrl('dashboard') ?>">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_active('portfolio'); ?>" href="/index.php?url=<?= obfuscateUrl('portfolio') ?>">
                                <i class="bi bi-briefcase me-1"></i> Portfólios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_active('assets'); ?>" href="/index.php?url=<?= obfuscateUrl('assets') ?>">
                                <i class="bi bi-layers me-1"></i> Ativos
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-profile-dropdown d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><h6 class="dropdown-header">Minha Conta</h6></li>
                                <li><a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('profile') ?>"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <?php if (Auth::isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('admin') ?>"><i class="bi bi-shield-lock me-2"></i>Admin</a><i class="bi bi-shield-lock me-2"></i>Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/index.php?url=<?= obfuscateUrl('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/index.php?url=<?= obfuscateUrl('login') ?>">Entrar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>    
    <main class="container py-4">
        <?php if (Auth::isLoggedIn() && isset($this->params)): ?>
            <?php echo renderBreadcrumbs($this->params); ?>
        <?php endif; ?>

        <?php if ($error = Session::getFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                <strong>Erro:</strong> <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($success = Session::getFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> 
                <strong>Sucesso!</strong> <?= $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
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