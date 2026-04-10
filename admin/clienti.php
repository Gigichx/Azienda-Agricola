<?php
/**
 * CLIENTI - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Clienti';

// Clienti registrati (con account)
$sqlRegistrati = "SELECT c.*, u.email as emailUtente, u.dataRegistrazione,
        COUNT(DISTINCT v.idVendita) as totaleOrdini,
        COALESCE(SUM(v.totalePagato), 0) as totaleSpeso
        FROM CLIENTE c
        LEFT JOIN UTENTE u ON c.idUtente = u.idUtente
        LEFT JOIN VENDITA v ON c.idCliente = v.idCliente
        WHERE c.occasionale = FALSE
        GROUP BY c.idCliente
        ORDER BY c.nome";
$clienti = fetchAll($conn, $sqlRegistrati);

// Cliente occasionale
$clienteOccasionale = fetchOne($conn, "SELECT c.*, COALESCE(COUNT(v.idVendita),0) as totaleOrdini,
        COALESCE(SUM(v.totalePagato),0) as totaleSpeso
        FROM CLIENTE c
        LEFT JOIN VENDITA v ON c.idCliente = v.idCliente
        WHERE c.occasionale = TRUE LIMIT 1");

include '../includes/header_admin.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h4 mb-0 fw-bold">Anagrafica Clienti</h1>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuovoCliente">
        <i class="fas fa-user-plus me-1"></i>Nuovo Cliente
    </button>
</div>

<!-- Card cliente occasionale -->
<?php if ($clienteOccasionale): ?>
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #f59e0b !important;">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:44px;height:44px;color:#d97706">
            <i class="fas fa-user-clock"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold">Cliente Occasionale</div>
            <small class="text-muted">Utente anonimo per vendite al banco — ID #<?php echo $clienteOccasionale['idCliente']; ?></small>
        </div>
        <div class="text-end">
            <div class="fw-bold text-warning"><?php echo $clienteOccasionale['totaleOrdini']; ?> vendite</div>
            <small class="text-muted"><?php echo formatPrice($clienteOccasionale['totaleSpeso']); ?></small>
        </div>
        <span class="badge bg-warning text-dark">Occasionale</span>
    </div>
</div>
<?php endif; ?>

<!-- Tabella clienti registrati -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">Clienti Registrati</h6>
        <span class="badge bg-light text-secondary"><?php echo count($clienti); ?> clienti</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Cliente</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Registrato il</th>
                        <th class="text-center">Ordini</th>
                        <th class="text-end pe-3">Totale Speso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clienti as $c): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 fw-bold text-success"
                                     style="width:34px;height:34px;font-size:.75rem">
                                    <?php echo strtoupper(substr($c['nome'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($c['nome']); ?></div>
                                    <?php if ($c['nickname']): ?>
                                        <small class="text-muted">@<?php echo htmlspecialchars($c['nickname']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($c['emailUtente'] ?? $c['email'] ?? '—'); ?></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($c['telefono'] ?? '—'); ?></td>
                        <td class="text-muted small"><?php echo $c['dataRegistrazione'] ? formatDate($c['dataRegistrazione']) : '—'; ?></td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success fw-semibold"><?php echo $c['totaleOrdini']; ?></span>
                        </td>
                        <td class="text-end pe-3 fw-semibold"><?php echo formatPrice($c['totaleSpeso']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clienti)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Nessun cliente registrato</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuovo Cliente -->
<div class="modal fade" id="modalNuovoCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/api/clienti.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Nuovo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nickname</label>
                        <input type="text" name="nickname" class="form-control" maxlength="100">
                    </div>
                    <div class="row g-3">
                        <div class="col">
                            <label class="form-label fw-semibold">Telefono</label>
                            <input type="text" name="telefono" class="form-control" maxlength="20">
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="150">
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="occasionale" value="1" id="checkOccasionale">
                            <label class="form-check-label" for="checkOccasionale">
                                Cliente occasionale (nessun account)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-save me-1"></i>Salva Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
