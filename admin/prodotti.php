<?php
/**
 * GESTIONE PRODOTTI - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestione Prodotti';

// Ottieni tutti i prodotti con giacenza
$sql = "SELECT p.*, c.nome as nomeCategoria,
        COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale
        FROM PRODOTTO p
        INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
        LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
        GROUP BY p.idProdotto
        ORDER BY p.nome";
$prodotti = fetchAll($pdo, $sql);

// Ottieni categorie per form
$categorie = fetchAll($pdo, "SELECT * FROM CATEGORIA ORDER BY nome");

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gestione Prodotti</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovoProdotto">
        <i class="fas fa-plus me-1"></i> Nuovo Prodotto
    </button>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-2">
        <input type="text" id="searchProdotti" class="form-control form-control-sm" style="max-width:260px" placeholder="Cerca prodotto...">
        <small class="text-muted"><?php echo count($prodotti); ?> prodotti totali</small>
    </div>
    <div class="p-0">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Prodotto</th>
                    <th>Categoria</th>
                    <th>Unità Misura</th>
                    <th class="text-end">Prezzo Base</th>
                    <th class="text-center">Giacenza</th>
                    <th class="text-center">Azioni</th>
                </tr>
            </thead>
            <tbody id="tableProdotti">
                <?php foreach ($prodotti as $p): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-seedling text-success"></i>
                                <span class="fw-semibold"><?php echo htmlspecialchars($p['nome']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['nomeCategoria']); ?></td>
                        <td><?php echo htmlspecialchars($p['unitaMisura']); ?></td>
                        <td class="text-end"><?php echo formatPrice($p['prezzoBase']); ?></td>
                        <td class="text-center">
                            <?php if ($p['giacenzaTotale'] > 0): ?>
                                <span class="badge bg-success"><?php echo $p['giacenzaTotale']; ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick='editProdotto(<?php echo json_encode($p); ?>)' title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="/api/prodotti.php" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="idProdotto" value="<?php echo $p['idProdotto']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Eliminare questo prodotto?')" title="Elimina">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<!-- Modal Nuovo/Modifica Prodotto -->
<div class="modal fade" id="modalNuovoProdotto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Nuovo Prodotto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="/api/prodotti.php">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="idProdotto" id="formIdProdotto">

                
                <div class="mb-3">
                    <label class="form-label">Nome Prodotto <span class="text-danger">*</span></label>
                    <input type="text" name="nome" id="formNome" class="form-control" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col">
                        <label class="form-label">Categoria <span class="text-danger">*</span></label>
                        <select name="idCategoria" id="formCategoria" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($categorie as $cat): ?>
                                <option value="<?php echo $cat['idCategoria']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label">Unità di Misura <span class="text-danger">*</span></label>
                        <select name="unitaMisura" id="formUnita" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <option value="kg">Chilogrammo (kg)</option>
                            <option value="litro">Litro (L)</option>
                            <option value="pezzo">Pezzo</option>
                            <option value="grammo">Grammo (g)</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prezzo Base <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="prezzoBase" id="formPrezzo" class="form-control"
                               step="0.01" min="0" required>
                        <span class="input-group-text">€</span>
                    </div>
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
function editProdotto(prodotto) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('formIdProdotto').value = prodotto.idProdotto;
    document.getElementById('formNome').value = prodotto.nome;
    document.getElementById('formCategoria').value = prodotto.idCategoria;
    document.getElementById('formUnita').value = prodotto.unitaMisura;
    document.getElementById('formPrezzo').value = prodotto.prezzoBase;
    document.getElementById('modalTitle').textContent = 'Modifica Prodotto';
    new bootstrap.Modal(document.getElementById('modalNuovoProdotto')).show();
}

// Reset modal on hide
document.getElementById('modalNuovoProdotto')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'Nuovo Prodotto';
    this.querySelector('form').reset();
});

// Search
document.getElementById('searchProdotti').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#tableProdotti tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
