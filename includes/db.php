<?php
/**
 * Connessione al Database - MySQLi procedurale
 */

$host     = 'db';
$dbname   = 'azienda_agricola';
$user     = 'myuser';
$password = 'mypassword';

// Connessione
$conn = mysqli_connect($host, $user, $password, $dbname);

// Controllo connessione
if (!$conn) {
    die("Errore di connessione: " . mysqli_connect_error());
}

// Imposta charset UTF-8
mysqli_set_charset($conn, 'utf8mb4');


/**
 * Esegue una query con prepared statement
 * Restituisce il risultato (mysqli_result o true)
 */
function executeQuery($conn, $sql, $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        error_log("Errore prepare: " . mysqli_error($conn));
        throw new Exception("Errore nella query");
    }
    
    // Bind parametri se presenti
    if (!empty($params)) {
        // Determina i tipi automaticamente
        $types = '';
        foreach ($params as $param) {
            if (is_int($param))    $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else                   $types .= 's';
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    return $stmt;
}


/**
 * Ritorna un singolo record come array associativo
 */
function fetchOne($conn, $sql, $params = []) {
    $stmt  = executeQuery($conn, $sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $row   = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row; // null se non trovato
}


/**
 * Ritorna tutti i record come array di array associativi
 */
function fetchAll($conn, $sql, $params = []) {
    $stmt   = executeQuery($conn, $sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}


/**
 * Esegue un INSERT e ritorna l'ID generato
 */
function insertAndGetId($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    $id   = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}