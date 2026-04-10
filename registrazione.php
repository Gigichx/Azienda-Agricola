<?php
/**
 * REGISTRAZIONE.PHP
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome             = sanitizeInput($_POST['nome'] ?? '');
    $cognome          = sanitizeInput($_POST['cognome'] ?? '');
    $email            = sanitizeInput($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $telefono         = sanitizeInput($_POST['telefono'] ?? '');

    if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        $error = 'Tutti i campi obbligatori devono essere compilati.';
    } elseif (!validateEmail($email)) {
        $error = 'Indirizzo email non valido.';
    } elseif (!validatePassword($password)) {
        $error = 'La password deve avere almeno 6 caratteri.';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non coincidono.';
    } else {
        try {
            $check = fetchOne($conn, "SELECT idUtente FROM UTENTE WHERE email = ?", [$email]);
            if ($check) {
                $error = 'Email già registrata. Prova ad accedere.';
            } else {
                mysqli_begin_transaction($conn);

                $sqlU = "INSERT INTO UTENTE (nome, cognome, email, password, ruolo)
                         VALUES (?, ?, ?, ?, 'cliente')";
                $idU  = insertAndGetId($conn, $sqlU, [$nome, $cognome, $email, hashPassword($password)]);

                $sqlC = "INSERT INTO CLIENTE (idUtente, nome, nickname, telefono, email, occasionale)
                         VALUES (?, ?, ?, ?, ?, FALSE)";
                executeQuery($conn, $sqlC, [
                    $idU,
                    $nome . ' ' . $cognome,
                    generaNickname($nome, $cognome),
                    $telefono ?: null,
                    $email,
                ]);

                mysqli_commit($conn);
                $success = true;
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("Errore registrazione: " . $e->getMessage());
            $error = 'Errore durante la registrazione. Riprova più tardi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati | Azienda Agricola</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/css/cliente.css">
</head>
<body class="bg-light">

<div class="auth-page">
    <div class="auth-card">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <div class="auth-logo-icon mb-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4 class="fw-semibold mb-1">Crea Account</h4>
                    <p class="text-muted small mb-0">Registrati per acquistare i nostri prodotti</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Registrazione completata!</strong><br>
                        <a href="/login.php" class="btn btn-success btn-sm mt-3">
                            <i class="fas fa-sign-in-alt me-1"></i>Accedi ora
                        </a>
                    </div>
                <?php else: ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small">
                            <i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/registrazione.php">
                        <div class="row g-3 mb-3">
                            <div class="col">
                                <label class="form-label small fw-semibold">Nome <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                                       placeholder="Mario" required autocomplete="given-name">
                            </div>
                            <div class="col">
                                <label class="form-label small fw-semibold">Cognome <span class="text-danger">*</span></label>
                                <input type="text" name="cognome" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['cognome'] ?? ''); ?>"
                                       placeholder="Rossi" required autocomplete="family-name">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="tua@email.it" required autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Telefono</label>
                            <input type="tel" name="telefono" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                   placeholder="333 1234567">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Minimo 6 caratteri" required minlength="6" autocomplete="new-password">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Conferma password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirm" class="form-control"
                                   placeholder="Ripeti la password" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-user-plus me-1"></i>Crea Account
                        </button>
                    </form>

                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent text-center py-3">
                <small class="text-muted">
                    Hai già un account?
                    <a href="/login.php" class="text-success fw-semibold">Accedi</a>
                </small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
