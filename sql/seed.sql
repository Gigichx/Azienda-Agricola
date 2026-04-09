-- ============================================
-- AZIENDA AGRICOLA - DATI DI ESEMPIO
-- ============================================

USE azienda_agricola;

-- ============================================
-- UTENTI
-- ============================================
-- Password: "admin123" e "cliente123" (hash bcrypt)
-- In produzione usare password_hash() di PHP

INSERT INTO UTENTE (nome, cognome, email, password, ruolo) VALUES
('Mario', 'Rossi', 'admin@azienda.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Giulia', 'Bianchi', 'giulia@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente'),
('Luca', 'Verdi', 'luca@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente');

-- ============================================
-- CLIENTI
-- ============================================
INSERT INTO CLIENTE (idUtente, nome, nickname, telefono, email, occasionale) VALUES
(2, 'Giulia Bianchi', 'Giulia', '3331234567', 'giulia@email.it', FALSE),
(3, 'Luca Verdi', 'Luca', '3337654321', 'luca@email.it', FALSE),
(NULL, 'Cliente Occasionale', 'Occasionale', NULL, NULL, TRUE);

-- ============================================
-- CATEGORIE
-- ============================================
INSERT INTO CATEGORIA (nome, descrizione) VALUES
('Conserve', 'Conserve di pomodoro e verdure'),
('Olio', 'Olio extravergine di oliva'),
('Vino', 'Vini rossi e bianchi'),
('Marmellate', 'Marmellate e confetture di frutta'),
('Miele', 'Miele di produzione propria'),
('Verdure fresche', 'Verdure fresche di stagione');

-- ============================================
-- PRODOTTI
-- ============================================
INSERT INTO PRODOTTO (nome, unitaMisura, prezzoBase, idCategoria) VALUES
-- Conserve
('Passata di pomodoro', 'litro', 4.50, 1),
('Pelati', 'kg', 3.50, 1),
('Pomodorini ciliegino', 'kg', 5.00, 1),
-- Olio
('Olio EVO classico', 'litro', 12.00, 2),
('Olio EVO DOP', 'litro', 18.00, 2),
-- Vino
('Rosso da tavola', 'litro', 6.00, 3),
('Bianco frizzante', 'litro', 7.00, 3),
-- Marmellate
('Marmellata albicocche', 'grammo', 0.01, 4),
('Confettura fichi', 'grammo', 0.012, 4),
-- Miele
('Miele millefiori', 'grammo', 0.015, 5),
('Miele acacia', 'grammo', 0.018, 5),
-- Verdure fresche
('Pomodori freschi', 'kg', 2.50, 6),
('Zucchine', 'kg', 1.80, 6),
('Melanzane', 'kg', 2.20, 6);

-- ============================================
-- LUOGHI
-- ============================================
INSERT INTO LUOGO (nome, indirizzo, tipo) VALUES
('Campo principale', 'Via Campestre 12', 'campo'),
('Laboratorio', 'Via del Lavoro 5', 'laboratorio'),
('Punto vendita centro', 'Piazza Mercato 3', 'punto vendita'),
('Magazzino centrale', 'Via Industriale 20', 'magazzino');

-- ============================================
-- DISPENSE
-- ============================================
INSERT INTO DISPENSA (nome, ubicazione, idLuogo) VALUES
('Dispensa A', 'Piano terra - settore nord', 2),
('Dispensa B', 'Piano terra - settore sud', 2),
('Magazzino riserve', 'Sala principale', 4);

-- ============================================
-- LAVORAZIONI
-- ============================================
INSERT INTO LAVORAZIONE (tipoLavorazione, dataLavorazione, quantitaIngresso, quantitaOttenuta, idProdotto, idLuogo) VALUES
('Spremitura', '2026-03-15', 500.00, 80.00, 4, 2),
('Confezionamento passata', '2026-03-20', 200.00, 180.00, 1, 2),
('Cottura marmellata', '2026-04-01', 50.00, 45.00, 8, 2),
('Vendemmia e vinificazione', '2026-09-10', 1000.00, 700.00, 6, 1);

-- ============================================
-- RISERVE
-- ============================================
INSERT INTO RISERVA (nome, dataProduzione, quantitaIniziale, quantitaAttuale, prezzoAlKg, contenitore, idProdotto, idLavorazione, idDispensa) VALUES
('Olio EVO 2026 - Lotto 1', '2026-03-15', 80.00, 65.00, 12.00, 'Damigiana 50L', 4, 1, 1),
('Passata Marzo 2026', '2026-03-20', 100.00, 85.00, 4.50, 'Fusto 100L', 1, 2, 1),
('Marmellata Albicocche', '2026-04-01', 30.00, 22.00, 10.00, 'Contenitore vetro', 8, 3, 2);

-- ============================================
-- CONFEZIONAMENTI
-- ============================================
INSERT INTO CONFEZIONAMENTO (dataProduzione, dataConfezionamento, numeroConfezioni, pesoNetto, prezzo, giacenzaAttuale, idProdotto, idLavorazione, idRiserva, idLuogo) VALUES
('2026-03-15', '2026-03-16', 100, 0.75, 9.00, 85, 4, 1, NULL, 2),
('2026-03-15', '2026-03-16', 50, 0.50, 6.00, 42, 4, 1, NULL, 2),
('2026-03-20', '2026-03-21', 150, 0.70, 3.50, 120, 1, 2, NULL, 2),
('2026-04-01', '2026-04-02', 80, 0.25, 2.50, 65, 8, 3, NULL, 2),
('2026-04-01', '2026-04-02', 40, 0.50, 5.00, 35, 8, 3, NULL, 2);

-- ============================================
-- VENDITE
-- ============================================
INSERT INTO VENDITA (dataVendita, totaleCalcolato, totalePagato, note, idCliente, idLuogo) VALUES
('2026-04-05 10:30:00', 28.00, 28.00, 'Vendita online - consegna prevista', 1, 3),
('2026-04-06 15:20:00', 45.50, 45.50, NULL, 2, 3),
('2026-04-07 09:15:00', 15.00, 15.00, 'Cliente abituale', 1, 3);

-- ============================================
-- DETTAGLI VENDITE
-- ============================================
INSERT INTO DETTAGLIO_VENDITA (idVendita, tipoVendita, quantita, pesoVenduto, prezzoUnitario, omaggio, idProdotto, idConfezionamento) VALUES
-- Vendita 1 - Giulia
(1, 'CONFEZIONATO', 2, NULL, 9.00, FALSE, 4, 1),
(1, 'CONFEZIONATO', 2, NULL, 5.00, FALSE, 8, 5),

-- Vendita 2 - Luca
(2, 'CONFEZIONATO', 3, NULL, 9.00, FALSE, 4, 1),
(2, 'CONFEZIONATO', 5, NULL, 3.50, FALSE, 1, 3),
(2, 'CONFEZIONATO', 1, NULL, 2.50, TRUE, 8, 4),

-- Vendita 3 - Giulia
(3, 'CONFEZIONATO', 1, NULL, 9.00, FALSE, 4, 1),
(3, 'CONFEZIONATO', 1, NULL, 6.00, FALSE, 4, 2);

-- ============================================
-- SPOSTAMENTI (esempio)
-- ============================================
INSERT INTO SPOSTAMENTO (dataSpostamento, quantita, note, idRiserva, idConfezionamento, idLuogoOrigine, idLuogoDestinazione) VALUES
('2026-04-03 14:00:00', 20, 'Trasferimento per punto vendita', NULL, 1, 2, 3),
('2026-04-04 11:30:00', 15, 'Rifornimento magazzino', NULL, 3, 2, 4);
