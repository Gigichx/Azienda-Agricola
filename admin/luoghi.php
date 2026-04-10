<?php
/**
 * LUOGHI - Admin
 * Azienda Agricola — CRUD completo
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Luoghi';

$luoghi   = fetchAll($conn, "SELECT * FROM LUOGO ORDER BY tipo, nome");
$dispense = fetchAll($conn,
    "SELECT d.*, l.nome as nomeLuogo
     FROM DISPENSA d
     INNER JOIN LUOGO l ON d.idLuogo = l.idLuogo
     ORDER BY l.nome, d.nome"
);

// Raggruppa dispense per luogo per visualizzazione
$dispensePerLuogo = [];
foreach ($dispense as $d) {
    $dispensePerLuogo[$d['idLuogo']][] = $d;
}

// Icone e label per tipo
$tipoInfo = [
    'campo'         => ['icon' => 'fa-seedling',  'label' => 'Campo',         'css' => 'campo'],
    'laboratorio'   => ['icon' => 'fa-flask',     'label' => 'Laboratorio',   'css' => 'laboratorio'],
    'punto vendita' => ['icon' => 'fa-store',     'label' => 'Punto Vendita', 'css' => 'punto-vendita'],
    'magazzino'     => ['icon' => 'fa-warehouse', 'label' => 'Magazzino',     'css' => 'magazzino'],
];

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">
        <i class="fas fa-map-marker-alt me-2 text-success" style="font-size:1rem"></i>
        Gestione Luoghi
    </h1>
    <div class="admin-page-actions">
        <button class="btn btn-primary" onclick="openModal('modalNuovoLuogo')">
            <i class="fas fa-plus me-1"></i> Nuovo Luogo
        </button>
    </div>
</div>

<!-- Griglia luoghi -->
<?php if (empty($luoghi)): ?>
<div class="empty-state">
    <i class="fas fa-map-marker-alt"></i>
    <h5 class="mt-2 fw-semibold text-muted">Nessun luogo configurato</h5>
    <p class="text-muted small">Aggiungi campi, laboratori, punti vendita o magazzini.</p>
</div>
<?php else: ?>
<div class="luoghi-grid">
    <?php foreach ($luoghi as $l):
        $tipo = $l['tipo'];
        $info = $tipoInfo[$tipo] ?? ['icon' => 'fa-map-marker-alt', 'label' => ucfirst($tipo), 'css' => 'magazzino'];
        $nDispense = count($dispensePerLuogo[$l['idLuogo']] ?? []);
    ?>
    <div class="luogo-card">
        <div class="luogo-card-header">
            <div>
                <h3 class="luogo-nome">
                    <i class="fas <?php echo $info['icon']; ?> me-2 text-success" style="font-size:.85rem"></i>
                    <?php echo htmlspecialchars($l['nome']); ?>
                </h3>
                <span class="luogo-tipo-badge <?php echo $info['css']; ?>">
                    <?php echo $info['label']; ?>
                </span>
            </div>
        </div>

        <div class="luogo-card-body">
            <?php if (!empty($l['indirizzo'])): ?>
            <p class="luogo-indirizzo">
                <i class="fas fa-location-dot text-muted" style="font-size:.75rem"></i>
                <?php echo htmlspecialchars($l['indirizzo']); ?>
            </p>
            <?php else: ?>
            <p class="luogo-indirizzo text-muted fst-italic">
                <i class="fas fa-location-dot" style="font-size:.75rem"></i>
                Nessun indirizzo
            </p>
            <?php endif; ?>

            <?php if ($nDispense > 0): ?>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-boxes-stacked me-1" style="font-size:.7rem"></i>
                    <?php echo $nDispense; ?> dispens<?php echo $nDispense != 1 ? 'e' : 'a'; ?> associate
                </small>
            </div>
            <?php endif; ?>
        </div>

        <div class="luogo-card-footer">
            <!-- Aggiungi dispensa -->
            <button class="action-btn" title="Aggiungi Dispensa"
                    onclick='openDispensaModal(<?php echo $l["idLuogo"]; ?>, <?php echo json_encode($l["nome"]); ?>)'
                    style="color: var(--ag-amber)">
                <i class="fas fa-boxes-stacked" style="font-size:.75rem"></i>
            </button>
            <!-- Modifica -->
            <button class="action-btn btn-edit" title="Modifica Luogo"
                    onclick='editLuogo(<?php echo json_encode($l); ?>)'>
                <i class="fas fa-pen" style="font-size:.75rem"></i>
            </button>
            <!-- Elimina -->
            <form method="POST" action="/api/luoghi.php" style="display:inline"
                  onsubmit="return confirm('Eliminare il luogo &quot;<?php echo htmlspecialchars(addslashes($l['nome'])); ?>&quot;?\nAttenzione: non sarà possibile se ha lavorazioni, confezionamenti, vendite o dispense associate.')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="idLuogo" value="<?php echo $l['idLuogo']; ?>">
                <button type="submit" class="btn-danger-sm" title="Elimina Luogo">
                    <i class="fas fa-trash-alt" style="font-size:.7rem"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Sezione Dispense -->
<?php if (!empty($dispense)): ?>
<div class="dispense-section">
    <h6 class="dispense-section-title">
        <i class="fas fa-boxes-stacked me-2" style="color: var(--ag-amber)"></i>
        Dispense (<?php echo count($dispense); ?>)
    </h6>
    <div class="table-container">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Nome Dispensa</th>
                    <th>Luogo</th>
                    <th>Ubicazione</th>
                    <th class="text-center pe-3">ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dispense as $d): ?>
                <tr>
                    <td class="ps-3 fw-semibold"><?php echo htmlspecialchars($d['nome']); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($d['nomeLuogo']); ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($d['ubicazione'] ?? '—'); ?></td>
                    <td class="text-center pe-3">
                        <span class="text-muted x-small">#<?php echo $d['idDispensa']; ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<!-- ===== MODAL NUOVO / MODIFICA LUOGO ===== -->
<div class="modal-overlay" id="modalNuovoLuogo">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalLuogoTitle">
                <i class="fas fa-map-marker-alt me-2 text-success" style="font-size:.85rem"></i>
                Nuovo Luogo
            </h3>
            <button class="modal-close" onclick="closeModal('modalNuovoLuogo')">&times;</button>
        </div>
        <form method="POST" action="/api/luoghi.php" id="formLuogo">
            <div class="modal-body">
                <input type="hidden" name="action" id="luogoAction" value="create">
                <input type="hidden" name="idLuogo" id="luogoId">

                <div class="form-group">
                    <label class="form-label required">Nome</label>
                    <input type="text" name="nome" id="luogoNome" class="form-input"
                           placeholder="es. Campo Nord, Laboratorio A..." required maxlength="150">
                </div>

                <div class="form-group">
                    <label class="form-label required">Tipo</label>
                    <select name="tipo" id="luogoTipo" class="form-input" required>
                        <option value="">-- Seleziona tipo --</option>
                        <option value="campo">🌾 Campo</option>
                        <option value="laboratorio">🔬 Laboratorio</option>
                        <option value="punto vendita">🏪 Punto Vendita</option>
                        <option value="magazzino">🏭 Magazzino</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Indirizzo</label>
                    <input type="text" name="indirizzo" id="luogoIndirizzo" class="form-input"
                           placeholder="Via, numero civico, città..." maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('modalNuovoLuogo')">
                    Annulla
                </button>
                <button type="submit" class="btn btn-primary" id="luogoSubmitBtn">
                    <i class="fas fa-save me-1"></i>Salva Luogo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL NUOVA DISPENSA ===== -->
<div class="modal-overlay" id="modalNuovaDispensa">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-boxes-stacked me-2" style="font-size:.85rem; color: var(--ag-amber)"></i>
                Nuova Dispensa
            </h3>
            <button class="modal-close" onclick="closeModal('modalNuovaDispensa')">&times;</button>
        </div>
        <form method="POST" action="/api/luoghi.php">
            <div class="modal-body">
                <input type="hidden" name="action" value="create_dispensa">
                <input type="hidden" name="idLuogo" id="dispensaIdLuogo">

                <div class="form-group">
                    <label class="form-label">Luogo selezionato</label>
                    <div class="form-input" id="dispensaLuogoLabel"
                         style="background:#f8fafc; cursor:default; color:#64748b; font-size:.8rem">
                        —
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label required">Nome Dispensa</label>
                    <input type="text" name="nomeDispensa" class="form-input"
                           placeholder="es. Dispensa Olio, Cella Frigorifera..." required maxlength="150">
                </div>

                <div class="form-group">
                    <label class="form-label">Ubicazione</label>
                    <input type="text" name="ubicazione" class="form-input"
                           placeholder="es. Piano -1, Zona B..." maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('modalNuovaDispensa')">
                    Annulla
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Salva Dispensa
                </button>
            </div>
        </form>
    </div>
</div>


<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    if (id === 'modalNuovoLuogo') resetLuogoModal();
}

function resetLuogoModal() {
    document.getElementById('luogoAction').value    = 'create';
    document.getElementById('luogoId').value        = '';
    document.getElementById('luogoNome').value      = '';
    document.getElementById('luogoTipo').value      = '';
    document.getElementById('luogoIndirizzo').value = '';
    document.getElementById('modalLuogoTitle').innerHTML =
        '<i class="fas fa-map-marker-alt me-2 text-success" style="font-size:.85rem"></i>Nuovo Luogo';
    document.getElementById('luogoSubmitBtn').innerHTML =
        '<i class="fas fa-save me-1"></i>Salva Luogo';
}

function editLuogo(luogo) {
    document.getElementById('luogoAction').value    = 'update';
    document.getElementById('luogoId').value        = luogo.idLuogo;
    document.getElementById('luogoNome').value      = luogo.nome;
    document.getElementById('luogoTipo').value      = luogo.tipo;
    document.getElementById('luogoIndirizzo').value = luogo.indirizzo || '';
    document.getElementById('modalLuogoTitle').innerHTML =
        '<i class="fas fa-pen me-2" style="font-size:.85rem;color:var(--ag-green)"></i>Modifica Luogo';
    document.getElementById('luogoSubmitBtn').innerHTML =
        '<i class="fas fa-save me-1"></i>Aggiorna Luogo';
    openModal('modalNuovoLuogo');
}

function openDispensaModal(idLuogo, nomeLuogo) {
    document.getElementById('dispensaIdLuogo').value   = idLuogo;
    document.getElementById('dispensaLuogoLabel').textContent = nomeLuogo;
    openModal('modalNuovaDispensa');
}
</script>

<?php include '../includes/footer.php'; ?>
