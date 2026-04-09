<?php
/**
 * CARRELLO.PHP - Cliente
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$pageTitle = 'Carrello';

// Inizializza carrello se non esiste
if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

$carrello = $_SESSION['carrello'];
$totale = 0;

// Ottieni dettagli prodotti nel carrello
$items = [];
if (!empty($carrello)) {
    foreach ($carrello as $key => $item) {
        $sql = "SELECT p.nome, p.unitaMisura, c.nome as categoria,
                conf.pesoNetto, conf.prezzo, conf.giacenzaAttuale, conf.idProdotto
                FROM CONFEZIONAMENTO conf
                INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
                INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
                WHERE conf.idConfezionamento = ?";
        
        $dettagli = fetchOne($pdo, $sql, [$item['idConfezionamento']]);
        
        if ($dettagli) {
            $subtotale = $dettagli['prezzo'] * $item['quantita'];
            $totale += $subtotale;
            
            $items[] = [
                'key' => $key,
                'idConfezionamento' => $item['idConfezionamento'],
                'idProdotto' => $dettagli['idProdotto'],
                'nome' => $dettagli['nome'],
                'categoria' => $dettagli['categoria'],
                'pesoNetto' => $dettagli['pesoNetto'],
                'unitaMisura' => $dettagli['unitaMisura'],
                'prezzo' => $dettagli['prezzo'],
                'quantita' => $item['quantita'],
                'giacenzaAttuale' => $dettagli['giacenzaAttuale'],
                'subtotale' => $subtotale
            ];
        }
    }
}

include '../includes/header_cliente.php';
?>

<?php if (empty($items)): ?>
    <!-- Carrello Vuoto -->
    <div class="carrello-vuoto">
        <div class="carrello-vuoto-icon">🛒</div>
        <h2>Il tuo carrello è vuoto</h2>
        <p class="carrello-vuoto-message">Aggiungi prodotti dal catalogo per procedere con l'acquisto</p>
        <a href="/cliente/catalogo.php" class="btn btn-primary btn-lg">
            Vai al Catalogo
        </a>
    </div>
<?php else: ?>
    <!-- Carrello con Items -->
    <div class="carrello-container">
        <!-- Items -->
        <div class="carrello-items">
            <h2 class="section-title">Carrello (<?php echo count($items); ?> prodott<?php echo count($items) != 1 ? 'i' : 'o'; ?>)</h2>
            
            <?php foreach ($items as $item): ?>
                <div class="carrello-item">
                    <div class="item-image">🌾</div>
                    
                    <div class="item-details">
                        <h3 class="item-nome"><?php echo htmlspecialchars($item['nome']); ?></h3>
                        <p class="item-categoria"><?php echo htmlspecialchars($item['categoria']); ?></p>
                        <p class="item-prezzo-unitario">
                            <?php echo formatWeight($item['pesoNetto'], $item['unitaMisura']); ?> - 
                            <?php echo formatPrice($item['prezzo']); ?>
                        </p>
                    </div>
                    
                    <div class="item-actions">
                        <div class="item-quantita-control">
                            <form method="POST" action="/api/carrello.php" style="display: contents;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="key" value="<?php echo $item['key']; ?>">
                                <button type="button" class="number-btn" 
                                        onclick="updateQuantita(this.form, <?php echo $item['quantita'] - 1; ?>)">−</button>
                                <input type="number" name="quantita" class="quantita-input" 
                                       value="<?php echo $item['quantita']; ?>" 
                                       min="1" max="<?php echo $item['giacenzaAttuale']; ?>"
                                       onchange="this.form.submit()">
                                <button type="button" class="number-btn" 
                                        onclick="updateQuantita(this.form, <?php echo $item['quantita'] + 1; ?>)">+</button>
                            </form>
                        </div>
                        
                        <div class="item-totale">
                            <?php echo formatPrice($item['subtotale']); ?>
                        </div>
                        
                        <form method="POST" action="/api/carrello.php">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="key" value="<?php echo $item['key']; ?>">
                            <button type="submit" class="item-remove">Rimuovi</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Riepilogo -->
        <div class="carrello-riepilogo">
            <h3 class="riepilogo-title">Riepilogo Ordine</h3>
            
            <div class="riepilogo-row">
                <span class="riepilogo-label">Prodotti (<?php echo count($items); ?>)</span>
                <span class="riepilogo-value"><?php echo formatPrice($totale); ?></span>
            </div>
            
            <div class="riepilogo-divider"></div>
            
            <div class="riepilogo-totale">
                <span class="totale-label">Totale</span>
                <span class="totale-value"><?php echo formatPrice($totale); ?></span>
            </div>
            
            <div class="riepilogo-actions">
                <a href="/cliente/checkout.php" class="btn btn-primary btn-block btn-lg">
                    Procedi al Checkout
                </a>
                <a href="/cliente/catalogo.php" class="btn btn-outline btn-block">
                    Continua ad Acquistare
                </a>
                
                <form method="POST" action="/api/carrello.php" style="margin-top: 1rem;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-ghost btn-block" 
                            onclick="return confirm('Vuoi svuotare il carrello?')">
                        Svuota Carrello
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function updateQuantita(form, newQuantita) {
    const input = form.querySelector('input[name="quantita"]');
    const max = parseInt(input.max);
    const min = parseInt(input.min);
    
    if (newQuantita >= min && newQuantita <= max) {
        input.value = newQuantita;
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
