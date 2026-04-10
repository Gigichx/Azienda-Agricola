<?php
/**
 * CATALOGO - Cliente / Ospite
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$pageTitle = 'Catalogo Prodotti';

// Filtro categoria
$categoriaFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

// Tutte le categorie per il menu
$sqlCategorie = "SELECT c.*, COUNT(p.idProdotto) as totaleProdotti
                 FROM CATEGORIA c
                 LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
                 GROUP BY c.idCategoria
                 ORDER BY c.nome";
$categorie = fetchAll($conn, $sqlCategorie);

// Prodotti con giacenza
$sqlProdotti = "SELECT p.*, c.nome as nomeCategoria,
                COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale
                FROM PRODOTTO p
                INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
                LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
                " . ($categoriaFiltro ? "WHERE p.idCategoria = ?" : "") . "
                GROUP BY p.idProdotto
                ORDER BY p.nome";

$params   = $categoriaFiltro ? [$categoriaFiltro] : [];
$prodotti = fetchAll($conn, $sqlProdotti, $params);

// Totale per "Tutti"
$totaleProdotti = fetchAll($conn, "SELECT p.idProdotto FROM PRODOTTO p INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria");

// Nome categoria selezionata
$categoriaNome = 'Tutti i Prodotti';
if ($categoriaFiltro) {
    $catData = fetchOne($conn, "SELECT nome FROM CATEGORIA WHERE idCategoria = ?", [$categoriaFiltro]);
    if ($catData) $categoriaNome = $catData['nome'];
}

include '../includes/header_cliente.php';
?>

<?php if (isGuest()): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="fas fa-user-clock fa-lg"></i>
    <div>
        <strong>Stai navigando come ospite.</strong>
        Puoi sfogliare il catalogo e aggiungere prodotti al carrello, ma dovrai
        <a href="/login.php" class="fw-semibold text-dark">accedere</a> o
        <a href="/registrazione.php" class="fw-semibold text-dark">registrarti</a>
        per completare l'ordine.
    </div>
</div>
<?php endif; ?>

<div class="catalogo-layout">

    <!-- Sidebar Categorie -->
    <aside class="catalogo-sidebar">
        <div class="categoria-panel">
            <div class="categoria-panel-title">
                <i class="fas fa-filter me-1"></i>Categorie
            </div>
            <ul class="categoria-list">
                <li class="categoria-item">
                    <a href="/cliente/catalogo.php"
                       class="categoria-link <?php echo !$categoriaFiltro ? 'active' : ''; ?>">
                        <span><i class="fas fa-th me-1"></i>Tutti</span>
                        <span class="categoria-count"><?php echo count($totaleProdotti); ?></span>
                    </a>
                </li>
                <?php foreach ($categorie as $cat): ?>
                    <li class="categoria-item">
                        <a href="/cliente/catalogo.php?categoria=<?php echo $cat['idCategoria']; ?><?php echo isGuest() ? '' : ''; ?>"
                           class="categoria-link <?php echo $categoriaFiltro == $cat['idCategoria'] ? 'active' : ''; ?>">
                            <span><?php echo htmlspecialchars($cat['nome']); ?></span>
                            <span class="categoria-count"><?php echo $cat['totaleProdotti']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <div>
        <!-- Header -->
        <div class="catalogo-header">
            <div>
                <h1 class="catalogo-title"><?php echo htmlspecialchars($categoriaNome); ?></h1>
                <p class="catalogo-subtitle">
                    <i class="fas fa-seedling me-1 text-success"></i>
                    <?php echo count($prodotti); ?>
                    prodott<?php echo count($prodotti) != 1 ? 'i' : 'o'; ?>
                    disponibil<?php echo count($prodotti) != 1 ? 'i' : 'e'; ?>
                </p>
            </div>
        </div>

        <?php if (empty($prodotti)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3>Nessun prodotto trovato</h3>
                <p>Non ci sono prodotti disponibili in questa categoria.</p>
                <a href="/cliente/catalogo.php" class="btn btn-success btn-sm">
                    <i class="fas fa-th me-1"></i>Vedi tutti i prodotti
                </a>
            </div>
        <?php else: ?>
            <div class="prodotti-grid">
                <?php foreach ($prodotti as $prodotto): ?>
                    <div class="product-card">
                        <div class="product-img">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="product-body">
                            <span class="product-cat-badge"><?php echo htmlspecialchars($prodotto['nomeCategoria']); ?></span>
                            <h3 class="product-name"><?php echo htmlspecialchars($prodotto['nome']); ?></h3>

                            <div class="product-price-row">
                                <span class="product-price"><?php echo formatPrice($prodotto['prezzoBase']); ?></span>
                                <span class="product-price-unit">/ <?php echo htmlspecialchars($prodotto['unitaMisura']); ?></span>
                            </div>

                            <div class="product-stock-row">
                                <?php if ($prodotto['giacenzaTotale'] > 0): ?>
                                    <span class="badge-disp"><i class="fas fa-circle-check me-1"></i>Disponibile</span>
                                    <span class="stock-qty"><?php echo $prodotto['giacenzaTotale']; ?> conf.</span>
                                <?php else: ?>
                                    <span class="badge-esaurito"><i class="fas fa-circle-xmark me-1"></i>Esaurito</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($prodotto['giacenzaTotale'] > 0): ?>
                                <a href="/cliente/prodotto.php?id=<?php echo $prodotto['idProdotto']; ?>"
                                   class="btn-detail">
                                    <i class="fas fa-eye me-1"></i>Dettagli
                                </a>
                            <?php else: ?>
                                <span class="btn-detail disabled">
                                    <i class="fas fa-ban me-1"></i>Non disponibile
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer_cliente.php'; ?>
