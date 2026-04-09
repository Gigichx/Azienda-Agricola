<?php
/**
 * API ORDINI
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    // Verifica carrello
    if (!isset($_SESSION['carrello']) || empty($_SESSION['carrello'])) {
        redirectWithMessage('/cliente/catalogo.php', 'Carrello vuoto', 'error');
    }
    
    $note = sanitizeInput($_POST['note'] ?? '');
    $carrello = $_SESSION['carrello'];
    
    try {
        // Inizia transazione
        $pdo->beginTransaction();
        
        // Ottieni ID cliente
        $sqlCliente = "SELECT idCliente FROM CLIENTE WHERE idUtente = ?";
        $clienteData = fetchOne($pdo, $sqlCliente, [getUserId()]);
        
        if (!$clienteData) {
            throw new Exception('Cliente non trovato');
        }
        
        $idCliente = $clienteData['idCliente'];
        
        // Calcola totale e verifica giacenze
        $totale = 0;
        $dettagliOrdine = [];
        
        foreach ($carrello as $item) {
            $sql = "SELECT conf.prezzo, conf.giacenzaAttuale, p.idProdotto
                    FROM CONFEZIONAMENTO conf
                    INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
                    WHERE conf.idConfezionamento = ?";
            
            $confData = fetchOne($pdo, $sql, [$item['idConfezionamento']]);
            
            if (!$confData) {
                throw new Exception('Prodotto non trovato');
            }
            
            // Verifica giacenza
            if ($confData['giacenzaAttuale'] < $item['quantita']) {
                throw new Exception('Giacenza insufficiente');
            }
            
            $subtotale = $confData['prezzo'] * $item['quantita'];
            $totale += $subtotale;
            
            $dettagliOrdine[] = [
                'idProdotto' => $confData['idProdotto'],
                'idConfezionamento' => $item['idConfezionamento'],
                'quantita' => $item['quantita'],
                'prezzo' => $confData['prezzo']
            ];
        }
        
        // Crea vendita (usa primo luogo disponibile - punto vendita)
        $luogoData = fetchOne($pdo, "SELECT idLuogo FROM LUOGO WHERE tipo = 'punto vendita' LIMIT 1");
        
        if (!$luogoData) {
            // Se non c'è punto vendita, usa il primo luogo disponibile
            $luogoData = fetchOne($pdo, "SELECT idLuogo FROM LUOGO LIMIT 1");
        }
        
        $idLuogo = $luogoData['idLuogo'];
        
        $sqlVendita = "INSERT INTO VENDITA (dataVendita, totaleCalcolato, totalePagato, note, idCliente, idLuogo)
                       VALUES (NOW(), ?, ?, ?, ?, ?)";
        
        $idVendita = insertAndGetId($pdo, $sqlVendita, [$totale, $totale, $note, $idCliente, $idLuogo]);
        
        // Inserisci dettagli e scala giacenze
        foreach ($dettagliOrdine as $det) {
            // Inserisci dettaglio
            $sqlDettaglio = "INSERT INTO DETTAGLIO_VENDITA 
                            (idVendita, tipoVendita, quantita, prezzoUnitario, omaggio, idProdotto, idConfezionamento)
                            VALUES (?, 'CONFEZIONATO', ?, ?, FALSE, ?, ?)";
            
            executeQuery($pdo, $sqlDettaglio, [
                $idVendita,
                $det['quantita'],
                $det['prezzo'],
                $det['idProdotto'],
                $det['idConfezionamento']
            ]);
            
            // Scala giacenza
            if (!scalaGiacenzaConfezionamento($pdo, $det['idConfezionamento'], $det['quantita'])) {
                throw new Exception('Errore scalatura giacenza');
            }
        }
        
        // Commit
        $pdo->commit();
        
        // Svuota carrello
        $_SESSION['carrello'] = [];
        
        // Redirect a pagina conferma
        $_SESSION['ordine_confermato'] = $idVendita;
        header('Location: /cliente/ordine-confermato.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Errore creazione ordine: " . $e->getMessage());
        redirectWithMessage('/cliente/checkout.php', 
            'Errore durante la creazione dell\'ordine: ' . $e->getMessage(), 'error');
    }
} else {
    header('Location: /cliente/catalogo.php');
    exit;
}
