<?php
/**
 * Gestione Autenticazione e Sessioni
 * Azienda Agricola
 */

// Avvia sessione se non già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se l'utente è autenticato (oppure guest/occasionale)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Verifica se l'utente è un cliente guest (occasionale senza account)
 */
function isGuest() {
    return isset($_SESSION['guest']) && $_SESSION['guest'] === true;
}

/**
 * Verifica se può accedere all'area cliente (loggato o guest)
 */
function canAccessCliente() {
    return isCliente() || isGuest();
}

/**
 * Verifica se l'utente è admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Verifica se l'utente è cliente registrato
 */
function isCliente() {
    return isLoggedIn() && $_SESSION['user_role'] === 'cliente';
}

/**
 * Ottiene l'ID utente dalla sessione
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottiene il ruolo utente dalla sessione
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Ottiene il nome completo utente dalla sessione
 */
function getUserName() {
    if (isGuest()) return 'Ospite';
    return $_SESSION['user_name'] ?? 'Utente';
}

/**
 * Ottiene l'email utente dalla sessione
 */
function getUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Ottiene l'ID cliente associato (solo per utenti cliente registrati)
 */
function getClienteId() {
    return $_SESSION['cliente_id'] ?? null;
}

/**
 * Effettua il login dell'utente
 */
function login($user) {
    // Rigenera session ID per sicurezza
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['idUtente'];
    $_SESSION['user_role']  = $user['ruolo'];
    $_SESSION['user_name']  = $user['nome'] . ' ' . $user['cognome'];
    $_SESSION['user_email'] = $user['email'];
    unset($_SESSION['guest']); // rimuovi eventuale sessione guest

    // Se è un cliente, salva anche l'idCliente
    if ($user['ruolo'] === 'cliente' && isset($user['idCliente'])) {
        $_SESSION['cliente_id'] = $user['idCliente'];
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Inizializza sessione guest (cliente occasionale senza account)
 */
function loginGuest() {
    session_regenerate_id(true);
    $_SESSION['guest']         = true;
    $_SESSION['last_activity'] = time();
    unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name'],
          $_SESSION['user_email'], $_SESSION['cliente_id']);
}

/**
 * Effettua il logout dell'utente
 */
function logout() {
    session_unset();
    session_destroy();
    session_start();
}

/**
 * Richiede autenticazione cliente o guest - redirect se non autorizzato
 */
function requireCliente($redirectTo = '/login.php') {
    if (!canAccessCliente()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Richiede ruolo admin - redirect se non autorizzato
 */
function requireAdmin($redirectTo = '/login.php') {
    if (!isAdmin()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Richiede autenticazione - redirect se non loggato
 */
function requireAuth($redirectTo = '/login.php') {
    if (!isLoggedIn() && !isGuest()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Redirect basato sul ruolo utente
 */
function redirectByRole() {
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }

    if (isAdmin()) {
        header("Location: /admin/index.php");
        exit;
    }

    if (isCliente()) {
        header("Location: /cliente/catalogo.php");
        exit;
    }

    // fallback
    header("Location: /login.php");
    exit;
}

/**
 * Verifica timeout sessione (30 minuti di inattività)
 */
function checkSessionTimeout($timeout = 1800) {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            logout();
            header("Location: /login.php?timeout=1");
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Genera CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
