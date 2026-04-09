/**
 * MAIN.JS
 * Azienda Agricola - Script principale
 */

// Gestione navbar mobile
document.addEventListener('DOMContentLoaded', function() {
    // Toggle menu mobile
    const navbarToggle = document.querySelector('.navbar-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    
    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
        });
    }
    
    // Chiudi alert dismissible
    const alertCloseButtons = document.querySelectorAll('.alert-close');
    alertCloseButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });
    
    // Auto-hide flash messages dopo 5 secondi
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(function(alert) {
        if (!alert.classList.contains('alert-dismissible')) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        }
    });
    
    // Gestione dropdown menu admin
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    dropdowns.forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            // Mobile: click per aprire/chiudere
            if (window.innerWidth <= 1100) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                });
            }
        }
    });
});

/**
 * Utility: Mostra toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <p class="toast-message">${escapeHtml(message)}</p>
        </div>
        <button class="toast-close">&times;</button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Chiudi al click
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', function() {
        toast.remove();
    });
    
    // Auto rimuovi dopo 5 secondi
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, 5000);
}

/**
 * Utility: Crea container per toast
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

/**
 * Utility: Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Utility: Formatta prezzo
 */
function formatPrice(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

/**
 * Utility: Mostra loading overlay
 */
function showLoading() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<div class="spinner"></div>';
    overlay.id = 'loading-overlay';
    document.body.appendChild(overlay);
}

/**
 * Utility: Nascondi loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Utility: Conferma azione
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Utility: Fetch API wrapper con gestione errori
 */
async function fetchAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}
