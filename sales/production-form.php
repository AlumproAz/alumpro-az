<?php
$pageTitle = 'İstehsalat Forması';
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

// Get order ID if provided
$orderId = $_GET['order_id'] ?? null;
$order = null;
$orderItems = [];

if ($orderId) {
    $order = $db->selectOne("
        SELECT o.*, c.full_name as customer_name, c.phone as customer_phone, s.name as store_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN stores s ON o.store_id = s.id
        WHERE o.id = :id AND o.salesperson_id = :salesperson_id
    ", ['id' => $orderId, 'salesperson_id' => $user['id']]);
    
    if ($order) {
        $orderItems = $db->select("
            SELECT oi.*, p.name as profile_name, p.type as profile_type, p.color as profile_color
            FROM order_items oi
            LEFT JOIN products p ON oi.profile_type_id = p.id
            WHERE oi.order_id = :order_id
        ", ['order_id' => $orderId]);
    }
}

// Get store information
$store = $db->selectOne("SELECT * FROM stores WHERE id = :id", ['id' => $storeId]);
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
    
    <style>
    .production-form {
        background: white;
        min-height: 297mm;
        width: 210mm;
        margin: 0 auto;
        padding: 20mm;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .form-header {
        text-align: center;
        border-bottom: 2px solid #333;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    
    .company-logo {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #11998e, #38ef7d);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .barcode {
        text-align: center;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        margin: 10px 0;
        border: 1px solid #333;
        padding: 5px;
    }
    
    .production-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    .production-table th,
    .production-table td {
        border: 1px solid #333;
        padding: 8px;
        text-align: left;
        vertical-align: top;
    }
    
    .production-table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    
    .glass-section {
        background-color: #e3f2fd;
        padding: 5px;
        margin: 3px 0;
        border-left: 3px solid #2196f3;
    }
    
    .signature-section {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        border-top: 1px solid #ddd;
        padding-top: 20px;
    }
    
    .signature-box {
        width: 200px;
        text-align: center;
    }
    
    .signature-line {
        border-bottom: 1px solid #333;
        margin-bottom: 5px;
        height: 40px;
    }
    
    @media print {
        body { margin: 0; }
        .no-print { display: none !important; }
        .production-form { 
            box-shadow: none; 
            margin: 0;
            width: 100%;
        }
    }
    </style>
</head>
<body>
    <!-- Navigation (no-print) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success no-print">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Satış Paneli - <?= htmlspecialchars($store['name']) ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </div>
    </nav>

    <!-- Controls (no-print) -->
    <div class="container-fluid mt-3 no-print">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-file-earmark-pdf"></i> İstehsalat Forması
                                <?= $order ? ' - Sifariş #' . htmlspecialchars($order['order_number']) : '' ?>
                            </h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" onclick="window.print()">
                                    <i class="bi bi-printer"></i> Çap Et
                                </button>
                                <button type="button" class="btn btn-success" onclick="sendToProduction()">
                                    <i class="bi bi-whatsapp"></i> İstehsalata Göndər
                                </button>
                                <?php if (!$order): ?>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderSelectModal">
                                    <i class="bi bi-search"></i> Sifariş Seç
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Form -->
    <div class="container-fluid mt-3">
        <div class="production-form">
            <!-- Header -->
            <div class="form-header">
                <div class="company-logo">
                    <i class="bi bi-building"></i>
                </div>
                <h2>ALUMPRO.AZ</h2>
                <h4>İSTEHSALAT QAİMESİ</h4>
                <div class="barcode">
                    <strong>Form №: PF-<?= date('Ymd') ?>-<?= $orderId ? str_pad($orderId, 4, '0', STR_PAD_LEFT) : '0000' ?></strong>
                </div>
            </div>

            <!-- Order Information -->
            <div class="row mb-3">
                <div class="col-6">
                    <strong>Tarix:</strong> <?= date('d.m.Y H:i') ?><br>
                    <strong>Satıçı:</strong> <?= htmlspecialchars($user['full_name']) ?><br>
                    <strong>Mağaza:</strong> <?= htmlspecialchars($store['name']) ?>
                </div>
                <div class="col-6">
                    <?php if ($order): ?>
                    <strong>Müştəri:</strong> <?= htmlspecialchars($order['customer_name']) ?><br>
                    <strong>Telefon:</strong> <?= htmlspecialchars($order['customer_phone']) ?><br>
                    <strong>Sifariş №:</strong> <?= htmlspecialchars($order['order_number']) ?>
                    <?php else: ?>
                    <strong>Müştəri:</strong> ____________________<br>
                    <strong>Telefon:</strong> ____________________<br>
                    <strong>Sifariş №:</strong> ____________________
                    <?php endif; ?>
                </div>
            </div>

            <!-- Production Details -->
            <table class="production-table">
                <thead>
                    <tr>
                        <th width="5%">№</th>
                        <th width="25%">Profil Tipi</th>
                        <th width="12%">Qapaq Ölçüsü (sm)</th>
                        <th width="8%">Say</th>
                        <th width="25%">Şüşə Məlumatı</th>
                        <th width="25%">İstehsalat Qeydləri</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($order && count($orderItems) > 0): ?>
                        <?php foreach ($orderItems as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['profile_name']) ?></strong><br>
                                <small>Tip: <?= htmlspecialchars($item['profile_type']) ?></small><br>
                                <small>Rəng: <?= htmlspecialchars($item['profile_color']) ?></small>
                            </td>
                            <td>
                                H: <?= $item['height'] ?> sm<br>
                                E: <?= $item['width'] ?> sm
                            </td>
                            <td><?= $item['quantity'] ?></td>
                            <td>
                                <?php if ($item['glass_type_id']): ?>
                                <div class="glass-section">
                                    <strong>Şüşə ölçüsü:</strong><br>
                                    H: <?= ($item['height'] - 4) ?> sm<br>
                                    E: <?= ($item['width'] - 4) ?> sm<br>
                                    <small>(4mm kiçik)</small>
                                </div>
                                <?php else: ?>
                                <div style="height: 60px; border: 1px dashed #ccc; padding: 5px;">
                                    <small>Şüşə məlumatı əlavə ediləcək</small>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="height: 80px; border: 1px solid #ddd;">
                                <!-- Empty space for production notes -->
                            </td>
                        </tr>
                        <!-- Add empty rows for glass details -->
                        <tr style="height: 40px;">
                            <td></td>
                            <td colspan="2"><em>Şüşə əlavə qeydləri:</em></td>
                            <td colspan="3" style="border: 1px solid #ddd;"></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Empty rows for manual filling -->
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <tr style="height: 60px;">
                            <td><?= $i ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <div style="height: 50px; border: 1px dashed #ccc; padding: 2px;">
                                    <small>Şüşə ölçüsü:</small><br>
                                    H: _____ sm<br>
                                    E: _____ sm
                                </div>
                            </td>
                            <td></td>
                        </tr>
                        <!-- Glass details row -->
                        <tr style="height: 30px;">
                            <td></td>
                            <td colspan="2"><em>Şüşə qeydləri:</em></td>
                            <td colspan="3"></td>
                        </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Additional Instructions -->
            <div style="margin: 20px 0; border: 1px solid #333; padding: 15px;">
                <strong>Əlavə Təlimatlar:</strong>
                <div style="height: 80px; margin-top: 10px;">
                    <?= $order ? htmlspecialchars($order['notes']) : '' ?>
                </div>
            </div>

            <!-- Master Assignment -->
            <div class="row" style="margin: 20px 0;">
                <div class="col-6">
                    <strong>İstehsalat Ustası:</strong><br>
                    <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 10px;"></div>
                    <small>Ad Soyad</small>
                </div>
                <div class="col-6">
                    <strong>Başlama Tarixi:</strong><br>
                    <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 10px;"></div>
                    <small>Tarix və vaxt</small>
                </div>
            </div>

            <!-- Signatures -->
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <strong>Sifarişi təhvil verən</strong><br>
                    <small><?= htmlspecialchars($user['full_name']) ?></small>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <strong>Sifarişi təhvil alan</strong><br>
                    <small>İstehsalat ustası</small>
                </div>
            </div>

            <!-- Quality Control Section -->
            <div style="margin-top: 30px; border: 2px solid #333; padding: 15px;">
                <div class="row">
                    <div class="col-8">
                        <strong>Keyfiyyət Nəzarəti:</strong><br>
                        <label>
                            <input type="checkbox"> Detallari yoxladım, heç bir problem yoxdur, detalları qəbul edirəm
                        </label>
                    </div>
                    <div class="col-4">
                        <div style="border-bottom: 1px solid #333; height: 30px; margin-bottom: 5px;"></div>
                        <small>İmza və tarix</small>
                    </div>
                </div>
            </div>

            <!-- Delivery Section -->
            <div style="margin-top: 20px; border: 1px solid #333; padding: 15px;">
                <div class="row">
                    <div class="col-6">
                        <strong>Müştəriyə təhvil verilən tarix:</strong><br>
                        <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 10px;"></div>
                    </div>
                    <div class="col-6">
                        <strong>Müştərinin imzası:</strong><br>
                        <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 10px;"></div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                <p>ALUMPRO.AZ - Keyfiyyətli Alüminium Məhsulları</p>
                <p><?= htmlspecialchars($store['address']) ?> | Tel: <?= htmlspecialchars($store['phone']) ?></p>
            </div>
        </div>
    </div>

    <!-- Order Select Modal -->
    <?php if (!$order): ?>
    <div class="modal fade" id="orderSelectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sifariş Seçin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="orderSearch" placeholder="Sifariş nömrəsi və ya müştəri adı axtarın...">
                    </div>
                    <div id="orderResults">
                        <!-- Order search results will appear here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function sendToProduction() {
        if (!confirm('İstehsalat formasını WhatsApp vasitəsilə göndərmək istəyirsiniz?')) {
            return;
        }
        
        // Generate PDF and send via WhatsApp
        const formData = new FormData();
        formData.append('order_id', '<?= $orderId ?>');
        formData.append('action', 'send_production_form');
        
        fetch('../api/generate-production-form.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('İstehsalat forması uğurla göndərildi!');
            } else {
                alert('Xəta: ' + data.message);
            }
        })
        .catch(error => {
            alert('Xəta baş verdi: ' + error.message);
        });
    }

    // Order search functionality
    <?php if (!$order): ?>
    document.getElementById('orderSearch').addEventListener('input', function() {
        const query = this.value;
        if (query.length >= 2) {
            fetch('../api/search-orders.php?q=' + encodeURIComponent(query) + '&salesperson_id=<?= $user['id'] ?>')
                .then(response => response.json())
                .then(data => {
                    displayOrderResults(data);
                });
        } else {
            document.getElementById('orderResults').innerHTML = '';
        }
    });

    function displayOrderResults(orders) {
        const resultsDiv = document.getElementById('orderResults');
        if (orders.length === 0) {
            resultsDiv.innerHTML = '<div class="alert alert-warning">Sifariş tapılmadı.</div>';
            return;
        }

        let html = '<div class="list-group">';
        orders.forEach(order => {
            html += `
                <a href="production-form.php?order_id=${order.id}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">${order.order_number}</h6>
                            <small>${order.customer_name} - ${order.customer_phone}</small>
                        </div>
                        <div>
                            <small class="text-muted">${order.created_at}</small><br>
                            <span class="badge bg-${order.status === 'completed' ? 'success' : 'warning'}">${order.status}</span>
                        </div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
        resultsDiv.innerHTML = html;
    }
    <?php endif; ?>
    </script>
</body>
</html>