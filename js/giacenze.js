/**
 * GIACENZE.JS
 * Controllo giacenze real-time + cap quantità automatico
 */

/**
 * Verifica giacenza disponibile per prodotto
 * @param {number} idProdotto
 * @returns {Promise<number>}
 */
async function getGiacenza(idProdotto) {
    try {
        const response = await fetch(`/api/giacenze.php?idProdotto=${idProdotto}`);
        const data     = await response.json();
        return data.giacenza || 0;
    } catch (error) {
        console.error('Errore recupero giacenza:', error);
        return 0;
    }
}

/**
 * Verifica giacenza confezionamento
 * @param {number} idConfezionamento
 * @returns {Promise<number>}
 */
async function getGiacenzaConfezionamento(idConfezionamento) {
    try {
        const response = await fetch(`/api/giacenze.php?idConfezionamento=${idConfezionamento}`);
        const data     = await response.json();
        return data.giacenza || 0;
    } catch (error) {
        console.error('Errore recupero giacenza:', error);
        return 0;
    }
}

/**
 * Applica il cap automatico della quantità rispetto alla giacenza disponibile.
 * Se il valore supera la giacenza o i 8 caratteri, viene portato al massimo.
 * @param {HTMLInputElement} input
 */
function capQuantita(input) {
    // Limite 8 caratteri
    if (input.value.length > 8) {
        input.value = input.value.slice(0, 8);
    }

    let val = parseInt(input.value) || 1;
    const max = parseInt(input.dataset.max || input.getAttribute('max')) || 99999999;
    const min = parseInt(input.min) || 1;

    if (val > max) {
        input.value = max;
        if (typeof showToast === 'function') {
            showToast(`Quantità ridotta alla giacenza massima disponibile (${max})`, 'warning');
        }
    }
    if (val < min) {
        input.value = min;
    }
}

/**
 * Inizializza tutti gli input quantità presenti nella pagina.
 * Aggiunge maxlength=8 e listener per il cap automatico.
 */
function initQtyInputs() {
    document.querySelectorAll('input[type="number"].qty-input, input[name="quantita"]').forEach(function(input) {
        // Forza attributo maxlength (HTML lo ignora per type=number, usiamo JS)
        input.setAttribute('maxlength', '8');

        // Limita lunghezza durante la digitazione
        input.addEventListener('input', function() {
            capQuantita(this);
        });

        // Cap al blur
        input.addEventListener('blur', function() {
            capQuantita(this);
        });
    });
}

/**
 * Aggiorna indicatore giacenza in UI
 */
function updateGiacenzaIndicator(element, giacenza) {
    if (!element) return;
    element.textContent = giacenza;
    element.classList.remove('badge-success', 'badge-warning', 'badge-error');
    if (giacenza === 0) {
        element.classList.add('badge-error');
    } else if (giacenza < 10) {
        element.classList.add('badge-warning');
    } else {
        element.classList.add('badge-success');
    }
}

/**
 * Mostra avviso giacenza bassa
 */
function showGiacenzaAvviso(nomeProdotto, giacenza) {
    if (giacenza === 0) {
        showToast(`${nomeProdotto} è ESAURITO`, 'error');
    } else if (giacenza < 10) {
        showToast(`Attenzione: solo ${giacenza} pezzi disponibili di ${nomeProdotto}`, 'warning');
    }
}

/**
 * Valida quantità rispetto giacenza.
 * Se supera la giacenza, porta automaticamente al massimo disponibile.
 * @returns {boolean} true se la quantità è valida (dopo eventuale correzione)
 */
function validateQuantita(input, giacenza) {
    // Supporta chiamata con (quantita, giacenza) oppure (input, giacenza)
    if (typeof input === 'number') {
        const quantita = input;
        if (quantita <= 0) {
            if (typeof showToast === 'function') showToast('Quantità deve essere maggiore di zero', 'error');
            return false;
        }
        if (quantita > giacenza) {
            if (typeof showToast === 'function') showToast(`Disponibili solo ${giacenza} pezzi — quantità impostata automaticamente`, 'warning');
            return false; // il chiamante deve correggere il valore
        }
        return true;
    }

    // input è un elemento HTML
    capQuantita(input);
    const val = parseInt(input.value) || 0;
    if (val <= 0) {
        if (typeof showToast === 'function') showToast('Quantità deve essere maggiore di zero', 'error');
        return false;
    }
    return true;
}

/**
 * Auto-aggiornamento giacenze periodico (ogni 30 secondi)
 */
function startGiacenzeAutoRefresh() {
    setInterval(async function() {
        const badges = document.querySelectorAll('[data-giacenza-id]');
        for (const badge of badges) {
            const idProdotto = badge.dataset.giacenzaId;
            const giacenza   = await getGiacenza(idProdotto);
            updateGiacenzaIndicator(badge, giacenza);
        }
    }, 30000);
}

document.addEventListener('DOMContentLoaded', function() {
    initQtyInputs();

    if (document.querySelector('[data-giacenza-id]')) {
        startGiacenzeAutoRefresh();
    }
});
