<?php
/**
 * PRODOTTO.PHP - Dettaglio Prodotto — FIXED
 * Fix: messaggio chiaro se esaurito, prezzo preview corretto
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (isset($_GET['guest']) && $_GET['guest'] == '1' && !isLoggedIn() && !isGuest()) {
    loginGuest();
}

requireCliente();

$idProdotto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idProdotto) {
    redirectWithMessage('/cliente/catalogo.php', 'Prodotto non trovato', 'error');
}

// Carica prodotto con giacenza totale (somma di tutti i confezionamenti disponibili)
$prodotto = fetchOne($conn,
    "SELECT p.*, c.nome as nomeCategoria, c.idCategoria,
            COALESCE(SUM(conf.giacenzaAttuale), 0) as giacenzaTotale
     FROM PRODOTTO p
     INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
     LEFT JOIN CONFEZIONAMENTO conf
         ON p.idProdotto = conf.idProdotto
         AND conf.giacenzaAttuale > 0
     WHERE p.idProdotto = ?
     GROUP BY p.idProdotto, p.nome, p.unitaMisura, p.prezzoBase,
              p.idCategoria, c.nome, c.idCategoria",
    [$idProdotto]
);

if (!$prodotto) {
    redirectWithMessage('/cliente/catalogo.php', 'Prodotto non trovato', 'error');
}

$pageTitle  = $prodotto['nome'];

// Solo i confezionamenti con giacenza > 0
$confezioni = fetchAll($conn,
    "SELECT idConfezionamento, pesoNetto, prezzo, giacenzaAttuale,
            dataProduzione, dataConfezionamento
     FROM CONFEZIONAMENTO
     WHERE idProdotto = ? AND giacenzaAttuale > 0
     ORDER BY pesoNetto ASC",
    [$idProdotto]
);

$hasConfezioni = !empty($confezioni);
$isDisponibile = (int)$prodotto['giacenzaTotale'] > 0 && $hasConfezioni;

include '../includes/header_cliente.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb" style="font-size:.8125rem">
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

    <!-- Dettagli -->
    <div class="col-md-7 col-lg-8">
        <span class="product-cat-badge mb-2 d-inline-block">
            <?php echo htmlspecialchars($prodotto['nomeCategoria']); ?>
        </span>
        <h2 class="h3 fw-bold mb-1"><?php echo htmlspecialchars($prodotto['nome']); ?></h2>

        <div class="product-price-row mb-3">
            <span class="product-price" style="font-size:1.4rem">
                <?php echo formatPrice($prodotto['prezzoBase']); ?>
            </span>
            <span class="product-price-unit">
                / <?php echo htmlspecialchars($prodotto['unitaMisura']); ?>
            </span>
        </div>

        <!-- Disponibilità -->
        <?php if ($isDisponibile): ?>
            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="fas fa-circle-check text-success"></i>
                <span class="text-success fw-semibold">Disponibile</span>
                <span class="text-muted small">
                    (<?php echo (int)$prodotto['giacenzaTotale']; ?> confezioni in stock)
                </span>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="fas fa-circle-xmark text-danger"></i>
                <span class="text-danger fw-semibold">Prodotto esaurito</span>
            </div>
        <?php endif; ?>

        <!-- Form aggiunta carrello — solo se disponibile -->
        <?php if ($isDisponibile): ?>

            <?php if (isGuest()): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2">
                <i class="fas fa-lock"></i>
                <span class="small">
                    <a href="/login.php" class="fw-semibold text-dark">Accedi</a> o
                    <a href="/registrazione.php" class="fw-semibold text-dark">registrati</a>
                    per acquistare.
                </span>
            </div>
            <?php endif; ?>

            <form action="/api/carrello.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="idProdotto" value="<?php echo $prodotto['idProdotto']; ?>">

                <!-- Selezione formato -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small">
                        <i class="fas fa-box me-1 text-muted"></i>
                        Formato <span class="text-danger">*</span>
                    </label>
                    <select name="idConfezionamento" class="form-select form-select-sm"
                            id="selConfezione" required>
                        <option value="">— Scegli formato —</option>
                        <?php foreach ($confezioni as $conf): ?>
                            <option value="<?php echo $conf['idConfezionamento']; ?>"
                                    data-giacenza="<?php echo (int)$conf['giacenzaAttuale']; ?>"
                                    data-prezzo="<?php echo (float)$conf['prezzo']; ?>">
                                <?php echo formatWeight($conf['pesoNetto'], $prodotto['unitaMisura']); ?>
                                — <?php echo formatPrice($conf['prezzo']); ?>
                                (<?php echo (int)$conf['giacenzaAttuale']; ?> disp.)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quantità -->
                <div class="mb-4">
                    <label class="form-label fw-semibold small">
                        <i class="fas fa-sort-numeric-up me-1 text-muted"></i>Quantità
                    </label>
                    <div class="d-flex align-items-center gap-2" style="max-width:160px">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="cambiaQta(-1)" style="width:34px;height:34px;padding:0">
                            <i class="fas fa-minus" style="font-size:.6rem"></i>
                        </button>
                        <input type="number" name="quantita" id="inputQta"
                               class="form-control form-control-sm text-center"
                               value="1" min="1" max="99" data-max="99"
                               required style="width:60px">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="cambiaQta(1)" style="width:34px;height:34px;padding:0">
                            <i class="fas fa-plus" style="font-size:.6rem"></i>
                        </button>
                    </div>
                </div>

                <!-- Preview prezzo -->
                <div id="prezzoPreview" class="mb-3" style="display:none">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded"
                         style="background:#f0fdf4;border:1px solid #bbf7d0">
                        <span class="text-muted small">Subtotale:</span>
                        <strong class="text-success" id="prezzoPreviewVal"></strong>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-cart-plus me-1"></i>Aggiungi al carrello
                    </button>
                    <a href="/cliente/catalogo.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Catalogo
                    </a>
                </div>
            </form>

        <?php else: ?>
            <!-- Prodotto esaurito — solo bottone torna -->
            <div class="alert alert-light border mb-3 small">
                <i class="fas fa-clock me-1 text-muted"></i>
                Questo prodotto non è al momento disponibile.
                Torna a controllare presto!
            </div>
            <a href="/cliente/catalogo.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Torna al catalogo
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    /* Aggiorna max quantità quando cambia la confezione */
    document.getElementById('selConfezione')?.addEventListener('change', function() {
        const opt      = this.options[this.selectedIndex];
        const giacenza = parseInt(opt?.dataset?.giacenza) || 99;
        const input    = document.getElementById('inputQta');
        if (input) {
            input.max         = giacenza;
            input.dataset.max = giacenza;
            if (parseInt(input.value) > giacenza) {
                input.value = giacenza;
            }
        }
        aggiornaPrezzo();
    });

    /* Aggiorna preview prezzo quando cambia la quantità */
    document.getElementById('inputQta')?.addEventListener('input', function() {
        /* Cap lunghezza */
        if (this.value.length > 6) this.value = this.value.slice(0, 6);
        const max = parseInt(this.dataset.max || this.max) || 99;
        if (parseInt(this.value) > max) this.value = max;
        if (parseInt(this.value) < 1)   this.value = 1;
        aggiornaPrezzo();
    });
})();

function cambiaQta(delta) {
    const input   = document.getElementById('inputQta');
    if (!input) return;
    const current = parseInt(input.value) || 1;
    const max     = parseInt(input.dataset.max || input.max) || 99;
    const newVal  = Math.max(1, Math.min(current + delta, max));
    input.value   = newVal;
    aggiornaPrezzo();
}

function aggiornaPrezzo() {
    const sel     = document.getElementById('selConfezione');
    const input   = document.getElementById('inputQta');
    const preview = document.getElementById('prezzoPreview');
    const valEl   = document.getElementById('prezzoPreviewVal');

    if (!sel || !input || !preview || !valEl) return;

    const opt    = sel.options[sel.selectedIndex];
    const prezzo = parseFloat(opt?.dataset?.prezzo);

    if (!opt || !prezzo || isNaN(prezzo)) {
        preview.style.display = 'none';
        return;
    }

    const qta = parseInt(input.value) || 1;
    const tot = (prezzo * qta).toFixed(2).replace('.', ',');
    valEl.textContent     = tot + ' €';
    preview.style.display = 'block';
}
</script>

<?php include '../includes/footer_cliente.php'; ?>
