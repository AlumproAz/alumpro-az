<?php
$pageTitle = 'Müştəri Panel';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// Check authentication and customer role
if (!$auth->isLoggedIn() || !$auth->hasRole('customer')) {
    header('Location: ../login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();

// Get customer data
$customer = $db->selectOne("
    SELECT * FROM customers WHERE user_id = :user_id
", ['user_id' => $currentUser['id']]);

// Get customer statistics
$totalOrders = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE customer_id = :customer_id
", ['customer_id' => $customer['id']])['count'];

$totalSpent = $db->selectOne("
    SELECT COALESCE(SUM(grand_total), 0) as total 
    FROM orders 
    WHERE customer_id = :customer_id AND status = 'completed'
", ['customer_id' => $customer['id']])['total'];

$pendingOrders = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE customer_id = :customer_id AND status = 'pending'
", ['customer_id' => $customer['id']])['count'];

$completedOrders = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE customer_id = :customer_id AND status = 'completed'
", ['customer_id' => $customer['id']])['count'];

// Recent orders for this customer
$recentOrders = $db->select("
    SELECT o.*, u.full_name as salesperson_name 
    FROM orders o 
    LEFT JOIN users u ON o.salesperson_id = u.id 
    WHERE o.customer_id = :customer_id
    ORDER BY o.created_at DESC 
    LIMIT 6
", ['customer_id' => $customer['id']]);

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
                        <h5><i class="bi bi-person"></i> Müştəri Panel</h5>
                    </div>
                    <div class="sidebar-menu">
                        <a href="index.php" class="menu-item active">
                            <i class="bi bi-grid"></i> Ana Panel
                        </a>
                        <a href="orders.php" class="menu-item">
                            <i class="bi bi-bag-check"></i> Sifarişlərim
                        </a>
                        <a href="new-request.php" class="menu-item">
                            <i class="bi bi-plus-circle"></i> Yeni Sorğu
                        </a>
                        <a href="products.php" class="menu-item">
                            <i class="bi bi-box-seam"></i> Məhsullar
                        </a>
                        <a href="messages.php" class="menu-item">
                            <i class="bi bi-chat-dots"></i> Mesajlar
                        </a>
                        <a href="profile.php" class="menu-item">
                            <i class="bi bi-person-gear"></i> Profil
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
                    <h2>Müştəri Panel</h2>
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
                                <h3><?= $totalOrders ?></h3>
                                <p>Ümumi Sifarişlər</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $completedOrders ?></h3>
                                <p>Tamamlanmış</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $pendingOrders ?></h3>
                                <p>Gözləyən</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= number_format($totalSpent, 0) ?> ₼</h3>
                                <p>Ümumi Xərc</p>
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
                                        <a href="new-request.php" class="quick-action-btn">
                                            <i class="bi bi-plus-circle"></i>
                                            <span>Yeni Sorğu</span>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="orders.php" class="quick-action-btn">
                                            <i class="bi bi-list-check"></i>
                                            <span>Sifarişlərim</span>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="products.php" class="quick-action-btn">
                                            <i class="bi bi-search"></i>
                                            <span>Məhsul Axtarış</span>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="messages.php" class="quick-action-btn">
                                            <i class="bi bi-chat-dots"></i>
                                            <span>Dəstək</span>
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
                                                <th>Satıcı</th>
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
                                                <td><?= htmlspecialchars($order['salesperson_name'] ?: 'N/A') ?></td>
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
                                    <a href="new-request.php" class="btn btn-primary">İlk Sorğunu Göndər</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-person-badge"></i> Profil Məlumatları</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <i class="bi bi-person text-primary"></i>
                                    <div>
                                        <strong>Ad Soyad</strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($currentUser['full_name']) ?></small>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-telephone text-success"></i>
                                    <div>
                                        <strong>Telefon</strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($currentUser['phone']) ?></small>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-envelope text-info"></i>
                                    <div>
                                        <strong>E-mail</strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($currentUser['email']) ?></small>
                                    </div>
                                </div>
                                <?php if (!empty($customer['address'])): ?>
                                <div class="info-item">
                                    <i class="bi bi-geo-alt text-warning"></i>
                                    <div>
                                        <strong>Ünvan</strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($customer['address']) ?></small>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a href="profile.php" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="bi bi-pencil"></i> Məlumatları Yenilə
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="bi bi-headset"></i> Dəstək & Əlaqə</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="messages.php" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-chat-dots"></i> Mesaj Göndər
                                    </a>
                                    <a href="tel:+994123456789" class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-telephone"></i> Zəng Et
                                    </a>
                                    <a href="mailto:info@alumpro.az" class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-envelope"></i> E-mail Göndər
                                    </a>
                                    <a href="https://wa.me/994123456789" class="btn btn-outline-success btn-sm" target="_blank">
                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                    </a>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> İş saatları: 09:00 - 18:00
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="bi bi-star"></i> Müştəri Statusu</h5>
                            </div>
                            <div class="card-body text-center">
                                <?php
                                $customerLevel = 'Yeni';
                                $customerBadge = 'secondary';
                                if ($totalSpent > 10000) {
                                    $customerLevel = 'VIP';
                                    $customerBadge = 'warning';
                                } elseif ($totalSpent > 5000) {
                                    $customerLevel = 'Premium';
                                    $customerBadge = 'info';
                                } elseif ($totalSpent > 1000) {
                                    $customerLevel = 'Sadiq';
                                    $customerBadge = 'success';
                                }
                                ?>
                                <span class="badge bg-<?= $customerBadge ?> fs-6 mb-2"><?= $customerLevel ?> Müştəri</span>
                                <p class="text-muted mb-0">
                                    <?php if ($customerLevel === 'Yeni'): ?>
                                        1000 ₼ xərc edərək Sadiq müştəri olun!
                                    <?php elseif ($customerLevel === 'Sadiq'): ?>
                                        5000 ₼ xərc edərək Premium müştəri olun!
                                    <?php elseif ($customerLevel === 'Premium'): ?>
                                        10000 ₼ xərc edərək VIP müştəri olun!
                                    <?php else: ?>
                                        Təbriklər! VIP müştərimizsiniz!
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>