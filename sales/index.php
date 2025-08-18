<?php
$pageTitle = 'Satış Panel';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// Check authentication and sales role
if (!$auth->isLoggedIn() || !$auth->hasRole('sales')) {
    header('Location: ../login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();

// Get salesperson statistics
$myOrders = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE salesperson_id = :salesperson_id
", ['salesperson_id' => $currentUser['id']])['count'];

$myCustomers = $db->selectOne("
    SELECT COUNT(DISTINCT customer_id) as count 
    FROM orders 
    WHERE salesperson_id = :salesperson_id
", ['salesperson_id' => $currentUser['id']])['count'];

$todayOrders = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE salesperson_id = :salesperson_id AND DATE(created_at) = CURDATE()
", ['salesperson_id' => $currentUser['id']])['count'];

$monthlyRevenue = $db->selectOne("
    SELECT COALESCE(SUM(grand_total), 0) as total 
    FROM orders 
    WHERE salesperson_id = :salesperson_id 
    AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
", ['salesperson_id' => $currentUser['id']])['total'];

// Recent orders for this salesperson
$recentOrders = $db->select("
    SELECT o.*, c.full_name as customer_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    WHERE o.salesperson_id = :salesperson_id
    ORDER BY o.created_at DESC 
    LIMIT 8
", ['salesperson_id' => $currentUser['id']]);

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
                        <h5><i class="bi bi-shop"></i> Satış Panel</h5>
                    </div>
                    <div class="sidebar-menu">
                        <a href="index.php" class="menu-item active">
                            <i class="bi bi-grid"></i> Ana Panel
                        </a>
                        <a href="orders.php" class="menu-item">
                            <i class="bi bi-bag-check"></i> Sifarişlərim
                        </a>
                        <a href="new-order.php" class="menu-item">
                            <i class="bi bi-plus-circle"></i> Yeni Sifariş
                        </a>
                        <a href="customers.php" class="menu-item">
                            <i class="bi bi-people"></i> Müştərilərim
                        </a>
                        <a href="products.php" class="menu-item">
                            <i class="bi bi-box-seam"></i> Məhsullar
                        </a>
                        <a href="inventory.php" class="menu-item">
                            <i class="bi bi-boxes"></i> Anbar
                        </a>
                        <a href="reports.php" class="menu-item">
                            <i class="bi bi-bar-chart"></i> Satış Hesabatı
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
                    <h2>Satış Panel</h2>
                    <p class="text-muted">Xoş gəlmisiniz, <?= htmlspecialchars($currentUser['full_name']) ?>!</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="bi bi-bag-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $myOrders ?></h3>
                                <p>Ümumi Sifarişlər</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $myCustomers ?></h3>
                                <p>Müştərilərim</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="bi bi-calendar-day"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $todayOrders ?></h3>
                                <p>Bu Gün</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= number_format($monthlyRevenue, 0) ?> ₼</h3>
                                <p>Bu Ay Satış</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-lightning"></i> Sürətli Əməliyyatlar</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="new-order.php" class="quick-action-btn">
                                            <i class="bi bi-plus-circle"></i>
                                            <span>Yeni Sifariş</span>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="customers.php?action=new" class="quick-action-btn">
                                            <i class="bi bi-person-plus"></i>
                                            <span>Yeni Müştəri</span>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="products.php" class="quick-action-btn">
                                            <i class="bi bi-search"></i>
                                            <span>Məhsul Axtarış</span>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="inventory.php" class="quick-action-btn">
                                            <i class="bi bi-boxes"></i>
                                            <span>Anbar Yoxla</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-clock-history"></i> Son Sifarişlərim</h5>
                                <a href="orders.php" class="btn btn-sm btn-outline-primary">Hamısına Bax</a>
                            </div>
                            <div class="card-body">
                                <?php if (count($recentOrders) > 0): ?>
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
                                                <td>#<?= htmlspecialchars($order['order_number']) ?></td>
                                                <td><?= htmlspecialchars($order['customer_name'] ?: 'N/A') ?></td>
                                                <td><?= number_format($order['grand_total'], 2) ?> ₼</td>
                                                <td>
                                                    <span class="badge bg-<?= $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'completed' ? 'success' : 'info') ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">Bax</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-bag-x display-4 text-muted"></i>
                                    <p class="mt-3 text-muted">Hələ ki sifariş yoxdur.</p>
                                    <a href="new-order.php" class="btn btn-primary">İlk Sifarişi Yarat</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-target"></i> Bu Ay Hədəflərim</h5>
                            </div>
                            <div class="card-body">
                                <div class="progress-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Satış Məbləği</span>
                                        <span><?= number_format($monthlyRevenue, 0) ?> / 50000 ₼</span>
                                    </div>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-success" style="width: <?= min(($monthlyRevenue / 50000) * 100, 100) ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="progress-item mt-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Sifariş Sayı</span>
                                        <span><?= $myOrders ?> / 100</span>
                                    </div>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-info" style="width: <?= min(($myOrders / 100) * 100, 100) ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="progress-item mt-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Yeni Müştərilər</span>
                                        <span><?= $myCustomers ?> / 50</span>
                                    </div>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-warning" style="width: <?= min(($myCustomers / 50) * 100, 100) ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="bi bi-info-circle"></i> Məlumat</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <i class="bi bi-clock text-primary"></i>
                                    <div>
                                        <strong>İş Saatları</strong>
                                        <small class="d-block text-muted">09:00 - 18:00</small>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-telephone text-success"></i>
                                    <div>
                                        <strong>Dəstək</strong>
                                        <small class="d-block text-muted">+994 12 345 67 89</small>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-envelope text-info"></i>
                                    <div>
                                        <strong>E-mail</strong>
                                        <small class="d-block text-muted">sales@alumpro.az</small>
                                    </div>
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