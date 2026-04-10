<?php
/**
 * RISERVE - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Riserve';

// Riserve attive con info complete
$riserve = fetchAll($pdo,
    "SELECT r.*,
            p.nome as nomeProdotto, p.unitaMisura,
            d.nome as nomeDispensa,
            l.nome as nomeLuogo
     FROM RISERVA r
     INNER JOIN PRODOTTO p ON r.idProdotto = p.idProdotto
     INNER JOIN DISPENSA d ON r.idDispensa = d.idDispensa
     INNER JOIN LUOGO l ON d.idLuogo = l.idLuogo
     ORDER BY r.quantitaAttuale > 0 DESC, r.dataProduzione DESC"
);

$prodotti  = fetchAll($pdo, "SELECT * FROM PRODOTTO ORDER BY nome");
$dispense  = fetchAll($pdo,
    "SELECT d.*, l.nome as nomeLuogo FROM DISPENSA d INNER JOIN LUOGO l ON d.idLuogo = l.idLuogo ORDER BY l.nome, d.nome"
);
$lavorazioni = fetchAll($pdo,
    "SELECT lav.*, p.nome as nomeProdotto FROM LAVORAZIONE lav INNER JOIN PRODOTTO p ON lav.idProdotto = p.idProdotto ORDER BY lav.dataLavorazione DESC LIMIT 50"
);

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Riserve in Dispensa</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovaRiserva">
        <i class="fas fa-plus me-1"></i> Nuova Riserva
    </button>
</div>

<!-- Riepilogo -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?php echo count(array_filter($riserve, fn($r) => $r['quantitaAttuale'] > 0)); ?></div>
            <div class="text-muted small">Riserve attive</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning"><?php echo count(array_filter($riserve, fn($r) => $r['quantitaAttuale'] <= 0)); ?></div>
            <div class="text-muted small">Riserve esaurite</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?php echo count($riserve); ?></div>
            <div class="text-muted small">Totale riserve</div>
        </div>
    </div>
</div>

<!-- Tabella riserve -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome / Prodotto</th>
                        <th>Dispensa</th>
                        <th>Data produz.</th>
                        <th>Contenitore</th>
                        <th class="text-end">Prezzo/kg</th>
                        <th class="text-center">Disponibilità</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riserve)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nessuna riserva registrata</td></tr>
                    <?php else: ?>
                    <?php foreach ($riserve as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($r['nome']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($r['nomeProdotto']); ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($r['nomeDispensa']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($r['nomeLuogo']); ?></small>
                            </td>
                            <td><?php echo formatDate($r['dataProduzione']); ?></td>
                            <td><?php echo htmlspecialchars($r['contenitore'] ?? '-'); ?></td>
                            <td class="text-end"><?php echo formatPrice($r['prezzoAlKg']); ?></td>
                            <td class="text-center">
                                <?php
                                $pct = $r['quantitaIniziale'] > 0
                                    ? min(100, ($r['quantitaAttuale'] / $r['quantitaIniziale']) * 100)
                                    : 0;
                                $colorBar = $pct > 50 ? 'success' : ($pct > 20 ? 'warning' : 'danger');
                                ?>
                                <div style="min-width:120px">
                                    <div class="progress mb-1" style="height:8px">
                                        <div class="progress-bar bg-<?php echo $colorBar; ?>"
                                             style="width:<?php echo $pct; ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo formatWeight($r['quantitaAttuale'], $r['unitaMisura']); ?>
                                        / <?php echo formatWeight($r['quantitaIniziale'], $r['unitaMisura']); ?>
                                    </small>
                                </div>
                                <?php if ($r['quantitaAttuale'] <= 0): ?>
                                    <span class="badge bg-danger">Esaurita</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary me-1"
                                        onclick='editRiserva(<?php echo json_encode($r); ?>)'
                                        title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="/api/riserva.php" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="idRiserva" value="<?php echo $r['idRiserva']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Eliminare questa riserva?')"
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

<!-- Modal Nuova Riserva -->
<div class="modal fade" id="modalNuovaRiserva" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuova Riserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/riserva.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label class="form-label">Nome riserva <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control" placeholder="Es: Olio EVO 2026 - Lotto 1" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Prodotto <span class="text-danger">*</span></label>
                            <select name="idProdotto" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($prodotti as $p): ?>
                                    <option value="<?php echo $p['idProdotto']; ?>"><?php echo htmlspecialchars($p['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label">Dispensa <span class="text-danger">*</span></label>
                            <select name="idDispensa" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($dispense as $d): ?>
                                    <option value="<?php echo $d['idDispensa']; ?>">
                                        <?php echo htmlspecialchars($d['nome']); ?> (<?php echo htmlspecialchars($d['nomeLuogo']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Data produzione <span class="text-danger">*</span></label>
                            <input type="date" name="dataProduzione" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Quantità iniziale (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="quantitaIniziale" class="form-control" step="0.01" min="0.01" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Prezzo/kg (€)</label>
                            <input type="number" name="prezzoAlKg" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col">
                            <label class="form-label">Contenitore</label>
                            <input type="text" name="contenitore" class="form-control" placeholder="Es: Bidone 50L, Sacco juta">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lavorazione collegata (opzionale)</label>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Riserva -->
<div class="modal fade" id="modalEditRiserva" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Riserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/riserva.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="idRiserva" id="editIdRiserva">

                    <div class="mb-3">
                        <label class="form-label">Nome riserva <span class="text-danger">*</span></label>
                        <input type="text" name="nome" id="editNome" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Prezzo/kg (€)</label>
                            <input type="number" name="prezzoAlKg" id="editPrezzoAlKg" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col">
                            <label class="form-label">Contenitore</label>
                            <input type="text" name="contenitore" id="editContenitore" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dispensa <span class="text-danger">*</span></label>
                        <select name="idDispensa" id="editIdDispensa" class="form-select" required>
                            <?php foreach ($dispense as $d): ?>
                                <option value="<?php echo $d['idDispensa']; ?>">
                                    <?php echo htmlspecialchars($d['nome']); ?> (<?php echo htmlspecialchars($d['nomeLuogo']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editRiserva(r) {
    document.getElementById('editIdRiserva').value  = r.idRiserva;
    document.getElementById('editNome').value        = r.nome;
    document.getElementById('editPrezzoAlKg').value  = r.prezzoAlKg;
    document.getElementById('editContenitore').value = r.contenitore || '';
    document.getElementById('editIdDispensa').value  = r.idDispensa;
    new bootstrap.Modal(document.getElementById('modalEditRiserva')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
