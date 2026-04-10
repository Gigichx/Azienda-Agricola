<?php
/**
 * ARCHIVIO / REPORT - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Archivio e Report';

// Filtri
$categoriaFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$prodottoFiltro  = isset($_GET['prodotto'])  ? (int)$_GET['prodotto']  : null;
$clienteFiltro   = isset($_GET['cliente'])   ? (int)$_GET['cliente']   : null;
$anno            = isset($_GET['anno']) && $_GET['anno'] !== '' ? (int)$_GET['anno'] : null;
$dataInizio      = $_GET['dataInizio'] ?? null;
$dataFine        = $_GET['dataFine']   ?? null;
$tipoReport      = $_GET['tipo'] ?? 'vendite'; // vendite | produzione | giacenze

// Metadati per filtri
$categorie = fetchAll($pdo, "SELECT * FROM CATEGORIA ORDER BY nome");
$prodotti  = fetchAll($pdo, "SELECT p.*, c.nome as nomeCategoria FROM PRODOTTO p INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria ORDER BY p.nome");
$clienti   = fetchAll($pdo, "SELECT * FROM CLIENTE WHERE occasionale = FALSE ORDER BY nome");

// ============================================
// REPORT VENDITE
// ============================================
$risultati = [];
$totaleVendite = 0;
$importoTotale = 0;

if ($tipoReport === 'vendite') {
    $sql = "SELECT v.idVendita, v.dataVendita, v.totaleCalcolato, v.totalePagato, v.note,
                   cl.nome as nomeCliente, cl.nickname,
                   l.nome as nomeLuogo,
                   (SELECT GROUP_CONCAT(DISTINCT p.nome ORDER BY p.nome SEPARATOR ', ')
                    FROM DETTAGLIO_VENDITA dv2
                    INNER JOIN PRODOTTO p ON dv2.idProdotto = p.idProdotto
                    WHERE dv2.idVendita = v.idVendita) as prodotti
            FROM VENDITA v
            INNER JOIN CLIENTE cl ON v.idCliente = cl.idCliente
            INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
            WHERE 1=1";
    $params = [];

    if ($anno) {
        $sql .= " AND YEAR(v.dataVendita) = ?";
        $params[] = $anno;
    }
    if ($dataInizio) {
        $sql .= " AND DATE(v.dataVendita) >= ?";
        $params[] = $dataInizio;
    }
    if ($dataFine) {
        $sql .= " AND DATE(v.dataVendita) <= ?";
        $params[] = $dataFine;
    }
    if ($clienteFiltro) {
        $sql .= " AND v.idCliente = ?";
        $params[] = $clienteFiltro;
    }
    if ($categoriaFiltro) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM DETTAGLIO_VENDITA dv
            INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
            WHERE dv.idVendita = v.idVendita AND p.idCategoria = ?
        )";
        $params[] = $categoriaFiltro;
    }
    if ($prodottoFiltro) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM DETTAGLIO_VENDITA dv
            WHERE dv.idVendita = v.idVendita AND dv.idProdotto = ?
        )";
        $params[] = $prodottoFiltro;
    }

    $sql .= " ORDER BY v.dataVendita DESC LIMIT 500";
    $risultati = fetchAll($pdo, $sql, $params);
    $totaleVendite = count($risultati);
    $importoTotale = array_sum(array_column($risultati, 'totalePagato'));

// ============================================
// REPORT PRODUZIONE (lavorazioni + confezionamenti)
// ============================================
} elseif ($tipoReport === 'produzione') {
    $sql = "SELECT 'lavorazione' as tipo_riga,
                   lav.dataLavorazione as data_riga,
                   p.nome as nomeProdotto, c.nome as nomeCategoria,
                   lav.tipoLavorazione as dettaglio,
                   lav.quantitaIngresso as q_in,
                   lav.quantitaOttenuta as q_out,
                   lu.nome as nomeLuogo,
                   NULL as prezzoUnit
            FROM LAVORAZIONE lav
            INNER JOIN PRODOTTO p ON lav.idProdotto = p.idProdotto
            INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
            INNER JOIN LUOGO lu ON lav.idLuogo = lu.idLuogo
            WHERE 1=1";
    $params = [];

    if ($anno) { $sql .= " AND YEAR(lav.dataLavorazione) = ?"; $params[] = $anno; }
    if ($dataInizio) { $sql .= " AND lav.dataLavorazione >= ?"; $params[] = $dataInizio; }
    if ($dataFine)   { $sql .= " AND lav.dataLavorazione <= ?"; $params[] = $dataFine; }
    if ($categoriaFiltro) { $sql .= " AND p.idCategoria = ?"; $params[] = $categoriaFiltro; }
    if ($prodottoFiltro)  { $sql .= " AND lav.idProdotto = ?"; $params[] = $prodottoFiltro; }

    $sql .= " UNION ALL
              SELECT 'confezionamento',
                     conf.dataConfezionamento,
                     p.nome, c.nome,
                     CONCAT(conf.numeroConfezioni, ' conf. × ', conf.pesoNetto, ' kg'),
                     conf.numeroConfezioni,
                     conf.giacenzaAttuale,
                     lu.nome,
                     conf.prezzo
              FROM CONFEZIONAMENTO conf
              INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
              INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
              INNER JOIN LUOGO lu ON conf.idLuogo = lu.idLuogo
              WHERE 1=1";

    if ($anno) { $sql .= " AND YEAR(conf.dataConfezionamento) = ?"; $params[] = $anno; }
    if ($dataInizio) { $sql .= " AND conf.dataConfezionamento >= ?"; $params[] = $dataInizio; }
    if ($dataFine)   { $sql .= " AND conf.dataConfezionamento <= ?"; $params[] = $dataFine; }
    if ($categoriaFiltro) { $sql .= " AND p.idCategoria = ?"; $params[] = $categoriaFiltro; }
    if ($prodottoFiltro)  { $sql .= " AND conf.idProdotto = ?"; $params[] = $prodottoFiltro; }

    $sql .= " ORDER BY data_riga DESC LIMIT 500";
    $risultati = fetchAll($pdo, $sql, $params);

// ============================================
// REPORT GIACENZE
// ============================================
} elseif ($tipoReport === 'giacenze') {
    $sql = "SELECT p.nome as nomeProdotto, c.nome as nomeCategoria,
                   SUM(conf.giacenzaAttuale) as totaleConfezioni,
                   SUM(conf.giacenzaAttuale * conf.pesoNetto) as pesoTotale,
                   MIN(conf.prezzo) as prezzoMin,
                   MAX(conf.prezzo) as prezzoMax
            FROM CONFEZIONAMENTO conf
            INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
            INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
            WHERE 1=1";
    $params = [];
    if ($categoriaFiltro) { $sql .= " AND p.idCategoria = ?"; $params[] = $categoriaFiltro; }
    if ($prodottoFiltro)  { $sql .= " AND conf.idProdotto = ?"; $params[] = $prodottoFiltro; }
    $sql .= " GROUP BY p.idProdotto ORDER BY c.nome, p.nome";
    $risultati = fetchAll($pdo, $sql, $params);
}

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Archivio e Report</h1>
</div>

<!-- Tipo report -->
<div class="mb-4">
    <div class="btn-group" role="group">
        <a href="?tipo=vendite&<?php echo http_build_query(array_filter(['categoria'=>$categoriaFiltro,'prodotto'=>$prodottoFiltro,'cliente'=>$clienteFiltro,'anno'=>$anno,'dataInizio'=>$dataInizio,'dataFine'=>$dataFine])); ?>"
           class="btn btn-<?php echo $tipoReport === 'vendite' ? 'primary' : 'outline-primary'; ?>">
            <i class="fas fa-shopping-cart me-1"></i>Vendite
        </a>
        <a href="?tipo=produzione&<?php echo http_build_query(array_filter(['categoria'=>$categoriaFiltro,'prodotto'=>$prodottoFiltro,'anno'=>$anno,'dataInizio'=>$dataInizio,'dataFine'=>$dataFine])); ?>"
           class="btn btn-<?php echo $tipoReport === 'produzione' ? 'primary' : 'outline-primary'; ?>">
            <i class="fas fa-cogs me-1"></i>Produzione
        </a>
        <a href="?tipo=giacenze&<?php echo http_build_query(array_filter(['categoria'=>$categoriaFiltro,'prodotto'=>$prodottoFiltro])); ?>"
           class="btn btn-<?php echo $tipoReport === 'giacenze' ? 'primary' : 'outline-primary'; ?>">
            <i class="fas fa-boxes me-1"></i>Giacenze
        </a>
    </div>
</div>

<!-- Filtri -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipoReport); ?>">
            <div class="row g-2 align-items-end">
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Categoria</label>
                    <select name="categoria" class="form-select form-select-sm">
                        <option value="">Tutte</option>
                        <?php foreach ($categorie as $cat): ?>
                            <option value="<?php echo $cat['idCategoria']; ?>"
                                <?php echo $categoriaFiltro == $cat['idCategoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Prodotto</label>
                    <select name="prodotto" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        <?php foreach ($prodotti as $p): ?>
                            <option value="<?php echo $p['idProdotto']; ?>"
                                <?php echo $prodottoFiltro == $p['idProdotto'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($tipoReport === 'vendite'): ?>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Cliente</label>
                    <select name="cliente" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        <?php foreach ($clienti as $c): ?>
                            <option value="<?php echo $c['idCliente']; ?>"
                                <?php echo $clienteFiltro == $c['idCliente'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if ($tipoReport !== 'giacenze'): ?>
                <div class="col-sm-1">
                    <label class="form-label small mb-1">Anno</label>
                    <select name="anno" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $anno == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Dal</label>
                    <input type="date" name="dataInizio" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($dataInizio ?? ''); ?>">
                </div>
                <div class="col-sm-2">
                    <label class="form-label small mb-1">Al</label>
                    <input type="date" name="dataFine" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($dataFine ?? ''); ?>">
                </div>
                <?php endif; ?>
                <div class="col-sm-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i>Filtra
                    </button>
                    <a href="/admin/archivio.php?tipo=<?php echo $tipoReport; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Risultati -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            <strong class="text-dark"><?php echo count($risultati); ?></strong> risultati trovati
            <?php if ($tipoReport === 'vendite' && $importoTotale > 0): ?>
                — Incasso: <strong class="text-dark"><?php echo formatPrice($importoTotale); ?></strong>
            <?php endif; ?>
        </div>
        <button class="btn btn-sm btn-outline-success" onclick="esportaCSV()">
            <i class="fas fa-download me-1"></i>Esporta CSV
        </button>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">

        <?php if ($tipoReport === 'vendite'): ?>
            <table class="table table-hover align-middle mb-0" id="tableReport">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Prodotti</th>
                        <th>Note</th>
                        <th class="text-end">Calcolato</th>
                        <th class="text-end">Pagato</th>
                        <th class="text-center">Dettaglio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($risultati as $r): ?>
                    <tr>
                        <td>#<?php echo $r['idVendita']; ?></td>
                        <td><?php echo formatDate($r['dataVendita'], true); ?></td>
                        <td>
                            <?php echo htmlspecialchars($r['nomeCliente']); ?>
                            <?php if ($r['nickname']): ?><br><small class="text-muted"><?php echo htmlspecialchars($r['nickname']); ?></small><?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($r['prodotti'] ?? '—'); ?></small></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($r['note'] ?? ''); ?></small></td>
                        <td class="text-end text-muted"><?php echo formatPrice($r['totaleCalcolato']); ?></td>
                        <td class="text-end fw-bold"><?php echo formatPrice($r['totalePagato']); ?></td>
                        <td class="text-center">
                            <a href="/admin/vendita-dettaglio.php?id=<?php echo $r['idVendita']; ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($tipoReport === 'produzione'): ?>
            <table class="table table-hover align-middle mb-0" id="tableReport">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Data</th>
                        <th>Prodotto</th>
                        <th>Categoria</th>
                        <th>Dettaglio</th>
                        <th class="text-end">Q.tà In/Conf</th>
                        <th class="text-end">Q.tà Out/Giac</th>
                        <th>Luogo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($risultati as $r):
                        $isLav = $r['tipo_riga'] === 'lavorazione';
                    ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?php echo $isLav ? 'secondary' : 'primary'; ?>">
                                <?php echo $isLav ? 'Lavorazione' : 'Confez.'; ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($r['data_riga']); ?></td>
                        <td><strong><?php echo htmlspecialchars($r['nomeProdotto']); ?></strong></td>
                        <td><small><?php echo htmlspecialchars($r['nomeCategoria']); ?></small></td>
                        <td><small><?php echo htmlspecialchars($r['dettaglio']); ?></small></td>
                        <td class="text-end"><?php echo number_format($r['q_in'], 2, ',', '.'); ?></td>
                        <td class="text-end"><strong><?php echo number_format($r['q_out'], 2, ',', '.'); ?></strong></td>
                        <td><small><?php echo htmlspecialchars($r['nomeLuogo']); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($tipoReport === 'giacenze'): ?>
            <table class="table table-hover align-middle mb-0" id="tableReport">
                <thead class="table-light">
                    <tr>
                        <th>Prodotto</th>
                        <th>Categoria</th>
                        <th class="text-end">Confezioni</th>
                        <th class="text-end">Peso tot (kg)</th>
                        <th class="text-end">Prezzo min</th>
                        <th class="text-end">Prezzo max</th>
                        <th class="text-center">Disponibilità</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($risultati as $r): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['nomeProdotto']); ?></strong></td>
                        <td><small><?php echo htmlspecialchars($r['nomeCategoria']); ?></small></td>
                        <td class="text-end"><?php echo (int)$r['totaleConfezioni']; ?></td>
                        <td class="text-end"><?php echo number_format($r['pesoTotale'], 2, ',', '.'); ?></td>
                        <td class="text-end"><?php echo formatPrice($r['prezzoMin']); ?></td>
                        <td class="text-end"><?php echo formatPrice($r['prezzoMax']); ?></td>
                        <td class="text-center">
                            <?php if ($r['totaleConfezioni'] > 0): ?>
                                <span class="badge bg-success"><?php echo (int)$r['totaleConfezioni']; ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger">Esaurito</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (empty($risultati)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-search fa-2x mb-2"></i>
                <p>Nessun risultato trovato con i filtri selezionati</p>
            </div>
        <?php endif; ?>

        </div>
    </div>
</div>

<script>
function esportaCSV() {
    const table = document.getElementById('tableReport');
    if (!table) return;
    let csv = [];
    table.querySelectorAll('thead th').forEach((th, i, all) => {
        if (i === 0) csv.push([]);
        // skip last column (azioni)
        if (i < all.length - 1 || all[all.length-1].textContent.trim() !== '') {
            csv[0].push('"' + th.textContent.trim().replace(/"/g,'""') + '"');
        }
    });
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g,'""').replace(/\n/g,' ') + '"');
        });
        csv.push(row);
    });
    const csvStr = '\uFEFF' + csv.map(r => r.join(';')).join('\n');
    const blob = new Blob([csvStr], {type: 'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'report_<?php echo $tipoReport; ?>_<?php echo date("Y-m-d"); ?>.csv';
    a.click();
}
</script>

<?php include '../includes/footer.php'; ?>
