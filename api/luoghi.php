<?php
/**
 * API LUOGHI
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create':
        $nome      = sanitizeInput($_POST['nome'] ?? '');
        $tipo      = $_POST['tipo'] ?? '';
        $indirizzo = sanitizeInput($_POST['indirizzo'] ?? '');

        $tipiValidi = ['campo', 'laboratorio', 'punto vendita', 'magazzino'];
        if (empty($nome) || !in_array($tipo, $tipiValidi)) {
            redirectWithMessage('/admin/luoghi.php', 'Dati non validi', 'error');
        }

        $check = fetchOne($conn, "SELECT COUNT(*) as c FROM LUOGO WHERE nome = ?", [$nome]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/luoghi.php', 'Luogo già esistente con questo nome', 'error');
        }

        $sql = "INSERT INTO LUOGO (nome, indirizzo, tipo) VALUES (?, ?, ?)";
        executeQuery($conn, $sql, [$nome, $indirizzo ?: null, $tipo]);

        redirectWithMessage('/admin/luoghi.php', 'Luogo creato con successo', 'success');
        break;

    case 'update':
        $idLuogo   = (int)($_POST['idLuogo'] ?? 0);
        $nome      = sanitizeInput($_POST['nome'] ?? '');
        $tipo      = $_POST['tipo'] ?? '';
        $indirizzo = sanitizeInput($_POST['indirizzo'] ?? '');

        $tipiValidi = ['campo', 'laboratorio', 'punto vendita', 'magazzino'];
        if (!$idLuogo || empty($nome) || !in_array($tipo, $tipiValidi)) {
            redirectWithMessage('/admin/luoghi.php', 'Dati non validi', 'error');
        }

        $check = fetchOne($conn,
            "SELECT COUNT(*) as c FROM LUOGO WHERE nome = ? AND idLuogo != ?",
            [$nome, $idLuogo]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/luoghi.php', 'Nome già in uso da un altro luogo', 'error');
        }

        $sql = "UPDATE LUOGO SET nome = ?, indirizzo = ?, tipo = ? WHERE idLuogo = ?";
        executeQuery($conn, $sql, [$nome, $indirizzo ?: null, $tipo, $idLuogo]);

        redirectWithMessage('/admin/luoghi.php', 'Luogo aggiornato', 'success');
        break;

    case 'delete':
        $idLuogo = (int)($_POST['idLuogo'] ?? 0);

        if (!$idLuogo) {
            redirectWithMessage('/admin/luoghi.php', 'ID non valido', 'error');
        }

        $checks = [
            ["SELECT COUNT(*) as c FROM LAVORAZIONE WHERE idLuogo = ?",    'lavorazioni'],
            ["SELECT COUNT(*) as c FROM CONFEZIONAMENTO WHERE idLuogo = ?", 'confezionamenti'],
            ["SELECT COUNT(*) as c FROM VENDITA WHERE idLuogo = ?",         'vendite'],
            ["SELECT COUNT(*) as c FROM DISPENSA WHERE idLuogo = ?",        'dispense'],
        ];
        foreach ($checks as [$sql, $label]) {
            $res = fetchOne($conn, $sql, [$idLuogo]);
            if ($res['c'] > 0) {
                redirectWithMessage('/admin/luoghi.php',
                    "Impossibile eliminare: il luogo ha $label associati", 'error');
            }
        }

        executeQuery($conn, "DELETE FROM LUOGO WHERE idLuogo = ?", [$idLuogo]);
        redirectWithMessage('/admin/luoghi.php', 'Luogo eliminato', 'success');
        break;

    case 'create_dispensa':
        $nomeDispensa = sanitizeInput($_POST['nomeDispensa'] ?? '');
        $idLuogo      = (int)($_POST['idLuogo'] ?? 0);
        $ubicazione   = sanitizeInput($_POST['ubicazione'] ?? '');

        if (empty($nomeDispensa) || !$idLuogo) {
            redirectWithMessage('/admin/luoghi.php', 'Dati non validi', 'error');
        }

        $check = fetchOne($conn, "SELECT COUNT(*) as c FROM DISPENSA WHERE nome = ? AND idLuogo = ?", [$nomeDispensa, $idLuogo]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/luoghi.php', 'Dispensa già esistente in questo luogo', 'error');
        }

        $sql = "INSERT INTO DISPENSA (nome, ubicazione, idLuogo) VALUES (?, ?, ?)";
        executeQuery($conn, $sql, [$nomeDispensa, $ubicazione ?: null, $idLuogo]);
        redirectWithMessage('/admin/luoghi.php', 'Dispensa creata con successo', 'success');
        break;

    default:
        header('Location: /admin/luoghi.php');
        exit;
}
