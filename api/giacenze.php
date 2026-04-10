<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$idConfezionamento = isset($_GET['idConfezionamento']) ? (int)$_GET['idConfezionamento'] : 0;

if ($idConfezionamento) {
    $sql    = "SELECT giacenzaAttuale FROM CONFEZIONAMENTO WHERE idConfezionamento = ?";
    $result = fetchOne($conn, $sql, [$idConfezionamento]);

    echo json_encode([
        'success'     => true,
        'giacenza'    => $result ? (int)$result['giacenzaAttuale'] : 0,
        'disponibile' => $result ? (int)$result['giacenzaAttuale'] : 0
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti']);
}