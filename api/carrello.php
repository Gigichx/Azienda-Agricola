<?php
/**
 * API CARRELLO — FIXED
 *
 * Fix: checkGiacenzaConfezionamento ora verifica la giacenza
 * considerando anche quanto già presente nel carrello
 * per lo stesso confezionamento, evitando di aggiungere
 * più pezzi di quelli disponibili.
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$action = $_POST['action'] ?? '';

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

        // Controlla giacenza disponibile
        $giacenzaDB = getGiacenzaConfezionamento($conn, $idConfezionamento);
        if ($giacenzaDB <= 0) {
            redirectWithMessage(
                '/cliente/prodotto.php?id=' . $idProdotto,
                'Prodotto esaurito',
                'error'
            );
        }

        // Calcola quanto è già nel carrello per questo confezionamento
        $giaInCarrello = 0;
        $foundKey      = null;
        foreach ($_SESSION['carrello'] as $k => $item) {
            if ((int)$item['idConfezionamento'] === $idConfezionamento) {
                $giaInCarrello = (int)$item['quantita'];
                $foundKey      = $k;
                break;
            }
        }

        $totaleRichiesto = $giaInCarrello + $quantita;

        if ($totaleRichiesto > $giacenzaDB) {
            $disponibileAggiungibile = $giacenzaDB - $giaInCarrello;
            if ($disponibileAggiungibile <= 0) {
                redirectWithMessage(
                    '/cliente/prodotto.php?id=' . $idProdotto,
                    'Hai già aggiunto tutte le confezioni disponibili al carrello',
                    'warning'
                );
            }
            // Aggiunge solo quello che rimane
            $quantita = $disponibileAggiungibile;
        }

        if ($foundKey !== null) {
            $_SESSION['carrello'][$foundKey]['quantita'] += $quantita;
        } else {
            $_SESSION['carrello'][] = [
                'idProdotto'        => $idProdotto,
                'idConfezionamento' => $idConfezionamento,
                'quantita'          => $quantita,
            ];
        }

        redirectWithMessage('/cliente/carrello.php', 'Prodotto aggiunto al carrello', 'success');
        break;

    case 'update':
        $key      = (int)($_POST['key'] ?? -1);
        $quantita = (int)($_POST['quantita'] ?? 1);

        if (!isset($_SESSION['carrello'][$key])) {
            header('Location: /cliente/carrello.php');
            exit;
        }

        $item      = $_SESSION['carrello'][$key];
        $giacenzaDB = getGiacenzaConfezionamento($conn, $item['idConfezionamento']);

        if ($quantita < 1) $quantita = 1;
        if ($quantita > $giacenzaDB) {
            $quantita = $giacenzaDB;
            redirectWithMessage(
                '/cliente/carrello.php',
                'Quantità ridotta alla giacenza disponibile (' . $giacenzaDB . ')',
                'warning'
            );
        }

        $_SESSION['carrello'][$key]['quantita'] = $quantita;
        redirectWithMessage('/cliente/carrello.php', 'Quantità aggiornata', 'success');
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
