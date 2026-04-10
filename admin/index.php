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
$sqlVenditeOggi = "SELECT COUNT(*) as totale, SUM(totalePagato) as importo
                   FROM VENDITA
                   WHERE DATE(dataVendita) = CURDATE()";
$venditeOggi = fetchOne($conn, $sqlVenditeOggi);

// KPI: Prodotti esauriti
$prodottiEsauriti = getProdottiEsauriti($conn);
$totaleEsauriti   = count($prodottiEsauriti);

// KPI: Totale confezioni in giacenza
$sqlGiacenze  = "SELECT SUM(giacenzaAttuale) as totale FROM CONFEZIONAMENTO";
$giacenzeData = fetchOne($conn, $sqlGiacenze);
$totaleGiacenze = $giacenzeData['totale'] ?? 0;

// KPI: Riserve attive
$sqlRiserve  = "SELECT COUNT(*) as totale FROM RISERVA WHERE quantitaAttuale > 0";
$riserveData = fetchOne($conn, $sqlRiserve);
$totaleRiserve = $riserveData['totale'];

// Ultime vendite
$sqlUltimeVendite = "SELECT v.*, c.nome as nomeCliente, l.nome as nomeLuogo
                     FROM VENDITA v
                     INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
                     INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
                     ORDER BY v.dataVendita DESC
                     LIMIT 5";
$ultimeVendite = fetchAll($conn, $sqlUltimeVendite);

// Ultime lavorazioni
$sqlUltimeLavorazioni = "SELECT l.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
                         FROM LAVORAZIONE l
                         INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
                         INNER JOIN LUOGO lu ON l.idLuogo = lu.idLuogo
                         ORDER BY l.dataLavorazione DESC
                         LIMIT 5";
$ultimeLavorazioni = fetchAll($conn, $sqlUltimeLavorazioni);

// Giacenze per categoria
$sqlGiacenzeCategoria = "SELECT c.nome,
                         COALESCE(SUM(conf.giacenzaAttuale), 0) as totale
                         FROM CATEGORIA c
                         LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
                         LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
                         GROUP BY c.idCategoria
                         ORDER BY totale DESC";
$giacenzeCategoria = fetchAll($conn, $sqlGiacenzeCategoria);

include '../includes/header_admin.php';
?>

<!-- KPI Cards -->
<div class="dashboard-kpis">
    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Vendite Oggi</h3>
            <div class="kpi-icon">💰</div>
        </div>
        <p class="kpi-value"><?php echo $venditeOggi['totale']; ?></p>
        <div class="kpi-footer">
            <span>Totale: <?php echo formatPrice($venditeOggi['importo'] ?? 0); ?></span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Prodotti Esauriti</h3>
            <div class="kpi-icon">⚠️</div>
        </div>
        <p class="kpi-value" style="color: <?php echo $totaleEsauriti > 0 ? 'var(--color-error)' : 'var(--color-success)'; ?>;">
            <?php echo $totaleEsauriti; ?>
        </p>
        <div class="kpi-footer">
            <span><?php echo $totaleEsauriti > 0 ? 'Necessita riordino' : 'Tutto ok'; ?></span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Giacenze Totali</h3>
            <div class="kpi-icon">📦</div>
        </div>
        <p class="kpi-value"><?php echo $totaleGiacenze; ?></p>
        <div class="kpi-footer">
            <span>Confezioni disponibili</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <h3 class="kpi-title">Riserve Attive</h3>
            <div class="kpi-icon">🏺</div>
        </div>
        <p class="kpi-value"><?php echo $totaleRiserve; ?></p>
        <div class="kpi-footer">
            <span>In dispensa</span>
        </div>
    </div>
</div>

<!-- Content -->
<div class="dashboard-content">
    <div class="dashboard-main">

        <!-- Ultime Vendite -->
        <div class="attivita-recenti">
            <div class="attivita-header">
                <h3 class="attivita-title">Ultime Vendite</h3>
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
                                    <strong><?php echo htmlspecialchars($v['nomeCliente']); ?></strong> -
                                    <?php echo htmlspecialchars($v['nomeLuogo']); ?>
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
                <h3 class="attivita-title">Ultime Lavorazioni</h3>
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
                                    <strong><?php echo htmlspecialchars($l['nomeProdotto']); ?></strong> -
                                    <?php echo htmlspecialchars($l['tipoLavorazione']); ?>
                                </p>
                                <p class="attivita-data">
                                    <?php echo formatDate($l['dataLavorazione']); ?> -
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
                <p class="avviso-empty">Nessun avviso</p>
            <?php else: ?>
                <?php foreach ($prodottiEsauriti as $pe): ?>
                    <div class="avviso-item critico">
                        <div class="avviso-icon">⚠️</div>
                        <div class="avviso-content">
                            <p class="avviso-prodotto"><?php echo htmlspecialchars($pe['nome']); ?></p>
                            <small>Categoria: <?php echo htmlspecialchars($pe['categoria']); ?></small><br>
                            <small style="color: var(--color-error);">ESAURITO</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Giacenze per Categoria -->
        <div class="giacenze-categoria">
            <h3 class="giacenze-title">Giacenze per Categoria</h3>
            <?php foreach ($giacenzeCategoria as $gc): ?>
                <div class="giacenza-item">
                    <div>
                        <p class="giacenza-nome"><?php echo htmlspecialchars($gc['nome']); ?></p>
                        <div class="giacenza-barra">
                            <div class="giacenza-progress" style="width: <?php echo min(100, ($gc['totale'] / max($totaleGiacenze, 1)) * 100); ?>%;"></div>
                        </div>
                    </div>
                    <span class="giacenza-valore"><?php echo $gc['totale']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>