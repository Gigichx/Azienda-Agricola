<?php
/**
 * API CARRELLO
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$action = $_POST['action'] ?? '';

// Inizializza carrello
if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

switch ($action) {

    case 'add':
        $idProdotto        = (int)($_POST['idProdotto'] ?? 0);
        $idConfezionamento = (int)($_POST['idConfezionamento'] ?? 0);
        $quantita          = (int)($_POST['quantita'] ?? 1);

        if (!$idProdotto || !$idConfezionamento || $quantita < 1) {
            redirectWithMessage('/cliente/catalogo.php', 'Dati non validi', 'error');
        }

        if (!checkGiacenzaConfezionamento($conn, $idConfezionamento, $quantita)) {
            redirectWithMessage('/cliente/prodotto.php?id=' . $idProdotto,
                'Giacenza insufficiente', 'error');
        }

        // Verifica se già presente nel carrello
        $found = false;
        foreach ($_SESSION['carrello'] as &$item) {
            if ($item['idConfezionamento'] == $idConfezionamento) {
                $item['quantita'] += $quantita;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['carrello'][] = [
                'idProdotto'        => $idProdotto,
                'idConfezionamento' => $idConfezionamento,
                'quantita'          => $quantita
            ];
        }

        redirectWithMessage('/cliente/carrello.php', 'Prodotto aggiunto al carrello', 'success');
        break;

    case 'update':
        $key      = (int)($_POST['key'] ?? -1);
        $quantita = (int)($_POST['quantita'] ?? 1);

        if (isset($_SESSION['carrello'][$key])) {
            $item = $_SESSION['carrello'][$key];

            if (!checkGiacenzaConfezionamento($conn, $item['idConfezionamento'], $quantita)) {
                redirectWithMessage('/cliente/carrello.php', 'Giacenza insufficiente', 'error');
            }

            $_SESSION['carrello'][$key]['quantita'] = $quantita;
            redirectWithMessage('/cliente/carrello.php', 'Quantità aggiornata', 'success');
        }

        header('Location: /cliente/carrello.php');
        break;

    case 'remove':
        $key = (int)($_POST['key'] ?? -1);

        if (isset($_SESSION['carrello'][$key])) {
            unset($_SESSION['carrello'][$key]);
            $_SESSION['carrello'] = array_values($_SESSION['carrello']);
            redirectWithMessage('/cliente/carrello.php', 'Prodotto rimosso dal carrello', 'success');
        }

        header('Location: /cliente/carrello.php');
        break;

    case 'clear':
        $_SESSION['carrello'] = [];
        redirectWithMessage('/cliente/carrello.php', 'Carrello svuotato', 'success');
        break;

    default:
        header('Location: /cliente/carrello.php');
        break;
}

exit;