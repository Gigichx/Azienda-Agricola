<?php
/**
 * PRODOTTO.PHP - Dettaglio Prodotto
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

// Ottieni ID prodotto
$idProdotto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$idProdotto) {
    redirectWithMessage('/cliente/catalogo.php', 'Prodotto non trovato', 'error');
}

// Query prodotto con categoria e giacenza
$sql = "SELECT p.*, c.nome as nomeCategoria, c.idCategoria,
        COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale
        FROM PRODOTTO p
        INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
        LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
        WHERE p.idProdotto = ?
        GROUP BY p.idProdotto";

$prodotto = fetchOne($pdo, $sql, [$idProdotto]);

if (!$prodotto) {
    redirectWithMessage('/cliente/catalogo.php', 'Prodotto non trovato', 'error');
}

$pageTitle = $prodotto['nome'];

// Ottieni confezioni disponibili
$sqlConf = "SELECT * FROM CONFEZIONAMENTO 
            WHERE idProdotto = ? AND giacenzaAttuale > 0
            ORDER BY pesoNetto";
$confezioni = fetchAll($pdo, $sqlConf, [$idProdotto]);

include '../includes/header_cliente.php';
?>

<div class="prodotto-dettaglio">
    <!-- Immagine -->
    <div class="prodotto-immagine-container">
        🌾
    </div>
    
    <!-- Info -->
    <div class="prodotto-info">
        <!-- Breadcrumb -->
        <div class="prodotto-breadcrumb">
            <a href="/cliente/catalogo.php">Catalogo</a> / 
            <a href="/cliente/catalogo.php?categoria=<?php echo $prodotto['idCategoria']; ?>">
                <?php echo htmlspecialchars($prodotto['nomeCategoria']); ?>
            </a>
        </div>
        
        <!-- Nome -->
        <h1 class="prodotto-nome"><?php echo htmlspecialchars($prodotto['nome']); ?></h1>
        
        <!-- Categoria Badge -->
        <span class="prodotto-categoria-badge"><?php echo htmlspecialchars($prodotto['nomeCategoria']); ?></span>
        
        <!-- Prezzo -->
        <div class="prodotto-prezzo-container">
            <span class="prodotto-prezzo"><?php echo formatPrice($prodotto['prezzoBase']); ?></span>
            <span class="prodotto-unita">/ <?php echo htmlspecialchars($prodotto['unitaMisura']); ?></span>
        </div>
        
        <!-- Disponibilità -->
        <?php if ($prodotto['giacenzaTotale'] > 0): ?>
            <div class="prodotto-disponibilita">
                <span>✓</span>
                <span>Disponibile - <?php echo $prodotto['giacenzaTotale']; ?> pezzi in stock</span>
            </div>
        <?php else: ?>
            <div class="prodotto-disponibilita esaurito">
                <span>✕</span>
                <span>Prodotto esaurito</span>
            </div>
        <?php endif; ?>
        
        <!-- Form Aggiunta Carrello -->
        <?php if ($prodotto['giacenzaTotale'] > 0 && !empty($confezioni)): ?>
            <form action="/api/carrello.php" method="POST" class="prodotto-quantita-selector">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="idProdotto" value="<?php echo $prodotto['idProdotto']; ?>">
                
                <!-- Selezione Confezione -->
                <div class="form-group">
                    <label class="quantita-label">Seleziona formato:</label>
                    <select name="idConfezionamento" class="form-select" required>
                        <option value="">-- Scegli formato --</option>
                        <?php foreach ($confezioni as $conf): ?>
                            <option value="<?php echo $conf['idConfezionamento']; ?>" 
                                    data-prezzo="<?php echo $conf['prezzo']; ?>"
                                    data-giacenza="<?php echo $conf['giacenzaAttuale']; ?>">
                                <?php echo formatWeight($conf['pesoNetto'], $prodotto['unitaMisura']); ?> - 
                                <?php echo formatPrice($conf['prezzo']); ?> 
                                (<?php echo $conf['giacenzaAttuale']; ?> disponibili)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Quantità -->
                <div class="form-group">
                    <label class="quantita-label">Quantità:</label>
                    <div class="number-input">
                        <button type="button" class="number-btn" onclick="decrementQuantita()">−</button>
                        <input type="number" name="quantita" id="quantita" class="form-input" 
                               value="1" min="1" max="<?php echo $prodotto['giacenzaTotale']; ?>" required>
                        <button type="button" class="number-btn" onclick="incrementQuantita()">+</button>
                    </div>
                </div>
                
                <!-- Azioni -->
                <div class="prodotto-actions">
                    <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                        Aggiungi al Carrello
                    </button>
                    <a href="/cliente/catalogo.php" class="btn btn-outline btn-lg">
                        Torna al Catalogo
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="prodotto-actions">
                <a href="/cliente/catalogo.php" class="btn btn-primary btn-lg btn-block">
                    Torna al Catalogo
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function incrementQuantita() {
    const input = document.getElementById('quantita');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decrementQuantita() {
    const input = document.getElementById('quantita');
    const min = parseInt(input.min);
    const current = parseInt(input.value);
    if (current > min) {
        input.value = current - 1;
    }
}

// Aggiorna max quantità quando cambia confezione
document.querySelector('select[name="idConfezionamento"]')?.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const giacenza = option.dataset.giacenza;
    const quantitaInput = document.getElementById('quantita');
    quantitaInput.max = giacenza;
    if (parseInt(quantitaInput.value) > parseInt(giacenza)) {
        quantitaInput.value = giacenza;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
