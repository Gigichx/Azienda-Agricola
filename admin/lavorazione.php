<?php
/**
 * LAVORAZIONE - Admin — FIXED
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Lavorazioni';

$lavorazioni = fetchAll($conn,
    "SELECT l.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
     FROM LAVORAZIONE l
     INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
     INNER JOIN LUOGO lu ON l.idLuogo = lu.idLuogo
     ORDER BY l.dataLavorazione DESC
     LIMIT 50"
);

$prodotti = fetchAll($conn, "SELECT * FROM PRODOTTO ORDER BY nome");
$luoghi   = fetchAll($conn, "SELECT * FROM LUOGO ORDER BY nome");

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Lavorazioni</h1>
    <button class="btn btn-success btn-sm" onclick="openModal('modalLavorazione')">
        <i class="fas fa-plus me-1"></i> Nuova Lavorazione
    </button>
</div>

<div class="table-container">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Data</th>
                <th>Prodotto</th>
                <th>Tipo</th>
                <th>Luogo</th>
                <th class="text-end">Q.tà Ingresso</th>
                <th class="text-end">Q.tà Ottenuta</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lavorazioni)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nessuna lavorazione registrata</td></tr>
            <?php endif; ?>
            <?php foreach ($lavorazioni as $l): ?>
                <tr>
                    <td class="text-muted small"><?php echo formatDate($l['dataLavorazione']); ?></td>
                    <td><strong><?php echo htmlspecialchars($l['nomeProdotto']); ?></strong></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($l['tipoLavorazione']); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($l['nomeLuogo']); ?></td>
                    <td class="text-end"><?php echo formatWeight($l['quantitaIngresso'], 'kg'); ?></td>
                    <td class="text-end"><strong><?php echo formatWeight($l['quantitaOttenuta'], 'kg'); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal — usa .ag-modal -->
<div class="modal-overlay" id="modalLavorazione">
    <div class="ag-modal ag-modal-lg">
        <div class="ag-modal-header">
            <h3 class="ag-modal-title">Nuova Lavorazione</h3>
            <button class="ag-modal-close" onclick="closeModal('modalLavorazione')">&times;</button>
        </div>
        <form method="POST" action="/api/lavorazione.php">
            <div class="ag-modal-body">
                <input type="hidden" name="action" value="create">

                <div class="ag-form-row">
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Prodotto</label>
                        <select name="idProdotto" class="form-select form-select-sm" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($prodotti as $p): ?>
                                <option value="<?php echo $p['idProdotto']; ?>">
                                    <?php echo htmlspecialchars($p['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Luogo</label>
                        <select name="idLuogo" class="form-select form-select-sm" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($luoghi as $lu): ?>
                                <option value="<?php echo $lu['idLuogo']; ?>">
                                    <?php echo htmlspecialchars($lu['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="ag-form-row">
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Tipo Lavorazione</label>
                        <input type="text" name="tipoLavorazione" class="ag-form-input"
                               placeholder="Es: Spremitura, Cottura..." required>
                    </div>
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Data</label>
                        <input type="date" name="dataLavorazione" class="ag-form-input"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="ag-form-row">
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Quantità Ingresso (kg)</label>
                        <input type="number" name="quantitaIngresso" class="ag-form-input"
                               step="0.01" min="0" required>
                    </div>
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Quantità Ottenuta (kg)</label>
                        <input type="number" name="quantitaOttenuta" class="ag-form-input"
                               step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="ag-modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closeModal('modalLavorazione')">Annulla</button>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-save me-1"></i> Salva
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.querySelector('#' + id + ' form')?.reset();
}
</script>

<?php include '../includes/footer.php'; ?>
