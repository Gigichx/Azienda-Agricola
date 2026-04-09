/**
 * VENDITE.JS
 * Gestione vendite manuali admin - calcolo totale e sconto
 */

document.addEventListener('DOMContentLoaded', function() {
    // Listener per calcolo automatico
    const formVendita = document.getElementById('formVendita');
    if (formVendita) {
        initVenditaForm();
    }
});

/**
 * Inizializza form vendita
 */
function initVenditaForm() {
    const scontoInput = document.getElementById('sconto');
    const itemsContainer = document.getElementById('venditaItems');
    
    if (scontoInput) {
        scontoInput.addEventListener('input', ricalcolaTotale);
    }
    
    if (itemsContainer) {
        // Observer per items aggiunti dinamicamente
        const observer = new MutationObserver(ricalcolaTotale);
        observer.observe(itemsContainer, { childList: true, subtree: true });
    }
}

/**
 * Ricalcola totale vendita con sconto
 */
function ricalcolaTotale() {
    const items = document.querySelectorAll('.item-vendita');
    let subtotale = 0;
    
    items.forEach(item => {
        const prezzo = parseFloat(item.dataset.prezzo || 0);
        const quantita = parseInt(item.dataset.quantita || 0);
        subtotale += prezzo * quantita;
    });
    
    const scontoPerc = parseFloat(document.getElementById('sconto')?.value || 0);
    const sconto = (subtotale * scontoPerc) / 100;
    const totale = subtotale - sconto;
    
    // Aggiorna UI
    document.getElementById('subtotaleVendita').textContent = formatPrice(subtotale);
    document.getElementById('scontoApplicato').textContent = formatPrice(sconto);
    document.getElementById('totaleVendita').textContent = formatPrice(totale);
}

/**
 * Aggiungi item vendita
 */
function aggiungiItemVendita(prodotto, quantita, prezzo) {
    const container = document.getElementById('venditaItems');
    const item = document.createElement('div');
    item.className = 'item-vendita';
    item.dataset.prezzo = prezzo;
    item.dataset.quantita = quantita;
    
    item.innerHTML = `
        <div class="item-vendita-info">
            <div class="item-vendita-nome">${escapeHtml(prodotto)}</div>
            <div class="item-vendita-dettagli">Quantità: ${quantita}</div>
        </div>
        <div class="item-vendita-prezzo">${formatPrice(prezzo * quantita)}</div>
        <button type="button" class="btn-icon btn-icon-sm" onclick="rimuoviItemVendita(this)">
            🗑️
        </button>
    `;
    
    container.appendChild(item);
    ricalcolaTotale();
}

/**
 * Rimuovi item vendita
 */
function rimuoviItemVendita(button) {
    button.closest('.item-vendita').remove();
    ricalcolaTotale();
}

/**
 * Applica omaggio
 */
function applicaOmaggio(itemElement) {
    itemElement.dataset.prezzo = 0;
    ricalcolaTotale();
    showToast('Omaggio applicato', 'success');
}
