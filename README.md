# Azienda Agricola - Gestionale Web

Sistema gestionale completo per aziende agricole con e-commerce integrato.

## 🌾 Caratteristiche Principali

### Area Cliente
- **Catalogo Prodotti** con filtro per categorie
- **Dettaglio Prodotto** con selezione formato e quantità
- **Carrello** con gestione quantità e rimozione items
- **Checkout** a 3 step con conferma ordine
- **Profilo Cliente** con storico acquisti e statistiche
- **Controllo giacenze real-time**

### Area Amministrazione
- **Dashboard** con KPI, avvisi urgenti e attività recenti
- **Gestione Prodotti** (CRUD con validazione duplicati)
- **Gestione Categorie** con controllo dipendenze
- **Lavorazioni** (registrazione processo produttivo)
- **Confezionamenti** con tracking giacenze
- **Riserve in Dispensa** con progress bar
- **Vendite Manuali** con calcolo automatico sconto
- **Anagrafica Clienti** con statistiche aggregate
- **Archivio/Report** con filtri ed export CSV
- **Gestione Luoghi** (sedi aziendali)

## 🛠️ Stack Tecnologico

- **Backend**: PHP 7.4+ (architettura procedurale organizzata)
- **Database**: MySQL 5.7+ con PDO
- **Frontend**: HTML5, CSS3 custom, JavaScript vanilla
- **Ambiente**: Docker (locale) con phpMyAdmin

## 📁 Struttura Progetto

```
azienda-agricola/
├── sql/                # Database schema, trigger, seed
├── includes/           # File condivisi PHP
├── api/               # Endpoint REST
├── cliente/           # Area cliente
├── admin/             # Area amministrazione
├── css/               # Fogli di stile modulari
└── js/                # JavaScript utilities
```

## 🎨 Design

**Palette "Naturale Elegante":**
- Verde oliva `#6B7C4A`
- Beige caldo `#F5F0E8`
- Marrone terra `#8B5E3C`
- Bianco avorio `#FDFAF5`

**Tipografia:**
- Titoli: Playfair Display (serif)
- Testo: Inter (sans-serif)

## 🚀 Installazione

### Requisiti
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx

### Setup Database

```bash
# Importa schema
mysql -u root -p < sql/schema.sql

# Importa trigger
mysql -u root -p azienda_agricola < sql/trigger.sql

# Importa dati esempio (opzionale)
mysql -u root -p azienda_agricola < sql/seed.sql
```

### Configurazione

Modifica `includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'azienda_agricola');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## 👤 Credenziali Demo

**Admin:**
- Email: `admin@azienda.it`
- Password: `admin123`

**Cliente:**
- Email: `giulia@email.it`
- Password: `cliente123`

## 📊 Database

### Tabelle Principali

- **UTENTE**: Autenticazione e ruoli
- **CLIENTE**: Anagrafica clienti
- **CATEGORIA**: Categorie prodotti
- **PRODOTTO**: Anagrafica prodotti
- **STORICO_PREZZI**: Variazioni prezzi (automatico via trigger)
- **LAVORAZIONE**: Processo produttivo
- **RISERVA**: Prodotti in dispensa
- **CONFEZIONAMENTO**: Prodotti confezionati
- **VENDITA**: Testata ordini
- **DETTAGLIO_VENDITA**: Righe ordini
- **LUOGO**: Sedi aziendali
- **DISPENSA**: Luoghi conservazione
- **SPOSTAMENTO**: Movimentazioni

### Trigger SQL

1. **Storico Prezzi**: Salva automaticamente vecchi prezzi
2. **Giacenza Confezionamento**: Inizializza giacenza
3. **Controllo Giacenze**: Impedisce valori negativi
4. **Validazione Vendite**: Verifica coerenza dati

## 🔒 Sicurezza

- Password hash con **bcrypt**
- **CSRF token** per form critici
- **Prepared statements** PDO (SQL injection protection)
- **Sanitizzazione input** su tutti i dati utente
- **Timeout sessione** (30 minuti inattività)
- **Validazione lato client e server**

## 💡 Funzionalità Avanzate

### Gestione Giacenze
- Controllo disponibilità real-time
- Scalatura automatica post-vendita
- Blocco ordini se giacenza insufficiente
- Badge visivi (disponibile/esaurito/pochi pezzi)

### Sistema Carrello
- Sessione persistente
- Aggiornamento quantità dinamico
- Validazione giacenze
- Calcolo totali automatico

### Ordini
- Transazioni atomiche
- Rollback automatico su errore
- Conferma via email (stub)
- Storico completo

### Report
- Filtri anno/categoria
- Export CSV con BOM UTF-8
- Statistiche aggregate
- Search real-time

## 📱 Responsive

Mobile-first design con breakpoint:
- Desktop: 1024px+
- Tablet: 768px - 1023px
- Mobile: < 768px

## 🧪 Testing

### Dati di Test

Il file `seed.sql` include:
- 3 utenti (1 admin, 2 clienti)
- 14 prodotti in 6 categorie
- 4 luoghi
- 3 dispense
- Lavorazioni, confezionamenti e riserve
- Vendite di esempio

## 📝 Note Tecniche

### Flusso Vendita
1. Cliente aggiunge prodotto al carrello
2. Sistema verifica giacenza disponibile
3. Checkout: riepilogo e note
4. Conferma: transazione DB (scalatura giacenza atomica)
5. Redirect a pagina conferma
6. Carrello svuotato

### Flusso Lavorazione
1. Admin registra lavorazione (input → output)
2. Può confezionare direttamente o mettere in riserva
3. Riserva può essere venduta sfusa o confezionata
4. Confezionamento crea giacenza vendibile

### Prezzi
- **Fresco Sfuso**: `prezzoBase × quantità`
- **Confezionato**: `prezzo confezione × numero`
- **Riserva Sfusa**: `prezzoAlKg × peso`
- **Sconto**: `totale − (totale × % / 100)`

## 🤝 Contributi

Progetto sviluppato come gestionale custom per aziende agricole.

## 📄 Licenza

Proprietario - Tutti i diritti riservati

---

**Sviluppato con** ❤️ **per aziende agricole italiane**
