<?php
/**
 * ORDINE CONFERMATO - Cliente
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

// Verifica che ci sia un ordine confermato
if (!isset($_SESSION['ordine_confermato'])) {
    redirectWithMessage('/cliente/catalogo.php', 'Nessun ordine da confermare', 'warning');
}

$idVendita = $_SESSION['ordine_confermato'];
unset($_SESSION['ordine_confermato']);

// Ottieni dettagli ordine
$sql = "SELECT v.*, l.nome as nomeLuogo
        FROM VENDITA v
        INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
        WHERE v.idVendita = ?";

$ordine = fetchOne($pdo, $sql, [$idVendita]);

if (!$ordine) {
    redirectWithMessage('/cliente/catalogo.php', 'Ordine non trovato', 'error');
}

$pageTitle = 'Ordine Confermato';

include '../includes/header_cliente.php';
?>

<div class="checkout-container">
    <div class="conferma-ordine">
        <div class="conferma-icon">✓</div>
        <h1 class="conferma-message">Ordine Confermato!</h1>
        <p class="ordine-numero">Ordine #<?php echo $ordine['idVendita']; ?></p>
        
        <div class="alert alert-success">
            <div class="alert-content">
                <p class="alert-title">Grazie per il tuo ordine!</p>
                <p>Il tuo ordine è stato registrato con successo e verrà processato a breve.</p>
                <p>Riceverai una conferma via email all'indirizzo registrato.</p>
            </div>
        </div>
        
        <div class="box" style="max-width: 500px; margin: 2rem auto;">
            <div class="box-header">
                <h3 class="box-title">Dettagli Ordine</h3>
            </div>
            <div class="box-body">
                <div class="riepilogo-row">
                    <span class="riepilogo-label">Data:</span>
                    <span class="riepilogo-value"><?php echo formatDate($ordine['dataVendita'], true); ?></span>
                </div>
                <div class="riepilogo-row">
                    <span class="riepilogo-label">Punto vendita:</span>
                    <span class="riepilogo-value"><?php echo htmlspecialchars($ordine['nomeLuogo']); ?></span>
                </div>
                <div class="riepilogo-row">
                    <span class="riepilogo-label">Totale:</span>
                    <span class="riepilogo-value" style="font-size: 1.5rem; color: var(--color-accent);">
                        <?php echo formatPrice($ordine['totalePagato']); ?>
                    </span>
                </div>
                
                <?php if ($ordine['note']): ?>
                    <div class="riepilogo-divider"></div>
                    <div>
                        <strong>Note:</strong><br>
                        <?php echo nl2br(htmlspecialchars($ordine['note'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="btn-group" style="margin-top: 2rem;">
            <a href="/cliente/profilo.php" class="btn btn-primary btn-lg">
                Vedi i Miei Ordini
            </a>
            <a href="/cliente/catalogo.php" class="btn btn-outline btn-lg">
                Continua ad Acquistare
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
