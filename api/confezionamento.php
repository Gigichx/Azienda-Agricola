<?php
/**
 * API CONFEZIONAMENTO
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create':
        $idProdotto          = (int)($_POST['idProdotto'] ?? 0);
        $idLuogo             = (int)($_POST['idLuogo'] ?? 0);
        $dataProduzione      = $_POST['dataProduzione'] ?? date('Y-m-d');
        $dataConfezionamento = $_POST['dataConfezionamento'] ?? date('Y-m-d');
        $numeroConfezioni    = (int)($_POST['numeroConfezioni'] ?? 0);
        $pesoNetto           = (float)($_POST['pesoNetto'] ?? 0);
        $prezzo              = (float)($_POST['prezzo'] ?? 0);
        $idLavorazione       = !empty($_POST['idLavorazione']) ? (int)$_POST['idLavorazione'] : null;
        $idRiserva           = !empty($_POST['idRiserva']) ? (int)$_POST['idRiserva'] : null;

        if (!$idProdotto || !$idLuogo || $numeroConfezioni <= 0 || $pesoNetto <= 0 || $prezzo <= 0) {
            redirectWithMessage('/admin/confezionamento.php', 'Dati non validi', 'error');
        }

        mysqli_begin_transaction($conn);

        try {
            $sql = "INSERT INTO CONFEZIONAMENTO
                        (dataProduzione, dataConfezionamento, numeroConfezioni, pesoNetto, prezzo,
                         giacenzaAttuale, idProdotto, idLavorazione, idRiserva, idLuogo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            executeQuery($conn, $sql, [
                $dataProduzione, $dataConfezionamento, $numeroConfezioni,
                $pesoNetto, $prezzo, $numeroConfezioni,
                $idProdotto, $idLavorazione, $idRiserva, $idLuogo
            ]);

            if ($idRiserva) {
                $pesoTotale = $pesoNetto * $numeroConfezioni;
                $riserva    = fetchOne($conn, "SELECT quantitaAttuale FROM RISERVA WHERE idRiserva = ?", [$idRiserva]);
                if (!$riserva || $riserva['quantitaAttuale'] < $pesoTotale) {
                    throw new Exception('Quantità in riserva insufficiente (' .
                        ($riserva['quantitaAttuale'] ?? 0) . ' kg disponibili, ' . $pesoTotale . ' kg richiesti)');
                }
                if (!scalaQuantitaRiserva($conn, $idRiserva, $pesoTotale)) {
                    throw new Exception('Errore scalatura riserva');
                }
            }

            mysqli_commit($conn);
            redirectWithMessage('/admin/confezionamento.php', 'Confezionamento registrato con successo', 'success');

        } catch (Exception $e) {
            mysqli_rollback($conn);
            redirectWithMessage('/admin/confezionamento.php', 'Errore: ' . $e->getMessage(), 'error');
        }
        break;

    case 'delete':
        $idConfezionamento = (int)($_POST['idConfezionamento'] ?? 0);

        if (!$idConfezionamento) {
            redirectWithMessage('/admin/confezionamento.php', 'ID non valido', 'error');
        }

        $check = fetchOne($conn,
            "SELECT COUNT(*) as c FROM DETTAGLIO_VENDITA WHERE idConfezionamento = ?",
            [$idConfezionamento]);
        if ($check['c'] > 0) {
            redirectWithMessage('/admin/confezionamento.php',
                'Impossibile eliminare: il confezionamento ha vendite associate', 'error');
        }

        executeQuery($conn, "DELETE FROM CONFEZIONAMENTO WHERE idConfezionamento = ?", [$idConfezionamento]);
        redirectWithMessage('/admin/confezionamento.php', 'Confezionamento eliminato', 'success');
        break;

    default:
        header('Location: /admin/confezionamento.php');
        exit;
}
