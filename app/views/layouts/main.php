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
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-18096825725"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'AW-18096825725');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Smart Returns - Backtest e Simulação de Portfólios de Investimentos'); ?></title>

    <!-- SEO: Meta tags principais -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? 'Smart Returns: simule e faça backtest de portfólios de investimentos com dados históricos reais. Analise ações, FIIs, renda fixa e criptoativos. Tome decisões financeiras mais inteligentes.'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords ?? 'backtest portfólio, simulação de investimentos, análise de portfólio, ações, FIIs, renda fixa, diversificação, Sharpe, drawdown, investimentos Brasil'); ?>">
    <meta name="author" content="Smart Returns">
    <meta name="robots" content="<?php echo $meta_robots ?? 'index, follow'; ?>">
    <meta name="theme-color" content="#0d6efd">
    <meta name="google" content="nositelinkssearchbox">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url ?? 'https://smartreturns.com.br' . ($_SERVER['REQUEST_URI'] ?? '/')); ?>">
    <link rel="alternate" hreflang="pt-BR" href="<?php echo htmlspecialchars($canonical_url ?? 'https://smartreturns.com.br' . ($_SERVER['REQUEST_URI'] ?? '/')); ?>">

    <!-- Open Graph (Facebook, LinkedIn, WhatsApp) -->
    <meta property="og:type" content="<?php echo $og_type ?? 'website'; ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url ?? 'https://smartreturns.com.br' . ($_SERVER['REQUEST_URI'] ?? '/')); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($title ?? 'Smart Returns - Backtest e Simulação de Portfólios'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description ?? 'Simule e analise portfólios de investimentos com dados históricos reais. Métricas de risco, gráficos interativos e estratégias de rebalanceamento.'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image ?? 'https://smartreturns.com.br/assets/og-image.png'); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Smart Returns - Plataforma de Backtest de Portfólios">
    <meta property="og:site_name" content="Smart Returns">
    <meta property="og:locale" content="pt_BR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@smartreturns_br">
    <meta name="twitter:creator" content="@smartreturns_br">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($title ?? 'Smart Returns - Backtest de Portfólios'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description ?? 'Simule portfólios de investimentos com dados históricos reais.'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image ?? 'https://smartreturns.com.br/assets/og-image.png'); ?>">
    <meta name="twitter:image:alt" content="Smart Returns - Plataforma de Backtest de Portfólios">

    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon.png">
    <link rel="manifest" href="/manifest.webmanifest">

    <!-- Preconnect para recursos externos críticos (Core Web Vitals) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="dns-prefetch" href="https://sdk.mercadopago.com">

    <!-- JSON-LD: Dados estruturados para Google -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Smart Returns",
        "url": "https://smartreturns.com.br",
        "description": "Plataforma brasileira de backtest e simulação de portfólios de investimentos com dados históricos reais. Analise ações da B3, FIIs, Tesouro Direto, ETFs e criptoativos.",
        "applicationCategory": "FinanceApplication",
        "applicationSubCategory": "Investment Portfolio Backtesting",
        "operatingSystem": "Web Browser",
        "inLanguage": "pt-BR",
        "availableOnDevice": "Desktop, Mobile, Tablet",
        "featureList": "Backtest de portfólios, Simulação Monte Carlo, Rebalanceamento automático, Cálculo de impostos, Análise Sharpe Ratio, Drawdown máximo, Comparação de simulações",
        "screenshot": "https://smartreturns.com.br/assets/og-image.png",
        "offers": [
            {
                "@type": "Offer",
                "name": "Plano Starter",
                "price": "0",
                "priceCurrency": "BRL",
                "description": "Plano gratuito com 20 simulações mensais"
            },
            {
                "@type": "Offer",
                "name": "Plano PRO Mensal",
                "price": "29.90",
                "priceCurrency": "BRL",
                "description": "1000 simulações mensais, Monte Carlo, histórico completo"
            },
            {
                "@type": "Offer",
                "name": "Plano PRO Anual",
                "price": "179.40",
                "priceCurrency": "BRL",
                "description": "Economia de 50% no plano anual com todos os recursos PRO"
            }
        ],
        "publisher": {
            "@type": "Organization",
            "name": "Smart Returns",
            "url": "https://smartreturns.com.br",
            "logo": {
                "@type": "ImageObject",
                "url": "https://smartreturns.com.br/assets/favicon.png"
            },
            "contactPoint": {
                "@type": "ContactPoint",
                "email": "contato@smartreturns.com.br",
                "contactType": "customer service",
                "availableLanguage": "Portuguese"
            }
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "127",
            "bestRating": "5",
            "worstRating": "1"
        }
    }
    </script>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <!-- Preload de recursos críticos para melhor LCP (Core Web Vitals) -->
    <link rel="preload" href="/css/style.css?v=<?php echo filemtime(dirname(__DIR__, 3) . '/public/css/style.css'); ?>" as="style">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" as="style" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    

    <link rel="stylesheet" href="/css/style.css?v=<?php echo filemtime(dirname(__DIR__, 3) . '/public/css/style.css'); ?>">

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
                            <a class="nav-link <?php echo $is_active('portfolio/simulations'); ?>" href="/index.php?url=<?= obfuscateUrl('portfolio/simulations') ?>">
                                <i class="bi bi-clock-history me-1"></i> Simulações
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
                        <li class="nav-item dropdown d-flex align-items-center">
                            <a class="nav-link dropdown-toggle user-profile-dropdown d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                            <?php if (Auth::isPro()): ?>
                                <span class="badge bg-primary ms-2" style="font-size: 0.65rem; padding: 0.25em 0.5em;">PRO</span>
                            <?php else: ?>
                                <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="badge bg-warning text-dark ms-2 text-decoration-none" style="font-size: 0.65rem; padding: 0.25em 0.5em;">UPGRADE</a>
                            <?php endif; ?>
                            
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header">Minha Conta</h6></li>
                                <li><a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('profile') ?>"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                                <li><a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('subscription/manage') ?>">
                                    <i class="bi bi-credit-card me-2"></i>Minha Assinatura
                                    <?php if (Auth::isPro()): ?>
                                        <span class="badge bg-success ms-1" style="font-size:0.6rem;">PRO</span>
                                    <?php endif; ?>
                                </a></li>
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
        <?php if (Auth::isLoggedIn()): ?>
            <?php echo renderBreadcrumbs($breadcrumbs ?? null, $this->params ?? []); ?>
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

    <!-- Modal Global de Paywall (Barreira de Vidro) -->
    <div class="modal fade" id="paywallModal" tabindex="-1" aria-labelledby="paywallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="paywallModalLabel">
                        <i class="bi bi-stars me-2"></i>Recurso Premium
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-4">
                        <div class="bg-primary bg-soft rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px;">
                            <i class="bi bi-lock-fill text-primary fs-1"></i>
                        </div>
                        <h4 class="fw-bold mb-2" id="paywallFeatureTitle">Aporte Direcionado ao Alvo</h4>
                        <p class="text-muted" id="paywallFeatureDesc">
                            Este recurso permite que o sistema direcione seus aportes automaticamente para o ativo que mais precisa de equilíbrio, otimizando seus custos e impostos.
                        </p>
                    </div>
                    
                    <div class="card bg-light border-0 mb-4 text-start shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 small text-uppercase text-primary"><i class="bi bi-check2-circle me-2"></i>Vantagens do Plano PRO:</h6>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i> 1000 Simulações mensais</li>
                                <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i> Simulação Avançada (Monte Carlo)</li>
                                <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i> Histórico completo de dados</li>
                                <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i> Cálculo automático de impostos</li>
                                <li><i class="bi bi-check-lg text-success me-2"></i> Estratégias de aporte avançadas</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="/index.php?url=<?= obfuscateUrl('upgrade') ?>" class="btn btn-primary btn-lg fw-bold shadow-sm">
                            Fazer Upgrade Agora
                        </a>
                        <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Agora não, obrigado</button>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 justify-content-center py-2">
                    <span class="text-muted smaller" style="font-size: 0.75rem;">
                        <i class="bi bi-shield-check me-1"></i> Pagamento seguro via Mercado Pago
                    </span>
                </div>
            </div>
        </div>
    </div>

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
            <div class="mb-2">
                <a href="/" class="text-muted fw-bold text-decoration-none fs-6" aria-label="Smart Returns - Página Inicial">
                    <i class="bi bi-graph-up-arrow me-1" style="color:#0d6efd"></i>Smart Returns
                </a>
            </div>
            <div class="mt-2 mb-3">
                <a href="/index.php?url=about" class="text-muted small text-decoration-none mx-2">Sobre</a>
                <span class="text-muted small">|</span>
                <a href="/index.php?url=terms" class="text-muted small text-decoration-none mx-2">Termos de Uso</a>
                <span class="text-muted small">|</span>
                <a href="/index.php?url=terms#isencao" class="text-muted small text-decoration-none mx-2">Isenção de Responsabilidade</a>
                <span class="text-muted small">|</span>
                <a href="/index.php?url=terms#reembolso" class="text-muted small text-decoration-none mx-2">Política de Reembolso</a>
            </div>

            <span class="text-muted small">Smart Returns &copy; <?php echo date('Y'); ?> — smartreturns.com.br</span>
            <p class="text-muted x-small mt-2 mb-0" style="font-size: 0.75rem;">
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

        // 4. Lógica Global para Paywall (Barreira de Vidro)
        window.showPaywallModal = function(feature, description) {
            const modalEl = document.getElementById('paywallModal');
            if (!modalEl) return;
            
            const titleEl = document.getElementById('paywallFeatureTitle');
            const descEl = document.getElementById('paywallFeatureDesc');
            
            if (feature) titleEl.textContent = feature;
            if (description) descEl.textContent = description;
            
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        };

        // Intercepta cliques em elementos com [data-paywall]
        document.addEventListener('click', function(e) {
            const paywallTrigger = e.target.closest('[data-paywall]');
            if (paywallTrigger) {
                e.preventDefault();
                e.stopPropagation();
                
                const feature = paywallTrigger.getAttribute('data-paywall-feature') || 'Recurso Premium';
                const desc = paywallTrigger.getAttribute('data-paywall-desc') || '';
                
                showPaywallModal(feature, desc);
            }
        });
    });
    </script>
</body>
</html>