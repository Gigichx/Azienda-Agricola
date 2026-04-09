<?php
/**
 * LOGIN.PHP
 * Azienda Agricola
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Se già loggato, redirect
if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$timeout_message = '';

// Messaggio timeout sessione
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $timeout_message = 'La tua sessione è scaduta. Effettua nuovamente il login.';
}

// Gestione form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Inserisci email e password';
    } else {
        try {
            // Query per ottenere utente con cliente associato
            $sql = "SELECT u.*, c.idCliente 
                    FROM UTENTE u
                    LEFT JOIN CLIENTE c ON u.idUtente = c.idUtente
                    WHERE u.email = ? AND u.attivo = TRUE";
            
            $user = fetchOne($pdo, $sql, [$email]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Login successful
                login($user);
                redirectByRole();
            } else {
                $error = 'Credenziali non valide';
            }
        } catch (Exception $e) {
            error_log("Errore login: " . $e->getMessage());
            $error = 'Errore durante il login. Riprova più tardi.';
        }
    }
}

$pageTitle = 'Accedi';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Azienda Agricola</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-logo">🌾</div>
            <h1 class="auth-title">Benvenuto</h1>
            <p class="auth-subtitle">Accedi al tuo account</p>
        </div>
        
        <div class="auth-body">
            <?php if ($timeout_message): ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($timeout_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login.php">
                <div class="form-group">
                    <label for="email" class="form-label required">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="tua@email.it"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label required">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Accedi
                </button>
            </form>
        </div>
        
        <div class="auth-footer">
            <p>
                Non hai un account? 
                <a href="/registrazione.php">Registrati ora</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
