/**
 * Portfolio Manager - Main JavaScript
 */

// Global application state
const App = {
    config: {
        apiUrl: '/api',
        baseUrl: window.location.origin,
        currency: 'BRL',
        locale: 'pt-BR',
        dateFormat: 'dd/MM/yyyy',
        decimalSeparator: ',',
        thousandSeparator: '.'
    },
    
    state: {
        user: null,
        portfolios: [],
        assets: [],
        notifications: [],
        darkMode: false,
        sidebarCollapsed: false
    },
    
    init: function() {
        console.log('Portfolio Manager initialized');
        
        // Initialize components
        this.initTheme();
        this.initSidebar();
        this.initNotifications();
        this.initEventListeners();
        this.initDataTables();
        this.initCharts();
        
        // Load initial data if user is logged in
        if (this.isLoggedIn()) {
            this.loadUserData();
        }
    },
    
    // Authentication
    isLoggedIn: function() {
        return !!this.state.user;
    },
    
    login: function(email, password) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.apiUrl + '/auth/login',
                method: 'POST',
                data: { email, password },
                success: (response) => {
                    if (response.success) {
                        this.state.user = response.data.user;
                        this.saveToLocalStorage('user', this.state.user);
                        resolve(response);
                    } else {
                        reject(response.error);
                    }
                },
                error: (xhr) => reject(xhr.responseJSON?.error || 'Erro de conexão')
            });
        });
    },
    
    logout: function() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.apiUrl + '/auth/logout',
                method: 'POST',
                success: (response) => {
                    this.state.user = null;
                    this.removeFromLocalStorage('user');
                    resolve(response);
                },
                error: (xhr) => reject(xhr.responseJSON?.error || 'Erro de conexão')
            });
        });
    },
    
    // Data loading
    loadUserData: function() {
        this.loadPortfolios();
        this.loadAssets();
        this.loadNotifications();
    },
    
    loadPortfolios: function() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.apiUrl + '/portfolios',
                method: 'GET',
                success: (response) => {
                    if (response.success) {
                        this.state.portfolios = response.data;
                        resolve(response.data);
                    } else {
                        reject(response.error);
                    }
                },
                error: (xhr) => reject(xhr.responseJSON?.error || 'Erro de conexão')
            });
        });
    },
    
    loadAssets: function() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.apiUrl + '/assets',
                method: 'GET',
                success: (response) => {
                    if (response.success) {
                        this.state.assets = response.data;
                        resolve(response.data);
                    } else {
                        reject(response.error);
                    }
                },
                error: (xhr) => reject(xhr.responseJSON?.error || 'Erro de conexão')
            });
        });
    },
    
    // Portfolio operations
    createPortfolio: function(portfolioData) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.apiUrl + '/portfolios',
                method: 'POST',
                data: portfolioData,
                success: (response) => {
                    if (response.success) {
                        this.state.portfolios.push(response.data);
                        resolve(response.data);
                    } else {
                        reject(response.error);
                    }
                },
                error: (xhr) => reject(xhr.responseJSON?.error || 'Erro de conexão')
            });
        });
    },
    
    simulatePortfolio: function(portfolioId, parameters = {}) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.apiUrl + '/portfolios/' + portfolioId + '/simulate',
                method: 'POST',
                data: parameters,
                success: (response) => {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.error);
                    }
                },
                error: (xhr) => reject(xhr.responseJSON?.error || 'Erro de conexão')
            });
        });
    },
    
    // UI Components
    initTheme: function() {
        const savedTheme = this.getFromLocalStorage('theme');
        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            this.enableDarkMode();
        }
    },
    
    initSidebar: function() {
        const savedState = this.getFromLocalStorage('sidebarCollapsed');
        if (savedState === 'true') {
            this.collapseSidebar();
        }
    },
    
    initNotifications: function() {
        // Load notifications from server
        this.loadNotifications();
        
        // Setup WebSocket for real-time notifications
        this.initWebSocket();
    },
    
    initEventListeners: function() {
        // Theme toggle
        $(document).on('click', '[data-action="toggle-theme"]', () => {
            this.toggleDarkMode();
        });
        
        // Sidebar toggle
        $(document).on('click', '[data-action="toggle-sidebar"]', () => {
            this.toggleSidebar();
        });
        
        // Logout
        $(document).on('click', '[data-action="logout"]', (e) => {
            e.preventDefault();
            this.showConfirmModal('Sair', 'Deseja realmente sair?', () => {
                this.logout().then(() => {
                    window.location.href = '/auth/login';
                });
            });
        });
        
        // Print
        $(document).on('click', '[data-action="print"]', () => {
            window.print();
        });
        
        // Export
        $(document).on('click', '[data-action="export"]', (e) => {
            const format = $(e.currentTarget).data('format') || 'csv';
            this.exportData(format);
        });
    },
    
    initDataTables: function() {
        if ($.fn.DataTable) {
            $('table.datatable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                },
                responsive: true,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
            });
        }
    },
    
    initCharts: function() {
        // Initialize any charts on the page
        $('.chart-container canvas').each((index, element) => {
            this.initChart(element);
        });
    },
    
    initChart: function(canvasElement) {
        const ctx = canvasElement.getContext('2d');
        const chartType = $(canvasElement).data('chart-type') || 'line';
        const chartData = $(canvasElement).data('chart-data');
        
        if (chartData) {
            return new Chart(ctx, {
                type: chartType,
                data: JSON.parse(chartData),
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }
    },
    
    initWebSocket: function() {
        if (typeof io !== 'undefined') {
            const socket = io();
            
            socket.on('connect', () => {
                console.log('WebSocket connected');
            });
            
            socket.on('notification', (data) => {
                this.showNotification(data.title, data.message, data.type);
            });
            
            socket.on('simulation_update', (data) => {
                this.updateSimulationStatus(data);
            });
        }
    },
    
    // UI Helpers
    showLoading: function(message = 'Carregando...') {
        $('#loadingOverlay').find('.loading-message').text(message);
        $('#loadingOverlay').fadeIn();
    },
    
    hideLoading: function() {
        $('#loadingOverlay').fadeOut();
    },
    
    showNotification: function(title, message, type = 'info') {
        const notification = {
            id: Date.now(),
            title,
            message,
            type,
            timestamp: new Date()
        };
        
        this.state.notifications.unshift(notification);
        this.renderNotification(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            this.removeNotification(notification.id);
        }, 5000);
    },
    
    renderNotification: function(notification) {
        const alertClass = {
            success: 'alert-success',
            error: 'alert-danger',
            warning: 'alert-warning',
            info: 'alert-info'
        }[notification.type] || 'alert-info';
        
        const html = `
            <div class="alert ${alertClass} alert-dismissible fade show" id="notification-${notification.id}">
                <strong>${notification.title}</strong>
                <p class="mb-0">${notification.message}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('#notifications-container').prepend(html);
    },
    
    removeNotification: function(id) {
        $(`#notification-${id}`).alert('close');
        this.state.notifications = this.state.notifications.filter(n => n.id !== id);
    },
    
    showConfirmModal: function(title, message, onConfirm, onCancel = null) {
        const modalHtml = `
            <div class="modal fade" id="confirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="confirmButton">Confirmar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        $('#confirmButton').click(() => {
            modal.hide();
            onConfirm();
        });
        
        $('#confirmModal').on('hidden.bs.modal', () => {
            $('#confirmModal').remove();
            if (onCancel) onCancel();
        });
        
        modal.show();
    },
    
    showError: function(message) {
        this.showNotification('Erro', message, 'error');
    },
    
    showSuccess: function(message) {
        this.showNotification('Sucesso', message, 'success');
    },
    
    // Theme management
    enableDarkMode: function() {
        $('body').addClass('dark-mode');
        this.state.darkMode = true;
        this.saveToLocalStorage('theme', 'dark');
    },
    
    disableDarkMode: function() {
        $('body').removeClass('dark-mode');
        this.state.darkMode = false;
        this.saveToLocalStorage('theme', 'light');
    },
    
    toggleDarkMode: function() {
        if (this.state.darkMode) {
            this.disableDarkMode();
        } else {
            this.enableDarkMode();
        }
    },
    
    // Sidebar management
    collapseSidebar: function() {
        $('.sidebar').addClass('collapsed');
        $('.main-content').addClass('expanded');
        this.state.sidebarCollapsed = true;
        this.saveToLocalStorage('sidebarCollapsed', 'true');
    },
    
    expandSidebar: function() {
        $('.sidebar').removeClass('collapsed');
        $('.main-content').removeClass('expanded');
        this.state.sidebarCollapsed = false;
        this.saveToLocalStorage('sidebarCollapsed', 'false');
    },
    
    toggleSidebar: function() {
        if (this.state.sidebarCollapsed) {
            this.expandSidebar();
        } else {
            this.collapseSidebar();
        }
    },
    
    // Local storage helpers
    saveToLocalStorage: function(key, value) {
        try {
            localStorage.setItem('portfolio_' + key, JSON.stringify(value));
        } catch (e) {
            console.error('Error saving to localStorage:', e);
        }
    },
    
    getFromLocalStorage: function(key) {
        try {
            const value = localStorage.getItem('portfolio_' + key);
            return value ? JSON.parse(value) : null;
        } catch (e) {
            console.error('Error reading from localStorage:', e);
            return null;
        }
    },
    
    removeFromLocalStorage: function(key) {
        try {
            localStorage.removeItem('portfolio_' + key);
        } catch (e) {
            console.error('Error removing from localStorage:', e);
        }
    },
    
    // Formatting helpers
    formatCurrency: function(value, currency = this.config.currency) {
        return new Intl.NumberFormat(this.config.locale, {
            style: 'currency',
            currency: currency
        }).format(value);
    },
    
    formatPercentage: function(value, decimals = 2) {
        return parseFloat(value).toFixed(decimals) + '%';
    },
    
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString(this.config.locale);
    },
    
    formatDateTime: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString(this.config.locale);
    },
    
    // Data export
    exportData: function(format = 'csv') {
        const table = $('table.datatable').DataTable();
        const data = table.data().toArray();
        
        if (format === 'csv') {
            this.exportToCSV(data);
        } else if (format === 'excel') {
            this.exportToExcel(data);
        } else if (format === 'json') {
            this.exportToJSON(data);
        }
    },
    
    exportToCSV: function(data) {
        const csv = this.convertToCSV(data);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'export.csv';
        a.click();
        URL.revokeObjectURL(url);
    },
    
    convertToCSV: function(data) {
        const headers = Object.keys(data[0] || {});
        const rows = data.map(row => 
            headers.map(header => 
                JSON.stringify(row[header] || '')
            ).join(',')
        );
        return [headers.join(','), ...rows].join('\n');
    },
    
    // Utility functions
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    throttle: function(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Error handling
    handleError: function(error) {
        console.error('Application error:', error);
        
        if (error.status === 401) {
            this.showError('Sessão expirada. Por favor, faça login novamente.');
            setTimeout(() => {
                window.location.href = '/auth/login';
            }, 2000);
        } else if (error.status === 403) {
            this.showError('Acesso negado.');
        } else if (error.status === 422) {
            const errors = error.responseJSON?.errors;
            let message = 'Erro de validação:\n';
            for (const field in errors) {
                message += `- ${errors[field].join(', ')}\n`;
            }
            this.showError(message);
        } else {
            this.showError(error.message || 'Ocorreu um erro inesperado.');
        }
    }
};

// Initialize app when DOM is ready
$(document).ready(() => {
    App.init();
});

// Make App globally available
window.App = App;