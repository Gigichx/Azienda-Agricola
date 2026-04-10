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

$confezionamenti = fetchAll($conn,
    "SELECT c.*, p.nome as nomeProdotto, p.unitaMisura,
            l.nome as nomeLuogo
     FROM CONFEZIONAMENTO c
     INNER JOIN PRODOTTO p ON c.idProdotto = p.idProdotto
     INNER JOIN LUOGO l ON c.idLuogo = l.idLuogo
     ORDER BY c.dataConfezionamento DESC
     LIMIT 100"
);

// Dati per il form di creazione
$prodotti  = fetchAll($conn, "SELECT * FROM PRODOTTO ORDER BY nome");
$luoghi    = fetchAll($conn, "SELECT * FROM LUOGO ORDER BY nome");
$riserve   = fetchAll($conn,
    "SELECT r.idRiserva, r.nome, r.quantitaAttuale, p.nome as nomeProdotto
     FROM RISERVA r
     INNER JOIN PRODOTTO p ON r.idProdotto = p.idProdotto
     WHERE r.quantitaAttuale > 0
     ORDER BY p.nome, r.nome"
);
$lavorazioni = fetchAll($conn,
    "SELECT l.idLavorazione, l.dataLavorazione, l.tipoLavorazione, p.nome as nomeProdotto
     FROM LAVORAZIONE l
     INNER JOIN PRODOTTO p ON l.idProdotto = p.idProdotto
     ORDER BY l.dataLavorazione DESC
     LIMIT 50"
);

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">
        <i class="fas fa-boxes-stacked me-2 text-success" style="font-size:1rem"></i>
        Confezionamenti
    </h1>
    <div class="admin-page-actions">
        <button class="btn btn-primary" onclick="openModal('modalNuovoConf')">
            <i class="fas fa-plus me-1"></i> Nuovo Confezionamento
        </button>
    </div>
</div>

<div class="conf-table-wrapper">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th class="ps-3">Data Conf.</th>
                <th>Prodotto</th>
                <th>Luogo</th>
                <th class="text-center">N° Conf.</th>
                <th class="text-end">Peso Netto</th>
                <th class="text-end">Prezzo</th>
                <th class="text-center">Giacenza</th>
                <th class="text-center pe-3">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($confezionamenti)): ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-5">
                    <i class="fas fa-boxes-stacked fa-2x mb-2 d-block opacity-25"></i>
                    Nessun confezionamento registrato
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($confezionamenti as $c): ?>
            <tr>
                <td class="ps-3 text-muted small"><?php echo formatDate($c['dataConfezionamento']); ?></td>
                <td>
                    <div class="fw-semibold" style="font-size:.8125rem"><?php echo htmlspecialchars($c['nomeProdotto']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($c['unitaMisura']); ?></small>
                </td>
                <td class="text-muted small"><?php echo htmlspecialchars($c['nomeLuogo']); ?></td>
                <td class="text-center fw-semibold"><?php echo $c['numeroConfezioni']; ?></td>
                <td class="text-end text-muted small"><?php echo formatWeight($c['pesoNetto'], $c['unitaMisura'] ?? 'kg'); ?></td>
                <td class="text-end fw-semibold text-success"><?php echo formatPrice($c['prezzo']); ?></td>
                <td class="text-center">
                    <?php if ($c['giacenzaAttuale'] > 0): ?>
                        <span class="badge badge-success"><?php echo $c['giacenzaAttuale']; ?></span>
                    <?php else: ?>
                        <span class="badge badge-error">0</span>
                    <?php endif; ?>
                </td>
                <td class="text-center pe-3">
                    <form method="POST" action="/api/confezionamento.php" style="display:inline"
                          onsubmit="return confirm('Eliminare questo confezionamento?\nATTENZIONE: operazione irreversibile.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="idConfezionamento" value="<?php echo $c['idConfezionamento']; ?>">
                        <button type="submit" class="btn-danger-sm" title="Elimina confezionamento">
                            <i class="fas fa-trash-alt" style="font-size:.7rem"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ===== MODAL NUOVO CONFEZIONAMENTO ===== -->
<div class="modal-overlay" id="modalNuovoConf">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-boxes-stacked me-2 text-success" style="font-size:.85rem"></i>
                Nuovo Confezionamento
            </h3>
            <button class="modal-close" onclick="closeModal('modalNuovoConf')">&times;</button>
        </div>
        <form method="POST" action="/api/confezionamento.php">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">Prodotto</label>
                        <select name="idProdotto" id="confIdProdotto" class="form-input" required
                                onchange="aggiornaUnitaConf(this)">
                            <option value="">-- Seleziona prodotto --</option>
                            <?php foreach ($prodotti as $p): ?>
                                <option value="<?php echo $p['idProdotto']; ?>"
                                        data-unita="<?php echo htmlspecialchars($p['unitaMisura']); ?>">
                                    <?php echo htmlspecialchars($p['nome']); ?>
                                    (<?php echo $p['unitaMisura']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Luogo</label>
                        <select name="idLuogo" class="form-input" required>
                            <option value="">-- Seleziona luogo --</option>
                            <?php foreach ($luoghi as $lu): ?>
                                <option value="<?php echo $lu['idLuogo']; ?>">
                                    <?php echo htmlspecialchars($lu['nome']); ?>
                                    (<?php echo $lu['tipo']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">Data Produzione</label>
                        <input type="date" name="dataProduzione" class="form-input"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Data Confezionamento</label>
                        <input type="date" name="dataConfezionamento" class="form-input"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">N° Confezioni</label>
                        <input type="number" name="numeroConfezioni" class="form-input"
                               min="1" step="1" placeholder="es. 50" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            Peso Netto / conf.
                            <span class="text-muted" id="confUnitaLabel" style="font-weight:400">(kg)</span>
                        </label>
                        <input type="number" name="pesoNetto" class="form-input"
                               step="0.001" min="0.001" placeholder="es. 0.50" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label required">Prezzo di Vendita (€/conf.)</label>
                    <div class="input-group">
                        <input type="number" name="prezzo" class="form-input"
                               step="0.01" min="0.01" placeholder="es. 3.50" required>
                        <span class="input-group-append">€</span>
                    </div>
                </div>

                <!-- Collegamento opzionale a Riserva -->
                <div class="form-group">
                    <label class="form-label">
                        Scala da Riserva
                        <span class="text-muted" style="font-weight:400">(opzionale)</span>
                    </label>
                    <select name="idRiserva" id="confRiservaSelect" class="form-input">
                        <option value="">-- Nessuna (confezionamento diretto) --</option>
                        <?php foreach ($riserve as $r): ?>
                            <option value="<?php echo $r['idRiserva']; ?>"
                                    data-prodotto="<?php echo $r['nomeProdotto']; ?>">
                                <?php echo htmlspecialchars($r['nomeProdotto']); ?> — <?php echo htmlspecialchars($r['nome']); ?>
                                (<?php echo formatWeight($r['quantitaAttuale'], 'kg'); ?> disp.)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Se selezionata, verrà scalata automaticamente la quantità dalla riserva.</small>
                </div>

                <!-- Collegamento opzionale a Lavorazione -->
                <div class="form-group">
                    <label class="form-label">
                        Lavorazione di Origine
                        <span class="text-muted" style="font-weight:400">(opzionale)</span>
                    </label>
                    <select name="idLavorazione" class="form-input">
                        <option value="">-- Nessuna --</option>
                        <?php foreach ($lavorazioni as $lav): ?>
                            <option value="<?php echo $lav['idLavorazione']; ?>">
                                #<?php echo $lav['idLavorazione']; ?> —
                                <?php echo htmlspecialchars($lav['nomeProdotto']); ?> —
                                <?php echo htmlspecialchars($lav['tipoLavorazione']); ?>
                                (<?php echo formatDate($lav['dataLavorazione']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('modalNuovoConf')">
                    Annulla
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Salva Confezionamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    if (id === 'modalNuovoConf') {
        document.querySelector('#modalNuovoConf form').reset();
        document.getElementById('confUnitaLabel').textContent = '(kg)';
    }
}

function aggiornaUnitaConf(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const unita = opt.dataset.unita || 'kg';
    document.getElementById('confUnitaLabel').textContent = '(' + unita + ')';
}
</script>

<?php include '../includes/footer.php'; ?>
