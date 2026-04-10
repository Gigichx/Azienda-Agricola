<?php
/**
 * API PRODOTTI
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
        $idCategoria = (int)($_POST['idCategoria'] ?? 0);
        $unitaMisura = $_POST['unitaMisura'] ?? '';
        $prezzoBase  = (float)($_POST['prezzoBase'] ?? 0);

        if (empty($nome) || !$idCategoria || !in_array($unitaMisura, ['kg', 'litro', 'pezzo', 'grammo'])) {
            redirectWithMessage('/admin/prodotti.php', 'Dati non validi', 'error');
        }

        if (prodottoEsiste($conn, $nome)) {
            redirectWithMessage('/admin/prodotti.php', 'Prodotto già esistente', 'error');
        }

        $sql = "INSERT INTO PRODOTTO (nome, idCategoria, unitaMisura, prezzoBase)
                VALUES (?, ?, ?, ?)";
        executeQuery($conn, $sql, [$nome, $idCategoria, $unitaMisura, $prezzoBase]);

        redirectWithMessage('/admin/prodotti.php', 'Prodotto creato con successo', 'success');
        break;

    case 'update':
        $idProdotto  = (int)($_POST['idProdotto'] ?? 0);
        $nome        = sanitizeInput($_POST['nome'] ?? '');
        $idCategoria = (int)($_POST['idCategoria'] ?? 0);
        $unitaMisura = $_POST['unitaMisura'] ?? '';
        $prezzoBase  = (float)($_POST['prezzoBase'] ?? 0);

        if (!$idProdotto || empty($nome) || !$idCategoria) {
            redirectWithMessage('/admin/prodotti.php', 'Dati non validi', 'error');
        }

        if (prodottoEsiste($conn, $nome, $idProdotto)) {
            redirectWithMessage('/admin/prodotti.php', 'Nome prodotto già in uso', 'error');
        }

        $sql = "UPDATE PRODOTTO
                SET nome = ?, idCategoria = ?, unitaMisura = ?, prezzoBase = ?
                WHERE idProdotto = ?";
        executeQuery($conn, $sql, [$nome, $idCategoria, $unitaMisura, $prezzoBase, $idProdotto]);

        redirectWithMessage('/admin/prodotti.php', 'Prodotto aggiornato', 'success');
        break;

    case 'delete':
        $idProdotto = (int)($_POST['idProdotto'] ?? 0);

        if (!$idProdotto) {
            redirectWithMessage('/admin/prodotti.php', 'ID non valido', 'error');
        }

        // Verifica dipendenze — lavorazioni
        $check = fetchOne($conn, "SELECT COUNT(*) as c FROM LAVORAZIONE WHERE idProdotto = ?", [$idProdotto]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/prodotti.php',
                'Impossibile eliminare: il prodotto ha lavorazioni associate', 'error');
        }

        // Verifica dipendenze — confezionamenti
        $checkConf = fetchOne($conn, "SELECT COUNT(*) as c FROM CONFEZIONAMENTO WHERE idProdotto = ?", [$idProdotto]);
        if ($checkConf['c'] > 0) {
            redirectWithMessage('/admin/prodotti.php',
                'Impossibile eliminare: il prodotto ha confezionamenti associati', 'error');
        }

        // Verifica dipendenze — dettagli vendita (via confezionamento)
        $checkVendita = fetchOne($conn,
            "SELECT COUNT(*) as c FROM DETTAGLIO_VENDITA dv
             INNER JOIN CONFEZIONAMENTO c ON dv.idConfezionamento = c.idConfezionamento
             WHERE c.idProdotto = ?", [$idProdotto]);
        if ($checkVendita['c'] > 0) {
            redirectWithMessage('/admin/prodotti.php',
                'Impossibile eliminare: il prodotto ha vendite associate', 'error');
        }

        executeQuery($conn, "DELETE FROM PRODOTTO WHERE idProdotto = ?", [$idProdotto]);
        redirectWithMessage('/admin/prodotti.php', 'Prodotto eliminato', 'success');
        break;

    default:
        header('Location: /admin/prodotti.php');
        exit;
}