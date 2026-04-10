-- ============================================
-- AZIENDA AGRICOLA - DATI DI ESEMPIO
-- ============================================

USE azienda_agricola;

-- ============================================
-- UTENTI
-- ============================================
-- Password: "admin123" e "cliente123" (bcrypt cost 10)
INSERT INTO UTENTE (nome, cognome, email, password, ruolo) VALUES
('Mario',  'Rossi',    'admin@azienda.it',
    '$2y$10$u5kPTMaKSEegL7JfF3k5.eCqCNWX1mHFqmJajL/KG2tBbCVNSd1iW', 'admin'),
('Giulia', 'Bianchi',  'giulia@email.it',
    '$2y$10$3i1sBr5QQxLBILhMOJVFeeI9h5FRnVFdIKGgHCRIZNIXVDQ7BUrJy', 'cliente'),
('Luca',   'Verdi',    'luca@email.it',
    '$2y$10$3i1sBr5QQxLBILhMOJVFeeI9h5FRnVFdIKGgHCRIZNIXVDQ7BUrJy', 'cliente');

-- ============================================
-- CLIENTI
-- ============================================
INSERT INTO CLIENTE (idUtente, nome, nickname, telefono, email, occasionale) VALUES
(2, 'Giulia Bianchi',     'Giulia',      '3331234567', 'giulia@email.it', FALSE),
(3, 'Luca Verdi',         'Luca',        '3337654321', 'luca@email.it',   FALSE),
(NULL, 'Cliente Occasionale', 'Occasionale', NULL,       NULL,             TRUE);

-- ============================================
-- CATEGORIE
-- ============================================
INSERT INTO CATEGORIA (nome, descrizione) VALUES
-- Prodotti sfusi
('Frutta Fresca',         'Frutta fresca di stagione a peso'),
('Verdure e Ortaggi',     'Verdure, ortaggi e legumi freschi'),
('Legumi',                'Legumi freschi e secchi sfusi'),
('Prodotti Selvatici',    'Erbe, funghi e prodotti spontanei del territorio'),
('Piante Aromatiche',     'Piante aromatiche fresche e in vaso'),
-- Prodotti confezionati
('Miele',                 'Miele grezzo e confezionato'),
('Olio',                  'Olio extravergine di oliva in varie confezioni'),
('Fiori Essiccati',       'Fiori essiccati in bustine o vasetti'),
('Aromatiche Essiccate',  'Piante aromatiche essiccate in bustine o vasetti'),
('Frutta Disidratata',    'Frutta disidratata in bustine o vasetti'),
('Marmellate',            'Marmellate e confetture artigianali'),
('Succhi di Frutta',      'Succhi e nettari di frutta naturali'),
('Vincotto',              'Vincotto di fichi, mele cotogne e gelsi'),
('Cotognata e Caramelle', 'Cotognata, caramelle e confetti artigianali'),
('Fichi Secchi',          'Fichi secchi naturali o mandorlati'),
('Olive',                 'Olive in acqua, salamoia e condite'),
('Cosmetici Naturali',    'Unguenti, saponette, oli essenziali e idrolati artigianali'),
-- Riserve
('Riserva Bulk',          'Prodotti in grandi formati per riserva e consumo proprio');

-- ============================================
-- PRODOTTI SFUSI
-- ============================================
INSERT INTO PRODOTTO (nome, unitaMisura, prezzoBase, idCategoria) VALUES
-- Frutta fresca (cat 1)
('Mele',          'kg', 2.20, 1),
('Pere',          'kg', 2.50, 1),
('Pesche',        'kg', 2.80, 1),
('Albicocche',    'kg', 3.00, 1),
('Fichi freschi', 'kg', 3.50, 1),
('Cachi',         'kg', 2.50, 1),
('Melograni',     'kg', 3.00, 1),
('Noci fresche',  'kg', 5.00, 1),
('Castagne',      'kg', 4.50, 1),
('Mandarini',     'kg', 2.00, 1),

-- Verdure e ortaggi (cat 2)
('Pomodori freschi',   'kg', 2.50, 2),
('Zucchine',           'kg', 1.80, 2),
('Melanzane',          'kg', 2.20, 2),
('Peperoni',           'kg', 2.50, 2),
('Patate',             'kg', 1.20, 2),
('Cipolle',            'kg', 1.50, 2),
('Aglio',              'kg', 6.00, 2),
('Carote',             'kg', 1.50, 2),
('Cavolo',             'kg', 1.80, 2),
('Broccoli',           'kg', 2.20, 2),
('Insalata mista',     'kg', 3.00, 2),
('Spinaci',            'kg', 2.50, 2),
('Pomodorini ciliegino','kg',3.50, 2),

-- Legumi (cat 3)
('Fagioli secchi',     'kg', 4.00, 3),
('Ceci secchi',        'kg', 3.50, 3),
('Lenticchie',         'kg', 3.50, 3),
('Fave secche',        'kg', 3.50, 3),
('Piselli secchi',     'kg', 3.50, 3),
('Fagioli freschi',    'kg', 2.50, 3),

-- Prodotti selvatici (cat 4)
('Funghi porcini freschi', 'kg', 15.00, 4),
('Asparagi selvatici',     'kg', 8.00,  4),
('Erbe di campo miste',    'kg', 5.00,  4),
('Rosa canina',            'kg', 6.00,  4),
('Sambuco (fiori)',        'kg', 8.00,  4),

-- Piante aromatiche fresche (cat 5)
('Rosmarino fresco',  'kg', 5.00, 5),
('Salvia fresca',     'kg', 5.00, 5),
('Menta fresca',      'kg', 4.50, 5),
('Basilico fresco',   'kg', 5.00, 5),
('Origano fresco',    'kg', 6.00, 5),
('Timo fresco',       'kg', 6.00, 5),
('Lavanda fresca',    'kg', 7.00, 5),

-- ============================================
-- PRODOTTI CONFEZIONATI
-- ============================================

-- Miele confezionato (cat 6)
('Miele millefiori (vasetto)',  'pezzo', 8.50, 6),
('Miele acacia (vasetto)',      'pezzo', 9.00, 6),
('Miele castagno (vasetto)',    'pezzo', 9.50, 6),
('Miele di timo (vasetto)',     'pezzo',10.00, 6),

-- Olio confezionato (cat 7)
('Olio EVO (bottiglia 0.5L)',   'pezzo', 7.00, 7),
('Olio EVO (bottiglia 0.75L)',  'pezzo', 9.50, 7),
('Olio EVO (bottiglia 1L)',     'pezzo',12.00, 7),
('Olio EVO (lattina 3L)',       'pezzo',32.00, 7),
('Olio EVO (box 5L)',           'pezzo',50.00, 7),

-- Fiori essiccati (cat 8)
('Lavanda essiccata (bustina)', 'pezzo', 3.50, 8),
('Camomilla essiccata (bustina)','pezzo',3.50, 8),
('Rosa essiccata (vasetto)',    'pezzo', 5.00, 8),
('Elicriso essiccato (bustina)','pezzo', 4.00, 8),
('Calendula essiccata (bustina)','pezzo',3.50, 8),

-- Aromatiche essiccate (cat 9)
('Rosmarino essiccato (bustina)', 'pezzo', 2.50, 9),
('Origano essiccato (bustina)',   'pezzo', 2.50, 9),
('Salvia essiccata (bustina)',    'pezzo', 2.50, 9),
('Menta essiccata (bustina)',     'pezzo', 2.50, 9),
('Timo essiccato (bustina)',      'pezzo', 2.50, 9),
('Mix aromatiche (vasetto)',      'pezzo', 5.00, 9),

-- Frutta disidratata (cat 10)
('Fichi disidratati (bustina)',   'pezzo', 4.50,10),
('Albicocche disidratate (bust)', 'pezzo', 5.00,10),
('Mele disidratate (bustina)',    'pezzo', 4.50,10),
('Prugne disidratate (bustina)',  'pezzo', 4.50,10),
('Mix frutta secca (vasetto)',    'pezzo', 6.00,10),

-- Marmellate (cat 11)
('Marmellata albicocche (vasetto)','pezzo',4.00,11),
('Confettura fichi (vasetto)',     'pezzo',4.50,11),
('Confettura more (vasetto)',      'pezzo',4.50,11),
('Marmellata arance (vasetto)',    'pezzo',4.00,11),
('Confettura prugne (vasetto)',    'pezzo',4.00,11),
('Confettura ciliegie (vasetto)',  'pezzo',5.00,11),

-- Succhi di frutta (cat 12)
('Succo mela (bottiglia)',         'pezzo', 3.50,12),
('Succo pera (bottiglia)',         'pezzo', 3.50,12),
('Succo albicocca (bottiglia)',    'pezzo', 3.50,12),
('Nettare fico (bottiglia)',       'pezzo', 4.00,12),
('Succo melograno (bottiglia)',    'pezzo', 4.50,12),

-- Vincotto (cat 13)
('Vincotto di fichi (bottiglia)',         'pezzo', 8.00,13),
('Vincotto di mele cotogne (bottiglia)',  'pezzo', 9.00,13),
('Vincotto di gelsi (bottiglia)',         'pezzo', 8.50,13),

-- Cotognata e Caramelle (cat 14)
('Cotognata (vaschetta)',          'pezzo', 5.00,14),
('Caramelle miele (vasetto)',      'pezzo', 4.50,14),
('Caramelle propoli (vasetto)',    'pezzo', 5.00,14),

-- Fichi secchi (cat 15)
('Fichi secchi naturali (vaschetta)',    'pezzo', 5.00,15),
('Fichi mandorlati (vaschetta)',         'pezzo', 6.50,15),
('Fichi al cioccolato (vaschetta)',      'pezzo', 7.00,15),

-- Olive (cat 16)
('Olive verdi in acqua (bottiglia)',  'pezzo', 4.00,16),
('Olive nere in acqua (vasetto)',     'pezzo', 4.50,16),
('Olive condite (vasetto)',           'pezzo', 5.00,16),

-- Cosmetici Naturali (cat 17)
('Unguento iperico (vasetto)',         'pezzo', 7.00,17),
('Unguento calendula (vasetto)',       'pezzo', 7.00,17),
('Unguento menta (vasetto)',           'pezzo', 7.00,17),
('Unguento salvia (vasetto)',          'pezzo', 7.00,17),
('Unguento lavanda (vasetto)',         'pezzo', 7.00,17),
('Unguento elicriso (vasetto)',        'pezzo', 7.00,17),
('Saponetta naturale',                 'pezzo', 4.50,17),
('Saponetta lavanda',                  'pezzo', 4.50,17),
('Saponetta rosmarino',                'pezzo', 4.50,17),
('Olio essenziale lavanda (contagocc)','pezzo',10.00,17),
('Olio essenziale menta (contagocc)', 'pezzo',10.00,17),
('Olio essenziale elicriso (contagocc)','pezzo',12.00,17),
('Idrolato lavanda (spray)',           'pezzo', 8.00,17),
('Idrolato calendula (spray)',         'pezzo', 8.00,17),
('Idrolato rosa (spray)',              'pezzo', 9.00,17),

-- ============================================
-- PRODOTTI RISERVA (grandi formati)
-- ============================================

-- Miele riserva (cat 18)
('Miele millefiori (secchio 5kg)',  'kg', 7.00,18),
('Miele acacia (secchio 5kg)',      'kg', 7.50,18),

-- Olio riserva (cat 18)
('Olio EVO (bidone 10L)',           'litro',10.00,18),
('Olio EVO (bidone 25L)',           'litro', 9.50,18),

-- Fiori e aromatiche riserva (cat 18)
('Lavanda essiccata (contenitore vetro)','kg',15.00,18),
('Calendula essiccata (contenitore vetro)','kg',14.00,18),
('Rosmarino essiccato (contenitore vetro)','kg',12.00,18),
('Salvia essiccata (contenitore vetro)',   'kg',12.00,18),

-- Frutta disidratata riserva (cat 18)
('Frutta disidratata mista (contenitore vetro)', 'kg',12.00,18),

-- Frutta secca (cat 18)
('Noci in guscio (sacco juta)',         'kg', 4.00,18),
('Mandorle in guscio (sacco juta)',     'kg', 5.00,18),
('Nocciole in guscio (sacco juta)',     'kg', 6.00,18),
('Noci sgusciate (contenitore vetro)',  'kg',12.00,18),
('Mandorle sgusciate (busta sottovuoto)','kg',14.00,18),
('Nocciole sgusciate (busta sottovuoto)','kg',15.00,18),

-- Cotognata riserva (cat 18)
('Cotognata (contenitore latta)',       'kg', 8.00,18),
('Cotognata (contenitore vetro)',       'kg', 9.00,18),

-- Fichi secchi riserva (cat 18)
('Fichi secchi naturali (contenitore latta)', 'kg',  7.00,18),
('Fichi mandorlati (contenitore vetro)',       'kg',  9.00,18),

-- Oleoliti e oli essenziali riserva (cat 18)
('Oleolito di iperico (bottiglia vetro)',    'litro',20.00,18),
('Oleolito di calendula (bottiglia vetro)', 'litro',20.00,18),
('Oleolito di menta (bottiglia vetro)',     'litro',18.00,18),
('Oleolito di salvia (bottiglia vetro)',    'litro',18.00,18),
('Oleolito di lavanda (bottiglia vetro)',   'litro',18.00,18),
('Oleolito di elicriso (bottiglia vetro)',  'litro',22.00,18),
('Olio essenziale lavanda (bottiglia vetro)','litro',80.00,18),
('Olio essenziale menta (bottiglia vetro)', 'litro',70.00,18),
('Idrolato lavanda (recipiente vetro)',     'litro',15.00,18),
('Idrolato calendula (recipiente vetro)',   'litro',15.00,18),
('Idrolato rosa (recipiente vetro)',        'litro',18.00,18);

-- ============================================
-- LUOGHI
-- ============================================
INSERT INTO LUOGO (nome, indirizzo, tipo) VALUES
('Campo principale',     'Via Campestre 12',    'campo'),
('Laboratorio',          'Via del Lavoro 5',    'laboratorio'),
('Punto vendita centro', 'Piazza Mercato 3',    'punto vendita'),
('Magazzino centrale',   'Via Industriale 20',  'magazzino');

-- ============================================
-- DISPENSE
-- ============================================
INSERT INTO DISPENSA (nome, ubicazione, idLuogo) VALUES
('Dispensa A',         'Piano terra - settore nord', 2),
('Dispensa B',         'Piano terra - settore sud',  2),
('Magazzino riserve',  'Sala principale',            4);

-- ============================================
-- LAVORAZIONI
-- ============================================
INSERT INTO LAVORAZIONE (tipoLavorazione, dataLavorazione, quantitaIngresso, quantitaOttenuta, idProdotto, idLuogo) VALUES
('Spremitura olio',          '2026-03-15', 500.00,  80.00, (SELECT idProdotto FROM PRODOTTO WHERE nome='Olio EVO (bottiglia 0.75L)' LIMIT 1), 2),
('Estrazione miele',         '2026-03-20', 100.00,  90.00, (SELECT idProdotto FROM PRODOTTO WHERE nome='Miele millefiori (vasetto)' LIMIT 1), 2),
('Cottura marmellata',       '2026-04-01',  50.00,  45.00, (SELECT idProdotto FROM PRODOTTO WHERE nome='Marmellata albicocche (vasetto)' LIMIT 1), 2),
('Essiccazione lavanda',     '2026-04-03',  30.00,  10.00, (SELECT idProdotto FROM PRODOTTO WHERE nome='Lavanda essiccata (bustina)' LIMIT 1), 2),
('Distillazione idrolato',   '2026-04-05',  20.00,  15.00, (SELECT idProdotto FROM PRODOTTO WHERE nome='Idrolato lavanda (spray)' LIMIT 1), 2);

-- ============================================
-- RISERVE
-- ============================================
INSERT INTO RISERVA (nome, dataProduzione, quantitaIniziale, quantitaAttuale, prezzoAlKg, contenitore, idProdotto, idLavorazione, idDispensa) VALUES
('Olio EVO 2026 - Lotto 1',   '2026-03-15',  80.00, 65.00, 12.00, 'Bidone 25L',           (SELECT idProdotto FROM PRODOTTO WHERE nome='Olio EVO (bidone 25L)' LIMIT 1),             1, 1),
('Miele millefiori 2026',     '2026-03-20',  90.00, 75.00,  7.00, 'Secchio 5kg',           (SELECT idProdotto FROM PRODOTTO WHERE nome='Miele millefiori (secchio 5kg)' LIMIT 1),    2, 2),
('Marmellata Albicocche',     '2026-04-01',  30.00, 22.00, 10.00, 'Contenitore vetro 10L', (SELECT idProdotto FROM PRODOTTO WHERE nome='Marmellata albicocche (vasetto)' LIMIT 1),   3, 2),
('Lavanda essiccata 2026',    '2026-04-03',  10.00,  8.50, 15.00, 'Contenitore vetro 5L',  (SELECT idProdotto FROM PRODOTTO WHERE nome='Lavanda essiccata (contenitore vetro)' LIMIT 1), 4, 2);

-- ============================================
-- CONFEZIONAMENTI
-- ============================================
INSERT INTO CONFEZIONAMENTO (dataProduzione, dataConfezionamento, numeroConfezioni, pesoNetto, prezzo, giacenzaAttuale, idProdotto, idLavorazione, idRiserva, idLuogo) VALUES
-- Olio EVO 0.75L
('2026-03-15', '2026-03-16', 100, 0.75,  9.50,  85, (SELECT idProdotto FROM PRODOTTO WHERE nome='Olio EVO (bottiglia 0.75L)' LIMIT 1), 1, NULL, 2),
-- Olio EVO 0.5L
('2026-03-15', '2026-03-16',  50, 0.50,  7.00,  42, (SELECT idProdotto FROM PRODOTTO WHERE nome='Olio EVO (bottiglia 0.5L)' LIMIT 1), 1, NULL, 2),
-- Miele millefiori vasetto
('2026-03-20', '2026-03-21', 120, 0.50,  8.50, 100, (SELECT idProdotto FROM PRODOTTO WHERE nome='Miele millefiori (vasetto)' LIMIT 1), 2, NULL, 2),
-- Marmellata albicocche
('2026-04-01', '2026-04-02',  80, 0.25,  4.00,  65, (SELECT idProdotto FROM PRODOTTO WHERE nome='Marmellata albicocche (vasetto)' LIMIT 1), 3, NULL, 2),
-- Lavanda essiccata bustina
('2026-04-03', '2026-04-04',  60, 0.05,  3.50,  55, (SELECT idProdotto FROM PRODOTTO WHERE nome='Lavanda essiccata (bustina)' LIMIT 1), 4, NULL, 2),
-- Confettura fichi
('2026-04-01', '2026-04-02',  40, 0.25,  4.50,  35, (SELECT idProdotto FROM PRODOTTO WHERE nome='Confettura fichi (vasetto)' LIMIT 1), 3, NULL, 2),
-- Unguento lavanda
('2026-04-05', '2026-04-06',  30, 0.05,  7.00,  28, (SELECT idProdotto FROM PRODOTTO WHERE nome='Unguento lavanda (vasetto)' LIMIT 1), NULL, NULL, 2),
-- Idrolato lavanda spray
('2026-04-05', '2026-04-06',  25, 0.10,  8.00,  23, (SELECT idProdotto FROM PRODOTTO WHERE nome='Idrolato lavanda (spray)' LIMIT 1), 5, NULL, 2);

-- ============================================
-- VENDITE di esempio
-- ============================================
INSERT INTO VENDITA (dataVendita, totaleCalcolato, totalePagato, note, idCliente, idLuogo) VALUES
('2026-03-28 10:30:00', 28.00, 28.00, 'Vendita online',    1, 3),
('2026-03-29 11:00:00', 15.00, 15.00, NULL,                3, 3),
('2026-04-05 10:30:00', 28.00, 28.00, 'Cliente abituale',  1, 3),
('2026-04-06 15:20:00', 45.50, 45.50, NULL,                2, 3),
('2026-04-07 09:15:00', 19.00, 19.00, 'Vendita al banco',  3, 3),
('2026-04-08 14:00:00', 33.50, 33.50, NULL,                1, 3),
('2026-04-09 11:30:00', 12.00, 10.00, 'Sconto fedeltà',    2, 3);

-- ============================================
-- DETTAGLI VENDITE
-- ============================================
INSERT INTO DETTAGLIO_VENDITA (idVendita, tipoVendita, quantita, pesoVenduto, prezzoUnitario, omaggio, idProdotto, idConfezionamento) VALUES
-- Vendita 1 - Giulia
(1, 'CONFEZIONATO', 2, NULL, 9.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Olio EVO (bottiglia 0.75L)' LIMIT 1), 1),
(1, 'CONFEZIONATO', 2, NULL, 4.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Marmellata albicocche (vasetto)' LIMIT 1), 4),
-- Vendita 2 - Occasionale
(2, 'CONFEZIONATO', 1, NULL, 8.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Miele millefiori (vasetto)' LIMIT 1), 3),
(2, 'CONFEZIONATO', 1, NULL, 3.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Lavanda essiccata (bustina)' LIMIT 1), 5),
-- Vendita 3 - Giulia
(3, 'CONFEZIONATO', 2, NULL, 9.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Olio EVO (bottiglia 0.75L)' LIMIT 1), 1),
(3, 'CONFEZIONATO', 1, NULL, 4.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Confettura fichi (vasetto)' LIMIT 1), 6),
-- Vendita 4 - Luca
(4, 'CONFEZIONATO', 3, NULL, 9.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Olio EVO (bottiglia 0.75L)' LIMIT 1), 1),
(4, 'CONFEZIONATO', 3, NULL, 8.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Miele millefiori (vasetto)' LIMIT 1), 3),
-- Vendita 5 - Occasionale
(5, 'CONFEZIONATO', 1, NULL, 7.00,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Unguento lavanda (vasetto)' LIMIT 1), 7),
(5, 'CONFEZIONATO', 1, NULL, 8.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Miele millefiori (vasetto)' LIMIT 1), 3),
-- Vendita 6 - Giulia
(6, 'CONFEZIONATO', 2, NULL, 8.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Miele millefiori (vasetto)' LIMIT 1), 3),
(6, 'CONFEZIONATO', 2, NULL, 4.00,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Marmellata albicocche (vasetto)' LIMIT 1), 4),
(6, 'CONFEZIONATO', 1, NULL, 8.00,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Idrolato lavanda (spray)' LIMIT 1), 8),
-- Vendita 7 - Luca
(7, 'CONFEZIONATO', 2, NULL, 3.50,  FALSE, (SELECT idProdotto FROM PRODOTTO WHERE nome='Lavanda essiccata (bustina)' LIMIT 1), 5),
(7, 'CONFEZIONATO', 1, NULL, 4.50,  TRUE,  (SELECT idProdotto FROM PRODOTTO WHERE nome='Confettura fichi (vasetto)' LIMIT 1), 6);

-- ============================================
-- SPOSTAMENTI
-- ============================================
INSERT INTO SPOSTAMENTO (dataSpostamento, quantita, note, idRiserva, idConfezionamento, idLuogoOrigine, idLuogoDestinazione) VALUES
('2026-04-03 14:00:00', 20, 'Trasferimento per punto vendita', NULL, 1, 2, 3),
('2026-04-04 11:30:00', 15, 'Rifornimento magazzino',          NULL, 3, 2, 4);
