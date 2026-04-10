<?php
/**
 * LAVORAZIONI - Admin
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Lavorazioni';

$lavorazioni = fetchAll($pdo,
    "SELECT l.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
     FROM LAVORAZIONE l
     INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
     INNER JOIN LUOGO lu ON l.idLuogo = lu.idLuogo
     ORDER BY l.dataLavorazione DESC
     LIMIT 100"
);

$prodotti = fetchAll($pdo, "SELECT * FROM PRODOTTO ORDER BY nome");
$luoghi   = fetchAll($pdo, "SELECT * FROM LUOGO ORDER BY nome");

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Lavorazioni</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovaLavorazione">
        <i class="fas fa-plus me-1"></i> Nuova Lavorazione
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Prodotto</th>
                        <th>Tipo</th>
                        <th>Luogo</th>
                        <th class="text-end">Q.tà ingresso</th>
                        <th class="text-end">Q.tà ottenuta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lavorazioni)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nessuna lavorazione registrata</td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($lavorazioni as $l): ?>
                        <tr>
                            <td><?php echo formatDate($l['dataLavorazione']); ?></td>
                            <td><strong><?php echo htmlspecialchars($l['nomeProdotto']); ?></strong></td>
                            <td><?php echo htmlspecialchars($l['tipoLavorazione']); ?></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($l['nomeLuogo']); ?></small></td>
                            <td class="text-end"><?php echo formatWeight($l['quantitaIngresso'], 'kg'); ?></td>
                            <td class="text-end"><strong><?php echo formatWeight($l['quantitaOttenuta'], 'kg'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuova Lavorazione -->
<div class="modal fade" id="modalNuovaLavorazione" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuova Lavorazione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/lavorazione.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Prodotto <span class="text-danger">*</span></label>
                            <select name="idProdotto" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($prodotti as $p): ?>
                                    <option value="<?php echo $p['idProdotto']; ?>">
                                        <?php echo htmlspecialchars($p['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Luogo <span class="text-danger">*</span></label>
                            <select name="idLuogo" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($luoghi as $lu): ?>
                                    <option value="<?php echo $lu['idLuogo']; ?>">
                                        <?php echo htmlspecialchars($lu['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo lavorazione <span class="text-danger">*</span></label>
                            <input type="text" name="tipoLavorazione" class="form-control"
                                   placeholder="Es: Smielatura, Distillazione, Essiccazione..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" name="dataLavorazione" class="form-control"
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantità ingresso (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="quantitaIngresso" class="form-control"
                                   step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantità ottenuta (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="quantitaOttenuta" class="form-control"
                                   step="0.01" min="0" placeholder="0.00" required>
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
