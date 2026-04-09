<?php
/**
 * REGISTRAZIONE.PHP
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
$success = false;

// Gestione form registrazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $cognome = sanitizeInput($_POST['cognome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validazione
    if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        $error = 'Tutti i campi sono obbligatori';
    } elseif (!validateEmail($email)) {
        $error = 'Email non valida';
    } elseif (!validatePassword($password)) {
        $error = 'La password deve essere di almeno 6 caratteri';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non coincidono';
    } else {
        try {
            // Verifica se email già esistente
            $checkEmail = fetchOne($pdo, "SELECT idUtente FROM UTENTE WHERE email = ?", [$email]);
            
            if ($checkEmail) {
                $error = 'Email già registrata';
            } else {
                // Inizia transazione
                $pdo->beginTransaction();
                
                // Crea utente
                $sqlUtente = "INSERT INTO UTENTE (nome, cognome, email, password, ruolo) 
                              VALUES (?, ?, ?, ?, 'cliente')";
                
                $hashedPassword = hashPassword($password);
                $idUtente = insertAndGetId($pdo, $sqlUtente, [$nome, $cognome, $email, $hashedPassword]);
                
                // Genera nickname
                $nickname = generaNickname($nome, $cognome);
                
                // Crea cliente associato
                $sqlCliente = "INSERT INTO CLIENTE (idUtente, nome, nickname, email, occasionale) 
                               VALUES (?, ?, ?, ?, FALSE)";
                
                $nomeCompleto = $nome . ' ' . $cognome;
                executeQuery($pdo, $sqlCliente, [$idUtente, $nomeCompleto, $nickname, $email]);
                
                // Commit transazione
                $pdo->commit();
                
                $success = true;
            }
        } catch (Exception $e) {
            // Rollback in caso di errore
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Errore registrazione: " . $e->getMessage());
            $error = 'Errore durante la registrazione. Riprova più tardi.';
        }
    }
}

$pageTitle = 'Registrati';
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
            <h1 class="auth-title">Crea Account</h1>
            <p class="auth-subtitle">Registrati per acquistare i nostri prodotti</p>
        </div>
        
        <div class="auth-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <div class="alert-content">
                        <p class="alert-title">Registrazione completata!</p>
                        <p>Il tuo account è stato creato con successo.</p>
                        <p style="margin-top: 1rem;">
                            <a href="/login.php" class="btn btn-primary">Accedi ora</a>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="/registrazione.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome" class="form-label required">Nome</label>
                            <input 
                                type="text" 
                                id="nome" 
                                name="nome" 
                                class="form-input" 
                                placeholder="Mario"
                                value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                                required
                                autocomplete="given-name"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="cognome" class="form-label required">Cognome</label>
                            <input 
                                type="text" 
                                id="cognome" 
                                name="cognome" 
                                class="form-input" 
                                placeholder="Rossi"
                                value="<?php echo htmlspecialchars($_POST['cognome'] ?? ''); ?>"
                                required
                                autocomplete="family-name"
                            >
                        </div>
                    </div>
                    
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
                            placeholder="Minimo 6 caratteri"
                            required
                            autocomplete="new-password"
                            minlength="6"
                        >
                        <small class="form-help">Minimo 6 caratteri</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm" class="form-label required">Conferma Password</label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            class="form-input" 
                            placeholder="Ripeti la password"
                            required
                            autocomplete="new-password"
                            minlength="6"
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Crea Account
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="auth-footer">
            <p>
                Hai già un account? 
                <a href="/login.php">Accedi</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
