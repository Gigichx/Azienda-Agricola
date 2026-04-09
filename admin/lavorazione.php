<?php
/**
 * LAVORAZIONE - Admin
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Lavorazioni';

$lavorazioni = fetchAll($pdo, "SELECT l.*, p.nome as nomeProdotto, lu.nome as nomeLuogo
                                FROM LAVORAZIONE l
                                INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
                                INNER JOIN LUOGO lu ON l.idLuogo = lu.idLuogo
                                ORDER BY l.dataLavorazione DESC
                                LIMIT 50");

$prodotti = fetchAll($pdo, "SELECT * FROM PRODOTTO ORDER BY nome");
$luoghi = fetchAll($pdo, "SELECT * FROM LUOGO ORDER BY nome");

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Lavorazioni</h1>
    <button class="btn btn-primary" onclick="openModal('modalLavorazione')">+ Nuova Lavorazione</button>
</div>

<div class="table-container">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Data</th>
                <th>Prodotto</th>
                <th>Tipo</th>
                <th>Luogo</th>
                <th class="text-right">Q.tà Ingresso</th>
                <th class="text-right">Q.tà Ottenuta</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lavorazioni as $l): ?>
                <tr>
                    <td><?php echo formatDate($l['dataLavorazione']); ?></td>
                    <td><strong><?php echo htmlspecialchars($l['nomeProdotto']); ?></strong></td>
                    <td><?php echo htmlspecialchars($l['tipoLavorazione']); ?></td>
                    <td><?php echo htmlspecialchars($l['nomeLuogo']); ?></td>
                    <td class="text-right"><?php echo formatWeight($l['quantitaIngresso'], 'kg'); ?></td>
                    <td class="text-right"><strong><?php echo formatWeight($l['quantitaOttenuta'], 'kg'); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalLavorazione">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Nuova Lavorazione</h3>
            <button class="modal-close" onclick="closeModal('modalLavorazione')">&times;</button>
        </div>
        <form method="POST" action="/api/lavorazione.php">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">Prodotto</label>
                        <select name="idProdotto" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($prodotti as $p): ?>
                                <option value="<?php echo $p['idProdotto']; ?>"><?php echo htmlspecialchars($p['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Luogo</label>
                        <select name="idLuogo" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($luoghi as $lu): ?>
                                <option value="<?php echo $lu['idLuogo']; ?>"><?php echo htmlspecialchars($lu['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">Tipo Lavorazione</label>
                        <input type="text" name="tipoLavorazione" class="form-input" placeholder="Es: Spremitura, Cottura..." required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Data</label>
                        <input type="date" name="dataLavorazione" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">Quantità Ingresso (kg)</label>
                        <input type="number" name="quantitaIngresso" class="form-input" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Quantità Ottenuta (kg)</label>
                        <input type="number" name="quantitaOttenuta" class="form-input" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('modalLavorazione')">Annulla</button>
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
</script>

<?php include '../includes/footer.php'; ?>
