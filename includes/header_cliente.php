<?php
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Azienda Agricola');
}

// Gestione accesso guest tramite parametro URL
if (isset($_GET['guest']) && $_GET['guest'] == '1' && !isLoggedIn()) {
    loginGuest();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Home'); ?> | <?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Custom -->
    <link rel="stylesheet" href="/css/cliente.css">
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top cliente-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/cliente/catalogo.php">
            <span class="brand-icon"><i class="fas fa-leaf"></i></span>
            <span class="brand-name"><?php echo APP_NAME; ?></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navCliente">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navCliente">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'catalogo.php' ? 'active' : ''; ?>"
                       href="/cliente/catalogo.php">
                        <i class="fas fa-store me-1"></i>Catalogo
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <!-- Carrello -->
                <a href="/cliente/carrello.php"
                   class="nav-cart-btn <?php echo basename($_SERVER['PHP_SELF']) == 'carrello.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-basket"></i>
                    <span class="nav-cart-label">Carrello</span>
                    <?php
                    $cartCount = 0;
                    if (isset($_SESSION['carrello'])) {
                        foreach ($_SESSION['carrello'] as $item) {
                            $cartCount += $item['quantita'] ?? 1;
                        }
                    }
                    if ($cartCount > 0): ?>
                        <span class="nav-cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isGuest()): ?>
                    <!-- Menu ospite -->
                    <div class="dropdown">
                        <button class="nav-user-btn dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user-clock me-1"></i>Ospite
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li>
                                <span class="dropdown-item-text small text-muted px-3 py-2">
                                    <i class="fas fa-info-circle me-1"></i>Stai navigando come ospite
                                </span>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item" href="/login.php">
                                    <i class="fas fa-sign-in-alt me-2 text-success"></i>Accedi
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/registrazione.php">
                                    <i class="fas fa-user-plus me-2 text-success"></i>Registrati
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Menu cliente registrato -->
                    <div class="dropdown">
                        <button class="nav-user-btn dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="nav-avatar"><?php echo strtoupper(substr(getUserName(), 0, 1)); ?></span>
                            <span class="d-none d-sm-inline"><?php echo htmlspecialchars(explode(' ', getUserName())[0]); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li>
                                <span class="dropdown-item-text small text-muted px-3 py-1">
                                    <?php echo htmlspecialchars(getUserName()); ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item" href="/cliente/profilo.php">
                                    <i class="fas fa-user me-2 text-muted"></i>Il mio profilo
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Esci
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="cliente-main">
    <div class="container py-4">
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
