<?php
/**
 * API CLIENTI
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create':
        $nome        = sanitizeInput($_POST['nome'] ?? '');
        $nickname    = sanitizeInput($_POST['nickname'] ?? '');
        $telefono    = sanitizeInput($_POST['telefono'] ?? '');
        $email       = sanitizeInput($_POST['email'] ?? '');
        $occasionale = isset($_POST['occasionale']) && $_POST['occasionale'] == '1';

        if (empty($nome)) {
            redirectWithMessage('/admin/clienti.php', 'Nome cliente obbligatorio', 'error');
        }

        // Verifica duplicato email (solo se fornita)
        if ($email) {
            $check = fetchOne($conn, "SELECT COUNT(*) as c FROM CLIENTE WHERE email = ?", [$email]);
            if ($check['c'] > 0) {
                redirectWithMessage('/admin/clienti.php', 'Email già in uso da un altro cliente', 'error');
            }
        }

        $sql = "INSERT INTO CLIENTE (idUtente, nome, nickname, telefono, email, occasionale)
                VALUES (NULL, ?, ?, ?, ?, ?)";
        executeQuery($conn, $sql, [
            $nome,
            $nickname ?: null,
            $telefono ?: null,
            $email ?: null,
            $occasionale ? 1 : 0
        ]);

        redirectWithMessage('/admin/clienti.php', 'Cliente creato con successo', 'success');
        break;

    case 'delete':
        $idCliente = (int)($_POST['idCliente'] ?? 0);

        if (!$idCliente) {
            redirectWithMessage('/admin/clienti.php', 'ID non valido', 'error');
        }

        // Verifica che non abbia vendite
        $check = fetchOne($conn, "SELECT COUNT(*) as c FROM VENDITA WHERE idCliente = ?", [$idCliente]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/clienti.php',
                'Impossibile eliminare: il cliente ha vendite associate', 'error');
        }

        executeQuery($conn, "DELETE FROM CLIENTE WHERE idCliente = ?", [$idCliente]);
        redirectWithMessage('/admin/clienti.php', 'Cliente eliminato', 'success');
        break;

    default:
        header('Location: /admin/clienti.php');
        exit;
}
