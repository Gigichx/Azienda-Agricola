/**
 * MAIN.JS — FIXED
 * - showToast usa classi .ag-toast invece di .toast (conflitto BS5)
 * - escapeHtml disponibile globalmente (era usata in admin/vendite.php inline)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle menu mobile (sidebar admin)
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-open');
        });
    }

    // Toggle menu mobile (navbar cliente)
    var navbarToggle = document.querySelector('.navbar-toggler');
    // Bootstrap 5 gestisce questo da solo, non serve JS manuale

    // Auto-hide alert Bootstrap dopo 5 secondi
    document.querySelectorAll('.alert.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() { alert.style.display = 'none'; }, 500);
        }, 5000);
    });
});

/**
 * Mostra toast notification
 * FIXED: usa .ag-toast per non confondersi con Bootstrap .toast
 */
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'ag-toast toast-' + type;
    toast.innerHTML =
        '<div style="flex:1;font-size:.8125rem">' + escapeHtml(message) + '</div>' +
        '<button class="ag-toast-close" onclick="this.parentElement.remove()">&times;</button>';

    container.appendChild(toast);

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .3s';
        setTimeout(function() { toast.remove(); }, 300);
    }, 5000);
}

/**
 * escapeHtml — disponibile globalmente
 * Necessaria anche negli script inline di admin/vendite.php
 */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

/**
 * formatPrice — utility
 */
function formatPrice(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

/**
 * showLoading / hideLoading
 */
function showLoading() {
    if (document.getElementById('ag-loading')) return;
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.id = 'ag-loading';
    overlay.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(overlay);
}
function hideLoading() {
    const overlay = document.getElementById('ag-loading');
    if (overlay) overlay.remove();
}

/**
 * confirmAction — utility
 */
function confirmAction(message, callback) {
    if (confirm(message)) callback();
}
