<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();
$pageTitle = 'Luoghi';

$luoghi = fetchAll($pdo, "SELECT * FROM LUOGO ORDER BY nome");

include '../includes/header_admin.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title">Gestione Luoghi</h1>
</div>

<div class="card-grid-3">
    <?php foreach ($luoghi as $l): ?>
        <div class="card">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($l['nome']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($l['tipo']); ?></p>
                <?php if ($l['indirizzo']): ?>
                    <p><small>📍 <?php echo htmlspecialchars($l['indirizzo']); ?></small></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
