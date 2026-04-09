/**
 * CARRELLO.JS
 * Gestione carrello e calcoli automatici
 */

document.addEventListener('DOMContentLoaded', function() {
    // Aggiornamento totale carrello
    updateCarrelloTotale();
    
    // Gestione badge carrello navbar
    updateCarrelloBadge();
});

/**
 * Aggiorna totale carrello in tempo reale
 */
function updateCarrelloTotale() {
    const items = document.querySelectorAll('.carrello-item');
    let totale = 0;
    
    items.forEach(item => {
        const prezzo = parseFloat(item.dataset.prezzo || 0);
        const quantita = parseInt(item.querySelector('.quantita-input')?.value || 0);
        totale += prezzo * quantita;
    });
    
    const totaleElement = document.querySelector('.totale-value');
    if (totaleElement) {
        totaleElement.textContent = formatPrice(totale);
    }
}

/**
 * Aggiorna badge numero items carrello
 */
function updateCarrelloBadge() {
    const badge = document.querySelector('.nav-link .badge');
    const items = document.querySelectorAll('.carrello-item');
    
    if (badge && items.length > 0) {
        badge.textContent = items.length;
    }
}

/**
 * Verifica disponibilità prodotto prima aggiunta carrello
 */
async function checkDisponibilita(idProdotto, quantita) {
    try {
        const response = await fetch(`/api/giacenze.php?idProdotto=${idProdotto}`);
        const data = await response.json();
        
        if (data.disponibile >= quantita) {
            return true;
        } else {
            showToast(`Disponibili solo ${data.disponibile} pezzi`, 'warning');
            return false;
        }
    } catch (error) {
        console.error('Errore verifica giacenza:', error);
        return false;
    }
}

/**
 * Calcola subtotale item
 */
function calcolaSubtotale(prezzo, quantita) {
    return prezzo * quantita;
}
