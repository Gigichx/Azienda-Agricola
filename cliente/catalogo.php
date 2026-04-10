<?php
/**
 * CATALOGO.PHP - Cliente
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();
$pageTitle = 'Catalogo';

$categoriaFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

$categorie = fetchAll($pdo,
    "SELECT c.*, COUNT(p.idProdotto) as totaleProdotti
     FROM CATEGORIA c
     LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
     GROUP BY c.idCategoria ORDER BY c.nome"
);

$sqlProdotti = "SELECT p.*, cat.nome as nomeCategoria,
                COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale
                FROM PRODOTTO p
                INNER JOIN CATEGORIA cat ON p.idCategoria = cat.idCategoria
                LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
                " . ($categoriaFiltro ? "WHERE p.idCategoria = ?" : "") . "
                GROUP BY p.idProdotto
                ORDER BY p.nome";

$params = $categoriaFiltro ? [$categoriaFiltro] : [];
$prodotti = fetchAll($pdo, $sqlProdotti, $params);

$titoloPagina = 'Tutti i prodotti';
if ($categoriaFiltro) {
    $catData = fetchOne($pdo, "SELECT nome FROM CATEGORIA WHERE idCategoria = ?", [$categoriaFiltro]);
    if ($catData) $titoloPagina = $catData['nome'];
}

include '../includes/header_cliente.php';
?>

<div class="row g-4">
    <!-- Sidebar categorie -->
    <aside class="col-lg-2 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pb-0">
                <small class="fw-semibold text-muted text-uppercase" style="font-size:.68rem;letter-spacing:.06em">Categorie</small>
            </div>
            <div class="list-group list-group-flush rounded-bottom">
                <a href="/cliente/catalogo.php"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 small <?php echo !$categoriaFiltro ? 'active' : ''; ?>">
                    Tutti
                    <span class="badge <?php echo !$categoriaFiltro ? 'bg-white text-success' : 'bg-light text-muted'; ?>"><?php echo array_sum(array_column($categorie, 'totaleProdotti')); ?></span>
                </a>
                <?php foreach ($categorie as $cat): ?>
                <a href="/cliente/catalogo.php?categoria=<?php echo $cat['idCategoria']; ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 small <?php echo $categoriaFiltro == $cat['idCategoria'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['nome']); ?>
                    <span class="badge <?php echo $categoriaFiltro == $cat['idCategoria'] ? 'bg-white text-success' : 'bg-light text-muted'; ?>"><?php echo $cat['totaleProdotti']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- Prodotti -->
    <div class="col-lg-10 col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><?php echo htmlspecialchars($titoloPagina); ?></h4>
            <small class="text-muted"><?php echo count($prodotti); ?> prodott<?php echo count($prodotti) != 1 ? 'i' : 'o'; ?></small>
        </div>

        <?php if (empty($prodotti)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-seedling fa-3x mb-3"></i>
                <p>Nessun prodotto disponibile in questa categoria.</p>
                <a href="/cliente/catalogo.php" class="btn btn-outline-success btn-sm">Vedi tutti</a>
            </div>
        <?php else: ?>
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                <?php foreach ($prodotti as $p): ?>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100">
                        <!-- Immagine placeholder -->
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:130px;color:#9ca3af">
                            <i class="fas fa-seedling fa-2x"></i>
                        </div>
                        <div class="card-body d-flex flex-column p-3">
                            <div class="mb-1">
                                <small class="text-muted"><?php echo htmlspecialchars($p['nomeCategoria']); ?></small>
                            </div>
                            <h6 class="card-title mb-2 fw-semibold" style="font-size:.9rem"><?php echo htmlspecialchars($p['nome']); ?></h6>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <div>
                                    <span class="fw-bold text-success"><?php echo formatPrice($p['prezzoBase']); ?></span>
                                    <span class="text-muted" style="font-size:.75rem">/ <?php echo $p['unitaMisura']; ?></span>
                                </div>
                                <?php if ($p['giacenzaTotale'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.68rem">Disponibile</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.68rem">Esaurito</span>
                                <?php endif; ?>
                            </div>
                            <a href="/cliente/prodotto.php?id=<?php echo $p['idProdotto']; ?>"
                               class="btn btn-outline-success btn-sm mt-3">
                                <i class="fas fa-eye me-1"></i>Dettagli
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer_cliente.php'; ?>
