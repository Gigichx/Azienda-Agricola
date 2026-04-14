-- ============================================
-- AZIENDA AGRICOLA - TRIGGER SQL — FIXED
-- Fix: tr_init_giacenza_confezionamento non
-- sovrascrive giacenzaAttuale se già impostata
-- ============================================

USE azienda_agricola;

-- Rimuovi il vecchio trigger prima di ricreare
DROP TRIGGER IF EXISTS tr_storico_prezzi_insert;
DROP TRIGGER IF EXISTS tr_storico_prezzi_update;
DROP TRIGGER IF EXISTS tr_init_giacenza_confezionamento;
DROP TRIGGER IF EXISTS tr_check_giacenza_confezionamento;
DROP TRIGGER IF EXISTS tr_check_quantita_riserva;
DROP TRIGGER IF EXISTS tr_validazione_spostamento;
DROP TRIGGER IF EXISTS tr_validazione_dettaglio_vendita;

-- ============================================
-- TRIGGER 1: Storico prezzi — insert prodotto
-- ============================================
DELIMITER $$
CREATE TRIGGER tr_storico_prezzi_insert
AFTER INSERT ON PRODOTTO
FOR EACH ROW
BEGIN
    INSERT INTO STORICO_PREZZI (idProdotto, prezzo, dataInizio, motivazione)
    VALUES (NEW.idProdotto, NEW.prezzoBase, NOW(), 'Prezzo iniziale');
END$$
DELIMITER ;

-- ============================================
-- TRIGGER 2: Storico prezzi — update prodotto
-- ============================================
DELIMITER $$
CREATE TRIGGER tr_storico_prezzi_update
BEFORE UPDATE ON PRODOTTO
FOR EACH ROW
BEGIN
    IF OLD.prezzoBase != NEW.prezzoBase THEN
        UPDATE STORICO_PREZZI
        SET dataFine = NOW()
        WHERE idProdotto = OLD.idProdotto AND dataFine IS NULL;

        INSERT INTO STORICO_PREZZI (idProdotto, prezzo, dataInizio, motivazione)
        VALUES (NEW.idProdotto, NEW.prezzoBase, NOW(), 'Aggiornamento prezzo');
    END IF;
END$$
DELIMITER ;

-- ============================================
-- TRIGGER 3: Giacenza confezionamento — FIXED
-- Imposta giacenzaAttuale = numeroConfezioni
-- SOLO SE non è già stata impostata (>0)
-- Questo evita che il seed.sql venga sovrascritto
-- ============================================
DELIMITER $$
CREATE TRIGGER tr_init_giacenza_confezionamento
BEFORE INSERT ON CONFEZIONAMENTO
FOR EACH ROW
BEGIN
    -- Usa giacenzaAttuale se già valorizzata, altrimenti usa numeroConfezioni
    IF NEW.giacenzaAttuale IS NULL OR NEW.giacenzaAttuale = 0 THEN
        SET NEW.giacenzaAttuale = NEW.numeroConfezioni;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- TRIGGER 4: Controllo giacenza negativa conf.
-- ============================================
DELIMITER $$
CREATE TRIGGER tr_check_giacenza_confezionamento
BEFORE UPDATE ON CONFEZIONAMENTO
FOR EACH ROW
BEGIN
    IF NEW.giacenzaAttuale < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giacenza confezionamento non può essere negativa';
    END IF;
END$$
DELIMITER ;

-- ============================================
-- TRIGGER 5: Controllo quantità riserva negativa
-- ============================================
DELIMITER $$
CREATE TRIGGER tr_check_quantita_riserva
BEFORE UPDATE ON RISERVA
FOR EACH ROW
BEGIN
    IF NEW.quantitaAttuale < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Quantità attuale riserva non può essere negativa';
    END IF;
END$$
DELIMITER ;

-- ============================================
-- TRIGGER 6: Validazione spostamento
-- ============================================
DELIMITER $$
CREATE TRIGGER tr_validazione_spostamento
BEFORE INSERT ON SPOSTAMENTO
FOR EACH ROW
BEGIN
    IF NEW.idRiserva IS NULL AND NEW.idConfezionamento IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Spostamento deve riferirsi a una riserva o un confezionamento';
    END IF;
    IF NEW.idLuogoOrigine = NEW.idLuogoDestinazione THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Luogo origine e destinazione devono essere diversi';
    END IF;
END$$
DELIMITER ;

-- ============================================
-- TRIGGER 7: Validazione dettaglio vendita
-- ============================================
DELIMITER $$
CREATE TRIGGER tr_validazione_dettaglio_vendita
BEFORE INSERT ON DETTAGLIO_VENDITA
FOR EACH ROW
BEGIN
    IF NEW.tipoVendita = 'FRESCO_SFUSO' THEN
        IF NEW.quantita IS NULL AND NEW.pesoVenduto IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Vendita fresco sfuso richiede quantità o peso';
        END IF;
        IF NEW.idConfezionamento IS NOT NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Vendita fresco sfuso non può avere idConfezionamento';
        END IF;
    END IF;

    IF NEW.tipoVendita = 'CONFEZIONATO' THEN
        IF NEW.quantita IS NULL OR NEW.idConfezionamento IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Vendita confezionato richiede quantità e idConfezionamento';
        END IF;
    END IF;

    IF NEW.tipoVendita = 'RISERVA_SFUSA' THEN
        IF NEW.pesoVenduto IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Vendita riserva sfusa richiede pesoVenduto';
        END IF;
        IF NEW.idConfezionamento IS NOT NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Vendita riserva sfusa non può avere idConfezionamento';
        END IF;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- FIX DATI ESISTENTI:
-- Se il DB è già popolato col seed vecchio,
-- esegui questo UPDATE per correggere le
-- giacenze che il vecchio trigger aveva azzerato
-- o impostato male.
-- Le vendite del seed scalano queste giacenze:
--   conf 1 (Olio 0.75L):     vendite 1(2)+3(2)+4(3) = 7 pz
--   conf 2 (Olio 0.5L):      nessuna vendita diretta
--   conf 3 (Miele mfior):    vendite 2(1)+4(3)+5(1)+6(2) = 7 pz
--   conf 4 (Marmellata alb): vendite 1(2)+6(2) = 4 pz
--   conf 5 (Lavanda bust):   vendite 2(1)+7(2) = 3 pz
--   conf 6 (Conf fichi):     vendite 3(1)+7(1 omaggio) = 2 pz
--   conf 7 (Unguento lav):   vendite 5(1) = 1 pz
--   conf 8 (Idrolato lav):   vendite 6(1) = 1 pz
-- ============================================

-- Prima porta tutto a numeroConfezioni (stato iniziale)
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni;

-- Poi scala le vendite del seed
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 7  WHERE idConfezionamento = 1;
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 0  WHERE idConfezionamento = 2;
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 7  WHERE idConfezionamento = 3;
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 4  WHERE idConfezionamento = 4;
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 3  WHERE idConfezionamento = 5;
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 2  WHERE idConfezionamento = 6;
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 1  WHERE idConfezionamento = 7;
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = numeroConfezioni - 1  WHERE idConfezionamento = 8;

-- Sicurezza: evita valori negativi
UPDATE CONFEZIONAMENTO SET giacenzaAttuale = 0 WHERE giacenzaAttuale < 0;
