<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Order.php';
require_once '../includes/Notification.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? 0;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$currentUser = $auth->getCurrentUser();

// Get order
$order = $db->selectOne("
    SELECT o.*, c.user_id as customer_user_id 
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = :order_id
", ['order_id' => $orderId]);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Sifariş tapılmadı']);
    exit;
}

// Check permission
if ($auth->hasRole('customer')) {
    // Customer can only cancel their own orders
    if ($order['customer_user_id'] != $currentUser['id']) {
        echo json_encode(['success' => false, 'message' => 'Bu sifarişi ləğv edə bilməzsiniz']);
        exit;
    }
    
    // Customer can only cancel pending orders
    if ($order['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Yalnız gözləyən sifarişləri ləğv edə bilərsiniz']);
        exit;
    }
}

// Update order status
$db->update('orders', 
    [
        'status' => 'cancelled',
        'cancelled_at' => date('Y-m-d H:i:s'),
        'cancelled_by' => $currentUser['id'],
        'cancellation_reason' => $data['reason'] ?? 'Müştəri tərəfindən ləğv edildi'
    ],
    'id = :id',
    ['id' => $orderId]
);

// Return items to inventory
$orderItems = $db->select("
    SELECT * FROM order_items WHERE order_id = :order_id
", ['order_id' => $orderId]);

$inventory = new Inventory();
foreach ($orderItems as $item) {
    if ($item['profile_type_id']) {
        $inventory->returnToStock($item['profile_type_id'], $item['quantity'], $order['store_id']);
    }
}

// Send notifications
$notification = new Notification();

// Notify salesperson
if ($order['salesperson_id']) {
    $notification->send(
        $order['salesperson_id'],
        'Sifariş Ləğv Edildi',
        'Sifariş №' . $order['order_number'] . ' ləğv edildi',
        'warning',
        $orderId
    );
}

// Log activity
$db->insert('activity_logs', [
    'user_id' => $currentUser['id'],
    'action' => 'order_cancelled',
    'description' => 'Sifariş №' . $order['order_number'] . ' ləğv edildi',
    'related_type' => 'order',
    'related_id' => $orderId,
    'created_at' => date('Y-m-d H:i:s')
]);

echo json_encode(['success' => true, 'message' => 'Sifariş uğurla ləğv edildi']);