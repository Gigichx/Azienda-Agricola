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

<div class="admin-page-header">
    <h1 class="admin-page-title">Gestione Prodotti</h1>
    <div class="admin-page-actions">
        <button class="btn btn-primary" onclick="openModal('modalNuovoProdotto')">
            + Nuovo Prodotto
        </button>
    </div>
</div>

<!-- Lista Prodotti -->
<div class="prodotti-container">
    <div class="prodotti-toolbar">
        <div class="prodotti-search">
            <input type="text" id="searchProdotti" class="form-input" placeholder="Cerca prodotto...">
        </div>
        <span class="text-muted"><?php echo count($prodotti); ?> prodotti totali</span>
    </div>
    
    <div class="table-container">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th>Categoria</th>
                    <th>Unità Misura</th>
                    <th class="text-right">Prezzo Base</th>
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
                        <td class="text-right"><?php echo formatPrice($p['prezzoBase']); ?></td>
                        <td class="text-center">
                            <?php if ($p['giacenzaTotale'] > 0): ?>
                                <span class="badge badge-success"><?php echo $p['giacenzaTotale']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-error">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="table-actions">
                                <button class="action-btn btn-edit" onclick='editProdotto(<?php echo json_encode($p); ?>)' title="Modifica">
                                    ✏️
                                </button>
                                <form method="POST" action="/api/prodotti.php" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="idProdotto" value="<?php echo $p['idProdotto']; ?>">
                                    <button type="submit" class="action-btn btn-delete" 
                                            onclick="return confirm('Eliminare questo prodotto?')" title="Elimina">
                                        🗑️
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

<!-- Modal Nuovo/Modifica Prodotto -->
<div class="modal-overlay" id="modalNuovoProdotto">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nuovo Prodotto</h3>
            <button class="modal-close" onclick="closeModal('modalNuovoProdotto')">&times;</button>
        </div>
        <form method="POST" action="/api/prodotti.php">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="idProdotto" id="formIdProdotto">
                
                <div class="form-group">
                    <label class="form-label required">Nome Prodotto</label>
                    <input type="text" name="nome" id="formNome" class="form-input" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">Categoria</label>
                        <select name="idCategoria" id="formCategoria" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($categorie as $cat): ?>
                                <option value="<?php echo $cat['idCategoria']; ?>">
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Unità di Misura</label>
                        <select name="unitaMisura" id="formUnita" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <option value="kg">Chilogrammo (kg)</option>
                            <option value="litro">Litro (L)</option>
                            <option value="pezzo">Pezzo</option>
                            <option value="grammo">Grammo (g)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Prezzo Base</label>
                    <div class="input-group">
                        <input type="number" name="prezzoBase" id="formPrezzo" class="form-input" 
                               step="0.01" min="0" required>
                        <span class="input-group-append">€</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('modalNuovoProdotto')">Annulla</button>
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    if (id === 'modalNuovoProdotto') {
        document.getElementById('formAction').value = 'create';
        document.getElementById('modalTitle').textContent = 'Nuovo Prodotto';
        document.querySelector('#modalNuovoProdotto form').reset();
    }
}

function editProdotto(prodotto) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('formIdProdotto').value = prodotto.idProdotto;
    document.getElementById('formNome').value = prodotto.nome;
    document.getElementById('formCategoria').value = prodotto.idCategoria;
    document.getElementById('formUnita').value = prodotto.unitaMisura;
    document.getElementById('formPrezzo').value = prodotto.prezzoBase;
    document.getElementById('modalTitle').textContent = 'Modifica Prodotto';
    openModal('modalNuovoProdotto');
}

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
