<?php
/**
 * PROFILO.PHP - Cliente
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();
$pageTitle = 'Il Mio Profilo';

$cliente = fetchOne($conn,
    "SELECT c.*, u.email, u.dataRegistrazione
     FROM CLIENTE c
     INNER JOIN UTENTE u ON c.idUtente = u.idUtente
     WHERE u.idUtente = ?",
    [getUserId()]
);

$ordini = fetchAll($conn,
    "SELECT v.*, l.nome as nomeLuogo,
            (SELECT COUNT(*) FROM DETTAGLIO_VENDITA WHERE idVendita = v.idVendita) as numeroArticoli
     FROM VENDITA v
     INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
     WHERE v.idCliente = ?
     ORDER BY v.dataVendita DESC
     LIMIT 20",
    [$cliente['idCliente']]
);

$stats = fetchOne($conn,
    "SELECT COUNT(*) as totaleOrdini, COALESCE(SUM(totalePagato),0) as totaleSpeso
     FROM VENDITA WHERE idCliente = ?",
    [$cliente['idCliente']]
);

include '../includes/header_cliente.php';
?>

<!-- Header profilo -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-4">
            <!-- Avatar -->
            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center fw-bold fs-4"
                 style="width:56px;height:56px;flex-shrink:0">
                <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
            </div>
            <div>
                <h5 class="mb-0 fw-semibold"><?php echo htmlspecialchars($cliente['nome']); ?></h5>
                <?php if ($cliente['email']): ?>
                    <div class="text-muted small">
                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($cliente['email']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($cliente['telefono']): ?>
                    <div class="text-muted small">
                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($cliente['telefono']); ?>
                    </div>
                <?php endif; ?>
                <div class="text-muted small">
                    <i class="fas fa-calendar me-1"></i>Cliente dal <?php echo formatDate($cliente['dataRegistrazione']); ?>
                </div>
            </div>
        </div>

        <!-- KPI quick -->
        <div class="row g-3">
            <div class="col-6 col-sm-4">
                <div class="border rounded p-3 text-center">
                    <div class="fs-4 fw-bold text-success"><?php echo $stats['totaleOrdini']; ?></div>
                    <div class="text-muted small">Ordini totali</div>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="border rounded p-3 text-center">
                    <div class="fs-5 fw-bold text-success"><?php echo formatPrice($stats['totaleSpeso']); ?></div>
                    <div class="text-muted small">Totale speso</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Storico ordini -->
<h5 class="mb-3">I miei ordini</h5>

<?php if (empty($ordini)): ?>
    <div class="text-center py-5 text-muted">
        <i class="fas fa-box-open fa-3x mb-3"></i>
        <p class="mb-3">Nessun ordine ancora</p>
        <a href="/cliente/catalogo.php" class="btn btn-success btn-sm">
            <i class="fas fa-store me-1"></i>Vai al catalogo
        </a>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($ordini as $ordine): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-semibold">Ordine #<?php echo $ordine['idVendita']; ?></div>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i><?php echo formatDate($ordine['dataVendita'], true); ?>
                            &nbsp;&nbsp;<i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($ordine['nomeLuogo']); ?>
                        </small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="fas fa-check me-1"></i>Completato
                    </span>
                </div>

                <?php
                $dettagli = fetchAll($conn,
                    "SELECT dv.*, p.nome as nomeProdotto
                     FROM DETTAGLIO_VENDITA dv
                     INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
                     WHERE dv.idVendita = ?",
                    [$ordine['idVendita']]
                );
                ?>

                <ul class="list-unstyled mb-3">
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
                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:.65rem">Omaggio</span>
                            <?php endif; ?>
                        </span>
                        <span class="text-muted">
                            <?php echo $det['omaggio'] ? 'Gratuito' : formatPrice($det['prezzoUnitario']); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($ordine['note']): ?>
                <div class="mb-2 small text-muted">
                    <i class="fas fa-sticky-note me-1"></i><?php echo htmlspecialchars($ordine['note']); ?>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end">
                    <span class="fw-bold text-success"><?php echo formatPrice($ordine['totalePagato']); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer_cliente.php'; ?>
