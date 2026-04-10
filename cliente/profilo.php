<?php
/**
 * PROFILO.PHP - Cliente
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Il profilo richiede login registrato (non guest)
if (!isCliente()) {
    if (isGuest()) {
        redirectWithMessage('/login.php', 'Accedi per visualizzare il tuo profilo', 'warning');
    } else {
        header('Location: /login.php');
        exit;
    }
}

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
<div class="profile-header-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
        </div>
        <div>
            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($cliente['nome']); ?></h5>
            <?php if ($cliente['nickname']): ?>
                <div class="text-muted small">
                    <i class="fas fa-at me-1"></i><?php echo htmlspecialchars($cliente['nickname']); ?>
                </div>
            <?php endif; ?>
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
                <i class="fas fa-calendar-alt me-1"></i>Cliente dal <?php echo formatDate($cliente['dataRegistrazione']); ?>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3">
        <div class="col-6 col-sm-4">
            <div class="profile-stat-card">
                <div class="profile-stat-value"><?php echo $stats['totaleOrdini']; ?></div>
                <div class="profile-stat-label">
                    <i class="fas fa-shopping-bag me-1"></i>Ordini totali
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4">
            <div class="profile-stat-card">
                <div class="profile-stat-value" style="font-size:1.1rem">
                    <?php echo formatPrice($stats['totaleSpeso']); ?>
                </div>
                <div class="profile-stat-label">
                    <i class="fas fa-euro-sign me-1"></i>Totale speso
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Storico ordini -->
<h5 class="mb-3 fw-bold">
    <i class="fas fa-history me-2 text-success"></i>I miei ordini
</h5>

<?php if (empty($ordini)): ?>
    <div class="empty-state">
        <div class="empty-state-icon mx-auto">
            <i class="fas fa-box-open fa-2x"></i>
        </div>
        <h3>Nessun ordine ancora</h3>
        <p>Visita il catalogo e acquista i nostri prodotti freschi.</p>
        <a href="/cliente/catalogo.php" class="btn btn-success btn-sm">
            <i class="fas fa-store me-1"></i>Vai al catalogo
        </a>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($ordini as $ordine): ?>
        <div class="ordine-card">
            <div class="ordine-card-header">
                <div>
                    <div class="fw-semibold">
                        <i class="fas fa-hashtag me-1 text-muted" style="font-size:.8rem"></i>Ordine #<?php echo $ordine['idVendita']; ?>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i><?php echo formatDate($ordine['dataVendita'], true); ?>
                        &nbsp;&nbsp;
                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($ordine['nomeLuogo']); ?>
                    </small>
                </div>
                <span class="badge-completato">
                    <i class="fas fa-check me-1"></i>Completato
                </span>
            </div>
            <div class="ordine-card-body">
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

                <div class="d-flex justify-content-end align-items-center gap-2">
                    <small class="text-muted">Totale IVA inclusa:</small>
                    <span class="fw-bold text-success fs-6"><?php echo formatPrice($ordine['totalePagato']); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer_cliente.php'; ?>
