<?php
/**
 * REPORT PRODUZIONE - Admin
 * Analisi Lavorazioni e Confezionamenti
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Report Produzione';

// Filtri
$dataDa = $_GET['data_da'] ?? date('Y-01-01');
$dataA  = $_GET['data_a'] ?? date('Y-12-31');
$idProdottoFiltro = isset($_GET['id_prodotto']) ? (int)$_GET['id_prodotto'] : null;

// Dati per filtri
$prodotti = fetchAll($conn, "SELECT idProdotto, nome FROM PRODOTTO ORDER BY nome");

// 1. Query Lavorazioni
$sqlLav = "SELECT l.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
           FROM LAVORAZIONE l
           INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
           INNER JOIN LUOGO lu ON l.idLuogo = lu.idLuogo
           WHERE l.dataLavorazione BETWEEN ? AND ?";
$paramsLav = [$dataDa, $dataA];

if ($idProdottoFiltro) {
    $sqlLav .= " AND l.idProdotto = ?";
    $paramsLav[] = $idProdottoFiltro;
}
$sqlLav .= " ORDER BY l.dataLavorazione DESC";
$lavorazioni = fetchAll($conn, $sqlLav, $paramsLav);

// 2. Query Confezionamenti
$sqlConf = "SELECT c.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
            FROM CONFEZIONAMENTO c
            INNER JOIN PRODOTTO p ON c.idProdotto = p.idProdotto
            INNER JOIN LUOGO lu ON c.idLuogo = lu.idLuogo
            WHERE c.dataConfezionamento BETWEEN ? AND ?";
$paramsConf = [$dataDa, $dataA];

if ($idProdottoFiltro) {
    $sqlConf .= " AND c.idProdotto = ?";
    $paramsConf[] = $idProdottoFiltro;
}
$sqlConf .= " ORDER BY c.dataConfezionamento DESC";
$confezionamenti = fetchAll($conn, $sqlConf, $paramsConf);

// Statistiche Lavorazioni
$totIn  = 0;
$totOut = 0;
foreach ($lavorazioni as $l) {
    $totIn  += $l['quantitaIngresso'];
    $totOut += $l['quantitaOttenuta'];
}
$resaMedia = $totIn > 0 ? ($totOut / $totIn) * 100 : 0;

// Statistiche Confezionamenti
$totConf = 0;
$pesoTot = 0;
foreach ($confezionamenti as $c) {
    $totConf += $c['numeroConfezioni'];
    $pesoTot += $c['numeroConfezioni'] * $c['pesoNetto'];
}

include '../includes/header_admin.php';
?>

<div class="admin-page-header mb-4">
    <h1 class="admin-page-title">Analisi Produzione</h1>
    <div class="text-muted small">Monitoraggio resa e volumi produttivi</div>
</div>

<!-- Filtri -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Periodo Da</label>
                <input type="date" name="data_da" class="form-control form-control-sm" value="<?php echo $dataDa; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Periodo A</label>
                <input type="date" name="data_a" class="form-control form-control-sm" value="<?php echo $dataA; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Prodotto</label>
                <select name="id_prodotto" class="form-select form-select-sm">
                    <option value="">Tutti i prodotti</option>
                    <?php foreach ($prodotti as $p): ?>
                        <option value="<?php echo $p['idProdotto']; ?>" <?php echo $idProdottoFiltro == $p['idProdotto'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm flex-grow-1">
                    <i class="fas fa-sync-alt me-1"></i> Aggiorna Report
                </button>
                <a href="/admin/report-produzione.php" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1">VOLUME INGRESSO</div>
                <div class="h3 mb-0 fw-bold text-dark"><?php echo number_format($totIn, 2, ',', '.'); ?> kg</div>
                <div class="mt-2 small text-success">Materia prima lavorata</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1">PRODOTTO OTTENUTO</div>
                <div class="h3 mb-0 fw-bold text-success"><?php echo number_format($totOut, 2, ',', '.'); ?> kg</div>
                <div class="mt-2 small">Totale netto</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1">RESA MEDIA</div>
                <div class="h3 mb-0 fw-bold <?php echo $resaMedia < 50 ? 'text-danger' : ($resaMedia < 80 ? 'text-warning' : 'text-primary'); ?>">
                    <?php echo number_format($resaMedia, 1, ',', '.'); ?>%
                </div>
                <div class="mt-2 progress" style="height: 4px;">
                    <div class="progress-bar" style="width: <?php echo $resaMedia; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1">CONFEZIONI CREATE</div>
                <div class="h3 mb-0 fw-bold text-info"><?php echo $totConf; ?></div>
                <div class="mt-2 small">Peso tot: <?php echo number_format($pesoTot, 2, ',', '.'); ?> kg</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Dettaglio -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom p-0">
        <ul class="nav nav-tabs border-0" id="reportTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active py-3 px-4 border-0 rounded-0" data-bs-toggle="tab" data-bs-target="#tab-lav">
                    <i class="fas fa-tools me-2"></i>Dettaglio Lavorazioni
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3 px-4 border-0 rounded-0" data-bs-toggle="tab" data-bs-target="#tab-conf">
                    <i class="fas fa-box-open me-2"></i>Dettaglio Confezionamenti
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content">
            <!-- Tab Lavorazioni -->
            <div class="tab-pane fade show active" id="tab-lav">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Data</th>
                                <th>Prodotto</th>
                                <th>Tipo</th>
                                <th class="text-end">Quantità IN</th>
                                <th class="text-end">Quantità OUT</th>
                                <th class="text-center">Resa</th>
                                <th>Luogo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lavorazioni as $l):
                                $resa = $l['quantitaIngresso'] > 0 ? ($l['quantitaOttenuta'] / $l['quantitaIngresso']) * 100 : 0;
                                $resaClass = $resa < 50 ? 'bg-danger' : ($resa < 80 ? 'bg-warning text-dark' : 'bg-success');
                            ?>
                            <tr>
                                <td class="ps-4"><?php echo formatDate($l['dataLavorazione']); ?></td>
                                <td><span class="fw-bold"><?php echo htmlspecialchars($l['nomeProdotto']); ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($l['tipoLavorazione']); ?></span></td>
                                <td class="text-end"><?php echo number_format($l['quantitaIngresso'], 2, ',', '.'); ?> kg</td>
                                <td class="text-end fw-bold"><?php echo number_format($l['quantitaOttenuta'], 2, ',', '.'); ?> kg</td>
                                <td class="text-center">
                                    <span class="badge <?php echo $resaClass; ?>">
                                        <?php echo round($resa, 1); ?>%
                                    </span>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($l['nomeLuogo']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($lavorazioni)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">Nessuna lavorazione nel periodo selezionato</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Confezionamenti -->
            <div class="tab-pane fade" id="tab-conf">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Data</th>
                                <th>Prodotto</th>
                                <th class="text-center">N° Confezioni</th>
                                <th class="text-end">Formato</th>
                                <th class="text-end">Peso Totale</th>
                                <th>Luogo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($confezionamenti as $c): ?>
                            <tr>
                                <td class="ps-4"><?php echo formatDate($c['dataConfezionamento']); ?></td>
                                <td><span class="fw-bold"><?php echo htmlspecialchars($c['nomeProdotto']); ?></span></td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info fw-bold">
                                        <?php echo $c['numeroConfezioni']; ?> pz
                                    </span>
                                </td>
                                <td class="text-end"><?php echo number_format($c['pesoNetto'], 2, ',', '.'); ?> kg</td>
                                <td class="text-end fw-bold"><?php echo number_format($c['numeroConfezioni'] * $c['pesoNetto'], 2, ',', '.'); ?> kg</td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($c['nomeLuogo']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($confezionamenti)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Nessun confezionamento nel periodo selezionato</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 text-end">
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i> Stampa Report
    </button>
</div>

<?php include '../includes/footer.php'; ?>