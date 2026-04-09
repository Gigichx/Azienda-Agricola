<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();
$pageTitle = 'Confezionamento';

$confezionamenti = fetchAll($pdo, "SELECT c.*, p.nome as nomeProdotto, l.nome as nomeLuogo
                                    FROM CONFEZIONAMENTO c
                                    INNER JOIN PRODOTTO p ON c.idProdotto = p.idProdotto
                                    INNER JOIN LUOGO l ON c.idLuogo = l.idLuogo
                                    ORDER BY c.dataConfezionamento DESC LIMIT 50");

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Confezionamenti</h1>
</div>

<div class="table-container">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Data Confez.</th>
                <th>Prodotto</th>
                <th>Luogo</th>
                <th class="text-right">N° Conf.</th>
                <th class="text-right">Peso Netto</th>
                <th class="text-right">Prezzo</th>
                <th class="text-center">Giacenza</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($confezionamenti as $c): ?>
                <tr>
                    <td><?php echo formatDate($c['dataConfezionamento']); ?></td>
                    <td><strong><?php echo htmlspecialchars($c['nomeProdotto']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['nomeLuogo']); ?></td>
                    <td class="text-right"><?php echo $c['numeroConfezioni']; ?></td>
                    <td class="text-right"><?php echo formatWeight($c['pesoNetto'], 'kg'); ?></td>
                    <td class="text-right"><?php echo formatPrice($c['prezzo']); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo $c['giacenzaAttuale'] > 0 ? 'badge-success' : 'badge-error'; ?>">
                            <?php echo $c['giacenzaAttuale']; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
