<?php
/**
 * VENDITE - Admin
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Vendite';

// Storico ultime 50 vendite
$sql = "SELECT v.*, c.nome as nomeCliente, c.occasionale, l.nome as nomeLuogo
        FROM VENDITA v
        INNER JOIN CLIENTE c ON v.idCliente = c.idCliente
        INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
        ORDER BY v.dataVendita DESC
        LIMIT 50";
$vendite = fetchAll($conn, $sql);

// Tutti i clienti (incluso occasionale) per il form nuova vendita
$clienti = fetchAll($conn, "SELECT idCliente, nome, occasionale FROM CLIENTE ORDER BY occasionale DESC, nome ASC");

// Luoghi punto vendita
$luoghi = fetchAll($conn, "SELECT idLuogo, nome, tipo FROM LUOGO ORDER BY nome");

// Prodotti con confezionamenti disponibili
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
                        <td class="ps-3 fw-semibold text-muted">#<?php echo $v['idVendita']; ?></td>
                        <td class="text-muted small"><?php echo formatDate($v['dataVendita'], true); ?></td>
                        <td>
                            <?php echo htmlspecialchars($v['nomeCliente']); ?>
                            <?php if ($v['occasionale']): ?>
                                <span class="badge bg-warning-subtle text-warning ms-1">Occ.</span>
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

<!-- Modal Nuova Vendita -->
<div class="modal fade" id="modalNuovaVendita" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="/api/vendita_admin.php" method="POST" id="formNuovaVendita">
                <input type="hidden" name="action" value="create">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold"><i class="fas fa-cash-register me-2 text-success"></i>Nuova Vendita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <!-- Cliente -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                            <select name="idCliente" class="form-select" required>
                                <option value="">— Seleziona cliente —</option>
                                <?php foreach ($clienti as $cl): ?>
                                    <option value="<?php echo $cl['idCliente']; ?>"
                                        <?php echo $cl['occasionale'] ? 'class="text-warning fw-semibold"' : ''; ?>>
                                        <?php echo htmlspecialchars($cl['nome']); ?>
                                        <?php echo $cl['occasionale'] ? ' ★ (Occasionale)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Luogo -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Luogo <span class="text-danger">*</span></label>
                            <select name="idLuogo" class="form-select" required>
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

                        <!-- Data -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Data Vendita</label>
                            <input type="datetime-local" name="dataVendita" class="form-control"
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>

                        <!-- Totale pagato (opzionale) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Totale Pagato (€) <small class="text-muted fw-normal">opzionale</small></label>
                            <input type="number" name="totalePagato" class="form-control"
                                   step="0.01" min="0" placeholder="Lascia vuoto = automatico">
                        </div>

                        <!-- Note -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Note</label>
                            <input type="text" name="note" class="form-control" maxlength="500"
                                   placeholder="Note opzionali sulla vendita…">
                        </div>
                    </div>

                    <!-- Aggiunta prodotti -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-semibold">Prodotti</h6>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="aggiungiRiga()">
                            <i class="fas fa-plus me-1"></i>Aggiungi prodotto
                        </button>
                    </div>

                    <div id="righeVendita">
                        <!-- Riga inserita dinamicamente -->
                    </div>

                    <!-- Totale calcolato -->
                    <div class="d-flex justify-content-end mt-3">
                        <div class="bg-light rounded px-3 py-2 fw-semibold">
                            Totale calcolato: <span id="totaleCalcolato" class="text-success">€ 0,00</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-check me-1"></i>Registra Vendita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* Dati prodotti PHP → JS */
const prodottiData = <?php
    $prodottiJson = [];
    foreach ($prodotti as $p) {
        $confezioni = fetchAll($conn,
            "SELECT idConfezionamento, pesoNetto, prezzo, giacenzaAttuale FROM CONFEZIONAMENTO
             WHERE idProdotto = ? AND giacenzaAttuale > 0 ORDER BY pesoNetto",
            [$p['idProdotto']]);
        $prodottiJson[] = [
            'id'          => $p['idProdotto'],
            'nome'        => $p['nome'],
            'unitaMisura' => $p['unitaMisura'],
            'prezzoBase'  => $p['prezzoBase'],
            'categoria'   => $p['nomeCategoria'],
            'confezioni'  => $confezioni,
        ];
    }
    echo json_encode($prodottiJson, JSON_UNESCAPED_UNICODE);
?>;

let rigaIndex = 0;

function aggiungiRiga() {
    const container = document.getElementById('righeVendita');
    const idx       = rigaIndex++;

    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 align-items-end riga-vendita';
    row.dataset.idx = idx;

    // Tipo
    const tipiOpts = [
        ['CONFEZIONATO','Confezionato'],
        ['FRESCO_SFUSO','Fresco / Sfuso'],
        ['RISERVA_SFUSA','Da Riserva'],
    ];
    const tipoSel = tipiOpts.map(([v,l]) =>
        `<option value="${v}">${l}</option>`).join('');

    // Prodotti
    const prodOpts = prodottiData.map(p =>
        `<option value="${p.id}" data-um="${p.unitaMisura}" data-prezzo="${p.prezzoBase}">${escapeHtml(p.categoria)} — ${escapeHtml(p.nome)}</option>`
    ).join('');

    row.innerHTML = `
        <input type="hidden" name="items[${idx}][tipo]" value="CONFEZIONATO" class="hiddenTipo">
        <input type="hidden" name="items[${idx}][idProdotto]" value="" class="hiddenProdotto">
        <input type="hidden" name="items[${idx}][idConfezionamento]" value="" class="hiddenConf">
        <input type="hidden" name="items[${idx}][idRiserva]" value="" class="hiddenRiserva">

        <div class="col-md-2">
            <label class="form-label small fw-semibold">Tipo</label>
            <select class="form-select form-select-sm selTipo" onchange="aggiornaTipo(this)">
                ${tipoSel}
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Prodotto</label>
            <select class="form-select form-select-sm selProdotto" onchange="aggiornaProdotto(this)">
                <option value="">— Seleziona —</option>
                ${prodOpts}
            </select>
        </div>
        <div class="col-md-3 divConfezione">
            <label class="form-label small fw-semibold">Confezione</label>
            <select class="form-select form-select-sm selConfezione" onchange="aggiornaConfezione(this)">
                <option value="">— prima scegli prodotto —</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Qta / Peso</label>
            <input type="number" class="form-control form-control-sm inputQta qty-input"
                   name="items[${idx}][quantita]" value="1" min="1" maxlength="8"
                   data-max="99999999"
                   oninput="capQuantitaRiga(this); ricalcolaTotaleModal()">
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold">Omaggio</label>
            <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="items[${idx}][omaggio]" value="1"
                       onchange="ricalcolaTotaleModal()">
            </div>
        </div>
        <div class="col-auto d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.riga-vendita').remove(); ricalcolaTotaleModal();">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    // Seleziona automaticamente il primo prodotto disponibile
}

function aggiornaTipo(sel) {
    const row   = sel.closest('.riga-vendita');
    const tipo  = sel.value;
    row.querySelector('.hiddenTipo').value = tipo;

    // Mostra/nascondi confezione vs peso
    const divConf = row.querySelector('.divConfezione');
    if (tipo === 'CONFEZIONATO') {
        divConf.style.display = '';
        const qta = row.querySelector('.inputQta');
        qta.setAttribute('name', qta.name.replace('[pesoVenduto]','[quantita]'));
        qta.step = '1';
    } else {
        divConf.style.display = 'none';
        const qta = row.querySelector('.inputQta');
        qta.setAttribute('name', qta.name.replace('[quantita]','[pesoVenduto]'));
        qta.step = '0.1';
    }
}

function aggiornaProdotto(sel) {
    const row    = sel.closest('.riga-vendita');
    const idProd = parseInt(sel.value);
    row.querySelector('.hiddenProdotto').value = idProd;

    if (!idProd) return;

    const prod    = prodottiData.find(p => p.id === idProd);
    const selConf = row.querySelector('.selConfezione');
    selConf.innerHTML = '<option value="">— Seleziona formato —</option>';

    if (prod && prod.confezioni.length > 0) {
        prod.confezioni.forEach(c => {
            const opt = document.createElement('option');
            opt.value              = c.idConfezionamento;
            opt.dataset.prezzo     = c.prezzo;
            opt.dataset.giacenza   = c.giacenzaAttuale;
            opt.textContent        = `${c.pesoNetto} — € ${parseFloat(c.prezzo).toFixed(2)} (disp. ${c.giacenzaAttuale})`;
            selConf.appendChild(opt);
        });
    }
    aggiornaConfezione(selConf);
}

function aggiornaConfezione(sel) {
    const row    = sel.closest('.riga-vendita');
    const idConf = sel.value;
    row.querySelector('.hiddenConf').value = idConf || '';

    const opt      = sel.options[sel.selectedIndex];
    const giacenza = parseInt(opt?.dataset?.giacenza) || 99999999;
    const qtaInput = row.querySelector('.inputQta');
    qtaInput.dataset.max = giacenza;
    qtaInput.max         = giacenza;
    if (parseInt(qtaInput.value) > giacenza) {
        qtaInput.value = giacenza;
        if (typeof showToast === 'function') showToast('Quantità ridotta alla giacenza max: ' + giacenza, 'warning');
    }
    ricalcolaTotaleModal();
}

function capQuantitaRiga(input) {
    if (input.value.length > 8) input.value = input.value.slice(0, 8);
    const max = parseInt(input.dataset.max) || 99999999;
    const min = parseInt(input.min) || 1;
    let val   = parseInt(input.value) || min;
    if (val > max) {
        input.value = max;
        if (typeof showToast === 'function') showToast('Quantità ridotta alla giacenza massima: ' + max, 'warning');
    }
    if (val < min) input.value = min;
}

function ricalcolaTotaleModal() {
    let totale = 0;
    document.querySelectorAll('.riga-vendita').forEach(row => {
        const omaggio  = row.querySelector('input[type="checkbox"]')?.checked;
        if (omaggio) return;
        const selConf  = row.querySelector('.selConfezione');
        const qtaInput = row.querySelector('.inputQta');
        const opt      = selConf?.options[selConf.selectedIndex];
        const prezzo   = parseFloat(opt?.dataset?.prezzo) || 0;
        const qta      = parseFloat(qtaInput?.value) || 0;
        totale        += prezzo * qta;
    });
    const el = document.getElementById('totaleCalcolato');
    if (el) el.textContent = '€ ' + totale.toFixed(2).replace('.', ',');
}
</script>

<?php include '../includes/footer.php'; ?>
