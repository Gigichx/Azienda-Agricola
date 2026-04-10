<?php
/**
 * ORDINE CONFERMATO - Cliente
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

if (!isset($_SESSION['ordine_confermato'])) {
    redirectWithMessage('/cliente/catalogo.php', 'Nessun ordine da confermare', 'warning');
}

$idVendita = $_SESSION['ordine_confermato'];
unset($_SESSION['ordine_confermato']);

$ordine = fetchOne($conn,
    "SELECT v.*, l.nome as nomeLuogo
     FROM VENDITA v
     INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
     WHERE v.idVendita = ?",
    [$idVendita]
);

if (!$ordine) {
    redirectWithMessage('/cliente/catalogo.php', 'Ordine non trovato', 'error');
}

$dettagli = fetchAll($conn,
    "SELECT dv.*, p.nome as nomeProdotto
     FROM DETTAGLIO_VENDITA dv
     INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
     WHERE dv.idVendita = ?",
    [$idVendita]
);

// Calcola imponibile e IVA dal totale pagato
$totalePagato = $ordine['totalePagato'];
$ivaPerc      = 22;
// totalePagato = imponibile * 1.22 => imponibile = totalePagato / 1.22
$imponibile   = round($totalePagato / 1.22, 2);
$ivaAmt       = round($totalePagato - $imponibile, 2);

$pageTitle = 'Ordine Confermato';

include '../includes/header_cliente.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        <!-- Icona successo -->
        <div class="text-center mb-4">
            <div class="success-icon">
                <i class="fas fa-check fa-2x"></i>
            </div>
            <h2 class="h3 fw-bold mb-1">Ordine confermato!</h2>
            <p class="text-muted">
                Ordine <strong>#<?php echo $ordine['idVendita']; ?></strong> registrato con successo.
            </p>
        </div>

        <!-- Riepilogo -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0">
                    <i class="fas fa-receipt me-2 text-muted"></i>Dettagli ordine
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-3">
                    <tr>
                        <td class="text-muted ps-0"><i class="fas fa-calendar-alt me-1"></i>Data</td>
                        <td class="fw-semibold"><?php echo formatDate($ordine['dataVendita'], true); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0"><i class="fas fa-map-marker-alt me-1"></i>Punto vendita</td>
                        <td><?php echo htmlspecialchars($ordine['nomeLuogo']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0"><i class="fas fa-calculator me-1"></i>Imponibile</td>
                        <td><?php echo formatPrice($imponibile); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0"><i class="fas fa-percent me-1"></i>IVA 22%</td>
                        <td><?php echo formatPrice($ivaAmt); ?></td>
                    </tr>
                    <tr class="fw-bold">
                        <td class="text-muted ps-0"><i class="fas fa-euro-sign me-1"></i>Totale pagato</td>
                        <td class="text-success fs-5"><?php echo formatPrice($totalePagato); ?></td>
                    </tr>
                    <?php if ($ordine['note']): ?>
                    <tr>
                        <td class="text-muted ps-0"><i class="fas fa-sticky-note me-1"></i>Note</td>
                        <td><?php echo htmlspecialchars($ordine['note']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <!-- Prodotti acquistati -->
                <?php if (!empty($dettagli)): ?>
                <hr class="my-2">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($dettagli as $det): ?>
                    <li class="d-flex justify-content-between py-1 border-bottom small">
                        <span>
                            <i class="fas fa-seedling me-1 text-success" style="font-size:.7rem"></i>
                            <?php echo htmlspecialchars($det['nomeProdotto']); ?>
                            <?php if ($det['quantita']): ?>
                                <span class="text-muted">&times; <?php echo $det['quantita']; ?></span>
                            <?php endif; ?>
                            <?php if ($det['pesoVenduto']): ?>
                                <span class="text-muted">&mdash; <?php echo formatWeight($det['pesoVenduto'], 'kg'); ?></span>
                            <?php endif; ?>
                            <?php if ($det['omaggio']): ?>
                                <span class="badge-omaggio ms-1">Omaggio</span>
                            <?php endif; ?>
                        </span>
                        <span class="fw-semibold">
                            <?php echo $det['omaggio'] ? 'Gratuito' : formatPrice($det['prezzoUnitario'] * ($det['quantita'] ?? $det['pesoVenduto'])); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- CTA -->
        <div class="d-flex gap-2">
            <a href="/cliente/profilo.php" class="btn btn-success flex-fill">
                <i class="fas fa-history me-1"></i>I miei ordini
            </a>
            <a href="/cliente/catalogo.php" class="btn btn-outline-secondary flex-fill">
                <i class="fas fa-store me-1"></i>Continua a comprare
            </a>
        </div>

    </div>
</div>

<?php include '../includes/footer_cliente.php'; ?>
