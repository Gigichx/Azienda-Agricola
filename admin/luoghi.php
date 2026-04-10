<?php
/**
 * LUOGHI - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Luoghi';

$luoghi = fetchAll($pdo,
    "SELECT l.*,
            (SELECT COUNT(*) FROM LAVORAZIONE WHERE idLuogo = l.idLuogo) as nLavorazioni,
            (SELECT COUNT(*) FROM VENDITA WHERE idLuogo = l.idLuogo) as nVendite,
            (SELECT COUNT(*) FROM DISPENSA WHERE idLuogo = l.idLuogo) as nDispense
     FROM LUOGO l
     ORDER BY l.tipo, l.nome"
);

$dispense = fetchAll($pdo,
    "SELECT d.*, l.nome as nomeLuogo FROM DISPENSA d
     INNER JOIN LUOGO l ON d.idLuogo = l.idLuogo
     ORDER BY l.nome, d.nome"
);

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gestione Luoghi</h1>
    <div>
        <button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#modalNuovaDispensa">
            <i class="fas fa-plus me-1"></i> Nuova Dispensa
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovoLuogo">
            <i class="fas fa-plus me-1"></i> Nuovo Luogo
        </button>
    </div>
</div>

<!-- Luoghi -->
<h5 class="mb-3 text-muted">Sedi aziendali</h5>
<div class="row g-3 mb-5">
    <?php if (empty($luoghi)): ?>
        <div class="col-12">
            <div class="alert alert-info">Nessun luogo registrato.</div>
        </div>
    <?php else: ?>
    <?php
    $tipoIcon = [
        'campo'        => 'fa-seedling',
        'laboratorio'  => 'fa-flask',
        'punto vendita'=> 'fa-store',
        'magazzino'    => 'fa-warehouse',
    ];
    $tipoBadge = [
        'campo'        => 'success',
        'laboratorio'  => 'primary',
        'punto vendita'=> 'warning',
        'magazzino'    => 'secondary',
    ];
    foreach ($luoghi as $l):
        $icon  = $tipoIcon[$l['tipo']] ?? 'fa-map-marker-alt';
        $badge = $tipoBadge[$l['tipo']] ?? 'secondary';
    ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <i class="fas <?php echo $icon; ?> text-<?php echo $badge; ?> me-2"></i>
                            <strong><?php echo htmlspecialchars($l['nome']); ?></strong>
                        </div>
                        <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($l['tipo']); ?></span>
                    </div>
                    <?php if ($l['indirizzo']): ?>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($l['indirizzo']); ?>
                        </p>
                    <?php endif; ?>
                    <div class="d-flex gap-2 text-muted small mb-3">
                        <span><i class="fas fa-cogs me-1"></i><?php echo $l['nLavorazioni']; ?> lav.</span>
                        <span><i class="fas fa-shopping-cart me-1"></i><?php echo $l['nVendite']; ?> vendite</span>
                        <span><i class="fas fa-box me-1"></i><?php echo $l['nDispense']; ?> dispense</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary flex-fill"
                                onclick='editLuogo(<?php echo json_encode($l); ?>)'>
                            <i class="fas fa-edit me-1"></i>Modifica
                        </button>
                        <form method="POST" action="/api/luoghi.php" class="d-inline flex-fill">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="idLuogo" value="<?php echo $l['idLuogo']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                                    onclick="return confirm('Eliminare questo luogo?')">
                                <i class="fas fa-trash me-1"></i>Elimina
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Dispense -->
<h5 class="mb-3 text-muted">Dispense / Magazzini interni</h5>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome dispensa</th>
                        <th>Luogo</th>
                        <th>Ubicazione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dispense)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Nessuna dispensa registrata</td></tr>
                    <?php else: ?>
                    <?php foreach ($dispense as $d): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['nomeLuogo']); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($d['ubicazione'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuovo Luogo -->
<div class="modal fade" id="modalNuovoLuogo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="luogoModalTitle">Nuovo Luogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/luoghi.php">
                <div class="modal-body">
                    <input type="hidden" name="action" id="luogoAction" value="create">
                    <input type="hidden" name="idLuogo" id="luogoId">

                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" id="luogoNome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" id="luogoTipo" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <option value="campo">Campo</option>
                            <option value="laboratorio">Laboratorio</option>
                            <option value="punto vendita">Punto vendita</option>
                            <option value="magazzino">Magazzino</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Indirizzo</label>
                        <input type="text" name="indirizzo" id="luogoIndirizzo" class="form-control" placeholder="Via...">
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

<!-- Modal Nuova Dispensa -->
<div class="modal fade" id="modalNuovaDispensa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuova Dispensa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/luoghi.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_dispensa">
                    <div class="mb-3">
                        <label class="form-label">Nome dispensa <span class="text-danger">*</span></label>
                        <input type="text" name="nomeDispensa" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Luogo <span class="text-danger">*</span></label>
                        <select name="idLuogo" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($luoghi as $l): ?>
                                <option value="<?php echo $l['idLuogo']; ?>"><?php echo htmlspecialchars($l['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ubicazione</label>
                        <input type="text" name="ubicazione" class="form-control" placeholder="Es: Piano terra - settore nord">
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

<script>
function editLuogo(l) {
    document.getElementById('luogoModalTitle').textContent = 'Modifica Luogo';
    document.getElementById('luogoAction').value    = 'update';
    document.getElementById('luogoId').value        = l.idLuogo;
    document.getElementById('luogoNome').value      = l.nome;
    document.getElementById('luogoTipo').value      = l.tipo;
    document.getElementById('luogoIndirizzo').value = l.indirizzo || '';
    new bootstrap.Modal(document.getElementById('modalNuovoLuogo')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
