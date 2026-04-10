<?php
/**
 * CARRELLO.PHP - Cliente
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();
$pageTitle = 'Carrello';

if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

$carrello = $_SESSION['carrello'];
$totale   = 0;
$items    = [];

foreach ($carrello as $key => $item) {
    $sql = "SELECT p.nome, p.unitaMisura, cat.nome as categoria,
            conf.pesoNetto, conf.prezzo, conf.giacenzaAttuale, conf.idProdotto
            FROM CONFEZIONAMENTO conf
            INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
            INNER JOIN CATEGORIA cat ON p.idCategoria = cat.idCategoria
            WHERE conf.idConfezionamento = ?";
    $det = fetchOne($conn, $sql, [$item['idConfezionamento']]);
    if ($det) {
        $sub    = $det['prezzo'] * $item['quantita'];
        $totale += $sub;
        $items[] = array_merge($det, [
            'key'              => $key,
            'idConfezionamento'=> $item['idConfezionamento'],
            'quantita'         => $item['quantita'],
            'subtotale'        => $sub,
        ]);
    }
}

include '../includes/header_cliente.php';
?>

<?php if (empty($items)): ?>
<div class="text-center py-5 my-4">
    <i class="fas fa-shopping-basket fa-3x text-muted mb-3"></i>
    <h4 class="text-muted">Il carrello è vuoto</h4>
    <p class="text-muted mb-4">Aggiungi prodotti dal catalogo per procedere all'acquisto.</p>
    <a href="/cliente/catalogo.php" class="btn btn-success">
        <i class="fas fa-store me-1"></i>Vai al Catalogo
    </a>
</div>
<?php else: ?>

<div class="row g-4">
    <!-- Items -->
    <div class="col-lg-8">
        <h4 class="mb-3">Il tuo carrello <span class="text-muted fs-6">(<?php echo count($items); ?> prodott<?php echo count($items) != 1 ? 'i' : 'o'; ?>)</span></h4>

        <div class="card border-0 shadow-sm">
            <?php foreach ($items as $i => $item): ?>
            <div class="card-body border-bottom d-flex align-items-center gap-3 <?php echo $i === count($items)-1 ? 'border-0' : ''; ?>">
                <!-- Icona -->
                <div class="bg-light rounded d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:52px;height:52px;color:#9ca3af">
                    <i class="fas fa-seedling"></i>
                </div>

                <!-- Info -->
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?php echo htmlspecialchars($item['nome']); ?></div>
                    <small class="text-muted">
                        <?php echo htmlspecialchars($item['categoria']); ?> —
                        <?php echo formatWeight($item['pesoNetto'], $item['unitaMisura']); ?>/conf —
                        <?php echo formatPrice($item['prezzo']); ?>/conf
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
                               maxlength="8"
                               data-max="<?php echo $item['giacenzaAttuale']; ?>"
                               onchange="capQuantita(this); this.form.submit()">
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="cambiaQta(this, 1)">
                            <i class="fas fa-plus" style="font-size:.6rem"></i>
                        </button>
                    </form>
                </div>

                <!-- Subtotale -->
                <div class="fw-bold text-success" style="min-width:70px;text-align:right">
                    <?php echo formatPrice($item['subtotale']); ?>
                </div>

                <!-- Rimuovi -->
                <form method="POST" action="/api/carrello.php">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="key" value="<?php echo $item['key']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Svuota -->
        <div class="mt-2 text-end">
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
        <div class="card border-0 shadow-sm sticky-top" style="top:80px">
            <div class="card-body">
                <h5 class="mb-3">Riepilogo</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Prodotti (<?php echo count($items); ?>)</span>
                    <span><?php echo formatPrice($totale); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-semibold">Totale</span>
                    <span class="fw-bold text-success fs-5"><?php echo formatPrice($totale); ?></span>
                </div>
                <a href="/cliente/checkout.php" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-lock me-1"></i>Procedi al Checkout
                </a>
                <a href="/cliente/catalogo.php" class="btn btn-outline-secondary w-100 btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Continua gli acquisti
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function cambiaQta(btn, delta) {
    const form = btn.closest('form');
    const input = form.querySelector('input[name="quantita"]');
    const val = parseInt(input.value) + delta;
    const min = parseInt(input.min);
    const max = parseInt(input.max);
    if (val >= min && val <= max) {
        input.value = val;
        form.submit();
    }
}
function capQuantita(input) {
    let val = parseInt(input.value) || 1;
    const max = parseInt(input.dataset.max) || 99999999;
    const min = parseInt(input.min) || 1;
    if (val > max) {
        input.value = max;
        showToast('Quantità ridotta alla giacenza massima disponibile (' + max + ')', 'warning');
    }
    if (val < min) input.value = min;
    // limite 8 cifre
    if (String(input.value).length > 8) input.value = String(input.value).slice(0, 8);
}
</script>
<?php endif; ?>

<?php include '../includes/footer_cliente.php'; ?>
