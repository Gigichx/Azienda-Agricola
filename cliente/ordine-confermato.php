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

$ordine = fetchOne($pdo,
    "SELECT v.*, l.nome as nomeLuogo
     FROM VENDITA v
     INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
     WHERE v.idVendita = ?",
    [$idVendita]
);

if (!$ordine) {
    redirectWithMessage('/cliente/catalogo.php', 'Ordine non trovato', 'error');
}

$dettagli = fetchAll($pdo,
    "SELECT dv.*, p.nome as nomeProdotto
     FROM DETTAGLIO_VENDITA dv
     INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
     WHERE dv.idVendita = ?",
    [$idVendita]
);

$pageTitle = 'Ordine Confermato';

include '../includes/header_cliente.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        <!-- Icona successo -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3"
                 style="width:72px;height:72px">
                <i class="fas fa-check fa-2x text-success"></i>
            </div>
            <h2 class="h3 fw-bold mb-1">Ordine confermato!</h2>
            <p class="text-muted">
                Ordine <strong>#<?php echo $ordine['idVendita']; ?></strong> registrato con successo.
            </p>
        </div>

        <!-- Riepilogo -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0"><i class="fas fa-receipt me-2 text-muted"></i>Dettagli ordine</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-3">
                    <tr>
                        <td class="text-muted ps-0">Data</td>
                        <td class="fw-semibold"><?php echo formatDate($ordine['dataVendita'], true); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Punto vendita</td>
                        <td><?php echo htmlspecialchars($ordine['nomeLuogo']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Totale pagato</td>
                        <td class="fw-bold text-success fs-5"><?php echo formatPrice($ordine['totalePagato']); ?></td>
                    </tr>
                    <?php if ($ordine['note']): ?>
                    <tr>
                        <td class="text-muted ps-0">Note</td>
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
                            <?php echo htmlspecialchars($det['nomeProdotto']); ?>
                            <?php if ($det['quantita']): ?>
                                <span class="text-muted">&times; <?php echo $det['quantita']; ?></span>
                            <?php endif; ?>
                            <?php if ($det['pesoVenduto']): ?>
                                <span class="text-muted">&mdash; <?php echo formatWeight($det['pesoVenduto'], 'kg'); ?></span>
                            <?php endif; ?>
                            <?php if ($det['omaggio']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Omaggio</span>
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
                <i class="fas fa-receipt me-1"></i>I miei ordini
            </a>
            <a href="/cliente/catalogo.php" class="btn btn-outline-secondary flex-fill">
                <i class="fas fa-store me-1"></i>Continua a comprare
            </a>
        </div>

    </div>
</div>

<?php include '../includes/footer_cliente.php'; ?>
