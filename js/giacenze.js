/**
 * GIACENZE.JS
 * Controllo giacenze real-time
 */

/**
 * Verifica giacenza disponibile per prodotto
 * @param {number} idProdotto 
 * @returns {Promise<number>} Giacenza disponibile
 */
async function getGiacenza(idProdotto) {
    try {
        const response = await fetch(`/api/giacenze.php?idProdotto=${idProdotto}`);
        const data = await response.json();
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
        const data = await response.json();
        return data.giacenza || 0;
    } catch (error) {
        console.error('Errore recupero giacenza:', error);
        return 0;
    }
}

/**
 * Aggiorna indicatore giacenza in UI
 * @param {HTMLElement} element 
 * @param {number} giacenza 
 */
function updateGiacenzaIndicator(element, giacenza) {
    if (!element) return;
    
    element.textContent = giacenza;
    
    // Aggiorna classe badge
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
 * @param {string} nomeProdotto 
 * @param {number} giacenza 
 */
function showGiacenzaAvviso(nomeProdotto, giacenza) {
    if (giacenza === 0) {
        showToast(`${nomeProdotto} è ESAURITO`, 'error');
    } else if (giacenza < 10) {
        showToast(`Attenzione: solo ${giacenza} pezzi disponibili di ${nomeProdotto}`, 'warning');
    }
}

/**
 * Valida quantità rispetto giacenza
 * @param {number} quantita 
 * @param {number} giacenza 
 * @returns {boolean}
 */
function validateQuantita(quantita, giacenza) {
    if (quantita <= 0) {
        showToast('Quantità deve essere maggiore di zero', 'error');
        return false;
    }
    
    if (quantita > giacenza) {
        showToast(`Disponibili solo ${giacenza} pezzi`, 'error');
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
            const giacenza = await getGiacenza(idProdotto);
            updateGiacenzaIndicator(badge, giacenza);
        }
    }, 30000); // 30 secondi
}

// Avvia auto-refresh se ci sono elementi da monitorare
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('[data-giacenza-id]')) {
        startGiacenzeAutoRefresh();
    }
});
