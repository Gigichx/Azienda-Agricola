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
 * Verifica se l'utente è autenticato
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Verifica se l'utente è admin
 * 
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Verifica se l'utente è cliente
 * 
 * @return bool
 */
function isCliente() {
    return isLoggedIn() && $_SESSION['user_role'] === 'cliente';
}

/**
 * Ottiene l'ID utente dalla sessione
 * 
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottiene il ruolo utente dalla sessione
 * 
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Ottiene il nome completo utente dalla sessione
 * 
 * @return string
 */
function getUserName() {
    return $_SESSION['user_name'] ?? 'Utente';
}

/**
 * Ottiene l'email utente dalla sessione
 * 
 * @return string|null
 */
function getUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Ottiene l'ID cliente associato (solo per utenti cliente)
 * 
 * @return int|null
 */
function getClienteId() {
    return $_SESSION['cliente_id'] ?? null;
}

/**
 * Effettua il login dell'utente
 * 
 * @param array $user Dati utente dal database
 */
function login($user) {
    $_SESSION['user_id'] = $user['idUtente'];
    $_SESSION['user_role'] = $user['ruolo'];
    $_SESSION['user_name'] = $user['nome'] . ' ' . $user['cognome'];
    $_SESSION['user_email'] = $user['email'];
    
    // Se è un cliente, salva anche l'idCliente
    if ($user['ruolo'] === 'cliente' && isset($user['idCliente'])) {
        $_SESSION['cliente_id'] = $user['idCliente'];
    }
    
    // Timestamp ultimo accesso
    $_SESSION['last_activity'] = time();
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
 * Richiede autenticazione - redirect se non loggato
 * 
 * @param string $redirectTo URL di redirect (default: login.php)
 */
function requireAuth($redirectTo = '/login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Richiede ruolo admin - redirect se non autorizzato
 * 
 * @param string $redirectTo URL di redirect
 */
function requireAdmin($redirectTo = '/login.php') {
    if (!isAdmin()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Richiede ruolo cliente - redirect se non autorizzato
 * 
 * @param string $redirectTo URL di redirect
 */
function requireCliente($redirectTo = '/login.php') {
    if (!isCliente()) {
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
}

/**
 * Verifica timeout sessione (30 minuti di inattività)
 * 
 * @param int $timeout Secondi di timeout (default: 1800 = 30 minuti)
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
 * 
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica CSRF token
 * 
 * @param string $token Token da verificare
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
