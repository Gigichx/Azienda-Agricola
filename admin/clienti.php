<?php
/**
 * CLIENTI - Admin
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Clienti';

$sql = "SELECT c.*, u.email, u.dataRegistrazione,
        COUNT(DISTINCT v.idVendita) as totaleOrdini,
        COALESCE(SUM(v.totalePagato), 0) as totaleSpeso
        FROM CLIENTE c
        LEFT JOIN UTENTE u ON c.idUtente = u.idUtente
        LEFT JOIN VENDITA v ON c.idCliente = v.idCliente
        WHERE c.occasionale = FALSE
        GROUP BY c.idCliente
        ORDER BY c.nome";
$clienti = fetchAll($pdo, $sql);

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Anagrafica Clienti</h1>
    <small class="text-muted"><?php echo count($clienti); ?> clienti registrati</small>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Contatti</th>
                        <th>Registrato il</th>
                        <th class="text-center">Ordini</th>
                        <th class="text-end">Totale speso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clienti)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nessun cliente registrato</td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($clienti as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($c['nome']); ?></div>
                                <?php if ($c['nickname']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($c['nickname']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['email']): ?>
                                    <div class="small">
                                        <i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($c['email']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($c['telefono']): ?>
                                    <div class="small text-muted">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($c['telefono']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo $c['dataRegistrazione'] ? formatDate($c['dataRegistrazione']) : '—'; ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?php echo $c['totaleOrdini']; ?></span>
                            </td>
                            <td class="text-end">
                                <strong class="<?php echo $c['totaleSpeso'] > 0 ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo formatPrice($c['totaleSpeso']); ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
