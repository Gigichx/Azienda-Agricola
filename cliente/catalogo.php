<?php
/**
 * CATALOGO - Cliente / Ospite — FIXED
 * Fix: query giacenze più robusta, UI badge disponibilità
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Inizializza sessione guest PRIMA di requireCliente()
if (isset($_GET['guest']) && $_GET['guest'] == '1' && !isLoggedIn() && !isGuest()) {
    loginGuest();
}

requireCliente();

$pageTitle = 'Catalogo Prodotti';

$categoriaFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

// Categorie per il menu laterale
$sqlCategorie = "SELECT c.*, COUNT(p.idProdotto) as totaleProdotti
                 FROM CATEGORIA c
                 LEFT JOIN PRODOTTO p ON c.idCategoria = p.idCategoria
                 GROUP BY c.idCategoria
                 ORDER BY c.nome";
$categorie = fetchAll($conn, $sqlCategorie);

// Prodotti con giacenza — FIXED: usa COALESCE e raggruppa correttamente
$sqlProdotti = "SELECT p.*, c.nome as nomeCategoria,
                COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale,
                COUNT(conf.idConfezionamento) as numConfezioni
                FROM PRODOTTO p
                INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
                LEFT JOIN CONFEZIONAMENTO conf
                    ON p.idProdotto = conf.idProdotto
                    AND conf.giacenzaAttuale > 0
                " . ($categoriaFiltro ? "WHERE p.idCategoria = ?" : "") . "
                GROUP BY p.idProdotto, p.nome, p.unitaMisura, p.prezzoBase,
                         p.idCategoria, c.nome
                ORDER BY p.nome";

$params   = $categoriaFiltro ? [$categoriaFiltro] : [];
$prodotti = fetchAll($conn, $sqlProdotti, $params);

// Totale prodotti per "Tutti"
$totaleQuery = fetchOne($conn, "SELECT COUNT(*) as tot FROM PRODOTTO");
$totaleProdotti = $totaleQuery['tot'] ?? 0;

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
    <i class="fas fa-user-clock"></i>
    <div>
        <strong>Stai navigando come ospite.</strong>
        <a href="/login.php" class="fw-semibold text-dark">Accedi</a> o
        <a href="/registrazione.php" class="fw-semibold text-dark">registrati</a>
        per completare un ordine.
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
                        <span class="categoria-count"><?php echo $totaleProdotti; ?></span>
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
    <div>
        <div class="catalogo-header">
            <div>
                <h1 class="catalogo-title"><?php echo htmlspecialchars($categoriaNome); ?></h1>
                <p class="catalogo-subtitle">
                    <i class="fas fa-seedling me-1 text-success"></i>
                    <?php echo count($prodotti); ?>
                    prodott<?php echo count($prodotti) != 1 ? 'i' : 'o'; ?> nel catalogo
                </p>
            </div>
        </div>

        <?php if (empty($prodotti)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3>Nessun prodotto trovato</h3>
                <p>Non ci sono prodotti in questa categoria.</p>
                <a href="/cliente/catalogo.php" class="btn btn-success btn-sm">
                    <i class="fas fa-th me-1"></i>Vedi tutti i prodotti
                </a>
            </div>
        <?php else: ?>
            <div class="prodotti-grid">
                <?php foreach ($prodotti as $prodotto):
                    $disponibile = (int)$prodotto['giacenzaTotale'] > 0;
                ?>
                    <div class="product-card">
                        <div class="product-img">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="product-body">
                            <span class="product-cat-badge">
                                <?php echo htmlspecialchars($prodotto['nomeCategoria']); ?>
                            </span>
                            <h3 class="product-name">
                                <?php echo htmlspecialchars($prodotto['nome']); ?>
                            </h3>

                            <div class="product-price-row">
                                <span class="product-price">
                                    <?php echo formatPrice($prodotto['prezzoBase']); ?>
                                </span>
                                <span class="product-price-unit">
                                    / <?php echo htmlspecialchars($prodotto['unitaMisura']); ?>
                                </span>
                            </div>

                            <div class="product-stock-row">
                                <?php if ($disponibile): ?>
                                    <span class="badge-disp">
                                        <i class="fas fa-circle-check me-1"></i>Disponibile
                                    </span>
                                    <span class="stock-qty">
                                        <?php echo (int)$prodotto['giacenzaTotale']; ?> conf.
                                    </span>
                                <?php else: ?>
                                    <span class="badge-esaurito">
                                        <i class="fas fa-circle-xmark me-1"></i>Esaurito
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($disponibile): ?>
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
