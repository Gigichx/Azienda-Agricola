<?php

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Archivio e Report';

$categoriaFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$idClienteFiltro = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : null;
$dataDa          = $_GET['data_da'] ?? '';
$dataA           = $_GET['data_a'] ?? '';
$anno            = isset($_GET['anno']) ? (int)$_GET['anno'] : (int)date('Y');

$categorie = fetchAll($conn, "SELECT * FROM CATEGORIA ORDER BY nome");
$clienti   = fetchAll($conn, "SELECT idCliente, nome FROM CLIENTE ORDER BY nome");

$sql    = "SELECT v.*, c.nome as nomeCliente, l.nome as nomeLuogo,
           (SELECT GROUP_CONCAT(p.nome SEPARATOR ', ')
            FROM DETTAGLIO_VENDITA dv
            INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
            WHERE dv.idVendita = v.idVendita) as prodotti
           FROM VENDITA v
           INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
           INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
           WHERE 1=1";
$params = [];

if (!empty($dataDa)) {
    $sql .= " AND DATE(v.dataVendita) >= ?";
    $params[] = $dataDa;
}
if (!empty($dataA)) {
    $sql .= " AND DATE(v.dataVendita) <= ?";
    $params[] = $dataA;
}
if (empty($dataDa) && empty($dataA)) {
    $sql .= " AND YEAR(v.dataVendita) = ?";
    $params[] = $anno;
}

if ($idClienteFiltro) {
    $sql .= " AND v.idCliente = ?";
    $params[] = $idClienteFiltro;
}

if ($categoriaFiltro) {
    $sql     .= " AND EXISTS (
        SELECT 1 FROM DETTAGLIO_VENDITA dv
        INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
        WHERE dv.idVendita = v.idVendita AND p.idCategoria = ?
    )";
    $params[] = $categoriaFiltro;
}

$sql .= " ORDER BY v.dataVendita DESC LIMIT 100";

$risultati = fetchAll($conn, $sql, $params);

$totaleVendite = count($risultati);
$importoTotale = array_sum(array_column($risultati, 'totalePagato'));

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Archivio e Report</h1>
</div>

<div class="archivio-container">
    <!-- Filtri -->
    <div class="archivio-filtri mb-4">
        <form method="GET" action="/admin/archivio.php">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Anno</label>
                            <select name="anno" class="form-select form-select-sm" <?php echo (!empty($dataDa) || !empty($dataA)) ? 'disabled' : ''; ?>>
                                <?php for ($y = (int)date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $anno == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Data Da</label>
                            <input type="date" name="data_da" class="form-select form-select-sm" value="<?php echo $dataDa; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Data A</label>
                            <input type="date" name="data_a" class="form-select form-select-sm" value="<?php echo $dataA; ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Categoria</label>
                            <select name="categoria" class="form-select form-select-sm">
                                <option value="">Tutte le categorie</option>
                                <?php foreach ($categorie as $cat): ?>
                                    <option value="<?php echo $cat['idCategoria']; ?>"
                                            <?php echo $categoriaFiltro == $cat['idCategoria'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Cliente</label>
                            <select name="id_cliente" class="form-select form-select-sm">
                                <option value="">Tutti i clienti</option>
                                <?php foreach ($clienti as $cl): ?>
                                    <option value="<?php echo $cl['idCliente']; ?>"
                                            <?php echo $idClienteFiltro == $cl['idCliente'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cl['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="/admin/archivio.php" class="btn btn-light btn-sm">Reset</a>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-filter me-1"></i> Filtra Risultati
                            </button>
                        </div>
                    </div>
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
    const table = document.getElementById('tableArchivio');
    let csv = [];

    const headers = [];
    table.querySelectorAll('thead th').forEach(th => headers.push(th.textContent.trim()));
    csv.push(headers.join(';'));

    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => row.push(td.textContent.trim()));
        csv.push(row.join(';'));
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'archivio_vendite_<?php echo $anno; ?>.csv';
    link.click();
}
</script>

<?php include '../includes/footer.php'; ?>