<?php
/**
 * CHECKOUT.PHP - Cliente
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();
$pageTitle = 'Conferma Ordine';

if (!isset($_SESSION['carrello']) || empty($_SESSION['carrello'])) {
    redirectWithMessage('/cliente/catalogo.php', 'Il carrello è vuoto', 'warning');
}

$carrello = $_SESSION['carrello'];
$totale   = 0;
$items    = [];

foreach ($carrello as $item) {
    $sql = "SELECT p.nome, conf.pesoNetto, conf.prezzo, conf.giacenzaAttuale, p.unitaMisura
            FROM CONFEZIONAMENTO conf
            INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
            WHERE conf.idConfezionamento = ?";
    $det = fetchOne($pdo, $sql, [$item['idConfezionamento']]);

    if ($det) {
        if ($det['giacenzaAttuale'] < $item['quantita']) {
            redirectWithMessage('/cliente/carrello.php',
                'Giacenza insufficiente per ' . $det['nome'], 'error');
        }
        $subtotale = $det['prezzo'] * $item['quantita'];
        $totale   += $subtotale;
        $items[]   = array_merge($item, [
            'nome'       => $det['nome'],
            'pesoNetto'  => $det['pesoNetto'],
            'unitaMisura'=> $det['unitaMisura'],
            'prezzo'     => $det['prezzo'],
            'subtotale'  => $subtotale,
        ]);
    }
}

// Dati cliente
$cliente = fetchOne($pdo,
    "SELECT c.* FROM CLIENTE c INNER JOIN UTENTE u ON c.idUtente = u.idUtente WHERE u.idUtente = ?",
    [getUserId()]
);

include '../includes/header_cliente.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">

        <h2 class="h4 mb-4">Conferma ordine</h2>

        <!-- Step 1 – Prodotti -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0">
                    <span class="badge bg-success rounded-circle me-2">1</span>Prodotti nel carrello
                </h6>
            </div>
            <div class="card-body">
                <?php foreach ($items as $item): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($item['nome']); ?></div>
                        <small class="text-muted">
                            <?php echo formatWeight($item['pesoNetto'], $item['unitaMisura']); ?>/conf
                            &times; <?php echo $item['quantita']; ?>
                        </small>
                    </div>
                    <span class="fw-semibold"><?php echo formatPrice($item['subtotale']); ?></span>
                </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between align-items-center pt-3">
                    <span class="fw-semibold">Totale ordine</span>
                    <span class="fw-bold fs-5 text-success"><?php echo formatPrice($totale); ?></span>
                </div>
            </div>
        </div>

        <!-- Step 2 – Dati cliente -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0">
                    <span class="badge bg-success rounded-circle me-2">2</span>I tuoi dati
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="small text-muted">Nome</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                    </div>
                    <?php if ($cliente['email']): ?>
                    <div class="col-sm-6">
                        <div class="small text-muted">Email</div>
                        <div><?php echo htmlspecialchars($cliente['email']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($cliente['telefono']): ?>
                    <div class="col-sm-6">
                        <div class="small text-muted">Telefono</div>
                        <div><?php echo htmlspecialchars($cliente['telefono']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Step 3 – Note e conferma -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0">
                    <span class="badge bg-success rounded-circle me-2">3</span>Note e conferma
                </h6>
            </div>
            <form method="POST" action="/api/ordini.php" id="checkoutForm">
                <div class="card-body">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label for="note" class="form-label small">Note o richieste particolari (opzionale)</label>
                        <textarea name="note" id="note" class="form-control" rows="2"
                                  placeholder="Es: preferisco la consegna al mattino..."></textarea>
                    </div>

                    <div class="alert alert-light border mb-0 py-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1 text-success"></i>
                            Confermando accetti l'ordine per un totale di
                            <strong><?php echo formatPrice($totale); ?></strong>.
                            Riceverai una conferma via email a
                            <strong><?php echo htmlspecialchars($cliente['email'] ?? '—'); ?></strong>.
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
