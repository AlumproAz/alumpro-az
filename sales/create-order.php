<?php
$pageTitle = 'Yeni Sifariş';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

session_start();

$auth = new Auth();
$db = Database::getInstance();

// Check if user is logged in and is sales staff
if (!$auth->isLoggedIn() || !$auth->hasRole('sales')) {
    header('Location: ../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$storeId = $user['store_id'];

// Get store information
$store = $db->selectOne("SELECT * FROM stores WHERE id = :id", ['id' => $storeId]);

// Get product categories
$categories = $db->select("SELECT * FROM categories ORDER BY name");

// Get glass types
$glassTypes = $db->select("SELECT * FROM glass_products ORDER BY name");
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/modern-dashboard.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Satış Paneli - <?= htmlspecialchars($store['name']) ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2"></i> Ana Səhifə
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="create-order.php">
                            <i class="bi bi-plus-circle"></i> Yeni Sifariş
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Sifarişlərim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customers.php">
                            <i class="bi bi-people"></i> Müştərilər
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['full_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Çıxış
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-plus-circle"></i> Yeni Sifariş Yarat
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="orderForm">
                            <!-- Customer Selection -->
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label class="form-label">Müştəri Seçimi <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="customerSearch" placeholder="Müştəri adı və ya telefon nömrəsi axtarın...">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                                            <i class="bi bi-person-plus"></i> Yeni
                                        </button>
                                    </div>
                                    <div id="customerSearchResults" class="mt-2"></div>
                                    <div id="selectedCustomer" class="mt-3" style="display: none;">
                                        <div class="alert alert-info">
                                            <strong>Seçilmiş müştəri:</strong>
                                            <span id="selectedCustomerName"></span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="clearCustomer()">
                                                <i class="bi bi-x"></i> Dəyiş
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sifariş Tarixi</label>
                                    <input type="date" class="form-control" id="orderDate" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <!-- Customer History -->
                            <div id="customerHistory" class="row mb-4" style="display: none;">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6><i class="bi bi-clock-history"></i> Müştəri Tarixçəsi</h6>
                                            <div id="customerHistoryContent">
                                                <!-- Customer history will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="mb-3">
                                        <i class="bi bi-list-ul"></i> Sifariş Təfərrüatları
                                    </h5>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="orderItemsTable">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th width="20%">Profil Tipi</th>
                                                    <th width="15%">Qapaq Hündürlüyü (sm)</th>
                                                    <th width="15%">Qapaq Eni (sm)</th>
                                                    <th width="10%">Sayı</th>
                                                    <th width="15%">Vahid Qiymət (₼)</th>
                                                    <th width="15%">Cəmi (₼)</th>
                                                    <th width="10%">Əməliyyat</th>
                                                </tr>
                                            </thead>
                                            <tbody id="orderItemsBody">
                                                <tr class="order-item-row">
                                                    <td>
                                                        <select class="form-control profile-select">
                                                            <option value="">Profil Tipi Seçin</option>
                                                            <!-- Will be populated via AJAX -->
                                                        </select>
                                                        <small class="text-muted inventory-info"></small>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control door-height" placeholder="Hündürlük" min="1" step="0.1">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control door-width" placeholder="En" min="1" step="0.1">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control quantity" placeholder="Sayı" min="1" value="1">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control unit-price" placeholder="Qiymət" min="0" step="0.01">
                                                    </td>
                                                    <td>
                                                        <span class="total-price">0.00</span> ₼
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-danger remove-item" disabled>
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline-primary" onclick="addOrderItem()">
                                        <i class="bi bi-plus"></i> Yeni Qapaq Əlavə Et
                                    </button>
                                </div>
                            </div>

                            <!-- Glass Selection -->
                            <div class="row mt-4" id="glassSelection" style="display: none;">
                                <div class="col-12">
                                    <h6><i class="bi bi-grid-3x3-gap"></i> Şüşə Seçimi</h6>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addGlassItems()">
                                        <i class="bi bi-plus"></i> Şüşə Əlavə Et
                                    </button>
                                    <div id="glassItemsContainer" class="mt-3">
                                        <!-- Glass items will be added here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Qeydlər</h6>
                                            <textarea class="form-control" id="orderNotes" rows="3" placeholder="Sifariş haqqında əlavə qeydlər..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Sifariş Xülasəsi</h6>
                                            <div class="d-flex justify-content-between">
                                                <span>Alt Məbləğ:</span>
                                                <span id="subtotal">0.00 ₼</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Endirim:</span>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control" id="discountAmount" min="0" step="0.01" value="0">
                                                    <span class="input-group-text">₼</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Nəqliyyat:</span>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control" id="shippingCost" min="0" step="0.01" value="0">
                                                    <span class="input-group-text">₼</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Quraşdırma:</span>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control" id="installationCost" min="0" step="0.01" value="0">
                                                    <span class="input-group-text">₼</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>ƏDV (18%):</span>
                                                <span id="taxAmount">0.00 ₼</span>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between fw-bold">
                                                <span>Ümumi Məbləğ:</span>
                                                <span id="grandTotal">0.00 ₼</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Sifarişi Yadda Saxla
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="generateProduction()">
                                            <i class="bi bi-file-earmark-pdf"></i> İstehsalat Forması
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="sendToCustomer()">
                                            <i class="bi bi-whatsapp"></i> Müştəriyə Göndər
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Geri
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Customer Modal -->
    <div class="modal fade" id="newCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Müştəri Əlavə Et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newCustomerForm">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customerName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefon <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="customerPhone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="customerEmail">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ünvan</label>
                            <textarea class="form-control" id="customerAddress" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv Et</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewCustomer()">Yadda Saxla</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
    let selectedCustomerId = null;
    let orderItems = [];

    // Customer search functionality
    document.getElementById('customerSearch').addEventListener('input', function() {
        const query = this.value;
        if (query.length >= 2) {
            fetch('../api/search-customers.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    displayCustomerResults(data);
                });
        } else {
            document.getElementById('customerSearchResults').innerHTML = '';
        }
    });

    function displayCustomerResults(customers) {
        const resultsDiv = document.getElementById('customerSearchResults');
        if (customers.length === 0) {
            resultsDiv.innerHTML = '<div class="alert alert-warning">Müştəri tapılmadı.</div>';
            return;
        }

        let html = '<div class="list-group">';
        customers.forEach(customer => {
            html += `
                <a href="#" class="list-group-item list-group-item-action" onclick="selectCustomer(${customer.id}, '${customer.full_name}', '${customer.phone}')">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">${customer.full_name}</h6>
                            <small>${customer.phone}</small>
                        </div>
                        <div>
                            <small class="text-muted">Son sifariş: ${customer.last_order || 'Yoxdur'}</small>
                        </div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
        resultsDiv.innerHTML = html;
    }

    function selectCustomer(id, name, phone) {
        selectedCustomerId = id;
        document.getElementById('selectedCustomerName').textContent = name + ' (' + phone + ')';
        document.getElementById('selectedCustomer').style.display = 'block';
        document.getElementById('customerSearchResults').innerHTML = '';
        document.getElementById('customerSearch').value = '';
        
        // Load customer history
        loadCustomerHistory(id);
    }

    function clearCustomer() {
        selectedCustomerId = null;
        document.getElementById('selectedCustomer').style.display = 'none';
        document.getElementById('customerHistory').style.display = 'none';
    }

    function loadCustomerHistory(customerId) {
        fetch('../api/get-customer-history.php?id=' + customerId)
            .then(response => response.json())
            .then(data => {
                let html = '<div class="row">';
                html += `<div class="col-md-3"><strong>Ümumi sifarişlər:</strong> ${data.total_orders}</div>`;
                html += `<div class="col-md-3"><strong>Ümumi məbləğ:</strong> ${data.total_spent} ₼</div>`;
                html += `<div class="col-md-3"><strong>Son sifariş:</strong> ${data.last_order_date || 'Yoxdur'}</div>`;
                html += `<div class="col-md-3"><strong>Orta çek:</strong> ${data.average_order} ₼</div>`;
                html += '</div>';
                
                document.getElementById('customerHistoryContent').innerHTML = html;
                document.getElementById('customerHistory').style.display = 'block';
            });
    }

    function addOrderItem() {
        const tbody = document.getElementById('orderItemsBody');
        const newRow = tbody.rows[0].cloneNode(true);
        
        // Clear values
        newRow.querySelectorAll('input').forEach(input => {
            if (input.classList.contains('quantity')) {
                input.value = 1;
            } else {
                input.value = '';
            }
        });
        newRow.querySelector('select').selectedIndex = 0;
        newRow.querySelector('.total-price').textContent = '0.00';
        newRow.querySelector('.inventory-info').textContent = '';
        newRow.querySelector('.remove-item').disabled = false;
        
        tbody.appendChild(newRow);
        attachRowEventListeners(newRow);
    }

    function attachRowEventListeners(row) {
        // Profile selection change
        row.querySelector('.profile-select').addEventListener('change', function() {
            const profileId = this.value;
            if (profileId) {
                // Load inventory info
                fetch('../api/get-inventory-data.php?product_id=' + profileId + '&store_id=<?= $storeId ?>')
                    .then(response => response.json())
                    .then(data => {
                        const infoSpan = row.querySelector('.inventory-info');
                        infoSpan.textContent = `Anbarda: ${data.quantity || 0} ${data.unit || 'ədəd'}`;
                        if (data.quantity < 5) {
                            infoSpan.classList.add('text-danger');
                        }
                    });
            }
        });

        // Quantity, price changes
        row.querySelectorAll('.quantity, .unit-price').forEach(input => {
            input.addEventListener('input', function() {
                calculateRowTotal(row);
            });
        });

        // Remove button
        row.querySelector('.remove-item').addEventListener('click', function() {
            if (document.querySelectorAll('.order-item-row').length > 1) {
                row.remove();
                calculateGrandTotal();
            }
        });
    }

    function calculateRowTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const total = quantity * unitPrice;
        
        row.querySelector('.total-price').textContent = total.toFixed(2);
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        document.querySelectorAll('.total-price').forEach(span => {
            subtotal += parseFloat(span.textContent) || 0;
        });
        
        const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
        const shipping = parseFloat(document.getElementById('shippingCost').value) || 0;
        const installation = parseFloat(document.getElementById('installationCost').value) || 0;
        
        const afterDiscount = subtotal - discount;
        const tax = afterDiscount * 0.18;
        const grandTotal = afterDiscount + tax + shipping + installation;
        
        document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' ₼';
        document.getElementById('taxAmount').textContent = tax.toFixed(2) + ' ₼';
        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2) + ' ₼';
    }

    function saveNewCustomer() {
        const formData = new FormData();
        formData.append('full_name', document.getElementById('customerName').value);
        formData.append('phone', document.getElementById('customerPhone').value);
        formData.append('email', document.getElementById('customerEmail').value);
        formData.append('address', document.getElementById('customerAddress').value);
        
        fetch('../api/save-customer.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                selectCustomer(data.customer_id, data.customer_name, data.customer_phone);
                bootstrap.Modal.getInstance(document.getElementById('newCustomerModal')).hide();
                document.getElementById('newCustomerForm').reset();
            } else {
                alert('Xəta: ' + data.message);
            }
        });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Load profiles for first row
        loadProfiles();
        
        // Attach event listeners to first row
        attachRowEventListeners(document.querySelector('.order-item-row'));
        
        // Add event listeners for cost calculations
        ['discountAmount', 'shippingCost', 'installationCost'].forEach(id => {
            document.getElementById(id).addEventListener('input', calculateGrandTotal);
        });
    });

    function loadProfiles() {
        fetch('../api/search-products.php?type=profile')
            .then(response => response.json())
            .then(data => {
                const selects = document.querySelectorAll('.profile-select');
                selects.forEach(select => {
                    select.innerHTML = '<option value="">Profil Tipi Seçin</option>';
                    data.forEach(product => {
                        select.innerHTML += `<option value="${product.id}">${product.name} - ${product.type}</option>`;
                    });
                });
            });
    }

    // Form submission
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!selectedCustomerId) {
            alert('Müştəri seçin!');
            return;
        }
        
        // Collect order data
        const orderData = {
            customer_id: selectedCustomerId,
            order_date: document.getElementById('orderDate').value,
            notes: document.getElementById('orderNotes').value,
            discount_amount: document.getElementById('discountAmount').value,
            shipping_cost: document.getElementById('shippingCost').value,
            installation_cost: document.getElementById('installationCost').value,
            items: []
        };
        
        // Collect order items
        document.querySelectorAll('.order-item-row').forEach(row => {
            const profileId = row.querySelector('.profile-select').value;
            const height = row.querySelector('.door-height').value;
            const width = row.querySelector('.door-width').value;
            const quantity = row.querySelector('.quantity').value;
            const unitPrice = row.querySelector('.unit-price').value;
            
            if (profileId && height && width && quantity && unitPrice) {
                orderData.items.push({
                    profile_id: profileId,
                    height: height,
                    width: width,
                    quantity: quantity,
                    unit_price: unitPrice
                });
            }
        });
        
        if (orderData.items.length === 0) {
            alert('Ən azı bir məhsul əlavə edin!');
            return;
        }
        
        // Submit order
        fetch('../api/save-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sifariş uğurla yadda saxlanıldı!');
                window.location.href = 'order-details.php?id=' + data.order_id;
            } else {
                alert('Xəta: ' + data.message);
            }
        });
    });
    </script>
</body>
</html>