<?php
/**
 * CATALOGO - Cliente
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

// Nome categoria selezionata
$categoriaNome = 'Tutti i Prodotti';
if ($categoriaFiltro) {
    $catData = fetchOne($conn, "SELECT nome FROM CATEGORIA WHERE idCategoria = ?", [$categoriaFiltro]);
    if ($catData) $categoriaNome = $catData['nome'];
}

include '../includes/header_cliente.php';
?>

<div class="catalogo-container">
    <!-- Sidebar Categorie -->
    <aside class="catalogo-sidebar">
        <div class="categoria-filter">
            <h3 class="categoria-filter-title">Categorie</h3>
            <ul class="categoria-list">
                <li class="categoria-item">
                    <a href="/cliente/catalogo.php"
                       class="categoria-link <?php echo !$categoriaFiltro ? 'active' : ''; ?>">
                        <span>Tutti i Prodotti</span>
                        <span class="categoria-count"><?php echo count($prodotti); ?></span>
                    </a>
                </li>
                <?php foreach ($categorie as $cat): ?>
                    <li class="categoria-item">
                        <a href="/cliente/catalogo.php?categoria=<?php echo $cat['idCategoria']; ?>"
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
    <main class="catalogo-main">
        <div class="catalogo-header">
            <div class="catalogo-info">
                <h1><?php echo htmlspecialchars($categoriaNome); ?></h1>
                <p class="catalogo-count">
                    <?php echo count($prodotti); ?>
                    prodott<?php echo count($prodotti) != 1 ? 'i' : 'o'; ?>
                    disponibil<?php echo count($prodotti) != 1 ? 'i' : 'e'; ?>
                </p>
            </div>
        </div>

        <?php if (empty($prodotti)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🌾</div>
                <h3 class="empty-state-title">Nessun prodotto trovato</h3>
                <p class="empty-state-message">Non ci sono prodotti disponibili in questa categoria.</p>
                <a href="/cliente/catalogo.php" class="btn btn-primary">Vedi tutti i prodotti</a>
            </div>
        <?php else: ?>
            <div class="catalogo-grid">
                <?php foreach ($prodotti as $prodotto): ?>
                    <div class="product-card">
                        <div class="product-image">🌾</div>
                        <div class="product-body">
                            <div class="product-category"><?php echo htmlspecialchars($prodotto['nomeCategoria']); ?></div>
                            <h3 class="product-name"><?php echo htmlspecialchars($prodotto['nome']); ?></h3>

                            <div class="product-meta">
                                <div class="product-price">
                                    <?php echo formatPrice($prodotto['prezzoBase']); ?>
                                    <span class="product-price-unit">/ <?php echo htmlspecialchars($prodotto['unitaMisura']); ?></span>
                                </div>

                                <div class="product-availability">
                                    <?php if ($prodotto['giacenzaTotale'] > 0): ?>
                                        <span class="status-badge disponibile">Disponibile</span>
                                        <span class="product-stock"><?php echo $prodotto['giacenzaTotale']; ?> pezzi</span>
                                    <?php else: ?>
                                        <span class="status-badge esaurito">Esaurito</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <a href="/cliente/prodotto.php?id=<?php echo $prodotto['idProdotto']; ?>"
                               class="btn btn-primary btn-block" style="margin-top: 1rem;">
                                Dettagli
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>