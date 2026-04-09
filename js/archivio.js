/**
 * ARCHIVIO.JS
 * Filtri dinamici ed export CSV
 */

document.addEventListener('DOMContentLoaded', function() {
    initArchivioFilters();
});

/**
 * Inizializza filtri archivio
 */
function initArchivioFilters() {
    const filterForm = document.getElementById('filterForm');
    
    if (filterForm) {
        // Auto-submit su cambio filtro
        filterForm.querySelectorAll('select, input[type="date"]').forEach(input => {
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }
}

/**
 * Export tabella a CSV
 * @param {string} tableId ID della tabella
 * @param {string} filename Nome file output
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) {
        showToast('Tabella non trovata', 'error');
        return;
    }
    
    const csv = [];
    const rows = table.querySelectorAll('tr');
    
    // Processa righe
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        
        cols.forEach(col => {
            // Pulisci testo (rimuovi tag HTML, spazi extra)
            let text = col.textContent.trim();
            text = text.replace(/\s+/g, ' '); // Normalizza spazi
            text = text.replace(/"/g, '""'); // Escape virgolette
            rowData.push(`"${text}"`);
        });
        
        csv.push(rowData.join(';'));
    });
    
    // Download
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Download CSV file
 * @param {string} content Contenuto CSV
 * @param {string} filename Nome file
 */
function downloadCSV(content, filename) {
    // Aggiungi BOM UTF-8 per Excel
    const BOM = '\uFEFF';
    const blob = new Blob([BOM + content], { 
        type: 'text/csv;charset=utf-8;' 
    });
    
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Export completato', 'success');
}

/**
 * Filtra tabella in tempo reale
 * @param {string} searchValue Valore ricerca
 * @param {string} tableId ID tabella
 */
function filterTable(searchValue, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const search = searchValue.toLowerCase();
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const match = text.includes(search);
        
        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });
    
    // Aggiorna contatore risultati
    const counter = document.getElementById('resultCount');
    if (counter) {
        counter.textContent = `${visibleCount} risultat${visibleCount !== 1 ? 'i' : 'o'}`;
    }
}

/**
 * Sorting tabella
 * @param {number} columnIndex Indice colonna
 * @param {string} tableId ID tabella
 */
function sortTable(columnIndex, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Determina direzione sort
    const currentSort = tbody.dataset.sortColumn;
    const currentDir = tbody.dataset.sortDir || 'asc';
    const newDir = (currentSort == columnIndex && currentDir === 'asc') ? 'desc' : 'asc';
    
    // Sort
    rows.sort((a, b) => {
        const aVal = a.querySelectorAll('td')[columnIndex].textContent.trim();
        const bVal = b.querySelectorAll('td')[columnIndex].textContent.trim();
        
        // Prova parse numerico
        const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
        
        let comparison = 0;
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            comparison = aNum - bNum;
        } else {
            comparison = aVal.localeCompare(bVal);
        }
        
        return newDir === 'asc' ? comparison : -comparison;
    });
    
    // Riapplica righe
    rows.forEach(row => tbody.appendChild(row));
    
    // Salva stato
    tbody.dataset.sortColumn = columnIndex;
    tbody.dataset.sortDir = newDir;
    
    // Aggiorna header
    updateSortHeaders(table, columnIndex, newDir);
}

/**
 * Aggiorna header sorting
 */
function updateSortHeaders(table, columnIndex, direction) {
    const headers = table.querySelectorAll('th');
    
    headers.forEach((th, index) => {
        th.classList.remove('asc', 'desc');
        if (index === columnIndex) {
            th.classList.add(direction);
        }
    });
}

/**
 * Genera report riepilogo
 * @param {Array} data Dati
 * @returns {Object} Report
 */
function generateReport(data) {
    const totale = data.reduce((sum, item) => sum + (item.importo || 0), 0);
    const media = totale / (data.length || 1);
    
    return {
        totaleVendite: data.length,
        importoTotale: totale,
        importoMedio: media,
        dataInizio: data[0]?.data || null,
        dataFine: data[data.length - 1]?.data || null
    };
}
