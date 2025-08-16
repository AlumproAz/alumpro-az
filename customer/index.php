<?php
$pageTitle = 'Müştəri Paneli';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

session_start();

$auth = new Auth();
$db = Database::getInstance();

// Check if user is logged in and is customer
if (!$auth->isLoggedIn() || !$auth->hasRole('customer')) {
    header('Location: ../login.php');
    exit;
}

$user = $auth->getCurrentUser();

// Get customer information
$customer = $db->selectOne("SELECT * FROM customers WHERE user_id = :user_id", ['user_id' => $user['id']]);

// Get customer orders
$orders = $db->select("
    SELECT o.*, s.name as store_name
    FROM orders o
    LEFT JOIN stores s ON o.store_id = s.id
    WHERE o.customer_id = :customer_id
    ORDER BY o.created_at DESC
    LIMIT 10
", ['customer_id' => $customer['id']]);

// Get order statistics
$totalOrders = $db->selectOne("SELECT COUNT(*) as count FROM orders WHERE customer_id = :customer_id", ['customer_id' => $customer['id']])['count'] ?? 0;
$totalSpent = $db->selectOne("SELECT SUM(grand_total) as total FROM orders WHERE customer_id = :customer_id", ['customer_id' => $customer['id']])['total'] ?? 0;
$pendingOrders = $db->selectOne("SELECT COUNT(*) as count FROM orders WHERE customer_id = :customer_id AND status = 'pending'", ['customer_id' => $customer['id']])['count'] ?? 0;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-person-circle"></i> Müştəri Paneli
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
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Sifarişlərim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="support.php">
                            <i class="bi bi-chat-dots"></i> Dəstək
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($customer['full_name']) ?>
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2>Xoş gəlmisiniz, <?= htmlspecialchars($customer['full_name']) ?>!</h2>
                                <p class="mb-0">Sifarişlərinizi izləyin və dəstək alın.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-md-4 mb-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Ümumi Sifarişlər</h5>
                                <h2><?= $totalOrders ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-cart-check fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Ümumi Xərclər</h5>
                                <h2><?= number_format($totalSpent, 0) ?> ₼</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-currency-dollar fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Gözləyən Sifarişlər</h5>
                                <h2><?= $pendingOrders ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-clock-history fs-1"></i>
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
                            <i class="bi bi-clock-history"></i> Son Sifarişlərim
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sifariş №</th>
                                        <th>Mağaza</th>
                                        <th>Məbləğ</th>
                                        <th>Status</th>
                                        <th>Tarix</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                        <td><?= htmlspecialchars($order['store_name']) ?></td>
                                        <td><?= number_format($order['grand_total'], 2) ?> ₼</td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'info') ?>">
                                                <?php
                                                switch($order['status']) {
                                                    case 'pending': echo 'Gözləyir'; break;
                                                    case 'in_production': echo 'İstehsalda'; break;
                                                    case 'completed': echo 'Tamamlandı'; break;
                                                    case 'cancelled': echo 'Ləğv edildi'; break;
                                                    default: echo ucfirst($order['status']);
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <a href="order-details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Bax
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($orders) == 0): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-cart-x fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Hələ heç bir sifarişiniz yoxdur.</p>
                            <a href="../contact.php" class="btn btn-primary">İlk Sifarişinizi Verin</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions & Contact -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning"></i> Tez Əməliyyatlar
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../contact.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Yeni Sifariş Ver
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary">
                                <i class="bi bi-person"></i> Profili Redaktə Et
                            </a>
                            <a href="support.php" class="btn btn-outline-info">
                                <i class="bi bi-chat-dots"></i> Dəstək
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-telephone"></i> Əlaqə
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Mağaza 1:</h6>
                        <p class="mb-2">
                            <i class="bi bi-geo-alt"></i> Bakı, Nəsimi r., H.Əliyev pr. 123<br>
                            <i class="bi bi-telephone"></i> +994 12 345 67 89<br>
                            <button class="btn btn-sm btn-outline-success mt-1" onclick="selectStore(1)">
                                <i class="bi bi-telephone"></i> Zəng Et
                            </button>
                        </p>
                        
                        <h6 class="mt-3">Mağaza 2:</h6>
                        <p class="mb-2">
                            <i class="bi bi-geo-alt"></i> Bakı, Yasamal r., Nobel pr. 456<br>
                            <i class="bi bi-telephone"></i> +994 12 987 65 43<br>
                            <button class="btn btn-sm btn-outline-success mt-1" onclick="selectStore(2)">
                                <i class="bi bi-telephone"></i> Zəng Et
                            </button>
                        </p>

                        <hr>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="openWhatsApp()">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </button>
                            <button class="btn btn-info" onclick="openTelegram()">
                                <i class="bi bi-telegram"></i> Telegram
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Chat Button -->
    <div class="position-fixed bottom-0 end-0 m-3">
        <button class="btn btn-info btn-lg rounded-circle" data-bs-toggle="modal" data-bs-target="#supportModal">
            <i class="bi bi-chat-dots"></i>
        </button>
    </div>

    <!-- Support Modal -->
    <div class="modal fade" id="supportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-chat-dots"></i> Dəstək Xidməti
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="chatMessages" style="height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                        <!-- Chat messages will appear here -->
                        <div class="text-center text-muted">
                            <i class="bi bi-chat-text fs-3"></i>
                            <p>Salam! Sizə necə kömək edə bilərəm?</p>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" class="form-control" id="chatInput" placeholder="Mesajınızı yazın...">
                        <button class="btn btn-primary" type="button" onclick="sendMessage()">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                    
                    <div class="mt-2">
                        <small class="text-muted">
                            Tez cavablar: 
                            <a href="#" onclick="quickReply('Sifariş statusu')">Sifariş statusu</a> | 
                            <a href="#" onclick="quickReply('Qiymət')">Qiymət soruşu</a> | 
                            <a href="#" onclick="quickReply('Çatdırılma')">Çatdırılma</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
    function selectStore(storeId) {
        const phones = {
            1: '+994123456789',
            2: '+994129876543'
        };
        window.open('tel:' + phones[storeId]);
    }

    function openWhatsApp() {
        window.open('https://wa.me/994123456789', '_blank');
    }

    function openTelegram() {
        window.open('https://t.me/alumpro_az', '_blank');
    }

    function sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        if (message) {
            // Add message to chat (placeholder functionality)
            const chatDiv = document.getElementById('chatMessages');
            chatDiv.innerHTML += `
                <div class="mb-2 text-end">
                    <span class="badge bg-primary">${message}</span>
                </div>
            `;
            input.value = '';
            chatDiv.scrollTop = chatDiv.scrollHeight;
            
            // Simulate auto-reply
            setTimeout(() => {
                chatDiv.innerHTML += `
                    <div class="mb-2">
                        <span class="badge bg-secondary">Mesajınız qəbul edildi. Tezliklə cavab veriləcək.</span>
                    </div>
                `;
                chatDiv.scrollTop = chatDiv.scrollHeight;
            }, 1000);
        }
    }

    function quickReply(message) {
        document.getElementById('chatInput').value = message;
        sendMessage();
    }

    // Enter key support for chat
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    </script>
</body>
</html>