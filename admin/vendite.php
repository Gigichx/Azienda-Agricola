<?php
/**
 * VENDITE - Admin — FIXED
 * - Modal Bootstrap 5 funzionante
 * - Script inline senza dipendenze da main.js
 * - aggiungiRiga() robusto con gestione errori
 * - ricalcolaTotaleModal() non crasha su tipo non CONFEZIONATO
 * - capQuantitaRiga() non dipende da showToast al momento del load
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Vendite';

$sql = "SELECT v.*, c.nome as nomeCliente, c.occasionale, l.nome as nomeLuogo
        FROM VENDITA v
        INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
        INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
        ORDER BY v.dataVendita DESC
        LIMIT 50";
$vendite = fetchAll($conn, $sql);

$clienti = fetchAll($conn, "SELECT idCliente, nome, occasionale FROM CLIENTE ORDER BY occasionale DESC, nome ASC");
$luoghi  = fetchAll($conn, "SELECT idLuogo, nome, tipo FROM LUOGO ORDER BY nome");
$prodotti = fetchAll($conn,
    "SELECT p.idProdotto, p.nome, p.unitaMisura, p.prezzoBase, c.nome as nomeCategoria
     FROM PRODOTTO p
     INNER JOIN CATEGORIA c ON p.idCategoria = c.idCategoria
     ORDER BY c.nome, p.nome");

include '../includes/header_admin.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h4 mb-0 fw-bold">Vendite</h1>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuovaVendita">
        <i class="fas fa-plus me-1"></i>Nuova Vendita
    </button>
</div>

<!-- Storico vendite -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">Storico Vendite</h6>
        <span class="badge bg-light text-secondary">ultime 50</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Luogo</th>
                        <th class="text-end">Totale</th>
                        <th class="text-center pe-3">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendite as $v): ?>
                    <tr>
                        <td class="ps-3 fw-semibold text-muted small">#<?php echo $v['idVendita']; ?></td>
                        <td class="text-muted small"><?php echo formatDate($v['dataVendita'], true); ?></td>
                        <td>
                            <?php echo htmlspecialchars($v['nomeCliente']); ?>
                            <?php if ($v['occasionale']): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">Occ.</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($v['nomeLuogo']); ?></td>
                        <td class="text-end fw-semibold"><?php echo formatPrice($v['totalePagato']); ?></td>
                        <td class="text-center pe-3">
                            <a href="/admin/vendita-dettaglio.php?id=<?php echo $v['idVendita']; ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($vendite)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Nessuna vendita registrata</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL NUOVA VENDITA — Bootstrap 5 nativo
     ============================================================ -->
<div class="modal fade" id="modalNuovaVendita" tabindex="-1" aria-labelledby="modalNuovaVenditaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form action="/api/vendita_admin.php" method="POST" id="formNuovaVendita">
                <input type="hidden" name="action" value="create">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modalNuovaVenditaLabel">
                        <i class="fas fa-cash-register me-2 text-success"></i>Nuova Vendita
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>

                <div class="modal-body">

                    <!-- Riga 1: Cliente + Luogo -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">
                                Cliente <span class="text-danger">*</span>
                            </label>
                            <select name="idCliente" class="form-select form-select-sm" required>
                                <option value="">— Seleziona cliente —</option>
                                <?php foreach ($clienti as $cl): ?>
                                    <option value="<?php echo $cl['idCliente']; ?>"
                                        <?php echo $cl['occasionale'] ? 'style="color:#d97706;font-weight:600"' : ''; ?>>
                                        <?php echo htmlspecialchars($cl['nome']); ?>
                                        <?php echo $cl['occasionale'] ? ' ★' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">
                                Luogo <span class="text-danger">*</span>
                            </label>
                            <select name="idLuogo" class="form-select form-select-sm" required>
                                <option value="">— Seleziona luogo —</option>
                                <?php foreach ($luoghi as $l): ?>
                                    <option value="<?php echo $l['idLuogo']; ?>"
                                            <?php echo $l['tipo'] === 'punto vendita' ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($l['nome']); ?>
                                        (<?php echo htmlspecialchars($l['tipo']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Riga 2: Data + Totale pagato -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Data Vendita</label>
                            <input type="datetime-local" name="dataVendita" class="form-control form-control-sm"
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">
                                Totale Pagato (€)
                                <small class="text-muted fw-normal">lascia vuoto = automatico</small>
                            </label>
                            <input type="number" name="totalePagato" class="form-control form-control-sm"
                                   id="inputTotalePagato"
                                   step="0.01" min="0" placeholder="Calcolato automaticamente">
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm"
                               placeholder="Note opzionali sulla vendita…" maxlength="500">
                    </div>

                    <!-- Sezione prodotti -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-semibold small text-uppercase text-muted">Prodotti</h6>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="aggiungiRiga()">
                            <i class="fas fa-plus me-1"></i>Aggiungi prodotto
                        </button>
                    </div>

                    <div id="righeVendita" class="d-flex flex-column gap-2">
                        <!-- Le righe vengono aggiunte dinamicamente -->
                    </div>

                    <div id="msgNessunProdotto" class="text-center text-muted py-3 small" style="border:1px dashed #e2e8f0;border-radius:8px">
                        <i class="fas fa-cart-plus me-1"></i>
                        Clicca "Aggiungi prodotto" per inserire articoli nella vendita
                    </div>

                    <!-- Totale calcolato -->
                    <div class="d-flex justify-content-end mt-3">
                        <div class="bg-light rounded px-3 py-2">
                            <span class="text-muted small">Totale calcolato: </span>
                            <span id="totaleCalcolato" class="fw-bold text-success">€ 0,00</span>
                        </div>
                    </div>

                </div><!-- /.modal-body -->

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success btn-sm" id="btnRegistra">
                        <i class="fas fa-check me-1"></i>Registra Vendita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     JAVASCRIPT — tutto inline, nessuna dipendenza da main.js
     al momento dell'esecuzione (lo script è nel footer comunque)
     ============================================================ -->
<script>
/* ---------------------------------------------------------------
   Dati PHP → JS
   --------------------------------------------------------------- */
const PRODOTTI_DATA = <?php
    $prodottiJson = [];
    foreach ($prodotti as $p) {
        $confezioni = fetchAll($conn,
            "SELECT idConfezionamento, pesoNetto, prezzo, giacenzaAttuale
             FROM CONFEZIONAMENTO
             WHERE idProdotto = ? AND giacenzaAttuale > 0
             ORDER BY pesoNetto",
            [$p['idProdotto']]);
        $prodottiJson[] = [
            'id'          => (int)$p['idProdotto'],
            'nome'        => $p['nome'],
            'categoria'   => $p['nomeCategoria'],
            'unitaMisura' => $p['unitaMisura'],
            'prezzoBase'  => (float)$p['prezzoBase'],
            'confezioni'  => $confezioni,
        ];
    }
    echo json_encode($prodottiJson, JSON_UNESCAPED_UNICODE);
?>;

/* ---------------------------------------------------------------
   Utility locali — non dipendono da main.js
   --------------------------------------------------------------- */
function _esc(text) {
    if (text === null || text === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}

function _toast(msg, type) {
    /* Usa showToast se disponibile (main.js caricato), altrimenti
       fallback silenzioso alla console */
    if (typeof showToast === 'function') {
        showToast(msg, type);
    } else {
        console.warn('[vendite]', type, msg);
    }
}

/* ---------------------------------------------------------------
   Indice riga corrente
   --------------------------------------------------------------- */
let _rigaIdx = 0;

/* ---------------------------------------------------------------
   Aggiorna la visibilità del messaggio "nessun prodotto"
   --------------------------------------------------------------- */
function _aggiornaMessaggio() {
    const righe = document.querySelectorAll('.riga-vendita');
    const msg   = document.getElementById('msgNessunProdotto');
    if (msg) msg.style.display = righe.length > 0 ? 'none' : 'block';
}

/* ---------------------------------------------------------------
   aggiungiRiga() — aggiunge una riga prodotto al form
   --------------------------------------------------------------- */
function aggiungiRiga() {
    const container = document.getElementById('righeVendita');
    const idx       = _rigaIdx++;

    const opzioniProdotto = PRODOTTI_DATA.map(function(p) {
        return '<option value="' + p.id + '"' +
               ' data-um="' + _esc(p.unitaMisura) + '"' +
               ' data-prezzo="' + p.prezzoBase + '">' +
               _esc(p.categoria) + ' — ' + _esc(p.nome) +
               '</option>';
    }).join('');

    const row = document.createElement('div');
    row.className   = 'riga-vendita p-2 rounded';
    row.dataset.idx = idx;
    row.style.cssText = 'background:#f8fafc;border:1px solid #e2e8f0;';

    row.innerHTML = [
        /* campi hidden */
        '<input type="hidden" name="items[' + idx + '][tipo]" value="CONFEZIONATO" class="hiddenTipo">',
        '<input type="hidden" name="items[' + idx + '][idProdotto]" value="" class="hiddenProdotto">',
        '<input type="hidden" name="items[' + idx + '][idConfezionamento]" value="" class="hiddenConf">',
        '<input type="hidden" name="items[' + idx + '][idRiserva]" value="" class="hiddenRiserva">',

        '<div class="row g-2 align-items-end">',

        /* Tipo */
        '<div class="col-md-2">',
        '<label class="form-label small mb-1">Tipo</label>',
        '<select class="form-select form-select-sm selTipo" onchange="aggiornaTipo(this)">',
        '<option value="CONFEZIONATO">Confezionato</option>',
        '<option value="FRESCO_SFUSO">Fresco/Sfuso</option>',
        '<option value="RISERVA_SFUSA">Da Riserva</option>',
        '</select>',
        '</div>',

        /* Prodotto */
        '<div class="col-md-4">',
        '<label class="form-label small mb-1">Prodotto</label>',
        '<select class="form-select form-select-sm selProdotto" onchange="aggiornaProdotto(this)">',
        '<option value="">— Seleziona —</option>',
        opzioniProdotto,
        '</select>',
        '</div>',

        /* Confezione (visibile solo per CONFEZIONATO) */
        '<div class="col-md-3 divConfezione">',
        '<label class="form-label small mb-1">Confezione</label>',
        '<select class="form-select form-select-sm selConfezione" onchange="aggiornaConfezione(this)">',
        '<option value="">— prima scegli prodotto —</option>',
        '</select>',
        '</div>',

        /* Quantità / Peso */
        '<div class="col-md-2">',
        '<label class="form-label small mb-1 labelQta">Quantità</label>',
        '<input type="number" class="form-control form-control-sm qty-input"',
        '       name="items[' + idx + '][quantita]"',
        '       value="1" min="1" step="1" data-max="99999">',
        '</div>',

        /* Omaggio */
        '<div class="col-md-1">',
        '<label class="form-label small mb-1">Omag.</label>',
        '<div class="form-check mt-1">',
        '<input class="form-check-input" type="checkbox"',
        '       name="items[' + idx + '][omaggio]" value="1"',
        '       onchange="ricalcolaTotaleModal()">',
        '</div>',
        '</div>',

        /* Rimuovi */
        '<div class="col-auto d-flex align-items-end">',
        '<button type="button" class="btn-danger-sm"',
        '        onclick="rimuoviRiga(this)" title="Rimuovi">',
        '<i class="fas fa-trash-alt" style="font-size:.65rem"></i>',
        '</button>',
        '</div>',

        '</div>',/* /.row */
    ].join('');

    container.appendChild(row);
    _aggiornaMessaggio();
}

/* ---------------------------------------------------------------
   rimuoviRiga()
   --------------------------------------------------------------- */
function rimuoviRiga(btn) {
    btn.closest('.riga-vendita').remove();
    ricalcolaTotaleModal();
    _aggiornaMessaggio();
}

/* ---------------------------------------------------------------
   aggiornaTipo() — cambia label e campo quantità in base al tipo
   --------------------------------------------------------------- */
function aggiornaTipo(sel) {
    const row  = sel.closest('.riga-vendita');
    const tipo = sel.value;

    /* aggiorna campo hidden */
    row.querySelector('.hiddenTipo').value = tipo;

    const divConf  = row.querySelector('.divConfezione');
    const qtaInput = row.querySelector('.qty-input');
    const labelQta = row.querySelector('.labelQta');

    if (tipo === 'CONFEZIONATO') {
        divConf.style.display  = '';
        qtaInput.name          = qtaInput.name.replace('[pesoVenduto]', '[quantita]');
        qtaInput.step          = '1';
        qtaInput.min           = '1';
        if (labelQta) labelQta.textContent = 'Quantità';
    } else {
        /* FRESCO_SFUSO o RISERVA_SFUSA — nessun select confezione */
        divConf.style.display = 'none';
        qtaInput.name         = qtaInput.name.replace('[quantita]', '[pesoVenduto]');
        qtaInput.step         = '0.1';
        qtaInput.min          = '0.01';
        if (labelQta) labelQta.textContent = 'Peso (kg)';
    }

    ricalcolaTotaleModal();
}

/* ---------------------------------------------------------------
   aggiornaProdotto() — popola il select confezioni
   --------------------------------------------------------------- */
function aggiornaProdotto(sel) {
    const row    = sel.closest('.riga-vendita');
    const idProd = parseInt(sel.value);

    row.querySelector('.hiddenProdotto').value = idProd || '';

    const selConf = row.querySelector('.selConfezione');
    if (!selConf) return;

    selConf.innerHTML = '<option value="">— Seleziona formato —</option>';

    if (!idProd) { ricalcolaTotaleModal(); return; }

    const prod = PRODOTTI_DATA.find(function(p) { return p.id === idProd; });
    if (!prod) { ricalcolaTotaleModal(); return; }

    if (prod.confezioni && prod.confezioni.length > 0) {
        prod.confezioni.forEach(function(c) {
            const opt = document.createElement('option');
            opt.value             = c.idConfezionamento;
            opt.dataset.prezzo    = c.prezzo;
            opt.dataset.giacenza  = c.giacenzaAttuale;
            opt.textContent       = c.pesoNetto + ' ' + prod.unitaMisura +
                                    ' — €' + parseFloat(c.prezzo).toFixed(2) +
                                    ' (disp. ' + c.giacenzaAttuale + ')';
            selConf.appendChild(opt);
        });
        /* seleziona il primo automaticamente */
        selConf.selectedIndex = 1;
        aggiornaConfezione(selConf);
    } else {
        selConf.innerHTML = '<option value="">Nessuna confezione disponibile</option>';
        ricalcolaTotaleModal();
    }
}

/* ---------------------------------------------------------------
   aggiornaConfezione() — aggiorna max giacenza e ricalcola
   --------------------------------------------------------------- */
function aggiornaConfezione(sel) {
    const row    = sel.closest('.riga-vendita');
    const idConf = sel.value;

    row.querySelector('.hiddenConf').value = idConf || '';

    const opt      = sel.options[sel.selectedIndex];
    const giacenza = parseInt(opt?.dataset?.giacenza) || 99999;
    const qtaInput = row.querySelector('.qty-input');

    if (qtaInput) {
        qtaInput.dataset.max = giacenza;
        qtaInput.max         = giacenza;
        if (parseInt(qtaInput.value) > giacenza) {
            qtaInput.value = giacenza;
            _toast('Quantità ridotta alla giacenza max: ' + giacenza, 'warning');
        }
    }

    ricalcolaTotaleModal();
}

/* ---------------------------------------------------------------
   ricalcolaTotaleModal() — somma tutti gli importi delle righe
   --------------------------------------------------------------- */
function ricalcolaTotaleModal() {
    let totale = 0;

    document.querySelectorAll('.riga-vendita').forEach(function(row) {
        const omaggio = row.querySelector('input[type="checkbox"]')?.checked;
        if (omaggio) return;

        const tipo     = row.querySelector('.hiddenTipo')?.value || 'CONFEZIONATO';
        const qtaInput = row.querySelector('.qty-input');
        const qta      = parseFloat(qtaInput?.value) || 0;

        if (tipo === 'CONFEZIONATO') {
            const selConf = row.querySelector('.selConfezione');
            const opt     = selConf?.options[selConf?.selectedIndex];
            const prezzo  = parseFloat(opt?.dataset?.prezzo) || 0;
            totale += prezzo * qta;
        } else {
            /* FRESCO_SFUSO / RISERVA_SFUSA — usa prezzoBase del prodotto */
            const selProd = row.querySelector('.selProdotto');
            const idProd  = parseInt(selProd?.value) || 0;
            const prod    = PRODOTTI_DATA.find(function(p) { return p.id === idProd; });
            const prezzo  = prod ? prod.prezzoBase : 0;
            totale += prezzo * qta;
        }
    });

    const el = document.getElementById('totaleCalcolato');
    if (el) {
        el.textContent = '€ ' + totale.toFixed(2).replace('.', ',');
    }

    /* aggiorna anche il campo totalePagato solo se è vuoto */
    const inputPagato = document.getElementById('inputTotalePagato');
    if (inputPagato && inputPagato.value === '') {
        /* non precompiliamo — l'utente decide */
    }
}

/* ---------------------------------------------------------------
   capQuantitaRiga() — cap automatico + avviso
   --------------------------------------------------------------- */
function capQuantitaRiga(input) {
    if (String(input.value).length > 8) {
        input.value = String(input.value).slice(0, 8);
    }
    const max = parseInt(input.dataset.max || input.max) || 99999;
    const min = parseFloat(input.min) || 1;
    let   val = parseFloat(input.value) || min;

    if (val > max) {
        input.value = max;
        _toast('Quantità ridotta alla giacenza massima: ' + max, 'warning');
    }
    if (val < min) input.value = min;

    ricalcolaTotaleModal();
}

/* ---------------------------------------------------------------
   Delega eventi sugli input quantità (delegato al container)
   --------------------------------------------------------------- */
document.getElementById('righeVendita').addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input')) {
        capQuantitaRiga(e.target);
    }
});

/* ---------------------------------------------------------------
   Reset form quando il modal BS5 viene chiuso
   --------------------------------------------------------------- */
document.getElementById('modalNuovaVendita')?.addEventListener('hidden.bs.modal', function() {
    document.getElementById('formNuovaVendita').reset();
    document.getElementById('righeVendita').innerHTML = '';
    _rigaIdx = 0;
    ricalcolaTotaleModal();
    _aggiornaMessaggio();
});

/* ---------------------------------------------------------------
   Previeni doppio submit
   --------------------------------------------------------------- */
document.getElementById('formNuovaVendita')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('btnRegistra');

    /* Validazione minima: almeno una riga */
    const righe = document.querySelectorAll('.riga-vendita');
    if (righe.length === 0) {
        e.preventDefault();
        _toast('Aggiungi almeno un prodotto alla vendita', 'error');
        return;
    }

    /* Controlla che ogni riga CONFEZIONATO abbia una confezione selezionata */
    let ok = true;
    righe.forEach(function(row) {
        const tipo    = row.querySelector('.hiddenTipo')?.value;
        const idConf  = row.querySelector('.hiddenConf')?.value;
        const idProd  = row.querySelector('.hiddenProdotto')?.value;
        if (!idProd) { ok = false; return; }
        if (tipo === 'CONFEZIONATO' && !idConf) { ok = false; return; }
    });

    if (!ok) {
        e.preventDefault();
        _toast('Completa tutti i campi prodotto prima di procedere', 'error');
        return;
    }

    if (btn) {
        btn.disabled     = true;
        btn.innerHTML    = '<span class="spinner-border spinner-border-sm me-1"></span>Elaborazione…';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
