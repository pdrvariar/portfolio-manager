<?php
require_once __DIR__ . '/../../app/controllers/AssetController.php';
$controller = new AssetController();

// Tipos de ativos disponíveis
$assetTypes = [
    'STOCK' => 'Ação',
    'BOND' => 'Renda Fixa',
    'CRYPTO' => 'Criptomoeda',
    'COMMODITY' => 'Commodity',
    'CURRENCY' => 'Moeda',
    'INDEX' => 'Índice',
    'ETF' => 'ETF',
    'REIT' => 'FII',
    'FUND' => 'Fundo',
    'DERIVATIVE' => 'Derivativo'
];

// Moedas disponíveis
$currencies = [
    'BRL' => 'Real Brasileiro (BRL)',
    'USD' => 'Dólar Americano (USD)',
    'EUR' => 'Euro (EUR)',
    'GBP' => 'Libra Esterlina (GBP)',
    'JPY' => 'Iene Japonês (JPY)',
    'CNY' => 'Yuan Chinês (CNY)',
    'CHF' => 'Franco Suíço (CHF)',
    'CAD' => 'Dólar Canadense (CAD)',
    'AUD' => 'Dólar Australiano (AUD)',
    'BTC' => 'Bitcoin (BTC)',
    'ETH' => 'Ethereum (ETH)'
];
?>

<?php
$pageTitle = 'Importar Ativos';
$pageSubtitle = 'Importe dados históricos de ativos via arquivo CSV';
$pageActions = '
    <a href="/assets" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Voltar para Ativos
    </a>
';
include __DIR__ . '/../layout/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Import Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Importar Dados Históricos</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/assets/import" enctype="multipart/form-data" id="importForm">
                    <!-- Asset Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Informações do Ativo</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_code" class="form-label">Código do Ativo *</label>
                                    <input type="text" class="form-control" id="asset_code" name="asset_code" 
                                           required placeholder="Ex: PETR4, BTC-USD, SELIC">
                                    <div class="form-text">Código único para identificar o ativo</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_name" class="form-label">Nome do Ativo</label>
                                    <input type="text" class="form-control" id="asset_name" name="asset_name" 
                                           placeholder="Ex: Petrobras PN, Bitcoin, Taxa SELIC">
                                    <div class="form-text">Nome amigável para exibição (opcional)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_type" class="form-label">Tipo de Ativo *</label>
                                    <select class="form-select" id="asset_type" name="asset_type" required>
                                        <option value="">Selecione o tipo...</option>
                                        <?php foreach ($assetTypes as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Moeda *</label>
                                    <select class="form-select" id="currency" name="currency" required>
                                        <option value="">Selecione a moeda...</option>
                                        <?php foreach ($currencies as $value => $label): ?>
                                            <option value="<?= $value ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Arquivo CSV</h6>
                        
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">Arquivo CSV *</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" 
                                   accept=".csv,.txt" required>
                            <div class="form-text">
                                Formato esperado: CSV com colunas "Data" e "Valor"
                            </div>
                        </div>
                        
                        <!-- CSV Preview -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="has_header" name="has_header" checked>
                                <label class="form-check-label" for="has_header">
                                    O arquivo possui cabeçalho na primeira linha
                                </label>
                            </div>
                        </div>
                        
                        <!-- Date Format -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">Formato da Data</label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <option value="Y-m-d">YYYY-MM-DD (2024-01-15)</option>
                                        <option value="d/m/Y">DD/MM/YYYY (15/01/2024)</option>
                                        <option value="m/d/Y">MM/DD/YYYY (01/15/2024)</option>
                                        <option value="Y-m">YYYY-MM (2024-01)</option>
                                        <option value="m/Y">MM/YYYY (01/2024)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="decimal_separator" class="form-label">Separador Decimal</label>
                                    <select class="form-select" id="decimal_separator" name="decimal_separator">
                                        <option value=".">Ponto (.) - 1000.50</option>
                                        <option value=",">Vírgula (,) - 1000,50</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CSV Template -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Exemplo de Formato</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Valor</th>
                                        <th>Volume (opcional)</th>
                                        <th>Variação (opcional)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2024-01-15</td>
                                        <td>100.50</td>
                                        <td>1000000</td>
                                        <td>1.50%</td>
                                    </tr>
                                    <tr>
                                        <td>2024-01-14</td>
                                        <td>99.00</td>
                                        <td>950000</td>
                                        <td>-0.50%</td>
                                    </tr>
                                    <tr>
                                        <td>2024-01-13</td>
                                        <td>99.50</td>
                                        <td>900000</td>
                                        <td>0.75%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Formato mínimo:</strong> Apenas as colunas "Data" e "Valor" são obrigatórias.
                            Você pode baixar um 
                            <a href="#" id="downloadTemplate">modelo de CSV</a> para usar como base.
                        </div>
                    </div>
                    
                    <!-- Import Options -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">Opções de Importação</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="update_existing" name="update_existing">
                                        <label class="form-check-label" for="update_existing">
                                            Atualizar ativo existente
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Se marcado, atualiza dados de ativo com mesmo código
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" checked>
                                        <label class="form-check-label" for="skip_duplicates">
                                            Pular registros duplicados
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Ignora datas que já existem no histórico
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="validate_prices" name="validate_prices" checked>
                                        <label class="form-check-label" for="validate_prices">
                                            Validar preços
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Verifica se preços são positivos e válidos
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="normalize_dates" name="normalize_dates" checked>
                                        <label class="form-check-label" for="normalize_dates">
                                            Normalizar datas
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Ajusta datas para o primeiro dia do mês se necessário
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
                                <i class="bi bi-eye"></i> Pré-visualizar
                            </button>
                            <button type="submit" class="btn btn-primary" id="importBtn">
                                <i class="bi bi-upload"></i> Importar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Import Instructions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Instruções</h5>
            </div>
            <div class="card-body">
                <h6>📋 Pré-requisitos</h6>
                <ul class="small">
                    <li>Arquivo em formato CSV (UTF-8 recomendado)</li>
                    <li>Coluna "Data" no formato correto</li>
                    <li>Coluna "Valor" com números válidos</li>
                    <li>Dados ordenados por data (mais antigo primeiro)</li>
                </ul>
                
                <h6 class="mt-3">⚙️ Formatos Suportados</h6>
                <ul class="small">
                    <li><strong>Preços:</strong> Ações, ETFs, Fundos</li>
                    <li><strong>Taxas:</strong> Juros, CDI, SELIC</li>
                    <li><strong>Índices:</strong> IBOVESPA, S&P 500</li>
                    <li><strong>Câmbio:</strong> Taxas de conversão</li>
                    <li><strong>Cripto:</strong> Bitcoin, Ethereum</li>
                </ul>
                
                <h6 class="mt-3">⚠️ Limitações</h6>
                <ul class="small">
                    <li>Máximo de 100.000 registros por importação</li>
                    <li>Datas a partir de 2000-01-01</li>
                    <li>Preços entre 0.0001 e 10.000.000</li>
                    <li>Arquivos até 10MB</li>
                </ul>
            </div>
        </div>
        
        <!-- Recent Imports -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Últimas Importações</h5>
            </div>
            <div class="card-body">
                <div id="recentImports">
                    <p class="text-muted text-center py-3">Carregando histórico...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pré-visualização do CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-3">Processando arquivo...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="confirmImportBtn">
                    <i class="bi bi-check-circle"></i> Confirmar Importação
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

<script>
$(document).ready(function() {
    // Load recent imports
    loadRecentImports();
    
    // Download template
    $('#downloadTemplate').click(function(e) {
        e.preventDefault();
        downloadCSVTemplate();
    });
    
    // Preview CSV
    $('#previewBtn').click(function() {
        previewCSV();
    });
    
    // Form submission
    $('#importForm').submit(function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        importCSV();
    });
    
    // Confirm import from preview
    $('#confirmImportBtn').click(function() {
        $('#previewModal').modal('hide');
        importCSV();
    });
    
    // Load recent imports
    function loadRecentImports() {
        $.ajax({
            url: '/api/assets/imports/recent',
            method: 'GET',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    const imports = response.data;
                    let html = '<div class="list-group">';
                    
                    imports.forEach(function(importData) {
                        const date = new Date(importData.created_at).toLocaleDateString('pt-BR');
                        const time = new Date(importData.created_at).toLocaleTimeString('pt-BR', { 
                            hour: '2-digit', 
                            minute: '2-digit' 
                        });
                        
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <strong>${importData.asset_code}</strong>
                                    <small>${date} ${time}</small>
                                </div>
                                <div class="small text-muted">
                                    ${importData.records_imported} registros • ${importData.asset_type}
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    $('#recentImports').html(html);
                } else {
                    $('#recentImports').html('<p class="text-muted text-center py-3">Nenhuma importação recente</p>');
                }
            },
            error: function() {
                $('#recentImports').html('<p class="text-muted text-center py-3">Erro ao carregar histórico</p>');
            }
        });
    }
    
    // Download CSV template
    function downloadCSVTemplate() {
        const template = `Data,Valor,Volume,Variação
2024-01-15,100.50,1000000,1.50%
2024-01-14,99.00,950000,-0.50%
2024-01-13,99.50,900000,0.75%
2024-01-12,98.75,850000,0.25%
2024-01-11,98.50,800000,-0.25%`;
        
        const blob = new Blob([template], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'modelo_importacao_ativos.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showSuccess('Modelo baixado com sucesso!');
    }
    
    // Preview CSV file
    function previewCSV() {
        const fileInput = $('#csv_file')[0];
        
        if (!fileInput.files || fileInput.files.length === 0) {
            showError('Por favor, selecione um arquivo CSV primeiro');
            return;
        }
        
        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('has_header', $('#has_header').is(':checked') ? '1' : '0');
        formData.append('date_format', $('#date_format').val());
        formData.append('decimal_separator', $('#decimal_separator').val());
        
        // Show preview modal
        $('#previewModal').modal('show');
        
        $.ajax({
            url: '/api/assets/import/preview',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#previewContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-3">Processando arquivo...</p>
                    </div>
                `);
            },
            success: function(response) {
                if (response.success) {
                    displayPreview(response.data);
                } else {
                    $('#previewContent').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            ${response.error}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#previewContent').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Erro ao processar arquivo: ${xhr.responseJSON?.error || 'Erro desconhecido'}
                    </div>
                `);
            }
        });
    }
    
    // Display preview data
    function displayPreview(data) {
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Informações do Arquivo</h6>
                    <dl class="row">
                        <dt class="col-6">Total de linhas:</dt>
                        <dd class="col-6">${data.total_rows}</dd>
                        
                        <dt class="col-6">Colunas detectadas:</dt>
                        <dd class="col-6">${data.columns.join(', ')}</dd>
                        
                        <dt class="col-6">Período:</dt>
                        <dd class="col-6">${data.start_date} até ${data.end_date}</dd>
                        
                        <dt class="col-6">Valor mínimo:</dt>
                        <dd class="col-6">${data.min_value}</dd>
                        
                        <dt class="col-6">Valor máximo:</dt>
                        <dd class="col-6">${data.max_value}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h6>Validação</h6>
                    <div class="alert ${data.valid ? 'alert-success' : 'alert-warning'}">
                        <i class="bi ${data.valid ? 'bi-check-circle' : 'bi-exclamation-triangle'}"></i>
                        ${data.valid ? 'Arquivo válido para importação' : 'Atenção: problemas detectados'}
                    </div>
                    
                    ${data.issues.length > 0 ? `
                        <h6>Problemas Detectados</h6>
                        <ul class="small">
                            ${data.issues.map(issue => `<li>${issue}</li>`).join('')}
                        </ul>
                    ` : ''}
                </div>
            </div>
            
            <h6 class="mt-4">Primeiras 10 linhas</h6>
        `;
        
        // Create table preview
        html += `
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            ${data.columns.map(col => `<th>${col}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.preview.map(row => `
                            <tr>
                                ${data.columns.map(col => `<td>${row[col] || ''}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            ${data.total_rows > 10 ? `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Mostrando 10 de ${data.total_rows} registros. 
                    Todos os registros serão importados.
                </div>
            ` : ''}
        `;
        
        $('#previewContent').html(html);
    }
    
    // Validate form
    function validateForm() {
        const assetCode = $('#asset_code').val().trim();
        const assetType = $('#asset_type').val();
        const currency = $('#currency').val();
        const fileInput = $('#csv_file')[0];
        
        if (!assetCode) {
            showError('Por favor, informe o código do ativo');
            return false;
        }
        
        if (!assetType) {
            showError('Por favor, selecione o tipo de ativo');
            return false;
        }
        
        if (!currency) {
            showError('Por favor, selecione a moeda');
            return false;
        }
        
        if (!fileInput.files || fileInput.files.length === 0) {
            showError('Por favor, selecione um arquivo CSV');
            return false;
        }
        
        const file = fileInput.files[0];
        if (file.size > 10 * 1024 * 1024) { // 10MB
            showError('Arquivo muito grande. Tamanho máximo: 10MB');
            return false;
        }
        
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showError('Por favor, selecione um arquivo CSV');
            return false;
        }
        
        return true;
    }
    
    // Import CSV
    function importCSV() {
        const formData = new FormData($('#importForm')[0]);
        
        // Add additional form data
        formData.append('asset_name', $('#asset_name').val());
        formData.append('update_existing', $('#update_existing').is(':checked') ? '1' : '0');
        formData.append('skip_duplicates', $('#skip_duplicates').is(':checked') ? '1' : '0');
        formData.append('validate_prices', $('#validate_prices').is(':checked') ? '1' : '0');
        formData.append('normalize_dates', $('#normalize_dates').is(':checked') ? '1' : '0');
        
        $.ajax({
            url: '/api/assets/import',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#importBtn').prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    Importando...
                `);
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(`Importação concluída! ${response.data.records_imported} registros importados.`);
                    
                    // Reset form
                    $('#importForm')[0].reset();
                    
                    // Reload recent imports
                    loadRecentImports();
                    
                    // Redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = '/assets';
                    }, 2000);
                } else {
                    showError(response.error);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Erro ao importar arquivo');
            },
            complete: function() {
                $('#importBtn').prop('disabled', false).html('<i class="bi bi-upload"></i> Importar');
            }
        });
    }
    
    // Helper functions
    function showSuccess(message) {
        // Create success alert
        const alert = $(`
            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" 
                 role="alert" style="z-index: 9999; max-width: 400px;">
                <i class="bi bi-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(alert);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }
    
    function showError(message) {
        // Create error alert
        const alert = $(`
            <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" 
                 role="alert" style="z-index: 9999; max-width: 400px;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(alert);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }
});
</script>

<style>
/* Additional styles for import page */
.csv-preview {
    max-height: 400px;
    overflow-y: auto;
}

.import-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.progress-container {
    background-color: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    min-width: 300px;
}

.file-drop-zone {
    border: 2px dashed #6c757d;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.file-drop-zone:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.file-drop-zone.dragover {
    border-color: #28a745;
    background-color: #e7f3ff;
}

.format-example {
    font-family: monospace;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    font-size: 14px;
}

.validation-result {
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.validation-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.validation-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.validation-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.import-statistics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
}
</style>