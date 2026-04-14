<?php
/**
 * GESTIONE PRODOTTI - Admin
 * Azienda Agricola — FIXED: classi modal e badge aggiornate
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestione Prodotti';

$sql = "SELECT p.*, c.nome as nomeCategoria,
        COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale
        FROM PRODOTTO p
        INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
        LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
        GROUP BY p.idProdotto
        ORDER BY p.nome";
$prodotti = fetchAll($conn, $sql);

$categorie = fetchAll($conn, "SELECT * FROM CATEGORIA ORDER BY nome");

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Gestione Prodotti</h1>
    <div class="admin-page-actions">
        <button class="btn btn-success btn-sm" onclick="openModal('modalNuovoProdotto')">
            <i class="fas fa-plus me-1"></i> Nuovo Prodotto
        </button>
    </div>
</div>

<div class="prodotti-container">
    <div class="prodotti-toolbar">
        <div class="prodotti-search">
            <input type="text" id="searchProdotti" class="form-control form-control-sm" placeholder="Cerca prodotto...">
        </div>
        <span class="text-muted small"><?php echo count($prodotti); ?> prodotti totali</span>
    </div>

    <div class="table-container">
        <table class="table table-hover mb-0">
            <thead>
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
                            <div class="prodotto-row">
                                <div class="prodotto-icon">🌾</div>
                                <div class="prodotto-info">
                                    <div class="prodotto-nome"><?php echo htmlspecialchars($p['nome']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['nomeCategoria']); ?></td>
                        <td><?php echo htmlspecialchars($p['unitaMisura']); ?></td>
                        <td class="text-end"><?php echo formatPrice($p['prezzoBase']); ?></td>
                        <td class="text-center">
                            <?php if ($p['giacenzaTotale'] > 0): ?>
                                <span class="ag-badge-success"><?php echo $p['giacenzaTotale']; ?></span>
                            <?php else: ?>
                                <span class="ag-badge-error">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="table-actions justify-content-center">
                                <button class="action-btn"
                                        onclick='editProdotto(<?php echo json_encode($p); ?>)'
                                        title="Modifica">
                                    <i class="fas fa-pen" style="font-size:.7rem"></i>
                                </button>
                                <form method="POST" action="/api/prodotti.php" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="idProdotto" value="<?php echo $p['idProdotto']; ?>">
                                    <button type="submit" class="btn-danger-sm"
                                            onclick="return confirm('Eliminare il prodotto \'<?php echo htmlspecialchars(addslashes($p['nome'])); ?>\'?')"
                                            title="Elimina">
                                        <i class="fas fa-trash-alt" style="font-size:.7rem"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL NUOVO/MODIFICA PRODOTTO — usa .ag-modal ===== -->
<div class="modal-overlay" id="modalNuovoProdotto">
    <div class="ag-modal">
        <div class="ag-modal-header">
            <h3 class="ag-modal-title" id="modalTitle">Nuovo Prodotto</h3>
            <button class="ag-modal-close" onclick="closeModal('modalNuovoProdotto')">&times;</button>
        </div>
        <form method="POST" action="/api/prodotti.php">
            <div class="ag-modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="idProdotto" id="formIdProdotto">

                <div class="ag-form-group">
                    <label class="ag-form-label required">Nome Prodotto</label>
                    <input type="text" name="nome" id="formNome" class="ag-form-input" required>
                </div>

                <div class="ag-form-row">
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Categoria</label>
                        <select name="idCategoria" id="formCategoria" class="form-select form-select-sm" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($categorie as $cat): ?>
                                <option value="<?php echo $cat['idCategoria']; ?>">
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ag-form-group">
                        <label class="ag-form-label required">Unità di Misura</label>
                        <select name="unitaMisura" id="formUnita" class="form-select form-select-sm" required>
                            <option value="">-- Seleziona --</option>
                            <option value="kg">Chilogrammo (kg)</option>
                            <option value="litro">Litro (L)</option>
                            <option value="pezzo">Pezzo</option>
                            <option value="grammo">Grammo (g)</option>
                        </select>
                    </div>
                </div>

                <div class="ag-form-group">
                    <label class="ag-form-label required">
                        Prezzo Base
                        <span id="prezzoPer" class="text-muted" style="font-weight:400; font-size:.78rem"></span>
                    </label>
                    <div class="ag-input-group">
                        <input type="number" name="prezzoBase" id="formPrezzo" class="ag-form-input"
                               step="0.01" min="0" required>
                        <span class="ag-input-group-append">€</span>
                    </div>
                </div>
            </div>
            <div class="ag-modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closeModal('modalNuovoProdotto')">Annulla</button>
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
    if (id === 'modalNuovoProdotto') {
        document.getElementById('formAction').value = 'create';
        document.getElementById('modalTitle').textContent = 'Nuovo Prodotto';
        document.querySelector('#modalNuovoProdotto form').reset();
        document.getElementById('prezzoPer').textContent = '';
    }
}

const unitaLabels = {
    'kg':     '— prezzo per kg',
    'litro':  '— prezzo per litro',
    'pezzo':  '— prezzo al pezzo',
    'grammo': '— prezzo per grammo'
};
document.getElementById('formUnita')?.addEventListener('change', function() {
    const el = document.getElementById('prezzoPer');
    if (el) el.textContent = unitaLabels[this.value] || '';
});

function editProdotto(prodotto) {
    document.getElementById('formAction').value     = 'update';
    document.getElementById('formIdProdotto').value = prodotto.idProdotto;
    document.getElementById('formNome').value       = prodotto.nome;
    document.getElementById('formCategoria').value  = prodotto.idCategoria;
    document.getElementById('formUnita').value      = prodotto.unitaMisura;
    document.getElementById('formPrezzo').value     = prodotto.prezzoBase;
    document.getElementById('modalTitle').textContent = 'Modifica Prodotto';
    const el = document.getElementById('prezzoPer');
    if (el) el.textContent = unitaLabels[prodotto.unitaMisura] || '';
    openModal('modalNuovoProdotto');
}

document.getElementById('searchProdotti').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('#tableProdotti tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
