    <?php if (isset($_SESSION['user_id'])): ?>
            <!-- End Main Content -->
        </div>
    <?php else: ?>
            <!-- End Public Content -->
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="mb-3">Portfolio Manager</h6>
                    <p class="text-muted small">
                        Sistema de simulação e análise de portfólios de investimentos.
                        Ferramenta para auxiliar na tomada de decisões financeiras.
                    </p>
                </div>
                
                <div class="col-md-2">
                    <h6 class="mb-3">Links Rápidos</h6>
                    <ul class="list-unstyled">
                        <li><a href="/about" class="text-decoration-none text-muted small">Sobre</a></li>
                        <li><a href="/features" class="text-decoration-none text-muted small">Funcionalidades</a></li>
                        <li><a href="/pricing" class="text-decoration-none text-muted small">Preços</a></li>
                        <li><a href="/contact" class="text-decoration-none text-muted small">Contato</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3">
                    <h6 class="mb-3">Recursos</h6>
                    <ul class="list-unstyled">
                        <li><a href="/help" class="text-decoration-none text-muted small">Ajuda & FAQ</a></li>
                        <li><a href="/documentation" class="text-decoration-none text-muted small">Documentação</a></li>
                        <li><a href="/api" class="text-decoration-none text-muted small">API</a></li>
                        <li><a href="/status" class="text-decoration-none text-muted small">Status do Sistema</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3">
                    <h6 class="mb-3">Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="/terms" class="text-decoration-none text-muted small">Termos de Uso</a></li>
                        <li><a href="/privacy" class="text-decoration-none text-muted small">Privacidade</a></li>
                        <li><a href="/cookies" class="text-decoration-none text-muted small">Cookies</a></li>
                        <li><a href="/disclaimer" class="text-decoration-none text-muted small">Disclaimer</a></li>
                    </ul>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted small mb-0">
                        &copy; <?= date('Y') ?> Portfolio Manager. Todos os direitos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted small mb-0">
                        Versão <?= $_ENV['APP_VERSION'] ?? '1.0.0' ?> 
                        | <?= date('d/m/Y H:i:s') ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/app.js"></script>
    
    <!-- CSRF Token for AJAX -->
    <script>
        const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
        
        // Configure AJAX defaults
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        // Global error handler
        $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
            console.error('AJAX Error:', settings.url, thrownError);
            
            if (jqXHR.status === 401) {
                alert('Sessão expirada. Por favor, faça login novamente.');
                window.location.href = '/auth/login';
            } else if (jqXHR.status === 403) {
                alert('Acesso negado.');
            } else if (jqXHR.status === 422) {
                const errors = jqXHR.responseJSON.errors;
                let message = 'Erro de validação:\n';
                for (const field in errors) {
                    message += `- ${errors[field].join(', ')}\n`;
                }
                alert(message);
            } else if (jqXHR.status === 500) {
                alert('Erro interno do servidor. Por favor, tente novamente.');
            }
        });
    </script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($customJs)): ?>
        <script><?= $customJs ?></script>
    <?php endif; ?>
    
    <!-- Global Functions -->
    <script>
        // Show loading overlay
        function showLoading() {
            $('#loadingOverlay').fadeIn();
        }
        
        // Hide loading overlay
        function hideLoading() {
            $('#loadingOverlay').fadeOut();
        }
        
        // Format currency
        function formatCurrency(value, currency = 'BRL') {
            const formatter = new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return formatter.format(value);
        }
        
        // Format percentage
        function formatPercentage(value, decimals = 2) {
            return parseFloat(value).toFixed(decimals) + '%';
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }
        
        // Format datetime
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('pt-BR');
        }
        
        // Download file
        function downloadFile(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Copiado para a área de transferência!');
            }, function(err) {
                console.error('Erro ao copiar: ', err);
            });
        }
        
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = $('#sidebar');
            const mainContent = $('#mainContent');
            
            sidebar.toggleClass('collapsed');
            mainContent.toggleClass('expanded');
            
            // Save preference
            localStorage.setItem('sidebarCollapsed', sidebar.hasClass('collapsed'));
        }
        
        // Initialize sidebar state
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarToggle').click(toggleSidebar);
            
            // Restore sidebar state
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                $('#sidebar').addClass('collapsed');
                $('#mainContent').addClass('expanded');
            }
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Initialize popovers
            $('[data-bs-toggle="popover"]').popover();
            
            // Prevent double form submission
            $('form').submit(function() {
                const submitBtn = $(this).find('button[type="submit"]');
                if (submitBtn.length) {
                    submitBtn.prop('disabled', true);
                    submitBtn.html('<i class="bi bi-hourglass-split me-2"></i>Processando...');
                }
            });
            
            // Confirm delete actions
            $('.confirm-delete').click(function(e) {
                if (!confirm('Tem certeza que deseja excluir este item?')) {
                    e.preventDefault();
                }
            });
            
            // DataTables Portuguese translation
            if ($.fn.DataTable) {
                $.extend(true, $.fn.DataTable.defaults, {
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                    }
                });
            }
        });
        
        // Global event listeners
        $(document).on('click', '[data-action="logout"]', function(e) {
            e.preventDefault();
            if (confirm('Deseja realmente sair?')) {
                window.location.href = '/auth/logout';
            }
        });
        
        $(document).on('click', '[data-action="print"]', function() {
            window.print();
        });
        
        $(document).on('click', '[data-action="back"]', function() {
            window.history.back();
        });
    </script>
    
    <!-- Google Analytics -->
    <?php if (!empty($_ENV['GOOGLE_ANALYTICS_ID'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $_ENV['GOOGLE_ANALYTICS_ID'] ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= $_ENV['GOOGLE_ANALYTICS_ID'] ?>');
    </script>
    <?php endif; ?>
    
</body>
</html>