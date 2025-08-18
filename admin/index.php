<?php
$pageTitle = 'Admin Panel';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// Check authentication and admin role
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();

// Get dashboard statistics
$totalUsers = $db->selectOne("SELECT COUNT(*) as count FROM users")['count'];
$totalCustomers = $db->selectOne("SELECT COUNT(*) as count FROM customers")['count'];
$totalOrders = $db->selectOne("SELECT COUNT(*) as count FROM orders")['count'];
$totalProducts = $db->selectOne("SELECT COUNT(*) as count FROM products")['count'];

// Recent orders
$recentOrders = $db->select("
    SELECT o.*, c.full_name as customer_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");

// Active users count
$activeUsers = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")['count'];

$extraHeadContent = '
    <link rel="stylesheet" href="' . SITE_URL . '/assets/css/admin.css">
';

require_once '../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="admin-sidebar">
                    <div class="sidebar-header">
                        <h5><i class="bi bi-speedometer2"></i> Admin Panel</h5>
                    </div>
                    <div class="sidebar-menu">
                        <a href="index.php" class="menu-item active">
                            <i class="bi bi-grid"></i> Ana Panel
                        </a>
                        <a href="users.php" class="menu-item">
                            <i class="bi bi-people"></i> İstifadəçilər
                        </a>
                        <a href="customers.php" class="menu-item">
                            <i class="bi bi-person-badge"></i> Müştərilər
                        </a>
                        <a href="orders.php" class="menu-item">
                            <i class="bi bi-bag-check"></i> Sifarişlər
                        </a>
                        <a href="products.php" class="menu-item">
                            <i class="bi bi-box-seam"></i> Məhsullar
                        </a>
                        <a href="inventory.php" class="menu-item">
                            <i class="bi bi-boxes"></i> Anbar
                        </a>
                        <a href="reports.php" class="menu-item">
                            <i class="bi bi-bar-chart"></i> Hesabatlar
                        </a>
                        <a href="settings.php" class="menu-item">
                            <i class="bi bi-gear"></i> Ayarlar
                        </a>
                        <a href="notifications.php" class="menu-item">
                            <i class="bi bi-bell"></i> Bildirişlər
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <div class="admin-header">
                    <h2>Admin Panel</h2>
                    <p class="text-muted">Sistem idarəetməsi və nəzarəti</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $totalUsers ?></h3>
                                <p>Ümumi İstifadəçi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $totalCustomers ?></h3>
                                <p>Müştərilər</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="bi bi-bag-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $totalOrders ?></h3>
                                <p>Sifarişlər</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $totalProducts ?></h3>
                                <p>Məhsullar</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-clock-history"></i> Son Sifarişlər</h5>
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                                <td><?= htmlspecialchars($order['customer_name'] ?: 'N/A') ?></td>
                                                <td><?= number_format($order['grand_total'], 2) ?> ₼</td>
                                                <td>
                                                    <span class="badge bg-<?= $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'completed' ? 'success' : 'info') ?>">
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
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-activity"></i> Sistem Statusu</h5>
                            </div>
                            <div class="card-body">
                                <div class="system-status">
                                    <div class="status-item">
                                        <span class="status-indicator bg-success"></span>
                                        <div>
                                            <strong>Sistem</strong>
                                            <small class="d-block text-muted">Aktiv</small>
                                        </div>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-indicator bg-success"></span>
                                        <div>
                                            <strong>Verilənlər Bazası</strong>
                                            <small class="d-block text-muted">Əlaqəli</small>
                                        </div>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-indicator bg-warning"></span>
                                        <div>
                                            <strong>Aktiv İstifadəçilər</strong>
                                            <small class="d-block text-muted"><?= $activeUsers ?> son 30 gündə</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="bi bi-tools"></i> Sürətli Əməliyyatlar</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="orders.php?action=new" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-circle"></i> Yeni Sifariş
                                    </a>
                                    <a href="customers.php?action=new" class="btn btn-success btn-sm">
                                        <i class="bi bi-person-plus"></i> Yeni Müştəri
                                    </a>
                                    <a href="products.php?action=new" class="btn btn-info btn-sm">
                                        <i class="bi bi-box-seam"></i> Yeni Məhsul
                                    </a>
                                    <a href="reports.php" class="btn btn-warning btn-sm">
                                        <i class="bi bi-download"></i> Hesabat Yükle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>