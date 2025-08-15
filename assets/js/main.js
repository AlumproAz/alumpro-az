// Main JavaScript - Alumpro.Az
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initNavigation();
    initForms();
    initModals();
    initTooltips();
    initDataTables();
    initCharts();
    initNotifications();
    initSearch();
});

// Navigation
function initNavigation() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
    
    // Dropdown menus
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdown = toggle.nextElementSibling;
            dropdown.classList.toggle('show');
        });
    });
    
    // Active menu item
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
}

// Forms
function initForms() {
    // Form validation
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!validateForm(form)) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Phone number formatting
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('994')) {
                value = value.substring(0, 12);
                if (value.length >= 3) {
                    value = '+' + value.substring(0, 3) + ' ' + value.substring(3);
                }
                if (value.length >= 7) {
                    value = value.substring(0, 7) + ' ' + value.substring(7);
                }
                if (value.length >= 11) {
                    value = value.substring(0, 11) + ' ' + value.substring(11);
                }
            }
            e.target.value = value;
        });
    });
    
    // Price formatting
    document.querySelectorAll('input[data-type="currency"]').forEach(input => {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[^\d.]/g, '');
            if (value) {
                value = parseFloat(value).toFixed(2);
            }
            e.target.value = value;
        });
    });
}

function validateForm(form) {
    let isValid = true;
    
    // Required fields
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Bu sahə məcburidir');
            isValid = false;
        }
    });
    
    // Email validation
    form.querySelectorAll('input[type="email"]').forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Düzgün e-poçt ünvanı daxil edin');
            isValid = false;
        }
    });
    
    // Phone validation
    form.querySelectorAll('input[type="tel"]').forEach(field => {
        if (field.value && !isValidPhone(field.value)) {
            showFieldError(field, 'Düzgün telefon nömrəsi daxil edin');
            isValid = false;
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    const feedback = field.nextElementSibling;
    if (feedback && feedback.classList.contains('invalid-feedback')) {
        feedback.textContent = message;
    }
    field.classList.add('is-invalid');
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    return cleaned.length >= 9 && cleaned.length <= 12;
}

// Modals
function initModals() {
    document.querySelectorAll('[data-toggle="modal"]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-target');
            const modal = document.querySelector(modalId);
            if (modal) {
                showModal(modal);
            }
        });
    });
    
    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(closer => {
        closer.addEventListener('click', () => {
            const modal = closer.closest('.modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
}

function showModal(modal) {
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    
    // Create backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    document.body.appendChild(backdrop);
}

function hideModal(modal) {
    modal.classList.remove('show');
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

// Tooltips
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', (e) => {
            const text = element.getAttribute('data-tooltip');
            showTooltip(element, text);
        });
        
        element.addEventListener('mouseleave', () => {
            hideTooltip();
        });
    });
}

function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip-custom';
    tooltip.textContent = text;
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip-custom');
    if (tooltip) {
        tooltip.remove();
    }
}

// Data Tables
function initDataTables() {
    document.querySelectorAll('.data-table').forEach(table => {
        // Add search functionality
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control mb-3';
        searchInput.placeholder = 'Axtar...';
        
        table.parentElement.insertBefore(searchInput, table);
        
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Add sorting functionality
        table.querySelectorAll('th[data-sortable]').forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                sortTable(table, th);
            });
        });
    });
}

function sortTable(table, th) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(th.parentElement.children).indexOf(th);
    const isAscending = th.classList.contains('sort-asc');
    
    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        if (!isNaN(aValue) && !isNaN(bValue)) {
            return isAscending ? bValue - aValue : aValue - bValue;
        }
        
        return isAscending 
            ? bValue.localeCompare(aValue)
            : aValue.localeCompare(bValue);
    });
    
    // Update sort indicators
    table.querySelectorAll('th').forEach(header => {
        header.classList.remove('sort-asc', 'sort-desc');
    });
    th.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

// Charts
function initCharts() {
    // Initialize Chart.js charts
    const chartElements = document.querySelectorAll('[data-chart]');
    
    chartElements.forEach(element => {
        const type = element.getAttribute('data-chart');
        const data = JSON.parse(element.getAttribute('data-chart-data') || '{}');
        
        new Chart(element, {
            type: type,
            data: data,
            options: getChartOptions(type)
        });
    });
}

function getChartOptions(type) {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };
    
    if (type === 'line' || type === 'bar') {
        baseOptions.scales = {
            y: {
                beginAtZero: true
            }
        };
    }
    
    return baseOptions;
}

// Notifications
function initNotifications() {
    // Check for new notifications every 30 seconds
    if (window.isAuthenticated) {
        setInterval(checkNotifications, 30000);
    }
    
    // Request browser notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

async function checkNotifications() {
    try {
        const response = await fetch('/api/check-new-notifications.php');
        const data = await response.json();
        
        if (data.has_new) {
            updateNotificationBadge(data.count);
            
            if (Notification.permission === 'granted') {
                new Notification('Alumpro.Az', {
                    body: `Sizin ${data.count} yeni bildirişiniz var`,
                    icon: '/assets/img/logo.png'
                });
            }
        }
    } catch (error) {
        console.error('Failed to check notifications:', error);
    }
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Search
function initSearch() {
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('.search-input');
    const searchResults = document.querySelector('.search-results');
    
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                hideSearchResults();
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchForm.contains(e.target)) {
                hideSearchResults();
            }
        });
    }
}

async function performSearch(query) {
    try {
        const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        
        displaySearchResults(results);
    } catch (error) {
        console.error('Search failed:', error);
    }
}

function displaySearchResults(results) {
    const searchResults = document.querySelector('.search-results');
    if (!searchResults) return;
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="no-results">Nəticə tapılmadı</div>';
    } else {
        searchResults.innerHTML = results.map(result => `
            <a href="${result.url}" class="search-result-item">
                <div class="result-title">${result.title}</div>
                <div class="result-description">${result.description}</div>
            </a>
        `).join('');
    }
    
    searchResults.style.display = 'block';
}

function hideSearchResults() {
    const searchResults = document.querySelector('.search-results');
    if (searchResults) {
        searchResults.style.display = 'none';
    }
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('az-AZ', {
        style: 'currency',
        currency: 'AZN'
    }).format(amount);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('az-AZ').format(new Date(date));
}

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

// Export functions for use in other scripts
window.AlumproUtils = {
    formatCurrency,
    formatDate,
    debounce,
    showModal,
    hideModal,
    showTooltip,
    hideTooltip
};