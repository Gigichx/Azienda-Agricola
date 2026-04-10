<?php
/**
 * API RISERVA
 * Azienda Agricola
 * Gestisce CRUD riserve in dispensa
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create':
        $nome             = sanitizeInput($_POST['nome'] ?? '');
        $idProdotto       = (int)($_POST['idProdotto'] ?? 0);
        $idDispensa       = (int)($_POST['idDispensa'] ?? 0);
        $dataProduzione   = $_POST['dataProduzione'] ?? date('Y-m-d');
        $quantitaIniziale = (float)($_POST['quantitaIniziale'] ?? 0);
        $prezzoAlKg       = (float)($_POST['prezzoAlKg'] ?? 0);
        $contenitore      = sanitizeInput($_POST['contenitore'] ?? '');
        $idLavorazione    = !empty($_POST['idLavorazione']) ? (int)$_POST['idLavorazione'] : null;

        if (empty($nome) || !$idProdotto || !$idDispensa || $quantitaIniziale <= 0) {
            redirectWithMessage('/admin/riserva.php', 'Dati non validi', 'error');
        }

        $sql = "INSERT INTO RISERVA
                    (nome, dataProduzione, quantitaIniziale, quantitaAttuale, prezzoAlKg, contenitore, idProdotto, idLavorazione, idDispensa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        executeQuery($pdo, $sql, [
            $nome, $dataProduzione, $quantitaIniziale, $quantitaIniziale,
            $prezzoAlKg, $contenitore ?: null, $idProdotto, $idLavorazione, $idDispensa
        ]);

        redirectWithMessage('/admin/riserva.php', 'Riserva creata con successo', 'success');
        break;

    case 'update':
        $idRiserva  = (int)($_POST['idRiserva'] ?? 0);
        $nome       = sanitizeInput($_POST['nome'] ?? '');
        $prezzoAlKg = (float)($_POST['prezzoAlKg'] ?? 0);
        $contenitore = sanitizeInput($_POST['contenitore'] ?? '');
        $idDispensa = (int)($_POST['idDispensa'] ?? 0);

        if (!$idRiserva || empty($nome) || !$idDispensa) {
            redirectWithMessage('/admin/riserva.php', 'Dati non validi', 'error');
        }

        $sql = "UPDATE RISERVA
                SET nome = ?, prezzoAlKg = ?, contenitore = ?, idDispensa = ?
                WHERE idRiserva = ?";
        executeQuery($pdo, $sql, [$nome, $prezzoAlKg, $contenitore ?: null, $idDispensa, $idRiserva]);

        redirectWithMessage('/admin/riserva.php', 'Riserva aggiornata', 'success');
        break;

    case 'delete':
        $idRiserva = (int)($_POST['idRiserva'] ?? 0);

        if (!$idRiserva) {
            redirectWithMessage('/admin/riserva.php', 'ID non valido', 'error');
        }

        // Verifica che non ci siano confezionamenti collegati
        $check = fetchOne($pdo,
            "SELECT COUNT(*) as c FROM CONFEZIONAMENTO WHERE idRiserva = ?",
            [$idRiserva]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/riserva.php',
                'Impossibile eliminare: la riserva ha confezionamenti associati', 'error');
        }

        executeQuery($pdo, "DELETE FROM RISERVA WHERE idRiserva = ?", [$idRiserva]);
        redirectWithMessage('/admin/riserva.php', 'Riserva eliminata', 'success');
        break;

    default:
        header('Location: /admin/riserva.php');
        exit;
}
