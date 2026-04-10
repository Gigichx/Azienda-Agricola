<?php
/**
 * API VENDITA ADMIN
 * Gestisce la creazione di vendite manuali dall'area admin
 * Supporta: FRESCO_SFUSO, CONFEZIONATO, RISERVA_SFUSA
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$action = $_POST['action'] ?? '';

if ($action === 'create') {

    $idCliente    = (int)($_POST['idCliente'] ?? 0);
    $idLuogo      = (int)($_POST['idLuogo'] ?? 0);
    $dataVendita  = $_POST['dataVendita'] ?? date('Y-m-d H:i:s');
    $note         = sanitizeInput($_POST['note'] ?? '');
    $totalePagato = isset($_POST['totalePagato']) && $_POST['totalePagato'] !== '' ? (float)$_POST['totalePagato'] : null;
    $items        = $_POST['items'] ?? [];

    if (!$idCliente || !$idLuogo || empty($items)) {
        redirectWithMessage('/admin/vendite.php', 'Dati non validi: cliente, luogo e almeno un prodotto sono obbligatori', 'error');
    }

    try {
        $pdo->beginTransaction();

        $totaleCalcolato = 0;
        $dettagli        = [];

        foreach ($items as $item) {
            $tipo       = $item['tipo'] ?? '';
            $idProdotto = (int)($item['idProdotto'] ?? 0);
            $omaggio    = isset($item['omaggio']) && $item['omaggio'] == '1';

            if (!$idProdotto || !in_array($tipo, ['FRESCO_SFUSO', 'CONFEZIONATO', 'RISERVA_SFUSA'])) {
                throw new Exception("Tipo vendita o prodotto non valido");
            }

            $quantita          = null;
            $pesoVenduto       = null;
            $prezzoUnitario    = 0;
            $idConfezionamento = null;
            $idRiservaUsata    = null;
            $subtotale         = 0;

            if ($tipo === 'FRESCO_SFUSO') {
                $unitaMisura = $item['unitaMisura'] ?? 'pezzo';
                $prodotto = fetchOne($pdo, "SELECT prezzoBase FROM PRODOTTO WHERE idProdotto = ?", [$idProdotto]);
                if (!$prodotto) throw new Exception("Prodotto non trovato");
                $prezzoUnitario = (float)$prodotto['prezzoBase'];

                if ($unitaMisura === 'pezzo') {
                    $quantita = max(1, (int)($item['quantita'] ?? 0));
                    $subtotale = $omaggio ? 0 : $prezzoUnitario * $quantita;
                } else {
                    $pesoVenduto = (float)($item['pesoVenduto'] ?? 0);
                    if ($pesoVenduto <= 0) throw new Exception("Peso non valido per prodotto fresco");
                    $subtotale = $omaggio ? 0 : $prezzoUnitario * $pesoVenduto;
                }

            } elseif ($tipo === 'CONFEZIONATO') {
                $idConfezionamento = (int)($item['idConfezionamento'] ?? 0);
                $quantita          = (int)($item['quantita'] ?? 0);
                if (!$idConfezionamento || $quantita <= 0) throw new Exception("Dati confezionamento non validi");

                if (!checkGiacenzaConfezionamento($pdo, $idConfezionamento, $quantita)) {
                    throw new Exception("Giacenza insufficiente per un prodotto confezionato");
                }

                $conf = fetchOne($pdo, "SELECT prezzo FROM CONFEZIONAMENTO WHERE idConfezionamento = ?", [$idConfezionamento]);
                if (!$conf) throw new Exception("Confezionamento non trovato");
                $prezzoUnitario = (float)$conf['prezzo'];
                $subtotale      = $omaggio ? 0 : $prezzoUnitario * $quantita;

                // Scala giacenza (anche per omaggi)
                if (!scalaGiacenzaConfezionamento($pdo, $idConfezionamento, $quantita)) {
                    throw new Exception("Errore scalatura giacenza confezionamento");
                }

            } elseif ($tipo === 'RISERVA_SFUSA') {
                $idRiservaUsata = (int)($item['idRiserva'] ?? 0);
                $pesoVenduto    = (float)($item['pesoVenduto'] ?? 0);
                if (!$idRiservaUsata || $pesoVenduto <= 0) throw new Exception("Dati riserva non validi");

                if (!checkGiacenzaRiserva($pdo, $idRiservaUsata, $pesoVenduto)) {
                    throw new Exception("Quantità in riserva insufficiente");
                }

                $riserva = fetchOne($pdo, "SELECT prezzoAlKg FROM RISERVA WHERE idRiserva = ?", [$idRiservaUsata]);
                if (!$riserva) throw new Exception("Riserva non trovata");
                $prezzoUnitario = (float)$riserva['prezzoAlKg'];
                $subtotale      = $omaggio ? 0 : $prezzoUnitario * $pesoVenduto;

                // Scala quantità riserva (anche per omaggi)
                if (!scalaQuantitaRiserva($pdo, $idRiservaUsata, $pesoVenduto)) {
                    throw new Exception("Errore scalatura quantità riserva");
                }
            }

            $totaleCalcolato += $subtotale;

            $dettagli[] = [
                'tipo'             => $tipo,
                'idProdotto'       => $idProdotto,
                'idConfezionamento'=> $idConfezionamento,
                'idRiserva'        => $idRiservaUsata,   // FIX: incluso per RISERVA_SFUSA
                'quantita'         => $quantita,
                'pesoVenduto'      => $pesoVenduto,
                'prezzoUnitario'   => $prezzoUnitario,
                'omaggio'          => $omaggio,
            ];
        }

        // Se totalePagato non specificato o 0, coincide col calcolato
        if ($totalePagato === null || $totalePagato < 0) {
            $totalePagato = $totaleCalcolato;
        }

        // Crea testata vendita
        $sqlVendita = "INSERT INTO VENDITA (dataVendita, totaleCalcolato, totalePagato, note, idCliente, idLuogo)
                       VALUES (?, ?, ?, ?, ?, ?)";
        $idVendita  = insertAndGetId($pdo, $sqlVendita, [
            $dataVendita, $totaleCalcolato, $totalePagato, $note ?: null, $idCliente, $idLuogo
        ]);

        // Inserisci righe dettaglio
        foreach ($dettagli as $det) {
            // Il trigger tr_validazione_dettaglio_vendita verifica i campi obbligatori per tipo
            $sqlDet = "INSERT INTO DETTAGLIO_VENDITA
                            (idVendita, tipoVendita, quantita, pesoVenduto, prezzoUnitario, omaggio, idProdotto, idConfezionamento)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            executeQuery($pdo, $sqlDet, [
                $idVendita,
                $det['tipo'],
                $det['quantita'],
                $det['pesoVenduto'],
                $det['prezzoUnitario'],
                $det['omaggio'] ? 1 : 0,
                $det['idProdotto'],
                $det['idConfezionamento'],   // NULL per FRESCO_SFUSO e RISERVA_SFUSA
            ]);
        }

        $pdo->commit();
        redirectWithMessage('/admin/vendite.php', 'Vendita registrata con successo (ID #' . $idVendita . ')', 'success');

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Errore vendita admin: " . $e->getMessage());
        redirectWithMessage('/admin/vendite.php', 'Errore: ' . $e->getMessage(), 'error');
    }

} else {
    header('Location: /admin/vendite.php');
    exit;
}
