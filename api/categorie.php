<?php
/**
 * API CATEGORIE
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create':
        $nome       = sanitizeInput($_POST['nome'] ?? '');
        $descrizione = sanitizeInput($_POST['descrizione'] ?? '');

        if (empty($nome)) {
            redirectWithMessage('/admin/categorie.php', 'Nome richiesto', 'error');
        }

        if (categoriaEsiste($conn, $nome)) {
            redirectWithMessage('/admin/categorie.php', 'Categoria già esistente', 'error');
        }

        $sql = "INSERT INTO CATEGORIA (nome, descrizione) VALUES (?, ?)";
        executeQuery($conn, $sql, [$nome, $descrizione]);

        redirectWithMessage('/admin/categorie.php', 'Categoria creata con successo', 'success');
        break;

    case 'update':
        $idCategoria = (int)($_POST['idCategoria'] ?? 0);
        $nome        = sanitizeInput($_POST['nome'] ?? '');
        $descrizione = sanitizeInput($_POST['descrizione'] ?? '');

        if (!$idCategoria || empty($nome)) {
            redirectWithMessage('/admin/categorie.php', 'Dati non validi', 'error');
        }

        if (categoriaEsiste($conn, $nome, $idCategoria)) {
            redirectWithMessage('/admin/categorie.php', 'Nome già in uso', 'error');
        }

        $sql = "UPDATE CATEGORIA SET nome = ?, descrizione = ? WHERE idCategoria = ?";
        executeQuery($conn, $sql, [$nome, $descrizione, $idCategoria]);

        redirectWithMessage('/admin/categorie.php', 'Categoria aggiornata', 'success');
        break;

    case 'delete':
        $idCategoria = (int)($_POST['idCategoria'] ?? 0);

        if (!$idCategoria) {
            redirectWithMessage('/admin/categorie.php', 'ID non valido', 'error');
        }

        // Verifica che non ci siano prodotti associati
        $check = fetchOne($conn, "SELECT COUNT(*) as c FROM PRODOTTO WHERE idCategoria = ?", [$idCategoria]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/categorie.php',
                'Impossibile eliminare: la categoria ha prodotti associati', 'error');
        }

        executeQuery($conn, "DELETE FROM CATEGORIA WHERE idCategoria = ?", [$idCategoria]);
        redirectWithMessage('/admin/categorie.php', 'Categoria eliminata', 'success');
        break;

    default:
        header('Location: /admin/categorie.php');
        exit;
}