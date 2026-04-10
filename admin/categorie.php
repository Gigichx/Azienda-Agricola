<?php
/**
 * GESTIONE CATEGORIE - Admin
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Categorie';

$categorie = fetchAll($pdo,
    "SELECT c.*, COUNT(p.idProdotto) as totaleProdotti
     FROM CATEGORIA c
     LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
     GROUP BY c.idCategoria
     ORDER BY c.nome"
);

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Categorie</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
        <i class="fas fa-plus me-1"></i> Nuova Categoria
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th class="text-center">Prodotti</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorie)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nessuna categoria registrata</td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($categorie as $c): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                            <td class="text-muted"><?php echo htmlspecialchars($c['descrizione'] ?? '—'); ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?php echo $c['totaleProdotti']; ?></span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button class="btn btn-sm btn-outline-secondary"
                                            onclick='editCategoria(<?php echo json_encode($c); ?>)'
                                            title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="/api/categorie.php" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="idCategoria" value="<?php echo $c['idCategoria']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Eliminare questa categoria?')"
                                                title="Elimina">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crea / Modifica Categoria -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoriaModalTitle">Nuova Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/categorie.php">
                <div class="modal-body">
                    <input type="hidden" name="action" id="catAction" value="create">
                    <input type="hidden" name="idCategoria" id="catId">

                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" id="catNome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea name="descrizione" id="catDescrizione" class="form-control" rows="3"></textarea>
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
function editCategoria(cat) {
    document.getElementById('categoriaModalTitle').textContent = 'Modifica Categoria';
    document.getElementById('catAction').value      = 'update';
    document.getElementById('catId').value          = cat.idCategoria;
    document.getElementById('catNome').value        = cat.nome;
    document.getElementById('catDescrizione').value = cat.descrizione || '';
    new bootstrap.Modal(document.getElementById('modalCategoria')).show();
}

document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function () {
    document.getElementById('categoriaModalTitle').textContent = 'Nuova Categoria';
    document.getElementById('catAction').value = 'create';
    document.getElementById('catId').value     = '';
    this.querySelector('form').reset();
});
</script>

<?php include '../includes/footer.php'; ?>
