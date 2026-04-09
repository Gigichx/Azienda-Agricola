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
    <title><?php echo $pageTitle ?? 'Home'; ?> | <?php echo APP_NAME; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>
<body class="cliente-area">

<header class="navbar">
    <div class="container navbar-container">
        <div class="navbar-brand">
            <a href="/cliente/catalogo.php">
                <span class="logo-icon">🌾</span>
                <span class="logo-text"><?php echo APP_NAME; ?></span>
            </a>
        </div>
        
        <nav class="navbar-menu">
            <a href="/cliente/catalogo.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'catalogo.php' ? 'active' : ''; ?>">
                Catalogo
            </a>
            <a href="/cliente/carrello.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'carrello.php' ? 'active' : ''; ?>">
                Carrello
                <?php if (isset($_SESSION['carrello']) && count($_SESSION['carrello']) > 0): ?>
                    <span class="badge"><?php echo count($_SESSION['carrello']); ?></span>
                <?php endif; ?>
            </a>
            <a href="/cliente/profilo.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profilo.php' ? 'active' : ''; ?>">
                Profilo
            </a>
        </nav>
        
        <div class="navbar-user">
            <span class="user-welcome">Ciao, <?php echo htmlspecialchars(explode(' ', getUserName())[0]); ?></span>
            <a href="/logout.php" class="btn btn-outline-sm">Esci</a>
        </div>
    </div>
</header>

<main class="main-content">
    <div class="container">
        <?php
        // Mostra messaggio flash se presente
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
