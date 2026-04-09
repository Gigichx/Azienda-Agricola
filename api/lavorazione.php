<?php
/**
 * API LAVORAZIONE
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $idProdotto = (int)($_POST['idProdotto'] ?? 0);
    $idLuogo = (int)($_POST['idLuogo'] ?? 0);
    $tipoLavorazione = sanitizeInput($_POST['tipoLavorazione'] ?? '');
    $dataLavorazione = $_POST['dataLavorazione'] ?? date('Y-m-d');
    $quantitaIngresso = (float)($_POST['quantitaIngresso'] ?? 0);
    $quantitaOttenuta = (float)($_POST['quantitaOttenuta'] ?? 0);
    
    if (!$idProdotto || !$idLuogo || empty($tipoLavorazione) || $quantitaIngresso <= 0 || $quantitaOttenuta <= 0) {
        redirectWithMessage('/admin/lavorazione.php', 'Dati non validi', 'error');
    }
    
    $sql = "INSERT INTO LAVORAZIONE (tipoLavorazione, dataLavorazione, quantitaIngresso, quantitaOttenuta, idProdotto, idLuogo)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    executeQuery($pdo, $sql, [$tipoLavorazione, $dataLavorazione, $quantitaIngresso, $quantitaOttenuta, $idProdotto, $idLuogo]);
    
    redirectWithMessage('/admin/lavorazione.php', 'Lavorazione registrata con successo', 'success');
} else {
    header('Location: /admin/lavorazione.php');
    exit;
}
