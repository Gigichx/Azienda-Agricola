<?php
/**
 * Connessione al Database - PDO
 * Azienda Agricola
 */

// Configurazione database
define('DB_HOST', 'localhost');
define('DB_NAME', 'azienda_agricola');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Creazione connessione PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // In produzione, loggare l'errore senza mostrarlo
    error_log("Errore connessione database: " . $e->getMessage());
    die("Errore di connessione al database. Riprova più tardi.");
}

/**
 * Funzione helper per eseguire query preparate
 * 
 * @param PDO $pdo Connessione database
 * @param string $sql Query SQL
 * @param array $params Parametri per prepared statement
 * @return PDOStatement
 */
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Errore query: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Funzione per ottenere un singolo record
 * 
 * @param PDO $pdo Connessione database
 * @param string $sql Query SQL
 * @param array $params Parametri
 * @return array|false
 */
function fetchOne($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetch();
}

/**
 * Funzione per ottenere tutti i record
 * 
 * @param PDO $pdo Connessione database
 * @param string $sql Query SQL
 * @param array $params Parametri
 * @return array
 */
function fetchAll($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchAll();
}

/**
 * Funzione per inserire e ottenere l'ID generato
 * 
 * @param PDO $pdo Connessione database
 * @param string $sql Query SQL
 * @param array $params Parametri
 * @return string Last insert ID
 */
function insertAndGetId($pdo, $sql, $params = []) {
    executeQuery($pdo, $sql, $params);
    return $pdo->lastInsertId();
}
