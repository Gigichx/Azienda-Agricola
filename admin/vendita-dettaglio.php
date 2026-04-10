<?php
/**
 * DETTAGLIO VENDITA - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$idVendita = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$idVendita) {
    redirectWithMessage('/admin/vendite.php', 'ID vendita non valido', 'error');
}

// Testata vendita
$vendita = fetchOne($conn,
    "SELECT v.*, c.nome as nomeCliente, c.nickname, c.telefono, c.email as emailCliente,
            l.nome as nomeLuogo
     FROM VENDITA v
     INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
     INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
     WHERE v.idVendita = ?",
    [$idVendita]
);

if (!$vendita) {
    redirectWithMessage('/admin/vendite.php', 'Vendita non trovata', 'error');
}

// Righe dettaglio
$dettagli = fetchAll($conn,
    "SELECT dv.*,
            p.nome as nomeProdotto, p.unitaMisura,
            c.nome as nomeCategoria,
            conf.pesoNetto, conf.dataProduzione as confDataProd, conf.dataConfezionamento
     FROM DETTAGLIO_VENDITA dv
     INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
     INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
     LEFT JOIN CONFEZIONAMENTO conf ON dv.idConfezionamento = conf.idConfezionamento
     WHERE dv.idVendita = ?
     ORDER BY dv.idDettaglio",
    [$idVendita]
);

$pageTitle = 'Vendita #' . $idVendita;

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Vendita <span class="text-muted">#<?php echo $idVendita; ?></span></h1>
    <a href="/admin/vendite.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Torna alle vendite
    </a>
</div>

<div class="row g-4">

    <!-- Info vendita -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold text-muted mb-0">
                    <i class="fas fa-receipt me-2"></i>Riepilogo
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted ps-0">Data</td>
                        <td class="fw-semibold"><?php echo formatDate($vendita['dataVendita'], true); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Luogo</td>
                        <td><?php echo htmlspecialchars($vendita['nomeLuogo']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Totale calcolato</td>
                        <td><?php echo formatPrice($vendita['totaleCalcolato']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Totale pagato</td>
                        <td>
                            <strong class="fs-5 <?php echo $vendita['totalePagato'] < $vendita['totaleCalcolato'] ? 'text-warning' : 'text-success'; ?>">
                                <?php echo formatPrice($vendita['totalePagato']); ?>
                            </strong>
                            <?php if ($vendita['totalePagato'] < $vendita['totaleCalcolato']): ?>
                                <br><small class="text-warning">
                                    <i class="fas fa-tag me-1"></i>Sconto/omaggio:
                                    <?php echo formatPrice($vendita['totaleCalcolato'] - $vendita['totalePagato']); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($vendita['note']): ?>
                    <tr>
                        <td class="text-muted ps-0">Note</td>
                        <td><em><?php echo htmlspecialchars($vendita['note']); ?></em></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Info cliente -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold text-muted mb-0">
                    <i class="fas fa-user me-2"></i>Cliente
                </h6>
            </div>
            <div class="card-body">
                <div class="fw-semibold fs-5 mb-2"><?php echo htmlspecialchars($vendita['nomeCliente']); ?></div>
                <?php if ($vendita['nickname']): ?>
                    <div class="text-muted mb-1">
                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($vendita['nickname']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($vendita['emailCliente']): ?>
                    <div class="text-muted mb-1">
                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($vendita['emailCliente']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($vendita['telefono']): ?>
                    <div class="text-muted">
                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($vendita['telefono']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Conteggio prodotti -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold text-muted mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Composizione
                </h6>
            </div>
            <div class="card-body">
                <?php
                $tipiCount = array_count_values(array_column($dettagli, 'tipoVendita'));
                $omaggi    = count(array_filter($dettagli, fn($d) => $d['omaggio']));
                ?>
                <div class="mb-2">
                    <span class="badge bg-secondary me-1"><?php echo count($dettagli); ?></span> righe totali
                </div>
                <?php foreach ($tipiCount as $tipo => $cnt): ?>
                    <div class="mb-1">
                        <?php
                        $lab = ['FRESCO_SFUSO' => 'Fresco sfuso', 'CONFEZIONATO' => 'Confezionato', 'RISERVA_SFUSA' => 'Riserva sfusa'];
                        ?>
                        <span class="badge bg-light text-dark border me-1"><?php echo $cnt; ?></span>
                        <?php echo $lab[$tipo] ?? $tipo; ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($omaggi > 0): ?>
                    <div class="mt-2">
                        <span class="badge bg-success me-1"><?php echo $omaggi; ?></span> in omaggio
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Righe dettaglio -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent border-0">
        <h6 class="fw-semibold mb-0"><i class="fas fa-list me-2"></i>Prodotti venduti</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Prodotto</th>
                        <th>Categoria</th>
                        <th>Tipo</th>
                        <th class="text-center">Q.tà / Peso</th>
                        <th class="text-end">Prezzo unit.</th>
                        <th class="text-end">Subtotale</th>
                        <th class="text-center">Omaggio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dettagli)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">Nessun dettaglio disponibile</td></tr>
                    <?php else: ?>
                    <?php foreach ($dettagli as $d):
                        // Calcola subtotale
                        if ($d['tipoVendita'] === 'CONFEZIONATO') {
                            $qty = $d['quantita'];
                            $qtyLabel = $qty . ' conf.';
                            $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * $qty;
                        } elseif ($d['tipoVendita'] === 'RISERVA_SFUSA') {
                            $qty = $d['pesoVenduto'];
                            $qtyLabel = number_format($qty, 2, ',', '.') . ' kg';
                            $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * $qty;
                        } else {
                            // FRESCO_SFUSO
                            if ($d['quantita'] !== null) {
                                $qtyLabel  = $d['quantita'] . ' pz';
                                $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * $d['quantita'];
                            } else {
                                $qtyLabel  = number_format($d['pesoVenduto'], 2, ',', '.') . ' kg';
                                $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * $d['pesoVenduto'];
                            }
                        }
                        $tipoLabel = ['FRESCO_SFUSO' => 'Fresco sfuso', 'CONFEZIONATO' => 'Confezionato', 'RISERVA_SFUSA' => 'Riserva sfusa'];
                        $tipoBadge = ['FRESCO_SFUSO' => 'light text-dark border', 'CONFEZIONATO' => 'primary', 'RISERVA_SFUSA' => 'info text-dark'];
                    ?>
                        <tr class="<?php echo $d['omaggio'] ? 'table-success' : ''; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($d['nomeProdotto']); ?></strong>
                                <?php if ($d['dataConfezionamento']): ?>
                                    <br><small class="text-muted">
                                        Prod: <?php echo formatDate($d['confDataProd']); ?> —
                                        Conf: <?php echo formatDate($d['dataConfezionamento']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($d['nomeCategoria']); ?></small></td>
                            <td><span class="badge bg-<?php echo $tipoBadge[$d['tipoVendita']] ?? 'secondary'; ?>">
                                <?php echo $tipoLabel[$d['tipoVendita']] ?? $d['tipoVendita']; ?>
                            </span></td>
                            <td class="text-center"><?php echo $qtyLabel; ?></td>
                            <td class="text-end"><?php echo formatPrice($d['prezzoUnitario']); ?></td>
                            <td class="text-end">
                                <?php if ($d['omaggio']): ?>
                                    <span class="text-success fw-semibold">Omaggio</span>
                                <?php else: ?>
                                    <strong><?php echo formatPrice($subtotale); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($d['omaggio']): ?>
                                    <i class="fas fa-gift text-success"></i>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end fw-semibold">Totale calcolato:</td>
                        <td class="text-end"><?php echo formatPrice($vendita['totaleCalcolato']); ?></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end fw-semibold">Totale pagato:</td>
                        <td class="text-end fs-5 fw-bold text-<?php echo $vendita['totalePagato'] < $vendita['totaleCalcolato'] ? 'warning' : 'success'; ?>">
                            <?php echo formatPrice($vendita['totalePagato']); ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
