<?php
/**
 * GESTIONE CATEGORIE - Admin
 * Azienda Agricola
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
    <button class="btn btn-primary" onclick="openModalCategoria()">+ Nuova Categoria</button>
</div>

<div class="table-container">
    <table class="table table-striped">
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
                    <td><?php echo htmlspecialchars($c['descrizione'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo $c['totaleProdotti']; ?></td>
                    <td class="text-center">
                        <div class="table-actions">
                            <button class="action-btn btn-edit"
                                    onclick='editCategoria(<?php echo json_encode($c); ?>)'>✏️</button>
                            <form method="POST" action="/api/categorie.php" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="idCategoria" value="<?php echo $c['idCategoria']; ?>">
                                <button type="submit" class="action-btn btn-delete"
                                        onclick="return confirm('Eliminare questa categoria?')">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalCategoria">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nuova Categoria</h3>
            <button class="modal-close" onclick="closeModalCategoria()">&times;</button>
        </div>
        <form method="POST" action="/api/categorie.php">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="idCategoria" id="formId">

                <div class="form-group">
                    <label class="form-label required">Nome</label>
                    <input type="text" name="nome" id="formNome" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrizione</label>
                    <textarea name="descrizione" id="formDescrizione" class="form-textarea"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModalCategoria()">Annulla</button>
                <button type="submit" class="btn btn-primary">Salva</button>
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
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'Nuova Categoria';
    document.querySelector('#modalCategoria form').reset();
}

function editCategoria(cat) {
    document.getElementById('formAction').value      = 'update';
    document.getElementById('formId').value          = cat.idCategoria;
    document.getElementById('formNome').value        = cat.nome;
    document.getElementById('formDescrizione').value = cat.descrizione || '';
    document.getElementById('modalTitle').textContent = 'Modifica Categoria';
    openModalCategoria();
}
</script>

<?php include '../includes/footer.php'; ?>