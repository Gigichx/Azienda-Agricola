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
    <title><?php echo htmlspecialchars($pageTitle ?? 'Home'); ?> | <?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Custom -->
    <link rel="stylesheet" href="/css/cliente.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/cliente/catalogo.php">
            <i class="fas fa-leaf text-success"></i>
            <span class="fw-semibold"><?php echo APP_NAME; ?></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navCliente">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navCliente">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'catalogo.php' ? 'active fw-semibold' : ''; ?>"
                       href="/cliente/catalogo.php">
                        <i class="fas fa-store me-1"></i>Catalogo
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <!-- Carrello -->
                <a href="/cliente/carrello.php"
                   class="btn btn-outline-secondary btn-sm position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'carrello.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-basket me-1"></i>Carrello
                    <?php if (isset($_SESSION['carrello']) && count($_SESSION['carrello']) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                            <?php echo count($_SESSION['carrello']); ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Profilo -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars(explode(' ', getUserName())[0]); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/cliente/profilo.php">
                                <i class="fas fa-user-circle me-2 text-muted"></i>Profilo
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Esci
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">
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
