<?php
/**
 * CHECKOUT.PHP - Cliente (solo utenti registrati)
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Il checkout è solo per clienti registrati
if (!isCliente()) {
    if (isGuest()) {
        redirectWithMessage('/cliente/carrello.php', 'Devi effettuare il login per completare l\'ordine', 'warning');
    } else {
        header('Location: /login.php');
        exit;
    }
}

$pageTitle = 'Conferma Ordine';

if (!isset($_SESSION['carrello']) || empty($_SESSION['carrello'])) {
    redirectWithMessage('/cliente/catalogo.php', 'Il carrello è vuoto', 'warning');
}

$carrello   = $_SESSION['carrello'];
$imponibile = 0;
$items      = [];

foreach ($carrello as $item) {
    $sql = "SELECT p.nome, conf.pesoNetto, conf.prezzo, conf.giacenzaAttuale, p.unitaMisura
            FROM CONFEZIONAMENTO conf
            INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
            WHERE conf.idConfezionamento = ?";
    $det = fetchOne($conn, $sql, [$item['idConfezionamento']]);

    if ($det) {
        if ($det['giacenzaAttuale'] < $item['quantita']) {
            redirectWithMessage('/cliente/carrello.php',
                'Giacenza insufficiente per ' . $det['nome'], 'error');
        }
        $subtotale   = $det['prezzo'] * $item['quantita'];
        $imponibile += $subtotale;
        $items[]     = array_merge($item, [
            'nome'        => $det['nome'],
            'pesoNetto'   => $det['pesoNetto'],
            'unitaMisura' => $det['unitaMisura'],
            'prezzo'      => $det['prezzo'],
            'subtotale'   => $subtotale,
        ]);
    }
}

// IVA 22%
$ivaPerc   = 22;
$ivaAmt    = round($imponibile * $ivaPerc / 100, 2);
$totaleCon = round($imponibile + $ivaAmt, 2);

// Dati cliente
$cliente = fetchOne($conn,
    "SELECT c.* FROM CLIENTE c INNER JOIN UTENTE u ON c.idUtente = u.idUtente WHERE u.idUtente = ?",
    [getUserId()]
);

include '../includes/header_cliente.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">

        <h2 class="h4 fw-bold mb-4">
            <i class="fas fa-clipboard-check me-2 text-success"></i>Conferma ordine
        </h2>

        <!-- Step 1 – Prodotti -->
        <div class="checkout-step-card">
            <div class="checkout-step-header">
                <span class="step-num">1</span>
                <h6 class="fw-semibold mb-0">Prodotti nel carrello</h6>
            </div>
            <div class="checkout-step-body">
                <?php foreach ($items as $item): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($item['nome']); ?></div>
                        <small class="text-muted">
                            <i class="fas fa-weight-hanging me-1"></i><?php echo formatWeight($item['pesoNetto'], $item['unitaMisura']); ?>/conf
                            &times; <?php echo $item['quantita']; ?>
                        </small>
                    </div>
                    <span class="fw-semibold"><?php echo formatPrice($item['subtotale']); ?></span>
                </div>
                <?php endforeach; ?>

                <!-- Riepilogo importi -->
                <div class="d-flex justify-content-between align-items-center pt-3">
                    <span class="text-muted">Imponibile</span>
                    <span><?php echo formatPrice($imponibile); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1">
                    <span class="text-muted small">
                        <i class="fas fa-percent me-1"></i>IVA <?php echo $ivaPerc; ?>%
                    </span>
                    <span class="small"><?php echo formatPrice($ivaAmt); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-6">Totale IVA inclusa</span>
                    <span class="fw-bold fs-5 text-success"><?php echo formatPrice($totaleCon); ?></span>
                </div>
            </div>
        </div>

        <!-- Step 2 – Dati cliente -->
        <div class="checkout-step-card">
            <div class="checkout-step-header">
                <span class="step-num">2</span>
                <h6 class="fw-semibold mb-0">I tuoi dati</h6>
            </div>
            <div class="checkout-step-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-muted"><i class="fas fa-user me-1"></i>Nome</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                    </div>
                    <?php if ($cliente['email']): ?>
                    <div class="col-sm-6">
                        <div class="small text-muted"><i class="fas fa-envelope me-1"></i>Email</div>
                        <div><?php echo htmlspecialchars($cliente['email']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($cliente['telefono']): ?>
                    <div class="col-sm-6">
                        <div class="small text-muted"><i class="fas fa-phone me-1"></i>Telefono</div>
                        <div><?php echo htmlspecialchars($cliente['telefono']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Step 3 – Note e conferma -->
        <div class="checkout-step-card">
            <div class="checkout-step-header">
                <span class="step-num">3</span>
                <h6 class="fw-semibold mb-0">Note e conferma</h6>
            </div>
            <form method="POST" action="/api/ordini.php" id="checkoutForm">
                <div class="checkout-step-body">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label for="note" class="form-label small fw-semibold">
                            <i class="fas fa-sticky-note me-1 text-muted"></i>Note o richieste particolari (opzionale)
                        </label>
                        <textarea name="note" id="note" class="form-control" rows="2"
                                  placeholder="Es: preferisco la consegna al mattino..."></textarea>
                    </div>

                    <div class="alert alert-light border d-flex gap-2 align-items-start mb-0 py-2">
                        <i class="fas fa-info-circle text-success mt-1"></i>
                        <small class="text-muted">
                            Confermando l'ordine accetti l'acquisto per un totale di
                            <strong><?php echo formatPrice($totaleCon); ?></strong>
                            (IVA <?php echo $ivaPerc; ?>% inclusa).
                            <?php if ($cliente['email']): ?>
                            Riceverai conferma a <strong><?php echo htmlspecialchars($cliente['email']); ?></strong>.
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-fill" id="btnConferma">
                        <i class="fas fa-check me-1"></i>Conferma ordine
                    </button>
                    <a href="/cliente/carrello.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Carrello
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('checkoutForm').addEventListener('submit', function () {
    const btn = document.getElementById('btnConferma');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Elaborazione...';
});
</script>

<?php include '../includes/footer_cliente.php'; ?>
