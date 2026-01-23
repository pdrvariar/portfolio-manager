<?php
/**
 * @var array $users Lista de todos os usuários do sistema
 */
$title = 'Gerenciar Usuários';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Gerenciar Usuários</h2>
</div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nome / Usuário</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Tipo</th>
                        <th>Criado em</th>
                        <th class="text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="ps-3"><?php echo $user['id']; ?></td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></div>
                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $user['is_admin'] ? '<span class="badge bg-danger">Admin</span>' : 'Investidor'; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group">
                                <a href="/index.php?url=<?php echo obfuscateUrl('admin/users/edit/' . $user['id']); ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <button class="btn btn-sm btn-outline-danger" title="Excluir" disabled><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>
