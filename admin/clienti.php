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

<div class="admin-page-header">
    <h1 class="admin-page-title">Anagrafica Clienti</h1>
</div>

<div class="table-container">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Data Registrazione</th>
                <th class="text-center">Ordini</th>
                <th class="text-right">Totale Speso</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clienti as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($c['telefono'] ?? '-'); ?></td>
                    <td><?php echo $c['dataRegistrazione'] ? formatDate($c['dataRegistrazione']) : '-'; ?></td>
                    <td class="text-center">
                        <span class="badge badge-primary"><?php echo $c['totaleOrdini']; ?></span>
                    </td>
                    <td class="text-right"><strong><?php echo formatPrice($c['totaleSpeso']); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
