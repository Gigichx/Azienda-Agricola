<?php
/**
 * ARCHIVIO / REPORT - Admin
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Archivio e Report';

// Filtri
$categoriaFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$anno = isset($_GET['anno']) ? (int)$_GET['anno'] : date('Y');

// Ottieni categorie
$categorie = fetchAll($pdo, "SELECT * FROM CATEGORIA ORDER BY nome");

// Query report vendite
$sql = "SELECT v.*, c.nome as nomeCliente, l.nome as nomeLuogo,
        (SELECT GROUP_CONCAT(p.nome SEPARATOR ', ')
         FROM DETTAGLIO_VENDITA dv
         INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
         WHERE dv.idVendita = v.idVendita) as prodotti
        FROM VENDITA v
        INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
        INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
        WHERE YEAR(v.dataVendita) = ?";

$params = [$anno];

if ($categoriaFiltro) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM DETTAGLIO_VENDITA dv
        INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
        WHERE dv.idVendita = v.idVendita AND p.idCategoria = ?
    )";
    $params[] = $categoriaFiltro;
}

$sql .= " ORDER BY v.dataVendita DESC LIMIT 100";

$risultati = fetchAll($pdo, $sql, $params);

// Statistiche
$totaleVendite = count($risultati);
$importoTotale = array_sum(array_column($risultati, 'totalePagato'));

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Archivio e Report</h1>
</div>

<div class="archivio-container">
    <!-- Filtri -->
    <div class="archivio-filtri">
        <form method="GET" action="/admin/archivio.php">
            <div class="filter-box">
                <div class="filter-group">
                    <label class="filter-label">Anno</label>
                    <select name="anno" class="form-select">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $anno == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Categoria</label>
                    <select name="categoria" class="form-select">
                        <option value="">Tutte</option>
                        <?php foreach ($categorie as $cat): ?>
                            <option value="<?php echo $cat['idCategoria']; ?>" 
                                    <?php echo $categoriaFiltro == $cat['idCategoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtra</button>
                    <a href="/admin/archivio.php" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Risultati -->
    <div class="archivio-risultati">
        <div class="archivio-toolbar">
            <div class="risultati-count">
                <strong><?php echo $totaleVendite; ?></strong> vendite trovate - 
                Totale: <strong><?php echo formatPrice($importoTotale); ?></strong>
            </div>
            <div class="export-actions">
                <button class="btn btn-success" onclick="exportCSV()">📥 Esporta CSV</button>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table table-striped" id="tableArchivio">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Prodotti</th>
                        <th>Luogo</th>
                        <th class="text-right">Totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($risultati as $r): ?>
                        <tr>
                            <td>#<?php echo $r['idVendita']; ?></td>
                            <td><?php echo formatDate($r['dataVendita'], true); ?></td>
                            <td><?php echo htmlspecialchars($r['nomeCliente']); ?></td>
                            <td><small><?php echo htmlspecialchars($r['prodotti']); ?></small></td>
                            <td><?php echo htmlspecialchars($r['nomeLuogo']); ?></td>
                            <td class="text-right"><strong><?php echo formatPrice($r['totalePagato']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportCSV() {
    // Semplice export CSV lato client
    const table = document.getElementById('tableArchivio');
    let csv = [];
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => headers.push(th.textContent.trim()));
    csv.push(headers.join(';'));
    
    // Rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => row.push(td.textContent.trim()));
        csv.push(row.join(';'));
    });
    
    // Download
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'archivio_vendite_<?php echo $anno; ?>.csv';
    link.click();
}
</script>

<?php include '../includes/footer.php'; ?>
