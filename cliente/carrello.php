<?php
/**
 * CARRELLO.PHP - Cliente / Ospite
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();
$pageTitle = 'Carrello';

if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

$carrello  = $_SESSION['carrello'];
$imponibile = 0;
$items     = [];

foreach ($carrello as $key => $item) {
    $sql = "SELECT p.nome, p.unitaMisura, cat.nome as categoria,
            conf.pesoNetto, conf.prezzo, conf.giacenzaAttuale, conf.idProdotto
            FROM CONFEZIONAMENTO conf
            INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
            INNER JOIN CATEGORIA cat ON p.idCategoria = cat.idCategoria
            WHERE conf.idConfezionamento = ?";
    $det = fetchOne($conn, $sql, [$item['idConfezionamento']]);
    if ($det) {
        $sub         = $det['prezzo'] * $item['quantita'];
        $imponibile += $sub;
        $items[]     = array_merge($det, [
            'key'               => $key,
            'idConfezionamento' => $item['idConfezionamento'],
            'quantita'          => $item['quantita'],
            'subtotale'         => $sub,
        ]);
    }
}

// IVA 22%
$ivaPerc  = 22;
$ivaAmt   = round($imponibile * $ivaPerc / 100, 2);
$totaleCon = round($imponibile + $ivaAmt, 2);

include '../includes/header_cliente.php';
?>

<?php if (empty($items)): ?>
<div class="text-center py-5 my-4">
    <div class="empty-state-icon mx-auto mb-3">
        <i class="fas fa-shopping-basket fa-2x"></i>
    </div>
    <h4 class="fw-semibold">Il carrello è vuoto</h4>
    <p class="text-muted mb-4">Aggiungi prodotti dal catalogo per procedere all'acquisto.</p>
    <a href="/cliente/catalogo.php" class="btn btn-success">
        <i class="fas fa-store me-1"></i>Vai al Catalogo
    </a>
</div>
<?php else: ?>

<div class="row g-4">
    <!-- Items -->
    <div class="col-lg-8">
        <h4 class="mb-3 fw-bold">
            <i class="fas fa-shopping-basket me-2 text-success"></i>
            Il tuo carrello
            <span class="text-muted fs-6 fw-normal">(<?php echo count($items); ?> prodott<?php echo count($items) != 1 ? 'i' : 'o'; ?>)</span>
        </h4>

        <div class="card border-0 shadow-sm">
            <?php foreach ($items as $i => $item): ?>
            <div class="card-body border-bottom d-flex align-items-center gap-3 <?php echo $i === count($items)-1 ? 'border-0' : ''; ?>">

                <!-- Icona prodotto -->
                <div class="cart-item-icon">
                    <i class="fas fa-seedling"></i>
                </div>

                <!-- Info -->
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($item['nome']); ?></div>
                    <small class="text-muted">
                        <?php echo htmlspecialchars($item['categoria']); ?>
                        &mdash; <?php echo formatWeight($item['pesoNetto'], $item['unitaMisura']); ?>/conf
                        &mdash; <?php echo formatPrice($item['prezzo']); ?>/conf
                    </small>
                </div>

                <!-- Quantità -->
                <div style="width:120px">
                    <form method="POST" action="/api/carrello.php" class="input-group input-group-sm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="key" value="<?php echo $item['key']; ?>">
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="cambiaQta(this, -1)">
                            <i class="fas fa-minus" style="font-size:.6rem"></i>
                        </button>
                        <input type="number" name="quantita"
                               class="form-control text-center qty-input"
                               value="<?php echo $item['quantita']; ?>"
                               min="1"
                               max="<?php echo $item['giacenzaAttuale']; ?>"
                               data-max="<?php echo $item['giacenzaAttuale']; ?>"
                               onchange="capQuantita(this); this.form.submit()">
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="cambiaQta(this, 1)">
                            <i class="fas fa-plus" style="font-size:.6rem"></i>
                        </button>
                    </form>
                </div>

                <!-- Subtotale -->
                <div class="fw-bold text-success" style="min-width:80px;text-align:right">
                    <?php echo formatPrice($item['subtotale']); ?>
                </div>

                <!-- Rimuovi -->
                <form method="POST" action="/api/carrello.php">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="key" value="<?php echo $item['key']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Rimuovi">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Svuota -->
        <div class="mt-2 d-flex justify-content-between align-items-center">
            <a href="/cliente/catalogo.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Continua gli acquisti
            </a>
            <form method="POST" action="/api/carrello.php" class="d-inline">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none"
                        onclick="return confirm('Svuotare il carrello?')">
                    <i class="fas fa-trash me-1"></i>Svuota carrello
                </button>
            </form>
        </div>
    </div>

    <!-- Riepilogo -->
    <div class="col-lg-4">
        <div class="cart-summary">
            <div class="cart-summary-title">
                <i class="fas fa-receipt me-2 text-success"></i>Riepilogo ordine
            </div>

            <div class="cart-summary-row">
                <span>Imponibile (<?php echo count($items); ?> prodott<?php echo count($items) != 1 ? 'i' : 'o'; ?>)</span>
                <span><?php echo formatPrice($imponibile); ?></span>
            </div>
            <div class="cart-summary-row iva">
                <span><i class="fas fa-percent me-1"></i>IVA <?php echo $ivaPerc; ?>%</span>
                <span><?php echo formatPrice($ivaAmt); ?></span>
            </div>

            <div class="cart-summary-total">
                <span class="cart-total-label">Totale IVA incl.</span>
                <span class="cart-total-value"><?php echo formatPrice($totaleCon); ?></span>
            </div>
            <div class="cart-iva-note">IVA al <?php echo $ivaPerc; ?>% inclusa nel totale</div>

            <div class="d-grid gap-2 mt-3">
                <?php if (isGuest()): ?>
                    <div class="alert alert-warning py-2 small text-center mb-2">
                        <i class="fas fa-lock me-1"></i>
                        <a href="/login.php" class="fw-semibold">Accedi</a> o
                        <a href="/registrazione.php" class="fw-semibold">registrati</a>
                        per procedere al checkout
                    </div>
                <?php else: ?>
                    <a href="/cliente/checkout.php" class="btn btn-success">
                        <i class="fas fa-lock me-1"></i>Procedi al Checkout
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function cambiaQta(btn, delta) {
    const form  = btn.closest('form');
    const input = form.querySelector('input[name="quantita"]');
    const val   = parseInt(input.value) + delta;
    const min   = parseInt(input.min);
    const max   = parseInt(input.max);
    if (val >= min && val <= max) {
        input.value = val;
        form.submit();
    }
}
function capQuantita(input) {
    let val      = parseInt(input.value) || 1;
    const maxVal = parseInt(input.dataset.max) || 99999999;
    const minVal = parseInt(input.min) || 1;
    if (val > maxVal) {
        input.value = maxVal;
        if (typeof showToast === 'function') showToast('Quantità ridotta alla giacenza massima (' + maxVal + ')', 'warning');
    }
    if (val < minVal) input.value = minVal;
    if (String(input.value).length > 8) input.value = String(input.value).slice(0, 8);
}
</script>
<?php endif; ?>

<?php include '../includes/footer_cliente.php'; ?>
