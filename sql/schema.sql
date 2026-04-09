-- ============================================
-- AZIENDA AGRICOLA - DATABASE SCHEMA
-- ============================================

-- Database creation
CREATE DATABASE IF NOT EXISTS azienda_agricola
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE azienda_agricola;

-- ============================================
-- TABELLA UTENTE - Gestione accessi
-- ============================================
CREATE TABLE IF NOT EXISTS UTENTE (
    idUtente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ruolo ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    dataRegistrazione DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    attivo BOOLEAN NOT NULL DEFAULT TRUE,
    
    INDEX idx_email (email),
    INDEX idx_ruolo (ruolo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA CLIENTE - Anagrafica clienti
-- ============================================
CREATE TABLE IF NOT EXISTS CLIENTE (
    idCliente INT AUTO_INCREMENT PRIMARY KEY,
    idUtente INT NULL,
    nome VARCHAR(150) NOT NULL,
    nickname VARCHAR(100) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    occasionale BOOLEAN NOT NULL DEFAULT FALSE,
    
    FOREIGN KEY (idUtente) REFERENCES UTENTE(idUtente) ON DELETE SET NULL,
    INDEX idx_occasionale (occasionale),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA CATEGORIA - Categorie prodotti
-- ============================================
CREATE TABLE IF NOT EXISTS CATEGORIA (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT NULL,
    
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA PRODOTTO - Anagrafica prodotti
-- ============================================
CREATE TABLE IF NOT EXISTS PRODOTTO (
    idProdotto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    unitaMisura ENUM('kg', 'litro', 'pezzo', 'grammo') NOT NULL,
    prezzoBase DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    idCategoria INT NOT NULL,
    
    FOREIGN KEY (idCategoria) REFERENCES CATEGORIA(idCategoria) ON DELETE RESTRICT,
    INDEX idx_categoria (idCategoria),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA STORICO_PREZZI - Variazioni prezzo
-- ============================================
CREATE TABLE IF NOT EXISTS STORICO_PREZZI (
    idStorico INT AUTO_INCREMENT PRIMARY KEY,
    idProdotto INT NOT NULL,
    prezzo DECIMAL(10, 2) NOT NULL,
    dataInizio DATETIME NOT NULL,
    dataFine DATETIME NULL,
    motivazione VARCHAR(255) NULL,
    
    FOREIGN KEY (idProdotto) REFERENCES PRODOTTO(idProdotto) ON DELETE CASCADE,
    INDEX idx_prodotto (idProdotto),
    INDEX idx_date (dataInizio, dataFine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA LUOGO - Sedi aziendali
-- ============================================
CREATE TABLE IF NOT EXISTS LUOGO (
    idLuogo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL UNIQUE,
    indirizzo VARCHAR(255) NULL,
    tipo ENUM('campo', 'laboratorio', 'punto vendita', 'magazzino') NOT NULL,
    
    INDEX idx_tipo (tipo),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA DISPENSA - Luoghi di conservazione
-- ============================================
CREATE TABLE IF NOT EXISTS DISPENSA (
    idDispensa INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    ubicazione VARCHAR(255) NULL,
    idLuogo INT NOT NULL,
    
    FOREIGN KEY (idLuogo) REFERENCES LUOGO(idLuogo) ON DELETE RESTRICT,
    INDEX idx_luogo (idLuogo),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA LAVORAZIONE - Processo produttivo
-- ============================================
CREATE TABLE IF NOT EXISTS LAVORAZIONE (
    idLavorazione INT AUTO_INCREMENT PRIMARY KEY,
    tipoLavorazione VARCHAR(100) NOT NULL,
    dataLavorazione DATE NOT NULL,
    quantitaIngresso DECIMAL(10, 2) NOT NULL,
    quantitaOttenuta DECIMAL(10, 2) NOT NULL,
    idProdotto INT NOT NULL,
    idLuogo INT NOT NULL,
    
    FOREIGN KEY (idProdotto) REFERENCES PRODOTTO(idProdotto) ON DELETE RESTRICT,
    FOREIGN KEY (idLuogo) REFERENCES LUOGO(idLuogo) ON DELETE RESTRICT,
    INDEX idx_data (dataLavorazione),
    INDEX idx_prodotto (idProdotto),
    INDEX idx_luogo (idLuogo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA RISERVA - Prodotti in dispensa
-- ============================================
CREATE TABLE IF NOT EXISTS RISERVA (
    idRiserva INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    dataProduzione DATE NOT NULL,
    quantitaIniziale DECIMAL(10, 2) NOT NULL,
    quantitaAttuale DECIMAL(10, 2) NOT NULL,
    prezzoAlKg DECIMAL(10, 2) NOT NULL,
    contenitore VARCHAR(100) NULL,
    idProdotto INT NOT NULL,
    idLavorazione INT NULL,
    idDispensa INT NOT NULL,
    
    FOREIGN KEY (idProdotto) REFERENCES PRODOTTO(idProdotto) ON DELETE RESTRICT,
    FOREIGN KEY (idLavorazione) REFERENCES LAVORAZIONE(idLavorazione) ON DELETE SET NULL,
    FOREIGN KEY (idDispensa) REFERENCES DISPENSA(idDispensa) ON DELETE RESTRICT,
    INDEX idx_prodotto (idProdotto),
    INDEX idx_lavorazione (idLavorazione),
    INDEX idx_dispensa (idDispensa),
    INDEX idx_quantita (quantitaAttuale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA CONFEZIONAMENTO - Prodotti confezionati
-- ============================================
CREATE TABLE IF NOT EXISTS CONFEZIONAMENTO (
    idConfezionamento INT AUTO_INCREMENT PRIMARY KEY,
    dataProduzione DATE NOT NULL,
    dataConfezionamento DATE NOT NULL,
    numeroConfezioni INT NOT NULL DEFAULT 0,
    pesoNetto DECIMAL(10, 2) NOT NULL,
    prezzo DECIMAL(10, 2) NOT NULL,
    giacenzaAttuale INT NOT NULL DEFAULT 0,
    idProdotto INT NOT NULL,
    idLavorazione INT NULL,
    idRiserva INT NULL,
    idLuogo INT NOT NULL,
    
    FOREIGN KEY (idProdotto) REFERENCES PRODOTTO(idProdotto) ON DELETE RESTRICT,
    FOREIGN KEY (idLavorazione) REFERENCES LAVORAZIONE(idLavorazione) ON DELETE SET NULL,
    FOREIGN KEY (idRiserva) REFERENCES RISERVA(idRiserva) ON DELETE SET NULL,
    FOREIGN KEY (idLuogo) REFERENCES LUOGO(idLuogo) ON DELETE RESTRICT,
    INDEX idx_prodotto (idProdotto),
    INDEX idx_lavorazione (idLavorazione),
    INDEX idx_riserva (idRiserva),
    INDEX idx_giacenza (giacenzaAttuale),
    INDEX idx_data_confez (dataConfezionamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA VENDITA - Testata acquisti
-- ============================================
CREATE TABLE IF NOT EXISTS VENDITA (
    idVendita INT AUTO_INCREMENT PRIMARY KEY,
    dataVendita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    totaleCalcolato DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    totalePagato DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    note TEXT NULL,
    idCliente INT NOT NULL,
    idLuogo INT NOT NULL,
    
    FOREIGN KEY (idCliente) REFERENCES CLIENTE(idCliente) ON DELETE RESTRICT,
    FOREIGN KEY (idLuogo) REFERENCES LUOGO(idLuogo) ON DELETE RESTRICT,
    INDEX idx_cliente (idCliente),
    INDEX idx_data (dataVendita),
    INDEX idx_luogo (idLuogo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA DETTAGLIO_VENDITA - Righe acquisti
-- ============================================
CREATE TABLE IF NOT EXISTS DETTAGLIO_VENDITA (
    idDettaglio INT AUTO_INCREMENT PRIMARY KEY,
    idVendita INT NOT NULL,
    tipoVendita ENUM('FRESCO_SFUSO', 'CONFEZIONATO', 'RISERVA_SFUSA') NOT NULL,
    quantita INT NULL,
    pesoVenduto DECIMAL(10, 2) NULL,
    prezzoUnitario DECIMAL(10, 2) NOT NULL,
    omaggio BOOLEAN NOT NULL DEFAULT FALSE,
    idProdotto INT NOT NULL,
    idConfezionamento INT NULL,
    
    FOREIGN KEY (idVendita) REFERENCES VENDITA(idVendita) ON DELETE CASCADE,
    FOREIGN KEY (idProdotto) REFERENCES PRODOTTO(idProdotto) ON DELETE RESTRICT,
    FOREIGN KEY (idConfezionamento) REFERENCES CONFEZIONAMENTO(idConfezionamento) ON DELETE SET NULL,
    INDEX idx_vendita (idVendita),
    INDEX idx_prodotto (idProdotto),
    INDEX idx_confezionamento (idConfezionamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA SPOSTAMENTO - Movimentazioni
-- ============================================
CREATE TABLE IF NOT EXISTS SPOSTAMENTO (
    idSpostamento INT AUTO_INCREMENT PRIMARY KEY,
    dataSpostamento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    quantita DECIMAL(10, 2) NOT NULL,
    note TEXT NULL,
    idRiserva INT NULL,
    idConfezionamento INT NULL,
    idLuogoOrigine INT NOT NULL,
    idLuogoDestinazione INT NOT NULL,
    
    FOREIGN KEY (idRiserva) REFERENCES RISERVA(idRiserva) ON DELETE SET NULL,
    FOREIGN KEY (idConfezionamento) REFERENCES CONFEZIONAMENTO(idConfezionamento) ON DELETE SET NULL,
    FOREIGN KEY (idLuogoOrigine) REFERENCES LUOGO(idLuogo) ON DELETE RESTRICT,
    FOREIGN KEY (idLuogoDestinazione) REFERENCES LUOGO(idLuogo) ON DELETE RESTRICT,
    INDEX idx_riserva (idRiserva),
    INDEX idx_confezionamento (idConfezionamento),
    INDEX idx_data (dataSpostamento),
    CHECK (idLuogoOrigine != idLuogoDestinazione)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
