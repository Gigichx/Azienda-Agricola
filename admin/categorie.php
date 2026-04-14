<?php
/**
 * GESTIONE CATEGORIE - Admin — FIXED
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestione Categorie';

$categorie = fetchAll($conn,
    "SELECT c.*, COUNT(p.idProdotto) as totaleProdotti
     FROM CATEGORIA c
     LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
     GROUP BY c.idCategoria
     ORDER BY c.nome"
);

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Gestione Categorie</h1>
    <button class="btn btn-success btn-sm" onclick="openModalCategoria()">
        <i class="fas fa-plus me-1"></i> Nuova Categoria
    </button>
</div>

<div class="table-container">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Descrizione</th>
                <th class="text-center">Prodotti</th>
                <th class="text-center">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categorie as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                    <td class="text-muted small"><?php echo htmlspecialchars($c['descrizione'] ?? '-'); ?></td>
                    <td class="text-center">
                        <span class="ag-badge-<?php echo $c['totaleProdotti'] > 0 ? 'success' : 'warning'; ?>">
                            <?php echo $c['totaleProdotti']; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="table-actions justify-content-center">
                            <button class="action-btn"
                                    onclick='editCategoria(<?php echo json_encode($c); ?>)'>
                                <i class="fas fa-pen" style="font-size:.7rem"></i>
                            </button>
                            <form method="POST" action="/api/categorie.php" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="idCategoria" value="<?php echo $c['idCategoria']; ?>">
                                <button type="submit" class="btn-danger-sm"
                                        onclick="return confirm('Eliminare la categoria \'<?php echo htmlspecialchars(addslashes($c['nome'])); ?>\'?')">
                                    <i class="fas fa-trash-alt" style="font-size:.7rem"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categorie)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Nessuna categoria</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Categoria — usa .ag-modal -->
<div class="modal-overlay" id="modalCategoria">
    <div class="ag-modal">
        <div class="ag-modal-header">
            <h3 class="ag-modal-title" id="modalCatTitle">Nuova Categoria</h3>
            <button class="ag-modal-close" onclick="closeModalCategoria()">&times;</button>
        </div>
        <form method="POST" action="/api/categorie.php">
            <div class="ag-modal-body">
                <input type="hidden" name="action" id="catAction" value="create">
                <input type="hidden" name="idCategoria" id="catId">

                <div class="ag-form-group">
                    <label class="ag-form-label required">Nome</label>
                    <input type="text" name="nome" id="catNome" class="ag-form-input" required>
                </div>

                <div class="ag-form-group">
                    <label class="ag-form-label">Descrizione</label>
                    <textarea name="descrizione" id="catDescrizione" class="form-control form-control-sm" rows="3"></textarea>
                </div>
            </div>
            <div class="ag-modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closeModalCategoria()">Annulla</button>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-save me-1"></i> Salva
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalCategoria() {
    document.getElementById('modalCategoria').classList.add('active');
}
function closeModalCategoria() {
    document.getElementById('modalCategoria').classList.remove('active');
    document.getElementById('catAction').value = 'create';
    document.getElementById('catId').value = '';
    document.getElementById('catNome').value = '';
    document.getElementById('catDescrizione').value = '';
    document.getElementById('modalCatTitle').textContent = 'Nuova Categoria';
}
function editCategoria(cat) {
    document.getElementById('catAction').value      = 'update';
    document.getElementById('catId').value          = cat.idCategoria;
    document.getElementById('catNome').value        = cat.nome;
    document.getElementById('catDescrizione').value = cat.descrizione || '';
    document.getElementById('modalCatTitle').textContent = 'Modifica Categoria';
    openModalCategoria();
}
</script>

<?php include '../includes/footer.php'; ?>
