<?php
/**
 * LOGOUT.PHP
 * Azienda Agricola
 */

require_once 'includes/auth.php';

// Esegui logout
logout();

// Redirect al login
header("Location: /login.php");
exit;
