<?php
/**
 * VENDITE - Admin
 * Azienda Agricola
 * Lista vendite + form nuova vendita manuale
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
$pageTitle = 'Vendite';

// Filtri
$filtroCliente = isset($_GET['idCliente']) ? (int)$_GET['idCliente'] : null;
$filtroAnno    = isset($_GET['anno']) ? (int)$_GET['anno'] : null;
$dataInizio    = $_GET['dataInizio'] ?? null;
$dataFine      = $_GET['dataFine'] ?? null;

// Query vendite
$sql = "SELECT v.*, c.nome as nomeCliente, c.nickname, l.nome as nomeLuogo
        FROM VENDITA v
        INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
        INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
        WHERE 1=1";
$params = [];

if ($filtroCliente) {
    $sql .= " AND v.idCliente = ?";
    $params[] = $filtroCliente;
}
if ($filtroAnno) {
    $sql .= " AND YEAR(v.dataVendita) = ?";
    $params[] = $filtroAnno;
}
if ($dataInizio) {
    $sql .= " AND DATE(v.dataVendita) >= ?";
    $params[] = $dataInizio;
}
if ($dataFine) {
    $sql .= " AND DATE(v.dataVendita) <= ?";
    $params[] = $dataFine;
}

$sql .= " ORDER BY v.dataVendita DESC LIMIT 100";
$vendite = fetchAll($pdo, $sql, $params);

// Dati per form nuova vendita
$clienti       = fetchAll($pdo, "SELECT * FROM CLIENTE ORDER BY nome");
$luoghi        = fetchAll($pdo, "SELECT * FROM LUOGO ORDER BY nome");
$prodotti      = fetchAll($pdo, "SELECT p.*, c.nome as nomeCategoria FROM PRODOTTO p INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria ORDER BY p.nome");
$confezionamenti = fetchAll($pdo,
    "SELECT conf.*, p.nome as nomeProdotto FROM CONFEZIONAMENTO conf
     INNER JOIN PRODOTTO p ON conf.idProdotto = p.idProdotto
     WHERE conf.giacenzaAttuale > 0
     ORDER BY p.nome, conf.dataConfezionamento DESC"
);
$riserve = fetchAll($pdo,
    "SELECT r.*, p.nome as nomeProdotto FROM RISERVA r
     INNER JOIN PRODOTTO p ON r.idProdotto = p.idProdotto
     WHERE r.quantitaAttuale > 0
     ORDER BY p.nome"
);
$tuttiiClienti = fetchAll($pdo, "SELECT * FROM CLIENTE ORDER BY nome");

include '../includes/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Storico Vendite</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovaVendita">
        <i class="fas fa-plus me-1"></i> Nuova Vendita
    </button>
</div>

<!-- Filtri rapidi -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">Cliente</label>
                <select name="idCliente" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    <?php foreach ($tuttiiClienti as $c): ?>
                        <option value="<?php echo $c['idCliente']; ?>"
                            <?php echo $filtroCliente == $c['idCliente'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Anno</label>
                <select name="anno" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $filtroAnno == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Dal</label>
                <input type="date" name="dataInizio" class="form-control form-control-sm" value="<?php echo htmlspecialchars($dataInizio ?? ''); ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Al</label>
                <input type="date" name="dataFine" class="form-control form-control-sm" value="<?php echo htmlspecialchars($dataFine ?? ''); ?>">
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-sm btn-primary me-1">
                    <i class="fas fa-filter me-1"></i>Filtra
                </button>
                <a href="/admin/vendite.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Totali -->
<?php
$totaleVendite = count($vendite);
$importoTot = array_sum(array_column($vendite, 'totalePagato'));
?>
<div class="mb-3 text-muted small">
    <strong><?php echo $totaleVendite; ?></strong> vendite trovate —
    Incasso totale: <strong class="text-dark"><?php echo formatPrice($importoTot); ?></strong>
</div>

<!-- Tabella -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Luogo</th>
                        <th>Note</th>
                        <th class="text-end">Calcolato</th>
                        <th class="text-end">Pagato</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendite)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Nessuna vendita trovata</td></tr>
                    <?php else: ?>
                    <?php foreach ($vendite as $v): ?>
                        <tr>
                            <td><strong>#<?php echo $v['idVendita']; ?></strong></td>
                            <td><?php echo formatDate($v['dataVendita'], true); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($v['nomeCliente']); ?></div>
                                <?php if ($v['nickname']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($v['nickname']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($v['nomeLuogo']); ?></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($v['note'] ?? '—'); ?></small></td>
                            <td class="text-end text-muted"><?php echo formatPrice($v['totaleCalcolato']); ?></td>
                            <td class="text-end">
                                <strong class="<?php echo $v['totalePagato'] < $v['totaleCalcolato'] ? 'text-warning' : ''; ?>">
                                    <?php echo formatPrice($v['totalePagato']); ?>
                                </strong>
                            </td>
                            <td class="text-center">
                                <a href="/admin/vendita-dettaglio.php?id=<?php echo $v['idVendita']; ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Dettaglio">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================
     MODAL NUOVA VENDITA
     ============================ -->
<div class="modal fade" id="modalNuovaVendita" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cash-register me-2"></i>Nuova Vendita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/api/vendita_admin.php" id="formNuovaVendita">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <!-- Testata -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select name="idCliente" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($clienti as $c): ?>
                                    <option value="<?php echo $c['idCliente']; ?>">
                                        <?php echo htmlspecialchars($c['nome']); ?>
                                        <?php echo $c['nickname'] ? '(' . htmlspecialchars($c['nickname']) . ')' : ''; ?>
                                        <?php echo $c['occasionale'] ? '[occasionale]' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Luogo vendita <span class="text-danger">*</span></label>
                            <select name="idLuogo" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($luoghi as $lu): ?>
                                    <option value="<?php echo $lu['idLuogo']; ?>"
                                        <?php echo $lu['tipo'] === 'punto vendita' ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lu['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data vendita</label>
                            <input type="datetime-local" name="dataVendita" class="form-control"
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Totale pagato (€)</label>
                            <input type="number" name="totalePagato" id="inputTotalePagato"
                                   class="form-control" step="0.01" min="0" placeholder="Auto">
                            <div class="form-text">Lascia vuoto = calcolato</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control" placeholder="Sconto, omaggio, note...">
                    </div>

                    <hr>

                    <!-- Aggiunta prodotti -->
                    <h6 class="mb-3">Prodotti venduti</h6>

                    <!-- Selettore tipo -->
                    <div class="row g-2 mb-3 align-items-end" id="selettoreNuovoItem">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Tipo vendita</label>
                            <select id="tipoVenditaSelector" class="form-select form-select-sm">
                                <option value="FRESCO_SFUSO">Fresco sfuso</option>
                                <option value="CONFEZIONATO">Confezionato</option>
                                <option value="RISERVA_SFUSA">Riserva sfusa</option>
                            </select>
                        </div>

                        <!-- FRESCO SFUSO -->
                        <div id="panelFresco" class="col-md-7 row g-2">
                            <div class="col-5">
                                <label class="form-label small mb-1">Prodotto</label>
                                <select id="frescoIdProdotto" class="form-select form-select-sm">
                                    <option value="">-- Seleziona --</option>
                                    <?php foreach ($prodotti as $p): ?>
                                        <option value="<?php echo $p['idProdotto']; ?>"
                                                data-prezzo="<?php echo $p['prezzoBase']; ?>"
                                                data-unita="<?php echo $p['unitaMisura']; ?>">
                                            <?php echo htmlspecialchars($p['nome']); ?>
                                            (<?php echo htmlspecialchars($p['nomeCategoria']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small mb-1">Quantità / kg</label>
                                <input type="number" id="frescoQta" class="form-control form-control-sm" step="0.01" min="0.01" value="1">
                            </div>
                            <div class="col-2">
                                <label class="form-label small mb-1">Omaggio</label>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="frescoOmaggio">
                                </div>
                            </div>
                        </div>

                        <!-- CONFEZIONATO -->
                        <div id="panelConfezionato" class="col-md-7 row g-2" style="display:none">
                            <div class="col-6">
                                <label class="form-label small mb-1">Confezione (prodotto)</label>
                                <select id="confIdConfezionamento" class="form-select form-select-sm">
                                    <option value="">-- Seleziona --</option>
                                    <?php foreach ($confezionamenti as $c): ?>
                                        <option value="<?php echo $c['idConfezionamento']; ?>"
                                                data-idprodotto="<?php echo $c['idProdotto']; ?>"
                                                data-prezzo="<?php echo $c['prezzo']; ?>"
                                                data-giacenza="<?php echo $c['giacenzaAttuale']; ?>">
                                            <?php echo htmlspecialchars($c['nomeProdotto']); ?>
                                            — <?php echo formatWeight($c['pesoNetto'], 'kg'); ?>/conf
                                            — <?php echo formatPrice($c['prezzo']); ?>
                                            (giac: <?php echo $c['giacenzaAttuale']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-2">
                                <label class="form-label small mb-1">N° conf.</label>
                                <input type="number" id="confQta" class="form-control form-control-sm" min="1" value="1">
                            </div>
                            <div class="col-2">
                                <label class="form-label small mb-1">Omaggio</label>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="confOmaggio">
                                </div>
                            </div>
                        </div>

                        <!-- RISERVA SFUSA -->
                        <div id="panelRiserva" class="col-md-7 row g-2" style="display:none">
                            <div class="col-5">
                                <label class="form-label small mb-1">Riserva</label>
                                <select id="riservaIdRiserva" class="form-select form-select-sm">
                                    <option value="">-- Seleziona --</option>
                                    <?php foreach ($riserve as $r): ?>
                                        <option value="<?php echo $r['idRiserva']; ?>"
                                                data-idprodotto="<?php echo $r['idProdotto']; ?>"
                                                data-prezzo="<?php echo $r['prezzoAlKg']; ?>"
                                                data-disponibile="<?php echo $r['quantitaAttuale']; ?>">
                                            <?php echo htmlspecialchars($r['nome']); ?>
                                            (<?php echo formatWeight($r['quantitaAttuale'], 'kg'); ?> disp.)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small mb-1">Peso (kg)</label>
                                <input type="number" id="riservaPeso" class="form-control form-control-sm" step="0.01" min="0.01" value="1">
                            </div>
                            <div class="col-2">
                                <label class="form-label small mb-1">Omaggio</label>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="riservaOmaggio">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-success btn-sm w-100" onclick="aggiungiItem()">
                                <i class="fas fa-plus me-1"></i>Aggiungi
                            </button>
                        </div>
                    </div>

                    <!-- Lista items aggiunti -->
                    <div id="venditaItems">
                        <!-- Items aggiunti dinamicamente -->
                    </div>

                    <!-- Riepilogo totale -->
                    <div class="border rounded p-3 mt-3 bg-light">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotale calcolato:</span>
                            <strong id="subtotaleDisplay">0,00 €</strong>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Totale da pagare:</span>
                            <strong id="totaleDisplay" class="fs-5">0,00 €</strong>
                        </div>
                    </div>

                    <!-- Hidden inputs per items -->
                    <div id="hiddenItems"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary" id="btnConfermaVendita" disabled>
                        <i class="fas fa-check me-1"></i>Conferma Vendita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Dati prodotti freschi
const prodottiFreschi = <?php echo json_encode(array_map(function($p) {
    return ['idProdotto' => $p['idProdotto'], 'nome' => $p['nome'], 'prezzoBase' => $p['prezzoBase'], 'unitaMisura' => $p['unitaMisura']];
}, $prodotti)); ?>;

let venditaItems = [];
let itemIndex = 0;

// Switch panels in base al tipo selezionato
document.getElementById('tipoVenditaSelector').addEventListener('change', function() {
    const tipo = this.value;
    document.getElementById('panelFresco').style.display       = tipo === 'FRESCO_SFUSO'  ? '' : 'none';
    document.getElementById('panelConfezionato').style.display = tipo === 'CONFEZIONATO'  ? '' : 'none';
    document.getElementById('panelRiserva').style.display      = tipo === 'RISERVA_SFUSA' ? '' : 'none';
});

function aggiungiItem() {
    const tipo = document.getElementById('tipoVenditaSelector').value;
    let item = { tipo: tipo, index: itemIndex++ };
    let errore = null;

    if (tipo === 'FRESCO_SFUSO') {
        const sel = document.getElementById('frescoIdProdotto');
        const opt = sel.options[sel.selectedIndex];
        if (!sel.value) { errore = 'Seleziona un prodotto'; }
        else {
            const qta   = parseFloat(document.getElementById('frescoQta').value);
            const unita = opt.dataset.unita;
            item.idProdotto   = sel.value;
            item.nomeProdotto = opt.text.split('(')[0].trim();
            item.unitaMisura  = unita;
            item.prezzo       = parseFloat(opt.dataset.prezzo);
            item.omaggio      = document.getElementById('frescoOmaggio').checked ? '1' : '0';
            if (unita === 'pezzo') {
                item.quantita = qta;
                item.label    = `${item.nomeProdotto} — ${qta} pz`;
                item.subtotale = item.omaggio === '1' ? 0 : item.prezzo * qta;
            } else {
                item.pesoVenduto = qta;
                item.label       = `${item.nomeProdotto} — ${qta} kg`;
                item.subtotale   = item.omaggio === '1' ? 0 : item.prezzo * qta;
            }
        }
    } else if (tipo === 'CONFEZIONATO') {
        const sel = document.getElementById('confIdConfezionamento');
        const opt = sel.options[sel.selectedIndex];
        if (!sel.value) { errore = 'Seleziona una confezione'; }
        else {
            const qta = parseInt(document.getElementById('confQta').value);
            const giac = parseInt(opt.dataset.giacenza);
            if (qta > giac) { errore = `Giacenza insufficiente (disponibili: ${giac})`; }
            else {
                item.idProdotto        = opt.dataset.idprodotto;
                item.idConfezionamento = sel.value;
                item.quantita          = qta;
                item.prezzo            = parseFloat(opt.dataset.prezzo);
                item.omaggio           = document.getElementById('confOmaggio').checked ? '1' : '0';
                item.label             = `${opt.text.split('—')[0].trim()} × ${qta}`;
                item.subtotale         = item.omaggio === '1' ? 0 : item.prezzo * qta;
            }
        }
    } else { // RISERVA_SFUSA
        const sel = document.getElementById('riservaIdRiserva');
        const opt = sel.options[sel.selectedIndex];
        if (!sel.value) { errore = 'Seleziona una riserva'; }
        else {
            const peso  = parseFloat(document.getElementById('riservaPeso').value);
            const disp  = parseFloat(opt.dataset.disponibile);
            if (peso > disp) { errore = `Quantità insufficiente (disponibili: ${disp} kg)`; }
            else {
                item.idProdotto  = opt.dataset.idprodotto;
                item.idRiserva   = sel.value;
                item.pesoVenduto = peso;
                item.prezzo      = parseFloat(opt.dataset.prezzo);
                item.omaggio     = document.getElementById('riservaOmaggio').checked ? '1' : '0';
                item.label       = `${opt.text.split('(')[0].trim()} — ${peso} kg`;
                item.subtotale   = item.omaggio === '1' ? 0 : item.prezzo * peso;
            }
        }
    }

    if (errore) { alert(errore); return; }

    venditaItems.push(item);
    renderItems();
    aggiornaHidden();
    aggiornaTotale();
}

function rimuoviItem(idx) {
    venditaItems = venditaItems.filter(i => i.index !== idx);
    renderItems();
    aggiornaHidden();
    aggiornaTotale();
}

function renderItems() {
    const container = document.getElementById('venditaItems');
    if (venditaItems.length === 0) {
        container.innerHTML = '<p class="text-muted small text-center py-2">Nessun prodotto aggiunto</p>';
        document.getElementById('btnConfermaVendita').disabled = true;
        return;
    }
    document.getElementById('btnConfermaVendita').disabled = false;

    container.innerHTML = venditaItems.map(item => `
        <div class="d-flex justify-content-between align-items-center border-bottom py-2 px-1">
            <div>
                <span class="badge bg-secondary me-2">${tipoLabel(item.tipo)}</span>
                ${escHtml(item.label)}
                ${item.omaggio === '1' ? '<span class="badge bg-success ms-2">Omaggio</span>' : ''}
            </div>
            <div class="d-flex align-items-center gap-3">
                <strong>${formatEur(item.subtotale)}</strong>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="rimuoviItem(${item.index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function aggiornaHidden() {
    const container = document.getElementById('hiddenItems');
    container.innerHTML = '';
    venditaItems.forEach((item, i) => {
        const add = (name, val) => {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = `items[${i}][${name}]`;
            inp.value = val ?? '';
            container.appendChild(inp);
        };
        add('tipo', item.tipo);
        add('idProdotto', item.idProdotto);
        add('omaggio', item.omaggio || '0');
        if (item.quantita    !== undefined) add('quantita',         item.quantita);
        if (item.pesoVenduto !== undefined) add('pesoVenduto',      item.pesoVenduto);
        if (item.idConfezionamento)         add('idConfezionamento', item.idConfezionamento);
        if (item.idRiserva)                 add('idRiserva',         item.idRiserva);
        if (item.unitaMisura)               add('unitaMisura',       item.unitaMisura);
    });
}

function aggiornaTotale() {
    const sub = venditaItems.reduce((s, i) => s + i.subtotale, 0);
    document.getElementById('subtotaleDisplay').textContent = formatEur(sub);
    document.getElementById('totaleDisplay').textContent    = formatEur(sub);
    // pre-compila il campo totale pagato se non è già modificato
    const tp = document.getElementById('inputTotalePagato');
    if (!tp.dataset.manuallySet) {
        tp.placeholder = formatEur(sub);
    }
}

document.getElementById('inputTotalePagato').addEventListener('input', function() {
    this.dataset.manuallySet = this.value ? '1' : '';
});

function tipoLabel(t) {
    return {FRESCO_SFUSO:'Fresco sfuso', CONFEZIONATO:'Confezionato', RISERVA_SFUSA:'Riserva sfusa'}[t] || t;
}
function formatEur(n) {
    return new Intl.NumberFormat('it-IT', {style:'currency', currency:'EUR'}).format(n);
}
function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Inizializza lista vuota
renderItems();
</script>

<?php include '../includes/footer.php'; ?>
