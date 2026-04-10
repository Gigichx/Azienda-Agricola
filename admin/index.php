<?php
/**
 * DASHBOARD ADMIN
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Dashboard';

// KPI
$venditeOggi = fetchOne($pdo,
    "SELECT COUNT(*) as totale, COALESCE(SUM(totalePagato),0) as importo
     FROM VENDITA WHERE DATE(dataVendita) = CURDATE()"
);

$prodottiEsauriti = getProdottiEsauriti($pdo);
$totaleEsauriti   = count($prodottiEsauriti);

$giacenzeData  = fetchOne($pdo, "SELECT COALESCE(SUM(giacenzaAttuale),0) as totale FROM CONFEZIONAMENTO");
$totaleGiacenze = (int)$giacenzeData['totale'];

$riserveData   = fetchOne($pdo, "SELECT COUNT(*) as totale FROM RISERVA WHERE quantitaAttuale > 0");
$totaleRiserve = (int)$riserveData['totale'];

// Ultime vendite
$ultimeVendite = fetchAll($pdo,
    "SELECT v.*, c.nome as nomeCliente, l.nome as nomeLuogo
     FROM VENDITA v
     INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
     INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
     ORDER BY v.dataVendita DESC LIMIT 6"
);

// Ultime lavorazioni
$ultimeLavorazioni = fetchAll($pdo,
    "SELECT l.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
     FROM LAVORAZIONE l
     INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
     INNER JOIN LUOGO lu ON l.idLuogo = lu.idLuogo
     ORDER BY l.dataLavorazione DESC LIMIT 6"
);

// Giacenze per categoria
$giacenzeCategoria = fetchAll($pdo,
    "SELECT c.nome, COALESCE(SUM(conf.giacenzaAttuale), 0) as totale
     FROM CATEGORIA c
     LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
     LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
     GROUP BY c.idCategoria
     ORDER BY totale DESC"
);

include '../includes/header_admin.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Vendite oggi</p>
                        <h3 class="mb-0 fw-bold"><?php echo (int)$venditeOggi['totale']; ?></h3>
                        <small class="text-muted"><?php echo formatPrice($venditeOggi['importo']); ?></small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fas fa-cash-register"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Prodotti esauriti</p>
                        <h3 class="mb-0 fw-bold <?php echo $totaleEsauriti > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $totaleEsauriti; ?>
                        </h3>
                        <small class="text-muted"><?php echo $totaleEsauriti > 0 ? 'Da riordinare' : 'Tutto ok'; ?></small>
                    </div>
                    <div class="bg-<?php echo $totaleEsauriti > 0 ? 'danger' : 'success'; ?> bg-opacity-10
                                  text-<?php echo $totaleEsauriti > 0 ? 'danger' : 'success'; ?> rounded p-2">
                        <i class="fas fa-<?php echo $totaleEsauriti > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Confezioni in giacenza</p>
                        <h3 class="mb-0 fw-bold"><?php echo $totaleGiacenze; ?></h3>
                        <small class="text-muted">Disponibili per la vendita</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Riserve attive</p>
                        <h3 class="mb-0 fw-bold"><?php echo $totaleRiserve; ?></h3>
                        <small class="text-muted">In dispensa</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content row -->
<div class="row g-4">

    <!-- Colonna principale -->
    <div class="col-lg-8">

        <!-- Ultime vendite -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Ultime vendite</h6>
                <a href="/admin/vendite.php" class="btn btn-sm btn-link text-success text-decoration-none">
                    Tutte <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimeVendite)): ?>
                    <p class="text-center text-muted py-4 mb-0">Nessuna vendita</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($ultimeVendite as $v): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:34px;height:34px;flex-shrink:0">
                                <i class="fas fa-shopping-cart" style="font-size:.75rem"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small"><?php echo htmlspecialchars($v['nomeCliente']); ?></div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <?php echo formatDate($v['dataVendita'], true); ?> — <?php echo htmlspecialchars($v['nomeLuogo']); ?>
                                </div>
                            </div>
                        </div>
                        <strong class="text-success"><?php echo formatPrice($v['totalePagato']); ?></strong>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ultime lavorazioni -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Ultime lavorazioni</h6>
                <a href="/admin/lavorazione.php" class="btn btn-sm btn-link text-success text-decoration-none">
                    Tutte <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimeLavorazioni)): ?>
                    <p class="text-center text-muted py-4 mb-0">Nessuna lavorazione</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($ultimeLavorazioni as $l): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:34px;height:34px;flex-shrink:0">
                                <i class="fas fa-cogs" style="font-size:.75rem"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small"><?php echo htmlspecialchars($l['nomeProdotto']); ?></div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <?php echo htmlspecialchars($l['tipoLavorazione']); ?> — <?php echo formatDate($l['dataLavorazione']); ?>
                                </div>
                            </div>
                        </div>
                        <span class="text-muted small"><?php echo formatWeight($l['quantitaOttenuta'], 'kg'); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

        <!-- Avvisi esauriti -->
        <?php if (!empty($prodottiEsauriti)): ?>
        <div class="card border-0 shadow-sm border-start border-danger border-3 mb-4">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0 fw-semibold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Prodotti esauriti
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($prodottiEsauriti as $pe): ?>
                    <li class="list-group-item py-2 px-3">
                        <div class="fw-semibold small"><?php echo htmlspecialchars($pe['nome']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($pe['categoria']); ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm border-start border-success border-3 mb-4">
            <div class="card-body py-3">
                <div class="text-success small fw-semibold">
                    <i class="fas fa-check-circle me-2"></i>Nessun prodotto esaurito
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Giacenze per categoria -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0 fw-semibold">Giacenze per categoria</h6>
            </div>
            <div class="card-body">
                <?php foreach ($giacenzeCategoria as $gc): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="fw-semibold"><?php echo htmlspecialchars($gc['nome']); ?></small>
                        <small class="text-muted"><?php echo (int)$gc['totale']; ?></small>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar bg-success"
                             style="width:<?php echo $totaleGiacenze > 0 ? min(100, ($gc['totale'] / $totaleGiacenze) * 100) : 0; ?>%">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($giacenzeCategoria)): ?>
                    <p class="text-muted small mb-0">Nessuna giacenza registrata</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
