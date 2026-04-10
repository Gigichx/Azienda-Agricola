<?php
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Azienda Agricola');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?> | Admin</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Custom admin CSS -->
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="bg-light">

<!-- Sidebar + Top bar layout -->
<div class="d-flex min-vh-100">

    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-white border-end">
        <a href="/admin/index.php" class="d-flex align-items-center mb-4 text-decoration-none sidebar-brand">
            <i class="fas fa-leaf text-success me-2 fs-5"></i>
            <span class="fw-semibold"><?php echo APP_NAME; ?></span>
        </a>
        <span class="sidebar-section-label">Dashboard</span>
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a href="/admin/index.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) !== '/' ? 'active' : ''; ?>">
                    <i class="fas fa-gauge-high me-2"></i>Panoramica
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Produzione</span>
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a href="/admin/lavorazione.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lavorazione.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs me-2"></i>Lavorazioni
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/riserva.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'riserva.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box me-2"></i>Riserve
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/confezionamento.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'confezionamento.php' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes-stacked me-2"></i>Confezionamenti
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Catalogo</span>
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a href="/admin/prodotti.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'prodotti.php' ? 'active' : ''; ?>">
                    <i class="fas fa-seedling me-2"></i>Prodotti
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/categorie.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorie.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags me-2"></i>Categorie
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Vendite</span>
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a href="/admin/vendite.php"
                   class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['vendite.php','vendita-dettaglio.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-cash-register me-2"></i>Vendite
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/clienti.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clienti.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i>Clienti
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Utilità</span>
        <ul class="nav nav-pills flex-column mb-2">
            <li class="nav-item">
                <a href="/admin/luoghi.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'luoghi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marker-alt me-2"></i>Luoghi
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/archivio.php"
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'archivio.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i>Archivio / Report
                </a>
            </li>
        </ul>

        <div class="mt-auto pt-3 border-top">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar-sm rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold">
                    <?php echo strtoupper(substr(getUserName(), 0, 1)); ?>
                </div>
                <div class="lh-sm">
                    <div class="small fw-semibold"><?php echo htmlspecialchars(getUserName()); ?></div>
                    <div class="x-small text-muted">Amministratore</div>
                </div>
            </div>
            <a href="/logout.php" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-sign-out-alt me-1"></i>Esci
            </a>
        </div>
    </nav>
    <!-- /Sidebar -->

    <!-- Main content -->
    <div class="flex-grow-1 d-flex flex-column overflow-hidden">

        <!-- Top bar mobile -->
        <header class="d-lg-none bg-white border-bottom px-3 py-2 d-flex align-items-center justify-content-between">
            <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <span class="fw-semibold"><?php echo htmlspecialchars($pageTitle ?? 'Admin'); ?></span>
            <a href="/logout.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </header>

        <main class="flex-grow-1 overflow-auto p-4">
            <?php
            $flash = getFlashMessage();
            if ($flash):
                $alertClass = match($flash['type']) {
                    'success' => 'alert-success',
                    'error'   => 'alert-danger',
                    'warning' => 'alert-warning',
                    default   => 'alert-info',
                };
            ?>
            <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show mb-4" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
