// Main JavaScript file for Alumpro.Az

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize notifications
    initNotifications();
    
    // Initialize search
    initSearch();
    
    // Initialize forms
    initForms();
    
    // Initialize data tables
    initDataTables();
    
    // Initialize charts
    initCharts();
    
    // Check for PWA support
    checkPWASupport();
}

// Tooltips initialization
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Notifications
function initNotifications() {
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        setTimeout(() => {
            requestNotificationPermission();
        }, 5000);
    }
    
    // Initialize OneSignal
    if (typeof OneSignal !== 'undefined') {
        OneSignal.init({
            appId: "YOUR_ONESIGNAL_APP_ID",
            safari_web_id: "web.onesignal.auto.YOUR_ID",
            notifyButton: {
                enable: false,
            },
            allowLocalhostAsSecureOrigin: true,
        });
    }
}

function requestNotificationPermission() {
    Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
            showNotification('Alumpro.Az', 'Bildirişlər aktivləşdirildi!');
        }
    });
}

function showNotification(title, body, icon = '/assets/img/icon-192.png') {
    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: icon,
            badge: '/assets/img/icon-72.png',
            vibrate: [200, 100, 200]
        });
    }
}

// Search functionality
function initSearch() {
    // Customer search
    const customerSearch = document.getElementById('customerSearch');
    if (customerSearch) {
        customerSearch.addEventListener('input', debounce(function() {
            searchCustomers(this.value);
        }, 300));
    }
    
    // Product search
    const productSearch = document.getElementById('productSearch');
    if (productSearch) {
        productSearch.addEventListener('input', debounce(function() {
            searchProducts(this.value);
        }, 300));
    }
}

function searchCustomers(term) {
    if (term.length < 2) return;
    
    fetch(`/api/search-customers.php?term=${encodeURIComponent(term)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults('customerSearchResults', data);
        })
        .catch(error => console.error('Search error:', error));
}

function searchProducts(term) {
    if (term.length < 2) return;
    
    fetch(`/api/search-products.php?term=${encodeURIComponent(term)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults('productSearchResults', data);
        })
        .catch(error => console.error('Search error:', error));
}

function displaySearchResults(containerId, results) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = '<div class="p-2 text-muted">Nəticə tapılmadı</div>';
        return;
    }
    
    results.forEach(item => {
        const div = document.createElement('div');
        div.className = 'search-result-item p-2';
        div.innerHTML = `
            <strong>${item.name}</strong>
            <small class="text-muted d-block">${item.details}</small>
        `;
        div.addEventListener('click', () => selectSearchResult(item));
        container.appendChild(div);
    });
}

function selectSearchResult(item) {
    // Handle search result selection
    console.log('Selected:', item);
}

// Form handling
function initForms() {
    // AJAX form submission
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        form.addEventListener('submit', handleAjaxForm);
    });
    
    // Auto-save forms
    document.querySelectorAll('form[data-autosave="true"]').forEach(form => {
        initAutoSave(form);
    });
    
    // Form validation
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

function handleAjaxForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('[type="submit"]');
    
    // Disable submit button
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Yüklənir...';
    }
    
    fetch(form.action, {
        method: form.method || 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message || 'Əməliyyat uğurla tamamlandı');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            showAlert('danger', data.message || 'Xəta baş verdi');
        }
    })
    .catch(error => {
        showAlert('danger', 'Sistem xətası: ' + error.message);
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Göndər';
        }
    });
}

function initAutoSave(form) {
    let saveTimeout;
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                autoSaveForm(form);
            }, 2000);
        });
    });
}

function autoSaveForm(form) {
    const formData = new FormData(form);
    formData.append('autosave', 'true');
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Avtomatik yadda saxlanıldı');
        }
    })
    .catch(error => console.error('Auto-save error:', error));
}

// Data tables
function initDataTables() {
    document.querySelectorAll('.data-table').forEach(table => {
        // Add sorting
        addTableSorting(table);
        
        // Add filtering
        addTableFiltering(table);
        
        // Add pagination
        addTablePagination(table);
    });
}

function addTableSorting(table) {
    const headers = table.querySelectorAll('th[data-sortable="true"]');
    
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            sortTable(table, header.cellIndex);
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.getAttribute('data-sort-order') !== 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (!isNaN(aValue) && !isNaN(bValue)) {
            return isAscending ? aValue - bValue : bValue - aValue;
        }
        
        return isAscending ? 
            aValue.localeCompare(bValue) : 
            bValue.localeCompare(aValue);
    });
    
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
    
    table.setAttribute('data-sort-order', isAscending ? 'asc' : 'desc');
}

function addTableFiltering(table) {
    const filterInput = document.querySelector(`[data-table-filter="${table.id}"]`);
    
    if (filterInput) {
        filterInput.addEventListener('input', debounce(function() {
            filterTable(table, this.value);
        }, 300));
    }
}

function filterTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const match = text.includes(searchTerm.toLowerCase());
        row.style.display = match ? '' : 'none';
    });
}

function addTablePagination(table) {
    const rowsPerPage = parseInt(table.getAttribute('data-rows-per-page')) || 10;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const pageCount = Math.ceil(rows.length / rowsPerPage);
    
    if (pageCount <= 1) return;
    
    // Create pagination container
    const paginationContainer = document.createElement('nav');
    paginationContainer.className = 'mt-3';
    paginationContainer.innerHTML = `
        <ul class="pagination justify-content-center">
            <li class="page-item">
                <a class="page-link" href="#" data-page="prev">Əvvəlki</a>
            </li>
            ${Array.from({length: pageCount}, (_, i) => `
                <li class="page-item ${i === 0 ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i + 1}">${i + 1}</a>
                </li>
            `).join('')}
            <li class="page-item">
                <a class="page-link" href="#" data-page="next">Növbəti</a>
            </li>
        </ul>
    `;
    
    table.parentElement.appendChild(paginationContainer);
    
    // Handle pagination clicks
    paginationContainer.addEventListener('click', (e) => {
        e.preventDefault();
        if (e.target.classList.contains('page-link')) {
            const page = e.target.getAttribute('data-page');
            showTablePage(table, page, rowsPerPage);
        }
    });
    
    // Show first page
    showTablePage(table, 1, rowsPerPage);
}

function showTablePage(table, page, rowsPerPage) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const currentPage = parseInt(table.getAttribute('data-current-page')) || 1;
    const pageCount = Math.ceil(rows.length / rowsPerPage);
    
    let targetPage;
    if (page === 'prev') {
        targetPage = Math.max(1, currentPage - 1);
    } else if (page === 'next') {
        targetPage = Math.min(pageCount, currentPage + 1);
    } else {
        targetPage = parseInt(page);
    }
    
    const start = (targetPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    rows.forEach((row, index) => {
        row.style.display = (index >= start && index < end) ? '' : 'none';
    });
    
    table.setAttribute('data-current-page', targetPage);
    
    // Update pagination active state
    const paginationItems = table.parentElement.querySelectorAll('.page-item');
    paginationItems.forEach(item => {
        item.classList.remove('active');
        const link = item.querySelector('.page-link');
        if (link && link.getAttribute('data-page') == targetPage) {
            item.classList.add('active');
        }
    });
}

// Charts
function initCharts() {
    // Sales chart
    const salesChartEl = document.getElementById('salesChart');
    if (salesChartEl && typeof Chart !== 'undefined') {
        createSalesChart(salesChartEl);
    }
    
    // Inventory chart
    const inventoryChartEl = document.getElementById('inventoryChart');
    if (inventoryChartEl && typeof Chart !== 'undefined') {
        createInventoryChart(inventoryChartEl);
    }
}

function createSalesChart(canvas) {
    fetch('/api/get-sales-data.php')
        .then(response => response.json())
        .then(data => {
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Satışlar',
                        data: data.values,
                        borderColor: '#1a936f',
                        backgroundColor: 'rgba(26, 147, 111, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });
        });
}

function createInventoryChart(canvas) {
    fetch('/api/get-inventory-data.php')
        .then(response => response.json())
        .then(data => {
            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: [
                            '#1a936f',
                            '#1a5493',
                            '#28a745',
                            '#ffc107',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
}

// PWA Support
function checkPWASupport() {
    // Check if app is installed
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('App is running in standalone mode');
    }
    
    // Handle install prompt
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallButton();
    });
    
    // Handle install button click
    const installBtn = document.getElementById('installPWA');
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response: ${outcome}`);
                deferredPrompt = null;
            }
        });
    }
}

function showInstallButton() {
    const installBtn = document.getElementById('installPWA');
    if (installBtn) {
        installBtn.style.display = 'block';
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast show position-fixed bottom-0 end-0 m-3';
    toast.innerHTML = `
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Export functions for external use
window.AlumproApp = {
    showNotification,
    showAlert,
    showToast,
    searchCustomers,
    searchProducts
};