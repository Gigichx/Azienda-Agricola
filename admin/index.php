<?php
/**
 * DASHBOARD ADMIN
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Dashboard';

// KPI: Vendite oggi
$venditeOggi = fetchOne($conn,
    "SELECT COUNT(*) as totale, COALESCE(SUM(totalePagato),0) as importo
     FROM VENDITA WHERE DATE(dataVendita) = CURDATE()");

// KPI: Prodotti esauriti
$prodottiEsauriti = getProdottiEsauriti($conn);
$totaleEsauriti   = count($prodottiEsauriti);

// KPI: Totale confezioni in giacenza
$giacenzeData   = fetchOne($conn, "SELECT COALESCE(SUM(giacenzaAttuale),0) as totale FROM CONFEZIONAMENTO");
$totaleGiacenze = (int)($giacenzeData['totale'] ?? 0);

// KPI: Riserve attive
$riserveData   = fetchOne($conn, "SELECT COUNT(*) as totale FROM RISERVA WHERE quantitaAttuale > 0");
$totaleRiserve = (int)($riserveData['totale'] ?? 0);

// KPI: Vendite ultimi 7 giorni (totale)
$vendite7g = fetchOne($conn,
    "SELECT COALESCE(SUM(totalePagato),0) as importo, COUNT(*) as totale
     FROM VENDITA WHERE dataVendita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");

// Ultime vendite
$ultimeVendite = fetchAll($conn,
    "SELECT v.*, c.nome as nomeCliente, c.occasionale, l.nome as nomeLuogo
     FROM VENDITA v
     INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
     INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
     ORDER BY v.dataVendita DESC LIMIT 6");

// Ultime lavorazioni
$ultimeLavorazioni = fetchAll($conn,
    "SELECT l.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
     FROM LAVORAZIONE l
     INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
     INNER JOIN LUOGO lu ON l.idLuogo = lu.idLuogo
     ORDER BY l.dataLavorazione DESC LIMIT 5");

// Giacenze per categoria
$giacenzeCategoria = fetchAll($conn,
    "SELECT c.nome, COALESCE(SUM(conf.giacenzaAttuale), 0) as totale
     FROM CATEGORIA c
     LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
     LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
     GROUP BY c.idCategoria
     HAVING totale > 0
     ORDER BY totale DESC LIMIT 8");

// Vendite per giorno ultimi 14 giorni (per grafico linea)
$venditeGiornaliere = fetchAll($conn,
    "SELECT DATE(dataVendita) as giorno,
            COUNT(*) as numVendite,
            COALESCE(SUM(totalePagato),0) as totaleGiorno
     FROM VENDITA
     WHERE dataVendita >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(dataVendita)
     ORDER BY giorno ASC");

// Top 5 prodotti per quantità venduta
$topProdotti = fetchAll($conn,
    "SELECT p.nome,
            SUM(COALESCE(dv.quantita,0)) as totVenduto,
            COALESCE(SUM(dv.quantita * dv.prezzoUnitario),0) as totRicavo
     FROM DETTAGLIO_VENDITA dv
     INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
     WHERE dv.tipoVendita = 'CONFEZIONATO' AND dv.omaggio = 0
     GROUP BY p.idProdotto
     ORDER BY totVenduto DESC LIMIT 5");

// Vendite per tipo cliente (registrati vs occasionali)
$venditeTipoCliente = fetchAll($conn,
    "SELECT c.occasionale,
            COUNT(DISTINCT v.idVendita) as numVendite,
            COALESCE(SUM(v.totalePagato),0) as totale
     FROM VENDITA v
     INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
     GROUP BY c.occasionale");

include '../includes/header_admin.php';
?>

<!-- KPI Cards -->
<div class="dashboard-kpis">
    <!-- Vendite oggi -->
    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Vendite Oggi</h3>
            <div class="kpi-icon"><i class="fas fa-cash-register"></i></div>
        </div>
        <p class="kpi-value"><?php echo (int)$venditeOggi['totale']; ?></p>
        <div class="kpi-footer">
            Incasso: <strong style="color:#16a34a"><?php echo formatPrice($venditeOggi['importo']); ?></strong>
        </div>
    </div>

    <!-- Prodotti esauriti -->
    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Prodotti Esauriti</h3>
            <div class="kpi-icon <?php echo $totaleEsauriti > 0 ? 'red' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        <p class="kpi-value" style="color:<?php echo $totaleEsauriti > 0 ? '#dc2626' : '#16a34a'; ?>">
            <?php echo $totaleEsauriti; ?>
        </p>
        <div class="kpi-footer">
            <?php echo $totaleEsauriti > 0 ? 'Richiede attenzione' : 'Tutto in ordine ✓'; ?>
        </div>
    </div>

    <!-- Giacenze totali -->
    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Giacenze Totali</h3>
            <div class="kpi-icon blue"><i class="fas fa-boxes-stacked"></i></div>
        </div>
        <p class="kpi-value"><?php echo $totaleGiacenze; ?></p>
        <div class="kpi-footer">Confezioni disponibili</div>
    </div>

    <!-- Riserve attive -->
    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Riserve Attive</h3>
            <div class="kpi-icon amber"><i class="fas fa-warehouse"></i></div>
        </div>
        <p class="kpi-value"><?php echo $totaleRiserve; ?></p>
        <div class="kpi-footer">In dispensa</div>
    </div>

    <!-- Fatturato 7gg -->
    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Fatturato 7 gg</h3>
            <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
        </div>
        <p class="kpi-value" style="font-size:1.4rem"><?php echo formatPrice($vendite7g['importo']); ?></p>
        <div class="kpi-footer"><?php echo (int)$vendite7g['totale']; ?> vendite negli ultimi 7 giorni</div>
    </div>
</div>

<!-- Grafici -->
<div class="row g-3 mb-3">
    <!-- Grafico vendite giornaliere -->
    <div class="col-lg-8">
        <div class="chart-card">
            <p class="chart-title"><i class="fas fa-chart-area me-2 text-success"></i>Andamento Vendite — ultimi 14 giorni</p>
            <div class="chart-wrap" style="height:220px">
                <canvas id="chartVendite"></canvas>
            </div>
        </div>
    </div>
    <!-- Grafico tipo cliente -->
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <p class="chart-title"><i class="fas fa-users me-2 text-success"></i>Vendite per Tipo Cliente</p>
            <div class="chart-wrap d-flex align-items-center justify-content-center" style="height:220px">
                <canvas id="chartClienti"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- Top prodotti -->
    <div class="col-lg-6">
        <div class="chart-card">
            <p class="chart-title"><i class="fas fa-trophy me-2 text-success"></i>Top 5 Prodotti Venduti</p>
            <div class="chart-wrap" style="height:200px">
                <canvas id="chartTopProdotti"></canvas>
            </div>
        </div>
    </div>
    <!-- Giacenze per categoria -->
    <div class="col-lg-6">
        <div class="chart-card">
            <p class="chart-title"><i class="fas fa-layer-group me-2 text-success"></i>Giacenze per Categoria</p>
            <div class="chart-wrap" style="height:200px">
                <canvas id="chartGiacenze"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Content -->
<div class="dashboard-content">
    <div class="dashboard-main">

        <!-- Ultime Vendite -->
        <div class="attivita-recenti">
            <div class="attivita-header">
                <h3 class="attivita-title" style="padding:0;border:none">Ultime Vendite</h3>
                <a href="/admin/vendite.php" class="btn btn-sm btn-outline-secondary">Vedi tutte</a>
            </div>
            <?php if (empty($ultimeVendite)): ?>
                <p class="empty-state">Nessuna vendita recente</p>
            <?php else: ?>
                <ul class="attivita-list">
                    <?php foreach ($ultimeVendite as $v): ?>
                    <li class="attivita-item">
                        <div class="attivita-icon vendita">💰</div>
                        <div class="attivita-content">
                            <p class="attivita-descrizione">
                                <strong><?php echo htmlspecialchars($v['nomeCliente']); ?></strong>
                                <?php if ($v['occasionale']): ?>
                                    <span class="badge bg-warning-subtle text-warning ms-1" style="font-size:.6rem">Occ.</span>
                                <?php endif; ?>
                                — <?php echo htmlspecialchars($v['nomeLuogo']); ?>
                            </p>
                            <p class="attivita-data"><?php echo formatDate($v['dataVendita'], true); ?></p>
                        </div>
                        <div class="attivita-valore"><?php echo formatPrice($v['totalePagato']); ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="attivita-footer">
                    <a href="/admin/vendite.php">Vedi tutte le vendite →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ultime Lavorazioni -->
        <div class="attivita-recenti">
            <div class="attivita-header">
                <h3 class="attivita-title" style="padding:0;border:none">Ultime Lavorazioni</h3>
                <a href="/admin/lavorazione.php" class="btn btn-sm btn-outline-secondary">Vedi tutte</a>
            </div>
            <?php if (empty($ultimeLavorazioni)): ?>
                <p class="empty-state">Nessuna lavorazione recente</p>
            <?php else: ?>
                <ul class="attivita-list">
                    <?php foreach ($ultimeLavorazioni as $l): ?>
                    <li class="attivita-item">
                        <div class="attivita-icon lavorazione">🔧</div>
                        <div class="attivita-content">
                            <p class="attivita-descrizione">
                                <strong><?php echo htmlspecialchars($l['nomeProdotto']); ?></strong>
                                — <?php echo htmlspecialchars($l['tipoLavorazione']); ?>
                            </p>
                            <p class="attivita-data">
                                <?php echo formatDate($l['dataLavorazione']); ?> —
                                <?php echo htmlspecialchars($l['nomeLuogo']); ?>
                            </p>
                        </div>
                        <div class="attivita-valore"><?php echo formatWeight($l['quantitaOttenuta'], 'kg'); ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="attivita-footer">
                    <a href="/admin/lavorazione.php">Vedi tutte le lavorazioni →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="dashboard-sidebar">

        <!-- Avvisi Urgenti -->
        <div class="avvisi-urgenti">
            <h3 class="avvisi-title">⚠️ Avvisi</h3>
            <?php if (empty($prodottiEsauriti)): ?>
                <p class="avviso-empty">Nessun avviso — tutto ok ✓</p>
            <?php else: ?>
                <?php foreach ($prodottiEsauriti as $pe): ?>
                <div class="avviso-item critico">
                    <div class="avviso-icon">🔴</div>
                    <div class="avviso-content">
                        <p class="avviso-prodotto"><?php echo htmlspecialchars($pe['nome']); ?></p>
                        <small style="color:#94a3b8"><?php echo htmlspecialchars($pe['categoria']); ?></small>
                        <br><small style="color:#dc2626;font-weight:700">ESAURITO</small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Giacenze per Categoria (barre) -->
        <div class="giacenze-categoria">
            <h3 class="giacenze-title">Giacenze per Categoria</h3>
            <?php foreach ($giacenzeCategoria as $gc): ?>
            <div class="giacenza-item">
                <div style="flex:1;min-width:0">
                    <p class="giacenza-nome"><?php echo htmlspecialchars($gc['nome']); ?></p>
                    <div class="giacenza-barra">
                        <div class="giacenza-progress"
                             style="width:<?php echo min(100, ($gc['totale'] / max($totaleGiacenze,1)) * 100); ?>%">
                        </div>
                    </div>
                </div>
                <span class="giacenza-valore"><?php echo $gc['totale']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
/* ---- Palette colori ---- */
const GREEN  = '#16a34a';
const GREEN2 = '#4ade80';
const AMBER  = '#f59e0b';
const BLUE   = '#3b82f6';
const RED    = '#ef4444';
const PURPLE = '#8b5cf6';
const SLATE  = '#64748b';

Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#64748b';
Chart.defaults.plugins.legend.position = 'bottom';
Chart.defaults.plugins.legend.labels.boxWidth = 10;
Chart.defaults.plugins.legend.labels.padding  = 16;

/* ---- 1. Andamento vendite (area) ---- */
(function() {
    // Prepara gli ultimi 14 giorni
    const labels = [], dataImporti = [], dataNum = [];

    <?php
    // Build a map date => data
    $mapVendite = [];
    foreach ($venditeGiornaliere as $row) {
        $mapVendite[$row['giorno']] = $row;
    }
    for ($i = 13; $i >= 0; $i--) {
        $day   = date('Y-m-d', strtotime("-$i days"));
        $label = date('d/m', strtotime("-$i days"));
        $row   = $mapVendite[$day] ?? ['numVendite' => 0, 'totaleGiorno' => 0];
        echo "labels.push('" . $label . "');\n";
        echo "dataImporti.push(" . (float)$row['totaleGiorno'] . ");\n";
        echo "dataNum.push("    . (int)$row['numVendite']   . ");\n";
    }
    ?>

    const ctx1 = document.getElementById('chartVendite').getContext('2d');
    const grad = ctx1.createLinearGradient(0,0,0,200);
    grad.addColorStop(0, 'rgba(22,163,74,.22)');
    grad.addColorStop(1, 'rgba(22,163,74,.01)');

    new Chart(ctx1, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Incasso (€)',
                data: dataImporti,
                borderColor: GREEN,
                backgroundColor: grad,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: GREEN,
                fill: true,
                tension: .35,
                yAxisID: 'y',
            },{
                label: 'N° Vendite',
                data: dataNum,
                borderColor: BLUE,
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: BLUE,
                fill: false,
                tension: .35,
                yAxisID: 'y1',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { grid: { color: '#f1f5f9' }, ticks: { maxRotation: 0 } },
                y: {
                    position: 'left',
                    grid: { color: '#f1f5f9' },
                    ticks: { callback: v => '€' + v.toFixed(0) }
                },
                y1: {
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
})();

/* ---- 2. Tipo cliente (doughnut) ---- */
(function() {
    <?php
    $labelsTipoCliente  = [];
    $dataNumClienti     = [];
    $dataTotaliClienti  = [];
    foreach ($venditeTipoCliente as $r) {
        $labelsTipoCliente[] = $r['occasionale'] ? 'Occasionale' : 'Registrato';
        $dataNumClienti[]    = (int)$r['numVendite'];
        $dataTotaliClienti[] = (float)$r['totale'];
    }
    ?>
    const labelsCliente = <?php echo json_encode($labelsTipoCliente); ?>;
    const numVenditeCliente = <?php echo json_encode($dataNumClienti); ?>;
    const colorsCliente = labelsCliente.map(l => l === 'Occasionale' ? AMBER : GREEN);

    const ctx2 = document.getElementById('chartClienti').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: labelsCliente,
            datasets: [{
                data: numVenditeCliente,
                backgroundColor: colorsCliente,
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} vendite`
                    }
                }
            }
        }
    });
})();

/* ---- 3. Top prodotti (bar orizzontale) ---- */
(function() {
    <?php
    $labelsProd = array_column($topProdotti, 'nome');
    $dataProd   = array_column($topProdotti, 'totVenduto');
    ?>
    const ctx3 = document.getElementById('chartTopProdotti').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labelsProd, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Quantità venduta',
                data: <?php echo json_encode(array_map('intval', $dataProd)); ?>,
                backgroundColor: [GREEN, GREEN2, BLUE, AMBER, PURPLE],
                borderRadius: 5,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` ${ctx.raw} pz` } }
            },
            scales: {
                x: { grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                y: { grid: { display: false } }
            }
        }
    });
})();

/* ---- 4. Giacenze per categoria (bar verticale) ---- */
(function() {
    <?php
    $labelsGiac = array_column($giacenzeCategoria, 'nome');
    $dataGiac   = array_column($giacenzeCategoria, 'totale');
    ?>
    const ctx4 = document.getElementById('chartGiacenze').getContext('2d');
    new Chart(ctx4, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labelsGiac, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Confezioni',
                data: <?php echo json_encode(array_map('intval', $dataGiac)); ?>,
                backgroundColor: 'rgba(22,163,74,.15)',
                borderColor: GREEN,
                borderWidth: 2,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        maxRotation: 40,
                        callback: function(v) {
                            const lbl = this.getLabelForValue(v);
                            return lbl.length > 10 ? lbl.slice(0,10) + '…' : lbl;
                        }
                    }
                },
                y: { grid: { color: '#f1f5f9' } }
            }
        }
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
