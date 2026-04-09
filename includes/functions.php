<?php
/**
 * Funzioni Riutilizzabili Globali
 * Azienda Agricola
 */

/**
 * Formatta prezzo in euro
 * 
 * @param float $amount Importo
 * @return string
 */
function formatPrice($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

/**
 * Formatta data italiana
 * 
 * @param string $date Data (formato Y-m-d o Y-m-d H:i:s)
 * @param bool $includeTime Includi ora
 * @return string
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
 * 
 * @param float $peso Peso
 * @param string $unita Unità (kg, grammo, litro, pezzo)
 * @return string
 */
function formatWeight($peso, $unita) {
    $peso = number_format($peso, 2, ',', '.');
    
    switch ($unita) {
        case 'kg':
            return $peso . ' kg';
        case 'grammo':
            return $peso . ' g';
        case 'litro':
            return $peso . ' L';
        case 'pezzo':
            return $peso . ' pz';
        default:
            return $peso;
    }
}

/**
 * Sanitizza input utente
 * 
 * @param string $data Dati da sanitizzare
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Valida email
 * 
 * @param string $email Email da validare
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida password (minimo 6 caratteri)
 * 
 * @param string $password Password da validare
 * @return bool
 */
function validatePassword($password) {
    return strlen($password) >= 6;
}

/**
 * Hash password con bcrypt
 * 
 * @param string $password Password in chiaro
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifica password con hash
 * 
 * @param string $password Password in chiaro
 * @param string $hash Hash salvato
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genera messaggio di alert HTML
 * 
 * @param string $message Messaggio
 * @param string $type Tipo (success, error, warning, info)
 * @return string HTML
 */
function alert($message, $type = 'info') {
    $class = 'alert alert-' . $type;
    return '<div class="' . $class . '">' . htmlspecialchars($message) . '</div>';
}

/**
 * Verifica disponibilità giacenza confezionamento
 * 
 * @param PDO $pdo Connessione database
 * @param int $idConfezionamento ID confezionamento
 * @param int $quantita Quantità richiesta
 * @return bool
 */
function checkGiacenzaConfezionamento($pdo, $idConfezionamento, $quantita) {
    $sql = "SELECT giacenzaAttuale FROM CONFEZIONAMENTO WHERE idConfezionamento = ?";
    $result = fetchOne($pdo, $sql, [$idConfezionamento]);
    
    if (!$result) return false;
    
    return $result['giacenzaAttuale'] >= $quantita;
}

/**
 * Verifica disponibilità riserva
 * 
 * @param PDO $pdo Connessione database
 * @param int $idRiserva ID riserva
 * @param float $pesoRichiesto Peso richiesto
 * @return bool
 */
function checkGiacenzaRiserva($pdo, $idRiserva, $pesoRichiesto) {
    $sql = "SELECT quantitaAttuale FROM RISERVA WHERE idRiserva = ?";
    $result = fetchOne($pdo, $sql, [$idRiserva]);
    
    if (!$result) return false;
    
    return $result['quantitaAttuale'] >= $pesoRichiesto;
}

/**
 * Scala giacenza confezionamento
 * 
 * @param PDO $pdo Connessione database
 * @param int $idConfezionamento ID confezionamento
 * @param int $quantita Quantità da scalare
 * @return bool
 */
function scalaGiacenzaConfezionamento($pdo, $idConfezionamento, $quantita) {
    try {
        $sql = "UPDATE CONFEZIONAMENTO 
                SET giacenzaAttuale = giacenzaAttuale - ? 
                WHERE idConfezionamento = ? AND giacenzaAttuale >= ?";
        
        $stmt = executeQuery($pdo, $sql, [$quantita, $idConfezionamento, $quantita]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Scala quantità riserva
 * 
 * @param PDO $pdo Connessione database
 * @param int $idRiserva ID riserva
 * @param float $peso Peso da scalare
 * @return bool
 */
function scalaQuantitaRiserva($pdo, $idRiserva, $peso) {
    try {
        $sql = "UPDATE RISERVA 
                SET quantitaAttuale = quantitaAttuale - ? 
                WHERE idRiserva = ? AND quantitaAttuale >= ?";
        
        $stmt = executeQuery($pdo, $sql, [$peso, $idRiserva, $peso]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Calcola totale carrello
 * 
 * @param array $items Items del carrello
 * @return float
 */
function calcolaTotaleCarrello($items) {
    $totale = 0;
    foreach ($items as $item) {
        $totale += $item['prezzo'] * $item['quantita'];
    }
    return $totale;
}

/**
 * Applica sconto percentuale
 * 
 * @param float $totale Totale
 * @param float $percentuale Percentuale sconto
 * @return float
 */
function applicaSconto($totale, $percentuale) {
    return $totale - ($totale * $percentuale / 100);
}

/**
 * Ottiene lista prodotti esauriti
 * 
 * @param PDO $pdo Connessione database
 * @return array
 */
function getProdottiEsauriti($pdo) {
    $sql = "SELECT p.idProdotto, p.nome, c.nome as categoria
            FROM PRODOTTO p
            LEFT JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
            LEFT JOIN CONFEZIONAMENTO conf ON p.idProdotto = conf.idProdotto
            GROUP BY p.idProdotto
            HAVING COALESCE(SUM(conf.giacenzaAttuale), 0) = 0";
    
    return fetchAll($pdo, $sql);
}

/**
 * Ottiene giacenza totale prodotto (somma tutte le confezioni)
 * 
 * @param PDO $pdo Connessione database
 * @param int $idProdotto ID prodotto
 * @return int
 */
function getGiacenzaTotaleProdotto($pdo, $idProdotto) {
    $sql = "SELECT COALESCE(SUM(giacenzaAttuale), 0) as totale
            FROM CONFEZIONAMENTO
            WHERE idProdotto = ?";
    
    $result = fetchOne($pdo, $sql, [$idProdotto]);
    return $result ? (int)$result['totale'] : 0;
}

/**
 * Verifica se prodotto esiste già (controllo duplicati)
 * 
 * @param PDO $pdo Connessione database
 * @param string $nome Nome prodotto
 * @param int|null $excludeId ID da escludere (per update)
 * @return bool
 */
function prodottoEsiste($pdo, $nome, $excludeId = null) {
    $sql = "SELECT COUNT(*) as count FROM PRODOTTO WHERE nome = ?";
    $params = [$nome];
    
    if ($excludeId) {
        $sql .= " AND idProdotto != ?";
        $params[] = $excludeId;
    }
    
    $result = fetchOne($pdo, $sql, $params);
    return $result['count'] > 0;
}

/**
 * Verifica se categoria esiste già (controllo duplicati)
 * 
 * @param PDO $pdo Connessione database
 * @param string $nome Nome categoria
 * @param int|null $excludeId ID da escludere (per update)
 * @return bool
 */
function categoriaEsiste($pdo, $nome, $excludeId = null) {
    $sql = "SELECT COUNT(*) as count FROM CATEGORIA WHERE nome = ?";
    $params = [$nome];
    
    if ($excludeId) {
        $sql .= " AND idCategoria != ?";
        $params[] = $excludeId;
    }
    
    $result = fetchOne($pdo, $sql, $params);
    return $result['count'] > 0;
}

/**
 * Genera codice cliente univoco (nickname)
 * 
 * @param string $nome Nome
 * @param string $cognome Cognome
 * @return string
 */
function generaNickname($nome, $cognome) {
    $base = strtolower(substr($nome, 0, 1) . $cognome);
    $base = preg_replace('/[^a-z0-9]/', '', $base);
    return $base . rand(100, 999);
}

/**
 * Export CSV
 * 
 * @param array $data Dati da esportare
 * @param string $filename Nome file
 * @param array $headers Intestazioni colonne
 */
function exportCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 per Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Intestazioni
    if (!empty($headers)) {
        fputcsv($output, $headers, ';');
    } elseif (!empty($data)) {
        fputcsv($output, array_keys($data[0]), ';');
    }
    
    // Dati
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

/**
 * Redirect con messaggio
 * 
 * @param string $url URL destinazione
 * @param string $message Messaggio
 * @param string $type Tipo messaggio (success, error, warning, info)
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Ottiene e pulisce messaggio flash dalla sessione
 * 
 * @return array|null ['message' => string, 'type' => string]
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}
