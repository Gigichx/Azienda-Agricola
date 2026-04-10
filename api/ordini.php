<?php
/**
 * API ORDINI
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Solo clienti registrati possono creare ordini (non guest)
if (!isCliente()) {
    if (isGuest()) {
        redirectWithMessage('/cliente/carrello.php', 'Devi effettuare il login per completare l\'ordine', 'warning');
    } else {
        header('Location: /login.php');
        exit;
    }
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {

    // Verifica carrello
    if (!isset($_SESSION['carrello']) || empty($_SESSION['carrello'])) {
        redirectWithMessage('/cliente/catalogo.php', 'Carrello vuoto', 'error');
    }

    $note    = sanitizeInput($_POST['note'] ?? '');
    $carrello = $_SESSION['carrello'];

    // IVA
    $ivaPerc = 22;

    // Inizia transazione
    mysqli_begin_transaction($conn);

    try {
        // Ottieni ID cliente
        $sqlCliente  = "SELECT idCliente FROM CLIENTE WHERE idUtente = ?";
        $clienteData = fetchOne($conn, $sqlCliente, [getUserId()]);

        if (!$clienteData) {
            throw new Exception('Cliente non trovato');
        }

        $idCliente = $clienteData['idCliente'];

        // Calcola imponibile e verifica giacenze
        $imponibile     = 0;
        $dettagliOrdine = [];

        foreach ($carrello as $item) {
            $sql = "SELECT conf.prezzo, conf.giacenzaAttuale, p.idProdotto
                    FROM CONFEZIONAMENTO conf
                    INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
                    WHERE conf.idConfezionamento = ?
                    FOR UPDATE";

            $confData = fetchOne($conn, $sql, [$item['idConfezionamento']]);

            if (!$confData) {
                throw new Exception('Prodotto non trovato');
            }

            if ($confData['giacenzaAttuale'] < $item['quantita']) {
                throw new Exception('Giacenza insufficiente per uno o più prodotti');
            }

            $subtotale   = $confData['prezzo'] * $item['quantita'];
            $imponibile += $subtotale;

            $dettagliOrdine[] = [
                'idProdotto'        => $confData['idProdotto'],
                'idConfezionamento' => $item['idConfezionamento'],
                'quantita'          => $item['quantita'],
                'prezzo'            => $confData['prezzo']
            ];
        }

        // Totale con IVA 22%
        $ivaAmt    = round($imponibile * $ivaPerc / 100, 2);
        $totaleCon = round($imponibile + $ivaAmt, 2);

        // Trova luogo "punto vendita"
        $luogoData = fetchOne($conn, "SELECT idLuogo FROM LUOGO WHERE tipo = 'punto vendita' LIMIT 1");
        if (!$luogoData) {
            $luogoData = fetchOne($conn, "SELECT idLuogo FROM LUOGO LIMIT 1");
        }
        $idLuogo = $luogoData['idLuogo'];

        // Crea vendita — salviamo totale IVA inclusa in totalePagato
        $sqlVendita = "INSERT INTO VENDITA (dataVendita, totaleCalcolato, totalePagato, note, idCliente, idLuogo)
                       VALUES (NOW(), ?, ?, ?, ?, ?)";

        $idVendita = insertAndGetId($conn, $sqlVendita, [
            $imponibile, $totaleCon, $note, $idCliente, $idLuogo
        ]);

        // Inserisci dettagli e scala giacenze
        foreach ($dettagliOrdine as $det) {
            $sqlDettaglio = "INSERT INTO DETTAGLIO_VENDITA
                            (idVendita, tipoVendita, quantita, prezzoUnitario, omaggio, idProdotto, idConfezionamento)
                            VALUES (?, 'CONFEZIONATO', ?, ?, FALSE, ?, ?)";

            executeQuery($conn, $sqlDettaglio, [
                $idVendita,
                $det['quantita'],
                $det['prezzo'],
                $det['idProdotto'],
                $det['idConfezionamento']
            ]);

            // Scala giacenza
            if (!scalaGiacenzaConfezionamento($conn, $det['idConfezionamento'], $det['quantita'])) {
                throw new Exception('Errore scalatura giacenza');
            }
        }

        // Commit
        mysqli_commit($conn);

        // Svuota carrello e redirect
        $_SESSION['carrello']          = [];
        $_SESSION['ordine_confermato'] = $idVendita;
        header('Location: /cliente/ordine-confermato.php');
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Errore creazione ordine: " . $e->getMessage());
        redirectWithMessage('/cliente/checkout.php',
            'Errore durante la creazione dell\'ordine: ' . $e->getMessage(), 'error');
    }

} else {
    header('Location: /cliente/catalogo.php');
    exit;
}
