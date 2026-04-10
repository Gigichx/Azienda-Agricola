<?php
/**
 * LOGIN.PHP
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$timeoutMsg = '';

if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $timeoutMsg = 'La tua sessione è scaduta. Effettua nuovamente il login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Inserisci email e password.';
    } else {
        try {
            $sql  = "SELECT u.*, c.idCliente
                     FROM UTENTE u
                     LEFT JOIN CLIENTE c ON u.idUtente = c.idUtente
                     WHERE u.email = ? AND u.attivo = TRUE";
            $user = fetchOne($pdo, $sql, [$email]);

            if ($user && verifyPassword($password, $user['password'])) {
                login($user);
                redirectByRole();
            } else {
                $error = 'Email o password non corretti.';
            }
        } catch (Exception $e) {
            error_log("Errore login: " . $e->getMessage());
            $error = 'Errore durante il login. Riprova più tardi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi | Azienda Agricola</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/css/cliente.css">
</head>
<body class="bg-light">

<div class="auth-page">
    <div class="auth-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">

                <!-- Logo -->
                <div class="text-center mb-4">
                    <div class="auth-logo-icon mb-3">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h4 class="fw-semibold mb-1">Benvenuto</h4>
                    <p class="text-muted small mb-0">Accedi al tuo account</p>
                </div>

                <?php if ($timeoutMsg): ?>
                    <div class="alert alert-warning alert-sm py-2 small">
                        <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($timeoutMsg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-sm py-2 small">
                        <i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="tua@email.it"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required autocomplete="email">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-sign-in-alt me-1"></i>Accedi
                    </button>
                </form>

            </div>
            <div class="card-footer bg-transparent text-center py-3">
                <small class="text-muted">
                    Non hai un account?
                    <a href="/registrazione.php" class="text-success fw-semibold">Registrati</a>
                </small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
