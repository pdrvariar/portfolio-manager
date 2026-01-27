<?php
/**
 * @var array $portfolio Dados do portfólio
 * @var array $allAssets Lista de todos os ativos disponíveis
 * @var array $portfolioAssets Ativos já vinculados a este portfólio
 */

$title = 'Editar Portfólio: ' . htmlspecialchars($portfolio['name']);
ob_start();
?>

    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="alert alert-warning shadow-sm mb-4" id="rangeWarning">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold">Conflito de Datas Detectado</h6>
                        <p class="mb-1 small" id="rangeWarningText"></p>
                        <button type="button" class="btn btn-sm btn-dark mt-2" onclick="autoAdjustDates()">
                            <i class="bi bi-magic me-1"></i> Ajustar Período do Portfólio Automaticamente
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Editar Configurações do Portfólio</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('portfolio/update/' . $portfolio['id']); ?>" id="portfolioForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-bold">Nome do Portfólio *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($portfolio['name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="initial_capital" class="form-label fw-bold">Capital Inicial *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo $portfolio['output_currency']; ?></span>
                                    <input type="number" class="form-control" id="initial_capital" name="initial_capital" step="0.01" min="100" value="<?php echo $portfolio['initial_capital']; ?>" required>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label fw-bold">Descrição / Estratégia</label>
                                <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($portfolio['description']); ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="start_date" class="form-label fw-bold">Data Início *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $portfolio['start_date']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label fw-bold">Data Fim (Opcional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $portfolio['end_date']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="rebalance_frequency" class="form-label fw-bold">Rebalanceamento *</label>
                                <select class="form-select" id="rebalance_frequency" name="rebalance_frequency" required>
                                    <?php
                                    $freqs = ['monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'biannual' => 'Semestral', 'annual' => 'Anual', 'never' => 'Nunca'];
                                    foreach ($freqs as $val => $label): ?>
                                        <option value="<?php echo $val; ?>" <?php echo $portfolio['rebalance_frequency'] == $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-4">
                                <label for="output_currency" class="form-label fw-bold">Moeda de Exibição *</label>
                                <select class="form-select" id="output_currency" name="output_currency" required>
                                    <option value="BRL" <?php echo $portfolio['output_currency'] == 'BRL' ? 'selected' : ''; ?>>BRL (Real)</option>
                                    <option value="USD" <?php echo $portfolio['output_currency'] == 'USD' ? 'selected' : ''; ?>>USD (Dólar)</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3 fw-bold"><i class="bi bi-calculator me-2 text-primary"></i>Tipo de Simulação</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="simulation_type" class="form-label fw-bold">Tipo de Simulação *</label>
                                    <select class="form-select" id="simulation_type" name="simulation_type" required onchange="toggleSimulationFields()">
                                        <option value="standard" <?= $portfolio['simulation_type'] == 'standard' ? 'selected' : '' ?>>Padrão (sem aportes)</option>
                                        <option value="monthly_deposit" <?= $portfolio['simulation_type'] == 'monthly_deposit' ? 'selected' : '' ?>>Com Aportes Periódicos</option>
                                        <option value="strategic_deposit" <?= $portfolio['simulation_type'] == 'strategic_deposit' ? 'selected' : '' ?>>Com Aportes Estratégicos</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Campos para Aportes Periódicos -->
                        <div id="monthly_deposit_fields" class="simulation-fields" style="display: <?= $portfolio['simulation_type'] == 'monthly_deposit' ? 'block' : 'none' ?>;">
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Configuração de Aportes Periódicos</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="deposit_amount" class="form-label">Valor do Aporte</label>
                                                <div class="input-group">
                                                    <span class="input-group-text" id="deposit_currency_label"><?= $portfolio['deposit_currency'] ?? 'BRL' ?></span>
                                                    <input type="number" class="form-control" id="deposit_amount" name="deposit_amount"
                                                           value="<?= htmlspecialchars($portfolio['deposit_amount'] ?? '') ?>"
                                                           step="0.01" min="0" placeholder="Ex: 5000.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="deposit_currency" class="form-label">Moeda do Aporte</label>
                                                <select class="form-select" id="deposit_currency" name="deposit_currency" onchange="document.getElementById('deposit_currency_label').innerText = this.value">
                                                    <option value="BRL" <?= ($portfolio['deposit_currency'] ?? 'BRL') == 'BRL' ? 'selected' : '' ?>>BRL (Real)</option>
                                                    <option value="USD" <?= ($portfolio['deposit_currency'] ?? 'BRL') == 'USD' ? 'selected' : '' ?>>USD (Dólar)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="deposit_frequency" class="form-label">Frequência</label>
                                                <select class="form-select" id="deposit_frequency" name="deposit_frequency">
                                                    <option value="monthly" <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'monthly' ? 'selected' : '' ?>>Mensal</option>
                                                    <option value="bimonthly" <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'bimonthly' ? 'selected' : '' ?>>Bimestral</option>
                                                    <option value="quarterly" <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'quarterly' ? 'selected' : '' ?>>Trimestral</option>
                                                    <option value="biannual" <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'biannual' ? 'selected' : '' ?>>Semestral</option>
                                                    <option value="annual" <?= ($portfolio['deposit_frequency'] ?? 'monthly') == 'annual' ? 'selected' : '' ?>>Anual</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-info py-2 small mb-0">
                                        <i class="bi bi-info-circle me-1"></i> Os aportes serão realizados automaticamente na data de início de cada período.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos para Aportes Estratégicos -->
                        <div id="strategic_deposit_fields" class="simulation-fields" style="display: <?= $portfolio['simulation_type'] == 'strategic_deposit' ? 'block' : 'none' ?>;">
                            <div class="card border-warning mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bi bi-graph-down me-2"></i>Configuração de Aportes Estratégicos</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="strategic_threshold" class="form-label">Limiar de Queda para Aporte</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="strategic_threshold" name="strategic_threshold"
                                                           value="<?= htmlspecialchars($portfolio['strategic_threshold'] ?? '') ?>"
                                                           step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <div class="form-text">Aporte será feito se o portfólio cair este percentual em um mês</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="strategic_deposit_percentage" class="form-label">Percentual do Aporte</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="strategic_deposit_percentage" name="strategic_deposit_percentage"
                                                           value="<?= htmlspecialchars($portfolio['strategic_deposit_percentage'] ?? '') ?>"
                                                           step="0.1" min="0" max="100" placeholder="Ex: 10.0">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <div class="form-text">Percentual do valor atual do portfólio a ser aportado</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning py-2 small mb-0">
                                        <i class="bi bi-lightbulb me-1"></i> Exemplo: Se cair 10% em um mês, será aportado 10% do valor atual do portfólio.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3 fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i>Alocação Estratégica de Ativos</h5>
                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle" id="assetsTable">
                                <thead class="table-light">
                                <tr>
                                    <th>Ativo e Disponibilidade Histórica</th>
                                    <th style="width: 160px;">Alocação (%)</th>
                                    <th style="width: 140px;">Fator Perf.</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                                </thead>
                                <tbody id="assetsBody">
                                </tbody>
                                <tfoot class="bg-light">
                                <tr>
                                    <td>
                                        <select class="form-select" id="assetSelect">
                                            <option value="">+ Adicionar novo ativo...</option>
                                            <?php foreach ($allAssets as $asset): ?>
                                                <option value="<?php echo $asset['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                                        data-min="<?php echo $asset['min_date']; ?>"
                                                        data-max="<?php echo $asset['max_date']; ?>">
                                                    <?php echo htmlspecialchars($asset['name']); ?>
                                                    (<?php echo date('m/Y', strtotime($asset['min_date'])); ?> a <?php echo date('m/Y', strtotime($asset['max_date'])); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="assetAllocation" step="0.01" min="0" max="100" placeholder="0.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="assetFactor" step="0.01" min="0.1" max="10" value="1.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success" onclick="addAsset()"><i class="bi bi-plus-lg"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold">TOTAL DA CARTEIRA:</td>
                                    <td class="fw-bold"><span id="totalAllocation">0</span>%</td>
                                    <td colspan="2"></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="alert alert-danger py-2" id="allocationWarning" style="display: none;">
                            <i class="bi bi-exclamation-octagon me-2"></i>A soma das alocações deve ser exatamente 100.00% para salvar.
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/index.php?url=<?= obfuscateUrl('portfolio/view/' . $portfolio['id']) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm" id="submitBtn">
                                <i class="bi bi-check-lg me-1"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSimulationFields() {
            const type = document.getElementById('simulation_type').value;
            document.querySelectorAll('.simulation-fields').forEach(el => el.style.display = 'none');
            if (type !== 'standard') {
                const fields = document.getElementById(type + '_fields');
                if (fields) fields.style.display = 'block';
            }
        }

        // Executar ao carregar para garantir visibilidade correta
        document.addEventListener('DOMContentLoaded', function() {
            toggleSimulationFields();
        });

        // Inicialização do array de ativos com dados históricos
        let assets = [
            <?php foreach ($portfolioAssets as $pa):
            // Busca as datas limites na lista global de ativos para popular o JS
            $min = ""; $max = "";
            foreach($allAssets as $aa) if($aa['id'] == $pa['asset_id']) { $min = $aa['min_date']; $max = $aa['max_date']; break; }
            ?>
            {
                id: <?php echo $pa['id']; ?>,
                asset_id: <?php echo $pa['asset_id']; ?>,
                name: "<?php echo htmlspecialchars($pa['name']); ?>",
                allocation: <?php echo (float)$pa['allocation_percentage']; ?>,
                factor: <?php echo (float)$pa['performance_factor']; ?>,
                min_date: "<?php echo $min; ?>",
                max_date: "<?php echo $max; ?>"
            },
            <?php endforeach; ?>
        ];

        let nextId = assets.length > 0 ? Math.max(...assets.map(a => a.id)) + 1 : 1;
        let suggestedMaxStart = "";
        let suggestedMinEnd = "";

        document.addEventListener('DOMContentLoaded', function() {
            updateTable(); // CORREÇÃO: Nome correto da função sincronizado com o carregamento
        });

        // Controle dos campos de simulação
        function toggleSimulationFields() {
            const type = document.getElementById('simulation_type').value;

            // Esconde todos os campos primeiro
            document.querySelectorAll('.simulation-fields').forEach(field => {
                field.style.display = 'none';
            });

            // Mostra os campos específicos
            if (type === 'monthly_deposit') {
                document.getElementById('monthly_deposit_fields').style.display = 'block';
                // Define valores padrão se estiver vazio
                const depositAmount = document.getElementById('deposit_amount');
                if (!depositAmount.value) {
                    depositAmount.value = '1000.00';
                }
            } else if (type === 'strategic_deposit') {
                document.getElementById('strategic_deposit_fields').style.display = 'block';
                // Define valores padrão se estiver vazio
                const threshold = document.getElementById('strategic_threshold');
                const percentage = document.getElementById('strategic_deposit_percentage');
                if (!threshold.value) {
                    threshold.value = '10.0';
                }
                if (!percentage.value) {
                    percentage.value = '10.0';
                }
            }
        }

        function addAsset() {
            const select = document.getElementById('assetSelect');
            const allocationInput = document.getElementById('assetAllocation');
            const factorInput = document.getElementById('assetFactor');

            if (!select.value || !allocationInput.value) {
                alert("Selecione um ativo e informe a alocação.");
                return;
            }

            const opt = select.options[select.selectedIndex];
            const assetId = parseInt(select.value);

            if (assets.find(a => a.asset_id === assetId)) {
                alert("Este ativo já está na lista.");
                return;
            }

            assets.push({
                id: nextId++,
                asset_id: assetId,
                name: opt.getAttribute('data-name'),
                allocation: parseFloat(allocationInput.value),
                factor: parseFloat(factorInput.value) || 1.0,
                min_date: opt.getAttribute('data-min'),
                max_date: opt.getAttribute('data-max')
            });

            select.value = '';
            allocationInput.value = '';
            factorInput.value = '1.00';

            updateTable();
        }

        function removeAsset(id) {
            assets = assets.filter(a => a.id !== id);
            updateTable();
        }

        function updateTable() {
            const tbody = document.getElementById('assetsBody');
            const totalSpan = document.getElementById('totalAllocation');
            let total = 0;

            tbody.innerHTML = '';

            assets.forEach(asset => {
                total += asset.allocation;
                const dateInfo = asset.min_date ? `<div class="asset-range-info"><i class="bi bi-calendar-check me-1"></i>Histórico: ${formatDateLabel(asset.min_date)} a ${formatDateLabel(asset.max_date)}</div>` : '';

                tbody.innerHTML += `
            <tr>
                <td>
                    <div class="fw-bold text-dark">${asset.name}</div>
                    ${dateInfo}
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" name="assets[${asset.asset_id}][allocation]"
                            value="${asset.allocation.toFixed(2)}" step="any" class="form-control fw-bold text-primary"
                            oninput="updateAssetData(${asset.asset_id}, 'allocation', this.value)">
                        <span class="input-group-text">%</span>
                    </div>
                </td>
                <td>
                    <input type="number" name="assets[${asset.asset_id}][performance_factor]"
                        value="${asset.factor}" step="0.01" class="form-control form-control-sm"
                        oninput="updateAssetData(${asset.asset_id}, 'factor', this.value)">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeAsset(${asset.id})"><i class="bi bi-trash"></i></button>
                    <input type="hidden" name="assets[${asset.asset_id}][asset_id]" value="${asset.asset_id}">
                </td>
            </tr>
        `;
            });

            totalSpan.innerText = total.toFixed(2);
            checkTotal(total);
            validatePortfolioRange(); // Valida as datas após qualquer mudança na lista
        }

        function updateAssetData(assetId, field, value) {
            const asset = assets.find(a => a.asset_id == assetId);
            if (asset) {
                if (field === 'allocation') asset.allocation = parseFloat(value) || 0;
                else if (field === 'factor') asset.factor = parseFloat(value) || 0;

                let total = assets.reduce((sum, a) => sum + a.allocation, 0);
                document.getElementById('totalAllocation').innerText = total.toFixed(2);
                checkTotal(total);
            }
        }

        function checkTotal(total) {
            const isCorrect = Math.abs(total - 100) < 0.01;
            const submitBtn = document.getElementById('submitBtn');
            const warning = document.getElementById('allocationWarning');

            submitBtn.disabled = !isCorrect;
            warning.style.display = isCorrect ? 'none' : 'block';
        }

        function validatePortfolioRange() {
            if (assets.length === 0) return;

            // O limitador de INÍCIO é o ativo que começou por ÚLTIMO (data mais recente)
            const limitStartAsset = assets.reduce((prev, curr) => (prev.min_date > curr.min_date) ? prev : curr);
            // O limitador de FIM é o ativo que terminou PRIMEIRO (data mais antiga)
            const limitEndAsset = assets.reduce((prev, curr) => (prev.max_date < curr.max_date) ? prev : curr);

            suggestedMaxStart = limitStartAsset.min_date;
            suggestedMinEnd = limitEndAsset.max_date;

            const portfolioStart = document.getElementById('start_date').value;
            const portfolioEnd = document.getElementById('end_date').value;
            const warning = document.getElementById('rangeWarning');
            const warningText = document.getElementById('rangeWarningText');

            let errors = [];
            if (portfolioStart < suggestedMaxStart) {
                errors.push(`O ativo <strong>${limitStartAsset.name}</strong> só possui dados a partir de <strong>${formatDateLabel(suggestedMaxStart)}</strong>.`);
            }
            if (portfolioEnd && portfolioEnd > suggestedMinEnd) {
                errors.push(`O ativo <strong>${limitEndAsset.name}</strong> só possui dados até <strong>${formatDateLabel(suggestedMinEnd)}</strong>.`);
            }

            if (errors.length > 0) {
                warning.style.display = 'block';
                warningText.innerHTML = errors.join('<br>');
                document.getElementById('submitBtn').disabled = true;
            } else {
                warning.style.display = 'none';
                checkTotal(assets.reduce((sum, a) => sum + a.allocation, 0));
            }
        }

        function autoAdjustDates() {
            document.getElementById('start_date').value = suggestedMaxStart;
            if (suggestedMinEnd) document.getElementById('end_date').value = suggestedMinEnd;
            validatePortfolioRange();
        }

        function formatDateLabel(dateStr) {
            if (!dateStr) return "-";
            const [y, m] = dateStr.split('-');
            return `${m}/${y}`;
        }

        // Ouvintes para validar em tempo real ao mudar as datas do portfólio
        document.getElementById('start_date').addEventListener('change', validatePortfolioRange);
        document.getElementById('end_date').addEventListener('change', validatePortfolioRange);

        // Inicializa os campos de simulação ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            toggleSimulationFields();
        });

    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>