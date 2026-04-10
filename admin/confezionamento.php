<?php
/**
 * CONFEZIONAMENTO - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Confezionamento';

$confezionamenti = fetchAll($pdo,
    "SELECT c.*,
            p.nome as nomeProdotto, p.unitaMisura,
            l.nome as nomeLuogo,
            r.nome as nomeRiserva,
            lav.tipoLavorazione
     FROM CONFEZIONAMENTO c
     INNER JOIN PRODOTTO p ON c.idProdotto = p.idProdotto
     INNER JOIN LUOGO l ON c.idLuogo = l.idLuogo
     LEFT JOIN RISERVA r ON c.idRiserva = r.idRiserva
     LEFT JOIN LAVORAZIONE lav ON c.idLavorazione = lav.idLavorazione
     ORDER BY c.dataConfezionamento DESC
     LIMIT 100"
);

$prodotti    = fetchAll($pdo, "SELECT * FROM PRODOTTO ORDER BY nome");
$luoghi      = fetchAll($pdo, "SELECT * FROM LUOGO ORDER BY nome");
$lavorazioni = fetchAll($pdo,
    "SELECT lav.*, p.nome as nomeProdotto FROM LAVORAZIONE lav
     INNER JOIN PRODOTTO p ON lav.idProdotto = p.idProdotto
     ORDER BY lav.dataLavorazione DESC LIMIT 50"
);
$riserve = fetchAll($pdo,
    "SELECT r.*, p.nome as nomeProdotto FROM RISERVA r
     INNER JOIN PRODOTTO p ON r.idProdotto = p.idProdotto
     WHERE r.quantitaAttuale > 0
     ORDER BY p.nome"
);

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Confezionamenti</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovoConfez">
        <i class="fas fa-plus me-1"></i> Nuovo Confezionamento
    </button>
</div>

<!-- Giacenza totale -->
<?php
$totaleConfezioni = array_sum(array_column($confezionamenti, 'giacenzaAttuale'));
$confDisponibili  = count(array_filter($confezionamenti, fn($c) => $c['giacenzaAttuale'] > 0));
?>
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?php echo count($confezionamenti); ?></div>
            <div class="text-muted small">Lotti totali</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?php echo $totaleConfezioni; ?></div>
            <div class="text-muted small">Confezioni in giacenza</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-<?php echo $confDisponibili > 0 ? 'success' : 'danger'; ?>"><?php echo $confDisponibili; ?></div>
            <div class="text-muted small">Lotti con giacenza > 0</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Prodotto</th>
                        <th>Origine</th>
                        <th>Data prod.</th>
                        <th>Data confez.</th>
                        <th class="text-end">N° Conf.</th>
                        <th class="text-end">Peso netto</th>
                        <th class="text-end">Prezzo</th>
                        <th class="text-center">Giacenza</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($confezionamenti)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Nessun confezionamento registrato</td></tr>
                    <?php else: ?>
                    <?php foreach ($confezionamenti as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($c['nomeProdotto']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($c['nomeLuogo']); ?></small>
                            </td>
                            <td>
                                <?php if ($c['nomeRiserva']): ?>
                                    <span class="badge bg-info text-dark">
                                        <i class="fas fa-box me-1"></i><?php echo htmlspecialchars($c['nomeRiserva']); ?>
                                    </span>
                                <?php elseif ($c['tipoLavorazione']): ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-cogs me-1"></i><?php echo htmlspecialchars($c['tipoLavorazione']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($c['dataProduzione']); ?></td>
                            <td><?php echo formatDate($c['dataConfezionamento']); ?></td>
                            <td class="text-end"><?php echo $c['numeroConfezioni']; ?></td>
                            <td class="text-end"><?php echo formatWeight($c['pesoNetto'], 'kg'); ?></td>
                            <td class="text-end"><strong><?php echo formatPrice($c['prezzo']); ?></strong></td>
                            <td class="text-center">
                                <?php if ($c['giacenzaAttuale'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $c['giacenzaAttuale']; ?></span>
                                <?php elseif ($c['giacenzaAttuale'] == 0): ?>
                                    <span class="badge bg-danger">Esaurito</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="/api/confezionamento.php" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="idConfezionamento" value="<?php echo $c['idConfezionamento']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Eliminare questo confezionamento?')"
                                            title="Elimina">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuovo Confezionamento -->
<div class="modal fade" id="modalNuovoConfez" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuovo Confezionamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/confezionamento.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Prodotto <span class="text-danger">*</span></label>
                            <select name="idProdotto" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($prodotti as $p): ?>
                                    <option value="<?php echo $p['idProdotto']; ?>"><?php echo htmlspecialchars($p['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Luogo confezionamento <span class="text-danger">*</span></label>
                            <select name="idLuogo" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($luoghi as $lu): ?>
                                    <option value="<?php echo $lu['idLuogo']; ?>"><?php echo htmlspecialchars($lu['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data produzione <span class="text-danger">*</span></label>
                            <input type="date" name="dataProduzione" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data confezionamento <span class="text-danger">*</span></label>
                            <input type="date" name="dataConfezionamento" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">N° confezioni <span class="text-danger">*</span></label>
                            <input type="number" name="numeroConfezioni" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Peso netto/conf (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="pesoNetto" class="form-control" step="0.001" min="0.001" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prezzo/confezione (€) <span class="text-danger">*</span></label>
                            <input type="number" name="prezzo" class="form-control" step="0.01" min="0.01" required>
                        </div>
                    </div>

                    <hr>
                    <p class="text-muted small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Origine del prodotto (opzionale): collegare a una riserva esistente
                        oppure a una lavorazione diretta.
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Da riserva (opzionale)</label>
                            <select name="idRiserva" class="form-select">
                                <option value="">-- Nessuna --</option>
                                <?php foreach ($riserve as $r): ?>
                                    <option value="<?php echo $r['idRiserva']; ?>">
                                        <?php echo htmlspecialchars($r['nome']); ?>
                                        (<?php echo formatWeight($r['quantitaAttuale'], 'kg'); ?> disp.)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">La quantità totale (peso × n° confezioni) verrà scalata dalla riserva.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Da lavorazione diretta (opzionale)</label>
                            <select name="idLavorazione" class="form-select">
                                <option value="">-- Nessuna --</option>
                                <?php foreach ($lavorazioni as $lav): ?>
                                    <option value="<?php echo $lav['idLavorazione']; ?>">
                                        <?php echo htmlspecialchars($lav['nomeProdotto']); ?> -
                                        <?php echo htmlspecialchars($lav['tipoLavorazione']); ?> -
                                        <?php echo formatDate($lav['dataLavorazione']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Registra</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
