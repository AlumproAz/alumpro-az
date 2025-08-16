<?php
$pageTitle = 'Satış Paneli';
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

// Get dashboard statistics for this store
$todaySales = $db->selectOne("
    SELECT SUM(grand_total) as total 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() 
    AND store_id = :store_id
", ['store_id' => $storeId])['total'] ?? 0;

$monthSales = $db->selectOne("
    SELECT SUM(grand_total) as total 
    FROM orders 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    AND store_id = :store_id
", ['store_id' => $storeId])['total'] ?? 0;

$myTodayOrders = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() 
    AND salesperson_id = :user_id
", ['user_id' => $user['id']])['count'] ?? 0;

// Recent orders for this salesperson
$recentOrders = $db->select("
    SELECT o.*, c.full_name as customer_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.salesperson_id = :user_id
    ORDER BY o.created_at DESC
    LIMIT 5
", ['user_id' => $user['id']]);

// Top customers for this store
$topCustomers = $db->select("
    SELECT c.full_name, COUNT(o.id) as order_count, SUM(o.grand_total) as total_spent
    FROM customers c
    JOIN orders o ON c.id = o.customer_id
    WHERE o.store_id = :store_id
    GROUP BY c.id
    ORDER BY total_spent DESC
    LIMIT 5
", ['store_id' => $storeId]);
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
    <link href="../assets/css/dashboard.css" rel="stylesheet">
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
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-speedometer2"></i> Ana Səhifə
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-order.php">
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
                    <li class="nav-item">
                        <a class="nav-link" href="inventory.php">
                            <i class="bi bi-boxes"></i> Anbar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Hesabatlar
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['full_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person"></i> Profil
                            </a></li>
                            <li><a class="dropdown-item" href="notifications.php">
                                <i class="bi bi-bell"></i> Bildirişlər
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
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
            <!-- Statistics Cards -->
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Bugünkü Satışım</h6>
                                <h2><?= $myTodayOrders ?></h2>
                                <small>sifariş</small>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-cart-check fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Bugünkü Mağaza Satışı</h6>
                                <h4><?= number_format($todaySales, 0) ?> ₼</h4>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-currency-dollar fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Aylıq Mağaza Satışı</h6>
                                <h4><?= number_format($monthSales, 0) ?> ₼</h4>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-graph-up fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Mağaza</h6>
                                <h5><?= htmlspecialchars($store['name']) ?></h5>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-shop fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning"></i> Tez Əməliyyatlar
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="create-order.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle"></i> Yeni Sifariş
                            </a>
                            <a href="customers.php?action=add" class="btn btn-outline-success">
                                <i class="bi bi-person-plus"></i> Yeni Müştəri
                            </a>
                            <a href="production-form.php" class="btn btn-outline-warning">
                                <i class="bi bi-file-earmark-pdf"></i> İstehsalat Forması
                            </a>
                            <a href="inventory.php" class="btn btn-outline-info">
                                <i class="bi bi-boxes"></i> Anbar Yoxla
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history"></i> Son Sifarişlərim
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sifariş №</th>
                                        <th>Müştəri</th>
                                        <th>Məbləğ</th>
                                        <th>Status</th>
                                        <th>Tarix</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= number_format($order['grand_total'], 2) ?> ₼</td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'info') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <a href="order-details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Customers -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-star"></i> Ən Çox Sifariş Verən Müştərilər
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($topCustomers as $customer): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($customer['full_name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= $customer['order_count'] ?> sifariş</small>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?= number_format($customer['total_spent'], 0) ?> ₼</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Search -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-search"></i> Müştəri Axtarışı
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="customerSearch" placeholder="Müştəri adı və ya telefon nömrəsi...">
                        </div>
                        <div id="customerSearchResults" class="list-group">
                            <!-- Search results will appear here -->
                        </div>
                        
                        <div class="mt-3">
                            <h6>Son Zəng Olunanlar:</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-telephone"></i> +994 50 123 45 67
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-telephone"></i> +994 55 987 65 43
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-telephone"></i> +994 70 456 78 90
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Chat Button -->
    <div class="position-fixed bottom-0 end-0 m-3">
        <button class="btn btn-primary btn-lg rounded-circle" data-bs-toggle="modal" data-bs-target="#supportModal">
            <i class="bi bi-chat-dots"></i>
        </button>
    </div>

    <!-- Support Modal (placeholder) -->
    <div class="modal fade" id="supportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dəstək</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Dəstək sistemi tezliklə əlavə olunacaq...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
    // Customer search functionality
    document.getElementById('customerSearch').addEventListener('input', function() {
        const query = this.value;
        if (query.length >= 2) {
            fetch('../api/search-customers.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('customerSearchResults');
                    resultsDiv.innerHTML = '';
                    
                    data.forEach(customer => {
                        const item = document.createElement('a');
                        item.className = 'list-group-item list-group-item-action';
                        item.href = 'customer-details.php?id=' + customer.id;
                        item.innerHTML = `
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1">${customer.full_name}</h6>
                                    <small>${customer.phone}</small>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="callCustomer('${customer.phone}')">
                                        <i class="bi bi-telephone"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        resultsDiv.appendChild(item);
                    });
                });
        }
    });

    function callCustomer(phone) {
        // Implement calling functionality
        window.open('tel:' + phone);
    }
    </script>
</body>
</html>