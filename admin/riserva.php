<?php
/**
 * RISERVE - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Riserve';

$riserve = fetchAll($conn,
    "SELECT r.*, p.nome as nomeProdotto, d.nome as nomeDispensa
     FROM RISERVA r
     INNER JOIN PRODOTTO p ON r.idProdotto = p.idProdotto
     INNER JOIN DISPENSA d ON r.idDispensa = d.idDispensa
     WHERE r.quantitaAttuale > 0
     ORDER BY r.dataProduzione DESC"
);

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Riserve Attive</h1>
</div>

<div class="riserve-grid">
    <?php foreach ($riserve as $r): ?>
        <div class="riserva-card">
            <div class="riserva-header">
                <h3 class="riserva-nome"><?php echo htmlspecialchars($r['nome']); ?></h3>
                <p class="riserva-prodotto"><?php echo htmlspecialchars($r['nomeProdotto']); ?></p>
            </div>

            <div class="riserva-quantita">
                <div>
                    <span class="quantita-label">Quantità Attuale</span>
                </div>
                <span class="quantita-valore"><?php echo formatWeight($r['quantitaAttuale'], 'kg'); ?></span>
            </div>

            <div class="riserva-meta">
                <p><strong>Produzione:</strong> <?php echo formatDate($r['dataProduzione']); ?></p>
                <p><strong>Dispensa:</strong> <?php echo htmlspecialchars($r['nomeDispensa']); ?></p>
                <p><strong>Contenitore:</strong> <?php echo htmlspecialchars($r['contenitore'] ?? '-'); ?></p>
                <p><strong>Prezzo/kg:</strong> <?php echo formatPrice($r['prezzoAlKg']); ?></p>
            </div>

            <div class="progress" style="margin-top: 1rem;">
                <div class="progress-bar"
                     style="width: <?php echo min(100, ($r['quantitaAttuale'] / $r['quantitaIniziale']) * 100); ?>%;"></div>
            </div>
            <small class="text-muted">
                <?php echo formatWeight($r['quantitaAttuale'], 'kg'); ?> di
                <?php echo formatWeight($r['quantitaIniziale'], 'kg'); ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>