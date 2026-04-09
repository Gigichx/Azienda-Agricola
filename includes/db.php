<?php
/**
 * Connessione al Database - PDO
 * Azienda Agricola
 */

// Configurazione database
$host     = 'db';           // nome del servizio nel docker-compose
$dbname = 'azienda_agricola';
$user     = 'myuser';       // MYSQL_USER
$password = 'mypassword';   // MYSQL_PASSWORD

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
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
