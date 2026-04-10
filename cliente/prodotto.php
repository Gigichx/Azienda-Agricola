<?php
/**
 * PRODOTTO.PHP - Dettaglio Prodotto
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$idProdotto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idProdotto) {
    redirectWithMessage('/cliente/catalogo.php', 'Prodotto non trovato', 'error');
}

$prodotto = fetchOne($conn,
    "SELECT p.*, c.nome as nomeCategoria, c.idCategoria,
            COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale
     FROM PRODOTTO p
     INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
     LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
     WHERE p.idProdotto = ?
     GROUP BY p.idProdotto",
    [$idProdotto]
);

if (!$prodotto) {
    redirectWithMessage('/cliente/catalogo.php', 'Prodotto non trovato', 'error');
}

$pageTitle  = $prodotto['nome'];
$confezioni = fetchAll($conn,
    "SELECT * FROM CONFEZIONAMENTO WHERE idProdotto = ? AND giacenzaAttuale > 0 ORDER BY pesoNetto",
    [$idProdotto]
);

include '../includes/header_cliente.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="/cliente/catalogo.php" class="text-success text-decoration-none">
                <i class="fas fa-store me-1"></i>Catalogo
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="/cliente/catalogo.php?categoria=<?php echo $prodotto['idCategoria']; ?>"
               class="text-success text-decoration-none">
                <?php echo htmlspecialchars($prodotto['nomeCategoria']); ?>
            </a>
        </li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($prodotto['nome']); ?></li>
    </ol>
</nav>

<div class="row g-4">

    <!-- Immagine placeholder -->
    <div class="col-md-5 col-lg-4">
        <div class="product-detail-img">
            <i class="fas fa-seedling"></i>
        </div>
    </div>

    <!-- Dettagli prodotto -->
    <div class="col-md-7 col-lg-8">
        <span class="product-cat-badge mb-2 d-inline-block">
            <?php echo htmlspecialchars($prodotto['nomeCategoria']); ?>
        </span>
        <h2 class="h3 fw-bold mb-1"><?php echo htmlspecialchars($prodotto['nome']); ?></h2>

        <div class="product-price-row mb-3">
            <span class="product-price" style="font-size:1.4rem"><?php echo formatPrice($prodotto['prezzoBase']); ?></span>
            <span class="product-price-unit">/ <?php echo htmlspecialchars($prodotto['unitaMisura']); ?></span>
        </div>

        <!-- Disponibilità -->
        <?php if ($prodotto['giacenzaTotale'] > 0): ?>
            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="fas fa-circle-check text-success"></i>
                <span class="text-success fw-semibold">Disponibile</span>
                <span class="text-muted small">(<?php echo $prodotto['giacenzaTotale']; ?> confezioni in stock)</span>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="fas fa-circle-xmark text-danger"></i>
                <span class="text-danger fw-semibold">Prodotto esaurito</span>
            </div>
        <?php endif; ?>

        <!-- Form aggiunta carrello -->
        <?php if ($prodotto['giacenzaTotale'] > 0 && !empty($confezioni)): ?>
        <?php if (isGuest()): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
            <i class="fas fa-lock"></i>
            <span>
                <a href="/login.php" class="fw-semibold text-dark">Accedi</a> o
                <a href="/registrazione.php" class="fw-semibold text-dark">registrati</a>
                per aggiungere prodotti al carrello e procedere all'ordine.
            </span>
        </div>
        <?php endif; ?>
        <form action="/api/carrello.php" method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="idProdotto" value="<?php echo $prodotto['idProdotto']; ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-box me-1 text-muted"></i>Formato <span class="text-danger">*</span>
                </label>
                <select name="idConfezionamento" class="form-select" id="selConfezione" required>
                    <option value="">-- Scegli formato --</option>
                    <?php foreach ($confezioni as $conf): ?>
                        <option value="<?php echo $conf['idConfezionamento']; ?>"
                                data-giacenza="<?php echo $conf['giacenzaAttuale']; ?>"
                                data-prezzo="<?php echo $conf['prezzo']; ?>">
                            <?php echo formatWeight($conf['pesoNetto'], $prodotto['unitaMisura']); ?>
                            — <?php echo formatPrice($conf['prezzo']); ?>
                            (<?php echo $conf['giacenzaAttuale']; ?> disp.)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-sort-numeric-up me-1 text-muted"></i>Quantità
                </label>
                <div class="input-group" style="max-width:160px">
                    <button type="button" class="btn btn-outline-secondary" onclick="cambiaQta(-1)">
                        <i class="fas fa-minus" style="font-size:.65rem"></i>
                    </button>
                    <input type="number" name="quantita" id="inputQta"
                           class="form-control text-center"
                           value="1" min="1" max="99"
                           data-max="99"
                           required>
                    <button type="button" class="btn btn-outline-secondary" onclick="cambiaQta(1)">
                        <i class="fas fa-plus" style="font-size:.65rem"></i>
                    </button>
                </div>
            </div>

            <!-- Prezzo selezionato preview -->
            <div id="prezzoPreview" class="mb-3 d-none">
                <small class="text-muted">Subtotale:
                    <strong class="text-success" id="prezzoPreviewVal"></strong>
                </small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-cart-plus me-1"></i>Aggiungi al carrello
                </button>
                <a href="/cliente/catalogo.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Catalogo
                </a>
            </div>
        </form>
        <?php else: ?>
        <a href="/cliente/catalogo.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Torna al catalogo
        </a>
        <?php endif; ?>
    </div>
</div>

<script>
function cambiaQta(delta) {
    const input   = document.getElementById('inputQta');
    const current = parseInt(input.value) || 1;
    const max     = parseInt(input.dataset.max || input.max) || 99;
    const min     = parseInt(input.min) || 1;
    const newVal  = current + delta;
    if (newVal >= min && newVal <= max) input.value = newVal;
    else if (newVal > max) {
        input.value = max;
        if (typeof showToast === 'function') showToast('Quantità massima disponibile: ' + max, 'warning');
    }
    aggiornaPrezzo();
}

function aggiornaPrezzo() {
    const sel     = document.getElementById('selConfezione');
    const input   = document.getElementById('inputQta');
    const preview = document.getElementById('prezzoPreview');
    const valEl   = document.getElementById('prezzoPreviewVal');
    if (!sel || !input) return;
    const opt     = sel.options[sel.selectedIndex];
    if (!opt || !opt.dataset.prezzo) { preview.classList.add('d-none'); return; }
    const prezzo  = parseFloat(opt.dataset.prezzo) || 0;
    const qta     = parseInt(input.value) || 1;
    const tot     = (prezzo * qta).toFixed(2).replace('.', ',');
    valEl.textContent = tot + ' €';
    preview.classList.remove('d-none');
}

document.getElementById('selConfezione')?.addEventListener('change', function () {
    const opt      = this.options[this.selectedIndex];
    const giacenza = parseInt(opt.dataset.giacenza) || 99;
    const input    = document.getElementById('inputQta');
    input.max         = giacenza;
    input.dataset.max = giacenza;
    if (parseInt(input.value) > giacenza) {
        input.value = giacenza;
        if (typeof showToast === 'function') showToast('Quantità ridotta alla giacenza massima: ' + giacenza, 'warning');
    }
    aggiornaPrezzo();
});

document.getElementById('inputQta')?.addEventListener('input', function () {
    if (this.value.length > 8) this.value = this.value.slice(0, 8);
    const max = parseInt(this.dataset.max || this.max) || 99;
    if (parseInt(this.value) > max) {
        this.value = max;
        if (typeof showToast === 'function') showToast('Quantità ridotta alla giacenza massima: ' + max, 'warning');
    }
    aggiornaPrezzo();
});
</script>

<?php include '../includes/footer_cliente.php'; ?>
