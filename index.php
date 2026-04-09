<?php
/**
 * INDEX.PHP - Entry Point
 * Azienda Agricola
 * 
 * Redirect automatico basato sullo stato di autenticazione
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Se l'utente è loggato, redirect basato sul ruolo
if (isLoggedIn()) {
    redirectByRole();
} else {
    // Altrimenti vai al login
    header("Location: /login.php");
    exit;
}
