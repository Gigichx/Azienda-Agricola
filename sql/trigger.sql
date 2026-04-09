-- ============================================
-- AZIENDA AGRICOLA - TRIGGER SQL
-- ============================================

USE azienda_agricola;

-- ============================================
-- TRIGGER: Storico prezzi automatico
-- ============================================
-- Quando viene aggiornato il prezzoBase di un prodotto,
-- salva automaticamente il vecchio prezzo nello storico

DELIMITER $$

CREATE TRIGGER tr_storico_prezzi_update
BEFORE UPDATE ON PRODOTTO
FOR EACH ROW
BEGIN
    -- Solo se il prezzo è effettivamente cambiato
    IF OLD.prezzoBase != NEW.prezzoBase THEN
        -- Chiude il record precedente nello storico (se esiste)
        UPDATE STORICO_PREZZI
        SET dataFine = NOW()
        WHERE idProdotto = OLD.idProdotto
        AND dataFine IS NULL;
        
        -- Inserisce il nuovo prezzo nello storico
        INSERT INTO STORICO_PREZZI (idProdotto, prezzo, dataInizio, motivazione)
        VALUES (NEW.idProdotto, NEW.prezzoBase, NOW(), 'Aggiornamento automatico');
    END IF;
END$$

DELIMITER ;

-- ============================================
-- TRIGGER: Inizializzazione giacenza confezionamento
-- ============================================
-- Quando viene creato un nuovo confezionamento,
-- imposta automaticamente la giacenza uguale al numero di confezioni

DELIMITER $$

CREATE TRIGGER tr_init_giacenza_confezionamento
BEFORE INSERT ON CONFEZIONAMENTO
FOR EACH ROW
BEGIN
    SET NEW.giacenzaAttuale = NEW.numeroConfezioni;
END$$

DELIMITER ;

-- ============================================
-- TRIGGER: Controllo giacenza negativa confezionamento
-- ============================================
-- Impedisce che la giacenza di un confezionamento diventi negativa

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
-- TRIGGER: Controllo quantità riserva negativa
-- ============================================
-- Impedisce che la quantità attuale di una riserva diventi negativa

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
-- TRIGGER: Validazione spostamento
-- ============================================
-- Verifica che almeno uno tra idRiserva o idConfezionamento sia valorizzato
-- e che origine e destinazione siano diverse

DELIMITER $$

CREATE TRIGGER tr_validazione_spostamento
BEFORE INSERT ON SPOSTAMENTO
FOR EACH ROW
BEGIN
    -- Almeno uno dei due deve essere valorizzato
    IF NEW.idRiserva IS NULL AND NEW.idConfezionamento IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Spostamento deve riferirsi a una riserva o un confezionamento';
    END IF;
    
    -- Origine e destinazione devono essere diverse
    IF NEW.idLuogoOrigine = NEW.idLuogoDestinazione THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Luogo origine e destinazione devono essere diversi';
    END IF;
END$$

DELIMITER ;

-- ============================================
-- TRIGGER: Validazione dettaglio vendita
-- ============================================
-- Verifica coerenza tra tipo vendita e campi valorizzati

DELIMITER $$

CREATE TRIGGER tr_validazione_dettaglio_vendita
BEFORE INSERT ON DETTAGLIO_VENDITA
FOR EACH ROW
BEGIN
    -- FRESCO_SFUSO: deve avere quantita o pesoVenduto
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
    
    -- CONFEZIONATO: deve avere quantita e idConfezionamento
    IF NEW.tipoVendita = 'CONFEZIONATO' THEN
        IF NEW.quantita IS NULL OR NEW.idConfezionamento IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Vendita confezionato richiede quantità e idConfezionamento';
        END IF;
    END IF;
    
    -- RISERVA_SFUSA: deve avere pesoVenduto
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
