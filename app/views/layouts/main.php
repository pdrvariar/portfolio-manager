<?php
/**
 * @var string $title Título da página
 * @var string $content Conteúdo principal injetado na layout
 * @var string|null $additional_css CSS extra para a página
 * @var string|null $additional_js JS extra para a página
 */
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-100" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Portfolio Backtest'; ?></title>
    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    

    <link rel="stylesheet" href="/css/style.css">

    <?php echo $additional_css ?? ''; ?>
</head>
<body class="h-100">
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
                <ul class="navbar-nav mx-auto"> 
                    <?php if (Auth::isLoggedIn()): ?>
                        <?php 
                            // 1. Captura a URL ofuscada vinda do navegador
                            $raw_url = $_GET['url'] ?? ''; 
                            
                            // 2. Converte o hash de volta para a rota legível (ex: 'Y29uZmln' -> 'dashboard')
                            $clean_url = function_exists('deobfuscateUrl') ? deobfuscateUrl($raw_url) : $raw_url;
                            
                            // 3. A comparação agora funciona com a rota descriptografada
                            $is_active = fn($path) => strpos($clean_url, $path) !== false ? 'active' : '';
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
                    <li class="nav-item d-flex align-items-center me-2">
                        <div id="theme-toggle" class="theme-toggle" title="Alternar Tema">
                            <i class="bi bi-sun-fill" id="theme-icon"></i>
                        </div>
                    </li>
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-profile-dropdown d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <?php if (Auth::isPro()): ?>
                                    <span class="badge bg-primary ms-2" style="font-size: 0.65rem; padding: 0.25em 0.5em;">PRO</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; padding: 0.25em 0.5em;">STARTER</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><h6 class="dropdown-header">Minha Conta</h6></li>
                                <li><a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('profile') ?>"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <?php if (Auth::isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('admin') ?>"><i class="bi bi-shield-lock me-2"></i>Admin</a></li>
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

    <footer class="footer mt-auto py-4 border-top">
        <div class="container text-center">
            <span class="text-muted small">Portfolio Backtest &copy; <?php echo date('Y'); ?></span>
            <div class="mt-2">
                <a href="/index.php?url=terms" class="text-muted small text-decoration-none mx-2">Termos de Uso</a>
                <span class="text-muted small">|</span>
                <a href="/index.php?url=terms" class="text-muted small text-decoration-none mx-2">Isenção de Responsabilidade</a>
                <span class="text-muted small">|</span>
                <a href="/index.php?url=terms" class="text-muted small text-decoration-none mx-2">Garantia e Reembolso</a>
            </div>
            <p class="text-muted x-small mt-3 mb-0" style="font-size: 0.75rem;">
                Este site é uma plataforma de estudo e simulação. Não realizamos recomendações de investimento.
            </p>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php echo $additional_js ?? ''; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme Toggle Logic
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const html = document.documentElement;

        function updateIcon(theme) {
            if (theme === 'dark') {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
            } else {
                themeIcon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
            }
        }

        // Initialize icon
        updateIcon(html.getAttribute('data-theme'));

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);

            // Update Chart.js defaults if present
            if (typeof Chart !== 'undefined') {
                updateChartDefaults(newTheme);
            }
        });

        function updateChartDefaults(theme) {
            const isDark = theme === 'dark';
            const color = isDark ? '#e9ecef' : '#212529';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

            if (typeof Chart !== 'undefined') {
                Chart.defaults.color = color;
                
                // Configuração global para escalas (v3+)
                if (Chart.defaults.scale) {
                    if (Chart.defaults.scale.grid) Chart.defaults.scale.grid.color = gridColor;
                    if (Chart.defaults.scale.ticks) Chart.defaults.scale.ticks.color = color;
                }
                
                // For Chart.js v3+ plugins
                if (Chart.defaults.plugins && Chart.defaults.plugins.legend && Chart.defaults.plugins.legend.labels) {
                    Chart.defaults.plugins.legend.labels.color = color;
                }

                // Refresh all charts on page
                Object.values(Chart.instances).forEach(chart => {
                    // Update scales
                    if (chart.options.scales) {
                        Object.keys(chart.options.scales).forEach(key => {
                            const scale = chart.options.scales[key];
                            if (scale.ticks) scale.ticks.color = color;
                            if (scale.grid) scale.grid.color = gridColor;
                            if (scale.title) scale.title.color = color;
                        });
                    }
                    // Update plugins
                    if (chart.options.plugins) {
                        if (chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                            chart.options.plugins.legend.labels.color = color;
                        }
                        if (chart.options.plugins.title) {
                            chart.options.plugins.title.color = color;
                        }
                    }
                    chart.update();
                });
            }
        }

        // Initialize Chart defaults
        if (typeof Chart !== 'undefined') {
            updateChartDefaults(html.getAttribute('data-theme'));
        }

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