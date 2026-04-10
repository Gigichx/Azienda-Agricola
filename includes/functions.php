<?php
/**
 * Funzioni Riutilizzabili Globali
 * Azienda Agricola
 */

/**
 * Formatta prezzo in euro
 */
function formatPrice($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

/**
 * Formatta data italiana
 */
function formatDate($date, $includeTime = false) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    if ($includeTime) {
        return date('d/m/Y H:i', $timestamp);
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Formatta peso con unità di misura
 */
function formatWeight($peso, $unita) {
    $peso = number_format($peso, 2, ',', '.');
    switch ($unita) {
        case 'kg':     return $peso . ' kg';
        case 'grammo': return $peso . ' g';
        case 'litro':  return $peso . ' L';
        case 'pezzo':  return $peso . ' pz';
        default:       return $peso;
    }
}

/**
 * Sanitizza input utente
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function alert($message, $type = 'info') {
    $class = 'alert alert-' . $type;
    return '<div class="' . $class . '">' . htmlspecialchars($message) . '</div>';
}

/**
 * Verifica disponibilità giacenza confezionamento
 * @param mysqli $conn Connessione MySQLi
 */
function checkGiacenzaConfezionamento($conn, $idConfezionamento, $quantita) {
    $sql    = "SELECT giacenzaAttuale FROM CONFEZIONAMENTO WHERE idConfezionamento = ?";
    $result = fetchOne($conn, $sql, [$idConfezionamento]);
    if (!$result) return false;
    return $result['giacenzaAttuale'] >= $quantita;
}

/**
 * Restituisce la giacenza attuale di un confezionamento
 * @param mysqli $conn
 */
function getGiacenzaConfezionamento($conn, $idConfezionamento) {
    $sql    = "SELECT giacenzaAttuale FROM CONFEZIONAMENTO WHERE idConfezionamento = ?";
    $result = fetchOne($conn, $sql, [$idConfezionamento]);
    return $result ? (int)$result['giacenzaAttuale'] : 0;
}

/**
 * Verifica disponibilità riserva
 * @param mysqli $conn Connessione MySQLi
 */
function checkGiacenzaRiserva($conn, $idRiserva, $pesoRichiesto) {
    $sql    = "SELECT quantitaAttuale FROM RISERVA WHERE idRiserva = ?";
    $result = fetchOne($conn, $sql, [$idRiserva]);
    if (!$result) return false;
    return $result['quantitaAttuale'] >= $pesoRichiesto;
}

/**
 * Scala giacenza confezionamento (MySQLi)
 * @param mysqli $conn
 */
function scalaGiacenzaConfezionamento($conn, $idConfezionamento, $quantita) {
    try {
        $sql  = "UPDATE CONFEZIONAMENTO
                 SET giacenzaAttuale = giacenzaAttuale - ?
                 WHERE idConfezionamento = ? AND giacenzaAttuale >= ?";
        $stmt = executeQuery($conn, $sql, [$quantita, $idConfezionamento, $quantita]);
        return mysqli_stmt_affected_rows($stmt) > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Scala quantità riserva (MySQLi)
 * @param mysqli $conn
 */
function scalaQuantitaRiserva($conn, $idRiserva, $peso) {
    try {
        $sql  = "UPDATE RISERVA
                 SET quantitaAttuale = quantitaAttuale - ?
                 WHERE idRiserva = ? AND quantitaAttuale >= ?";
        $stmt = executeQuery($conn, $sql, [$peso, $idRiserva, $peso]);
        return mysqli_stmt_affected_rows($stmt) > 0;
    } catch (Exception $e) {
        return false;
    }
}

function calcolaTotaleCarrello($items) {
    $totale = 0;
    foreach ($items as $item) {
        $totale += $item['prezzo'] * $item['quantita'];
    }
    return $totale;
}

function applicaSconto($totale, $percentuale) {
    return $totale - ($totale * $percentuale / 100);
}

/**
 * Ottiene lista prodotti esauriti
 * @param mysqli $conn
 */
function getProdottiEsauriti($conn) {
    $sql = "SELECT p.idProdotto, p.nome, c.nome as categoria
            FROM PRODOTTO p
            LEFT JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
            LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
            GROUP BY p.idProdotto
            HAVING COALESCE(SUM(conf.giacenzaAttuale), 0) = 0";
    return fetchAll($conn, $sql);
}

/**
 * Ottiene giacenza totale prodotto
 * @param mysqli $conn
 */
function getGiacenzaTotaleProdotto($conn, $idProdotto) {
    $sql    = "SELECT COALESCE(SUM(giacenzaAttuale), 0) as totale
               FROM CONFEZIONAMENTO WHERE idProdotto = ?";
    $result = fetchOne($conn, $sql, [$idProdotto]);
    return $result ? (int)$result['totale'] : 0;
}

/**
 * Verifica se prodotto esiste già
 * @param mysqli $conn
 */
function prodottoEsiste($conn, $nome, $excludeId = null) {
    $sql    = "SELECT COUNT(*) as count FROM PRODOTTO WHERE nome = ?";
    $params = [$nome];
    if ($excludeId) {
        $sql .= " AND idProdotto != ?";
        $params[] = $excludeId;
    }
    $result = fetchOne($conn, $sql, $params);
    return $result['count'] > 0;
}

/**
 * Verifica se categoria esiste già
 * @param mysqli $conn
 */
function categoriaEsiste($conn, $nome, $excludeId = null) {
    $sql    = "SELECT COUNT(*) as count FROM CATEGORIA WHERE nome = ?";
    $params = [$nome];
    if ($excludeId) {
        $sql .= " AND idCategoria != ?";
        $params[] = $excludeId;
    }
    $result = fetchOne($conn, $sql, $params);
    return $result['count'] > 0;
}

function generaNickname($nome, $cognome) {
    $base = strtolower(substr($nome, 0, 1) . $cognome);
    $base = preg_replace('/[^a-z0-9]/', '', $base);
    return $base . rand(100, 999);
}

function exportCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    if (!empty($headers)) {
        fputcsv($output, $headers, ';');
    } elseif (!empty($data)) {
        fputcsv($output, array_keys($data[0]), ';');
    }
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    fclose($output);
    exit;
}

function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
    header("Location: $url");
    exit;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type'    => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}
