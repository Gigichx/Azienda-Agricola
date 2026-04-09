<?php
/**
 * VENDITE - Admin
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Vendite';

$sql = "SELECT v.*, c.nome as nomeCliente, l.nome as nomeLuogo
        FROM VENDITA v
        INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
        INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
        ORDER BY v.dataVendita DESC
        LIMIT 50";
$vendite = fetchAll($pdo, $sql);

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Storico Vendite</h1>
</div>

<div class="table-container">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Cliente</th>
                <th>Luogo</th>
                <th class="text-right">Totale</th>
                <th class="text-center">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vendite as $v): ?>
                <tr>
                    <td><strong>#<?php echo $v['idVendita']; ?></strong></td>
                    <td><?php echo formatDate($v['dataVendita'], true); ?></td>
                    <td><?php echo htmlspecialchars($v['nomeCliente']); ?></td>
                    <td><?php echo htmlspecialchars($v['nomeLuogo']); ?></td>
                    <td class="text-right"><strong><?php echo formatPrice($v['totalePagato']); ?></strong></td>
                    <td class="text-center">
                        <a href="/admin/vendita-dettaglio.php?id=<?php echo $v['idVendita']; ?>" class="action-btn btn-view">👁️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
