<?php
/**
 * API GIACENZE
 * Ritorna giacenze disponibili in formato JSON
 */

require_once '../includes/db.php';

header('Content-Type: application/json');

$idProdotto = isset($_GET['idProdotto']) ? (int)$_GET['idProdotto'] : 0;
$idConfezionamento = isset($_GET['idConfezionamento']) ? (int)$_GET['idConfezionamento'] : 0;

try {
    if ($idConfezionamento) {
        // Giacenza specifica confezionamento
        $sql = "SELECT giacenzaAttuale FROM CONFEZIONAMENTO WHERE idConfezionamento = ?";
        $result = fetchOne($pdo, $sql, [$idConfezionamento]);
        
        echo json_encode([
            'success' => true,
            'giacenza' => $result ? (int)$result['giacenzaAttuale'] : 0,
            'disponibile' => $result ? (int)$result['giacenzaAttuale'] : 0
        ]);
        
    } elseif ($idProdotto) {
        // Giacenza totale prodotto (somma tutte confezioni)
        $sql = "SELECT COALESCE(SUM(giacenzaAttuale), 0) as totale
                FROM CONFEZIONAMENTO
                WHERE idProdotto = ?";
        $result = fetchOne($pdo, $sql, [$idProdotto]);
        
        echo json_encode([
            'success' => true,
            'giacenza' => (int)$result['totale'],
            'disponibile' => (int)$result['totale']
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Parametri mancanti'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore server'
    ]);
}
