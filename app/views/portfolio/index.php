<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Portfólios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../app/views/layouts/header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Meus Portfólios</h1>
            <a href="/portfolio/create" class="btn btn-primary">Novo Portfólio</a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table id="portfoliosTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Capital Inicial</th>
                        <th>Período</th>
                        <th>Moeda</th>
                        <th>Tipo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($portfolios as $portfolio): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($portfolio['name']); ?></td>
                        <td>R$ <?php echo number_format($portfolio['initial_capital'], 2, ',', '.'); ?></td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($portfolio['start_date'])); ?>
                            <?php if ($portfolio['end_date']): ?>
                                - <?php echo date('d/m/Y', strtotime($portfolio['end_date'])); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $portfolio['output_currency']; ?></td>
                        <td>
                            <?php if ($portfolio['is_system_default']): ?>
                                <span class="badge bg-info">Sistema</span>
                            <?php else: ?>
                                <span class="badge bg-success">Pessoal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/portfolio/view/<?php echo $portfolio['id']; ?>" class="btn btn-sm btn-info">Ver</a>
                            <?php if (!$portfolio['is_system_default']): ?>
                                <a href="/portfolio/edit/<?php echo $portfolio['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="/portfolio/clone/<?php echo $portfolio['id']; ?>" class="btn btn-sm btn-secondary">Clonar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#portfoliosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
        });
    </script>
</body>
</html>