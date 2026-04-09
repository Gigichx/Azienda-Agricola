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
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> | Amministrazione</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/css/admin.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>
<body class="admin-area">

<header class="navbar-admin">
    <div class="container navbar-container">
        <div class="navbar-brand">
            <a href="/admin/index.php">
                <span class="logo-icon">🌾</span>
                <span class="logo-text"><?php echo APP_NAME; ?></span>
                <span class="badge-admin">Admin</span>
            </a>
        </div>
        
        <nav class="navbar-menu">
            <a href="/admin/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            
            <div class="nav-dropdown">
                <button class="nav-link dropdown-toggle">Produzione</button>
                <div class="dropdown-menu">
                    <a href="/admin/lavorazione.php">Lavorazioni</a>
                    <a href="/admin/confezionamento.php">Confezionamento</a>
                    <a href="/admin/riserva.php">Riserve</a>
                </div>
            </div>
            
            <div class="nav-dropdown">
                <button class="nav-link dropdown-toggle">Gestione</button>
                <div class="dropdown-menu">
                    <a href="/admin/prodotti.php">Prodotti</a>
                    <a href="/admin/categorie.php">Categorie</a>
                    <a href="/admin/luoghi.php">Luoghi</a>
                </div>
            </div>
            
            <a href="/admin/vendite.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendite.php' ? 'active' : ''; ?>">
                Vendite
            </a>
            
            <a href="/admin/clienti.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clienti.php' ? 'active' : ''; ?>">
                Clienti
            </a>
            
            <a href="/admin/archivio.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'archivio.php' ? 'active' : ''; ?>">
                Archivio
            </a>
        </nav>
        
        <div class="navbar-user">
            <span class="user-welcome"><?php echo htmlspecialchars(getUserName()); ?></span>
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
