<?php
/**
 * DETTAGLIO VENDITA - Admin — FIXED
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$idVendita = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$idVendita) {
    redirectWithMessage('/admin/vendite.php', 'ID vendita non valido', 'error');
}

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

<!-- Intestazione pagina -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">
            Vendita <span class="text-muted fw-normal">#<?php echo $idVendita; ?></span>
        </h1>
        <p class="text-muted small mb-0">
            <?php echo formatDate($vendita['dataVendita'], true); ?>
            &mdash; <?php echo htmlspecialchars($vendita['nomeLuogo']); ?>
        </p>
    </div>
    <a href="/admin/vendite.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Torna alle vendite
    </a>
</div>

<!-- Griglia 3 card riepilogo -->
<div class="row g-3 mb-4">

    <!-- Riepilogo importi -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted small fw-semibold text-uppercase mb-3">
                    <i class="fas fa-receipt me-1"></i>Riepilogo
                </p>
                <table class="table table-borderless table-sm mb-0" style="font-size:.8125rem">
                    <tr>
                        <td class="text-muted ps-0">Totale calcolato</td>
                        <td class="fw-semibold"><?php echo formatPrice($vendita['totaleCalcolato']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Totale pagato</td>
                        <td>
                            <strong class="<?php echo $vendita['totalePagato'] < $vendita['totaleCalcolato'] ? 'text-warning' : 'text-success'; ?>" style="font-size:1.1rem">
                                <?php echo formatPrice($vendita['totalePagato']); ?>
                            </strong>
                            <?php if ($vendita['totalePagato'] < $vendita['totaleCalcolato']): ?>
                                <br><small class="text-warning">
                                    Sconto: <?php echo formatPrice($vendita['totaleCalcolato'] - $vendita['totalePagato']); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($vendita['note']): ?>
                    <tr>
                        <td class="text-muted ps-0">Note</td>
                        <td><em class="small"><?php echo htmlspecialchars($vendita['note']); ?></em></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Cliente -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted small fw-semibold text-uppercase mb-3">
                    <i class="fas fa-user me-1"></i>Cliente
                </p>
                <div class="fw-semibold mb-1"><?php echo htmlspecialchars($vendita['nomeCliente']); ?></div>
                <?php if ($vendita['nickname']): ?>
                    <div class="text-muted small">
                        <i class="fas fa-at me-1"></i><?php echo htmlspecialchars($vendita['nickname']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($vendita['emailCliente']): ?>
                    <div class="text-muted small">
                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($vendita['emailCliente']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($vendita['telefono']): ?>
                    <div class="text-muted small">
                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($vendita['telefono']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Composizione -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted small fw-semibold text-uppercase mb-3">
                    <i class="fas fa-chart-bar me-1"></i>Composizione
                </p>
                <?php
                $tipiCount = array_count_values(array_column($dettagli, 'tipoVendita'));
                $omaggi    = count(array_filter($dettagli, fn($d) => $d['omaggio']));
                $tipoLabel = [
                    'FRESCO_SFUSO'  => 'Fresco sfuso',
                    'CONFEZIONATO'  => 'Confezionato',
                    'RISERVA_SFUSA' => 'Riserva sfusa',
                ];
                ?>
                <div class="mb-2 small">
                    <span class="ag-badge-success"><?php echo count($dettagli); ?></span>
                    <span class="text-muted ms-1">righe totali</span>
                </div>
                <?php foreach ($tipiCount as $tipo => $cnt): ?>
                    <div class="mb-1 small">
                        <span class="ag-badge-warning"><?php echo $cnt; ?></span>
                        <span class="text-muted ms-1"><?php echo $tipoLabel[$tipo] ?? $tipo; ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($omaggi > 0): ?>
                    <div class="mt-2 small">
                        <span class="ag-badge-success"><?php echo $omaggi; ?></span>
                        <span class="text-muted ms-1">in omaggio</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Tabella dettaglio prodotti -->
<div class="table-container">
    <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center">
        <i class="fas fa-list me-2 text-muted" style="font-size:.8rem"></i>
        <span class="fw-semibold small">Prodotti venduti</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
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
                    <tr><td colspan="7" class="text-center text-muted py-3 small">Nessun dettaglio disponibile</td></tr>
                <?php endif; ?>

                <?php foreach ($dettagli as $d):
                    if ($d['tipoVendita'] === 'CONFEZIONATO') {
                        $qtyLabel  = ($d['quantita'] ?? 0) . ' conf.';
                        $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * ($d['quantita'] ?? 0);
                    } elseif ($d['tipoVendita'] === 'RISERVA_SFUSA') {
                        $qtyLabel  = number_format($d['pesoVenduto'] ?? 0, 2, ',', '.') . ' kg';
                        $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * ($d['pesoVenduto'] ?? 0);
                    } else {
                        if ($d['quantita'] !== null) {
                            $qtyLabel  = $d['quantita'] . ' pz';
                            $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * $d['quantita'];
                        } else {
                            $qtyLabel  = number_format($d['pesoVenduto'] ?? 0, 2, ',', '.') . ' kg';
                            $subtotale = $d['omaggio'] ? 0 : $d['prezzoUnitario'] * ($d['pesoVenduto'] ?? 0);
                        }
                    }
                    $tipoBadge = [
                        'FRESCO_SFUSO'  => ['class' => 'bg-light text-dark border', 'label' => 'Fresco sfuso'],
                        'CONFEZIONATO'  => ['class' => 'bg-success text-white',     'label' => 'Confezionato'],
                        'RISERVA_SFUSA' => ['class' => 'bg-info text-dark',         'label' => 'Riserva sfusa'],
                    ];
                    $tb = $tipoBadge[$d['tipoVendita']] ?? ['class' => 'bg-secondary text-white', 'label' => $d['tipoVendita']];
                ?>
                <tr class="<?php echo $d['omaggio'] ? 'table-success' : ''; ?>">
                    <td>
                        <div class="fw-semibold small"><?php echo htmlspecialchars($d['nomeProdotto']); ?></div>
                        <?php if ($d['dataConfezionamento']): ?>
                            <div class="text-muted" style="font-size:.7rem">
                                Prod: <?php echo formatDate($d['confDataProd']); ?>
                                &mdash; Conf: <?php echo formatDate($d['dataConfezionamento']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?php echo htmlspecialchars($d['nomeCategoria']); ?></td>
                    <td>
                        <span class="badge <?php echo $tb['class']; ?>" style="font-size:.65rem">
                            <?php echo $tb['label']; ?>
                        </span>
                    </td>
                    <td class="text-center small"><?php echo $qtyLabel; ?></td>
                    <td class="text-end small"><?php echo formatPrice($d['prezzoUnitario']); ?></td>
                    <td class="text-end">
                        <?php if ($d['omaggio']): ?>
                            <span class="ag-badge-success">Omaggio</span>
                        <?php else: ?>
                            <strong class="small"><?php echo formatPrice($subtotale); ?></strong>
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
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="5" class="text-end fw-semibold small">Totale calcolato:</td>
                    <td class="text-end small"><?php echo formatPrice($vendita['totaleCalcolato']); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end fw-semibold small">Totale pagato:</td>
                    <td class="text-end">
                        <strong class="<?php echo $vendita['totalePagato'] < $vendita['totaleCalcolato'] ? 'text-warning' : 'text-success'; ?>">
                            <?php echo formatPrice($vendita['totalePagato']); ?>
                        </strong>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
