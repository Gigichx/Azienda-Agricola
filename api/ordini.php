<?php
/**
 * API ORDINI — FIXED
 *
 * Fix principale: rimosso "FOR UPDATE" dalla query dentro fetchOne().
 * MySQLi + prepared statements non supportano FOR UPDATE dentro
 * mysqli_stmt_get_result(). La query restituiva NULL e l'ordine
 * falliva silenziosamente con "Prodotto non trovato".
 *
 * Il lock è ora gestito con una transazione InnoDB normale:
 * InnoDB blocca le righe in UPDATE tramite la transazione attiva,
 * quindi la consistenza è garantita senza FOR UPDATE esplicito.
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isCliente()) {
    if (isGuest()) {
        redirectWithMessage('/cliente/carrello.php',
            'Devi effettuare il login per completare l\'ordine', 'warning');
    } else {
        header('Location: /login.php');
        exit;
    }
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {

    if (!isset($_SESSION['carrello']) || empty($_SESSION['carrello'])) {
        redirectWithMessage('/cliente/catalogo.php', 'Carrello vuoto', 'error');
    }

    $note    = sanitizeInput($_POST['note'] ?? '');
    $carrello = $_SESSION['carrello'];
    $ivaPerc  = 22;

    mysqli_begin_transaction($conn);

    try {
        // Ottieni ID cliente
        $clienteData = fetchOne($conn,
            "SELECT idCliente FROM CLIENTE WHERE idUtente = ?",
            [getUserId()]
        );

        if (!$clienteData) {
            throw new Exception('Cliente non trovato');
        }

        $idCliente = $clienteData['idCliente'];

        // Verifica giacenze e calcola importo
        // FIXED: rimosso FOR UPDATE — incompatibile con MySQLi fetchOne
        $imponibile     = 0;
        $dettagliOrdine = [];

        foreach ($carrello as $item) {
            // Query senza FOR UPDATE — il lock è garantito dalla transazione
            $confData = fetchOne($conn,
                "SELECT c.prezzo, c.giacenzaAttuale, c.idProdotto
                 FROM CONFEZIONAMENTO c
                 WHERE c.idConfezionamento = ?",
                [$item['idConfezionamento']]
            );

            if (!$confData) {
                throw new Exception('Prodotto non trovato (ID conf: ' . $item['idConfezionamento'] . ')');
            }

            if ((int)$confData['giacenzaAttuale'] < (int)$item['quantita']) {
                throw new Exception(
                    'Giacenza insufficiente. Disponibili: ' .
                    $confData['giacenzaAttuale'] . ' pz'
                );
            }

            $subtotale   = $confData['prezzo'] * $item['quantita'];
            $imponibile += $subtotale;

            $dettagliOrdine[] = [
                'idProdotto'        => $confData['idProdotto'],
                'idConfezionamento' => $item['idConfezionamento'],
                'quantita'          => (int)$item['quantita'],
                'prezzo'            => (float)$confData['prezzo'],
            ];
        }

        $ivaAmt    = round($imponibile * $ivaPerc / 100, 2);
        $totaleCon = round($imponibile + $ivaAmt, 2);

        // Trova luogo punto vendita
        $luogoData = fetchOne($conn,
            "SELECT idLuogo FROM LUOGO WHERE tipo = 'punto vendita' LIMIT 1"
        );
        if (!$luogoData) {
            $luogoData = fetchOne($conn, "SELECT idLuogo FROM LUOGO LIMIT 1");
        }
        if (!$luogoData) {
            throw new Exception('Nessun luogo configurato nel sistema');
        }
        $idLuogo = $luogoData['idLuogo'];

        // Crea vendita
        $sqlVendita = "INSERT INTO VENDITA
                           (dataVendita, totaleCalcolato, totalePagato, note, idCliente, idLuogo)
                       VALUES (NOW(), ?, ?, ?, ?, ?)";

        $idVendita = insertAndGetId($conn, $sqlVendita, [
            $imponibile, $totaleCon, $note, $idCliente, $idLuogo
        ]);

        if (!$idVendita) {
            throw new Exception('Errore creazione vendita');
        }

        // Inserisci dettagli e scala giacenze atomicamente
        foreach ($dettagliOrdine as $det) {
            $sqlDettaglio = "INSERT INTO DETTAGLIO_VENDITA
                                (idVendita, tipoVendita, quantita, prezzoUnitario,
                                 omaggio, idProdotto, idConfezionamento)
                             VALUES (?, 'CONFEZIONATO', ?, ?, FALSE, ?, ?)";

            executeQuery($conn, $sqlDettaglio, [
                $idVendita,
                $det['quantita'],
                $det['prezzo'],
                $det['idProdotto'],
                $det['idConfezionamento'],
            ]);

            // Scala giacenza — verifica di nuovo che sia sufficiente (race condition)
            $scaled = scalaGiacenzaConfezionamento(
                $conn,
                $det['idConfezionamento'],
                $det['quantita']
            );

            if (!$scaled) {
                throw new Exception(
                    'Giacenza esaurita durante la conferma ordine. ' .
                    'Riprova o riduci la quantità.'
                );
            }
        }

        mysqli_commit($conn);

        $_SESSION['carrello']          = [];
        $_SESSION['ordine_confermato'] = $idVendita;
        header('Location: /cliente/ordine-confermato.php');
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Errore creazione ordine: " . $e->getMessage());
        redirectWithMessage(
            '/cliente/checkout.php',
            'Errore: ' . $e->getMessage(),
            'error'
        );
    }

} else {
    header('Location: /cliente/catalogo.php');
    exit;
}
