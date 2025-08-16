<?php
$pageTitle = 'Admin Panel';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

session_start();

$auth = new Auth();
$db = Database::getInstance();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$user = $auth->getCurrentUser();

// Get dashboard statistics
$totalUsers = $db->selectOne("SELECT COUNT(*) as count FROM users")['count'];
$totalCustomers = $db->selectOne("SELECT COUNT(*) as count FROM customers")['count'];
$totalOrders = $db->selectOne("SELECT COUNT(*) as count FROM orders")['count'];
$totalProducts = $db->selectOne("SELECT COUNT(*) as count FROM products")['count'];
$totalInventory = $db->selectOne("SELECT SUM(quantity) as total FROM inventory")['total'] ?? 0;

// Monthly sales data
$monthlySales = $db->selectOne("
    SELECT SUM(grand_total) as total 
    FROM orders 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
")['total'] ?? 0;

// Recent orders
$recentOrders = $db->select("
    SELECT o.*, c.full_name as customer_name, u.full_name as salesperson_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN users u ON o.salesperson_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
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
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    
    <!-- Charts.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-gear-fill"></i> Admin Panel
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-speedometer2"></i> Əsas Səhifə
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> İstifadəçilər
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box-seam"></i> Məhsullar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventory.php">
                            <i class="bi bi-boxes"></i> Anbar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Sifarişlər
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
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="bi bi-gear"></i> Ayarlar
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
                                <h5 class="card-title">İstifadəçilər</h5>
                                <h2><?= $totalUsers ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-people fs-1"></i>
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
                                <h5 class="card-title">Müştərilər</h5>
                                <h2><?= $totalCustomers ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-person-check fs-1"></i>
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
                                <h5 class="card-title">Sifarişlər</h5>
                                <h2><?= $totalOrders ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-cart-check fs-1"></i>
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
                                <h5 class="card-title">Aylıq Satış</h5>
                                <h2><?= number_format($monthlySales, 0) ?> ₼</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-currency-dollar fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Orders -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history"></i> Son Sifarişlər
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sifariş №</th>
                                        <th>Müştəri</th>
                                        <th>Satıcı</th>
                                        <th>Məbləğ</th>
                                        <th>Status</th>
                                        <th>Tarix</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($order['salesperson_name']) ?></td>
                                        <td><?= number_format($order['grand_total'], 2) ?> ₼</td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'info') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning"></i> Tez Əməliyyatlar
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="users.php?action=add" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Yeni İstifadəçi
                            </a>
                            <a href="products.php?action=add" class="btn btn-outline-success">
                                <i class="bi bi-plus-square"></i> Yeni Məhsul
                            </a>
                            <a href="inventory.php?action=add" class="btn btn-outline-warning">
                                <i class="bi bi-box-arrow-in-down"></i> Anbara Məhsul
                            </a>
                            <a href="settings.php" class="btn btn-outline-info">
                                <i class="bi bi-gear"></i> Sistem Ayarları
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0 text-danger">
                            <i class="bi bi-exclamation-triangle"></i> Az Qalan Məhsullar
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $lowStock = $db->select("
                            SELECT p.name, i.quantity, p.unit
                            FROM inventory i
                            JOIN products p ON i.product_id = p.id
                            WHERE i.quantity < 10
                            ORDER BY i.quantity ASC
                            LIMIT 5
                        ");
                        ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($lowStock as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($item['name']) ?></span>
                                <span class="badge bg-danger"><?= $item['quantity'] ?> <?= $item['unit'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>