// JavaScript principal da aplicação

$(document).ready(function() {
    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Inicializar popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Confirmar ações de exclusão
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Tem certeza que deseja excluir? Esta ação não pode ser desfeita.')) {
            e.preventDefault();
        }
    });
    
    // Formatação de números
    $('.format-number').each(function() {
        const value = parseFloat($(this).text());
        if (!isNaN(value)) {
            $(this).text(value.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }
    });
    
    // Formatação de porcentagens
    $('.format-percentage').each(function() {
        const value = parseFloat($(this).text());
        if (!isNaN(value)) {
            $(this).text(value.toFixed(2) + '%');
        }
    });
    
    // Auto-hide alerts após 5 segundos
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Validação de formulários
    $('form.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});

// Funções utilitárias
function showLoading(message = 'Processando...') {
    $('#loadingModal .modal-body').text(message);
    $('#loadingModal').modal('show');
}

function hideLoading() {
    $('#loadingModal').modal('hide');
}

function showError(message) {
    $('#errorModal .modal-body').text(message);
    $('#errorModal').modal('show');
}

function showSuccess(message) {
    $('#successModal .modal-body').text(message);
    $('#successModal').modal('show');
}

// AJAX helper
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    showLoading();
    
    $.ajax({
        url: url,
        method: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                if (successCallback) successCallback(response);
            } else {
                showError(response.error || 'Ocorreu um erro.');
                if (errorCallback) errorCallback(response);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showError('Erro na requisição: ' + error);
            if (errorCallback) errorCallback({ error: error });
        }
    });
}

// Upload de arquivos
function uploadFile(fileInput, progressCallback, successCallback, errorCallback) {
    const file = fileInput.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('file', file);
    
    showLoading('Enviando arquivo...');
    
    $.ajax({
        url: '/api/upload',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable && progressCallback) {
                    const percent = (e.loaded / e.total) * 100;
                    progressCallback(percent);
                }
            });
            return xhr;
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                if (successCallback) successCallback(response);
            } else {
                showError(response.error || 'Erro no upload.');
                if (errorCallback) errorCallback(response);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showError('Erro no upload: ' + error);
            if (errorCallback) errorCallback({ error: error });
        }
    });
}

// Modal de confirmação
function confirmAction(message, confirmCallback, cancelCallback) {
    $('#confirmModal .modal-body').text(message);
    
    $('#confirmModal').off('shown.bs.modal').on('shown.bs.modal', function() {
        $('#confirmModal .btn-primary').off('click').on('click', function() {
            $('#confirmModal').modal('hide');
            if (confirmCallback) confirmCallback();
        });
        
        $('#confirmModal .btn-secondary').off('click').on('click', function() {
            $('#confirmModal').modal('hide');
            if (cancelCallback) cancelCallback();
        });
    });
    
    $('#confirmModal').modal('show');
}

// Download de arquivos
function downloadFile(url, filename) {
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Filtro de tabelas
function filterTable(tableId, columnIndex, searchTerm) {
    const table = $('#' + tableId);
    table.find('tbody tr').each(function() {
        const cell = $(this).find('td').eq(columnIndex).text();
        if (cell.toLowerCase().indexOf(searchTerm.toLowerCase()) > -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Ordenação de tabelas
function sortTable(tableId, columnIndex, ascending = true) {
    const table = $('#' + tableId);
    const rows = table.find('tbody tr').toArray();
    
    rows.sort(function(a, b) {
        const aValue = $(a).find('td').eq(columnIndex).text();
        const bValue = $(b).find('td').eq(columnIndex).text();
        
        // Tentar converter para número
        const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return ascending ? aNum - bNum : bNum - aNum;
        } else {
            // Ordenação alfabética
            return ascending ? 
                aValue.localeCompare(bValue) : 
                bValue.localeCompare(aValue);
        }
    });
    
    table.find('tbody').empty().append(rows);
}

// Adicionar modal de carregamento ao DOM
if ($('#loadingModal').length === 0) {
    $('body').append(`
        <div class="modal fade" id="loadingModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2"></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Erro</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Sucesso</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    `);
}