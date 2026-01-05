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
        /* Estilos de Interface Sênior */
        :root {
            --nav-bg: #1a1d21; /* Um tom de cinza mais profundo e moderno que o preto puro */
            --nav-accent: #0d6efd;
        }

        .navbar {
            background-color: var(--nav-bg) !important;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.8px;
            font-size: 1.25rem;
            background: linear-gradient(45deg, #fff 30%, var(--nav-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Feedback de Estado Ativo (UEX) */
        .nav-link {
            font-weight: 500;
            font-size: 0.92rem;
            color: rgba(255,255,255,0.7) !important;
            padding: 0.5rem 1rem !important;
            margin: 0 0.2rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.05);
        }

        .nav-link.active {
            color: #fff !important;
            background: rgba(13, 110, 253, 0.15) !important;
            box-shadow: inset 0 -2px 0 var(--nav-accent);
            border-radius: 6px 6px 0 0;
        }

        /* Perfil do Usuário (Visual Profissional) */
        .user-profile-dropdown {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            transition: all 0.2s;
        }

        .user-profile-dropdown:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--nav-accent);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border-radius: 12px;
            margin-top: 10px !important;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/index.php?url=dashboard">
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
                            <a class="nav-link <?php echo $is_active('dashboard'); ?>" href="/index.php?url=dashboard">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_active('portfolio'); ?>" href="/index.php?url=portfolio">
                                <i class="bi bi-briefcase me-1"></i> Portfólios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_active('assets'); ?>" href="/index.php?url=assets">
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
                                <li><a class="dropdown-item" href="/index.php?url=profile"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <?php if (Auth::isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/index.php?url=admin"><i class="bi bi-shield-lock me-2"></i>Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/index.php?url=logout"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/index.php?url=login">Entrar</a></li>
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