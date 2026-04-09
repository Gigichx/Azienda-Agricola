<?php
/**
 * CHECKOUT.PHP - Cliente
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$pageTitle = 'Checkout';

// Verifica carrello non vuoto
if (!isset($_SESSION['carrello']) || empty($_SESSION['carrello'])) {
    redirectWithMessage('/cliente/catalogo.php', 'Il carrello è vuoto', 'warning');
}

$carrello = $_SESSION['carrello'];
$totale = 0;

// Ottieni dettagli prodotti
$items = [];
foreach ($carrello as $item) {
    $sql = "SELECT p.nome, conf.pesoNetto, conf.prezzo, conf.giacenzaAttuale, p.unitaMisura
            FROM CONFEZIONAMENTO conf
            INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
            WHERE conf.idConfezionamento = ?";
    
    $dettagli = fetchOne($pdo, $sql, [$item['idConfezionamento']]);
    
    if ($dettagli) {
        // Verifica giacenza
        if ($dettagli['giacenzaAttuale'] < $item['quantita']) {
            redirectWithMessage('/cliente/carrello.php', 
                'Giacenza insufficiente per ' . $dettagli['nome'], 'error');
        }
        
        $subtotale = $dettagli['prezzo'] * $item['quantita'];
        $totale += $subtotale;
        
        $items[] = array_merge($item, [
            'nome' => $dettagli['nome'],
            'pesoNetto' => $dettagli['pesoNetto'],
            'unitaMisura' => $dettagli['unitaMisura'],
            'prezzo' => $dettagli['prezzo'],
            'subtotale' => $subtotale
        ]);
    }
}

// Ottieni dati cliente
$sqlCliente = "SELECT c.* FROM CLIENTE c
               INNER JOIN UTENTE u ON c.idUtente = u.idUtente
               WHERE u.idUtente = ?";
$cliente = fetchOne($pdo, $sqlCliente, [getUserId()]);

include '../includes/header_cliente.php';
?>

<div class="checkout-container">
    <h1 class="page-title">Conferma Ordine</h1>
    
    <!-- Step 1: Riepilogo -->
    <div class="checkout-step">
        <h2 class="checkout-step-title">
            <span class="step-number">1</span>
            Riepilogo Prodotti
        </h2>
        
        <div class="checkout-riepilogo">
            <?php foreach ($items as $item): ?>
                <div class="riepilogo-item">
                    <span>
                        <strong><?php echo htmlspecialchars($item['nome']); ?></strong><br>
                        <small><?php echo formatWeight($item['pesoNetto'], $item['unitaMisura']); ?> × <?php echo $item['quantita']; ?></small>
                    </span>
                    <span><?php echo formatPrice($item['subtotale']); ?></span>
                </div>
            <?php endforeach; ?>
            
            <div class="riepilogo-divider"></div>
            
            <div class="riepilogo-item">
                <strong>Totale Ordine</strong>
                <strong style="font-size: 1.5rem; color: var(--color-accent);">
                    <?php echo formatPrice($totale); ?>
                </strong>
            </div>
        </div>
    </div>
    
    <!-- Step 2: Dati Cliente -->
    <div class="checkout-step">
        <h2 class="checkout-step-title">
            <span class="step-number">2</span>
            I Tuoi Dati
        </h2>
        
        <div class="info-card">
            <div class="info-card-content">
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($cliente['nome']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                <?php if ($cliente['telefono']): ?>
                    <p><strong>Telefono:</strong> <?php echo htmlspecialchars($cliente['telefono']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Step 3: Note (Opzionale) -->
    <div class="checkout-step">
        <h2 class="checkout-step-title">
            <span class="step-number">3</span>
            Note Ordine (Opzionale)
        </h2>
        
        <form method="POST" action="/api/ordini.php" id="checkoutForm">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="note" class="form-label">Note o richieste particolari</label>
                <textarea name="note" id="note" class="checkout-note form-textarea" 
                          placeholder="Inserisci eventuali note per l'ordine..."></textarea>
            </div>
            
            <!-- Conferma -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--color-border-light);">
                <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                    <div class="alert-content">
                        <p class="alert-title">Conferma Ordine</p>
                        <p>Procedendo confermi l'ordine per un totale di <strong><?php echo formatPrice($totale); ?></strong>.</p>
                        <p>Riceverai una conferma via email all'indirizzo <strong><?php echo htmlspecialchars($cliente['email']); ?></strong>.</p>
                    </div>
                </div>
                
                <div class="btn-group-block">
                    <button type="submit" class="btn btn-success btn-lg" style="flex: 1;">
                        Conferma Ordine
                    </button>
                    <a href="/cliente/carrello.php" class="btn btn-outline btn-lg">
                        Torna al Carrello
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner spinner-inline"></span> Elaborazione...';
});
</script>

<?php include '../includes/footer.php'; ?>
