<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Order.php';
require_once '../includes/Inventory.php';
require_once '../includes/Notification.php';
require_once '../includes/WhatsApp.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn() || !$auth->hasRole('sales')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $db->beginTransaction();
    
    $order = new Order();
    $inventory = new Inventory();
    $notification = new Notification();
    $whatsapp = new WhatsApp();
    
    // Validate required fields
    if (!isset($data['customer_id']) || !isset($data['items']) || empty($data['items'])) {
        throw new Exception('Məcburi sahələr boşdur');
    }
    
    // Create order
    $orderData = [
        'order_number' => $order->generateOrderNumber(),
        'customer_id' => $data['customer_id'],
        'store_id' => $_SESSION['user']['store_id'],
        'salesperson_id' => $_SESSION['user']['id'],
        'order_date' => date('Y-m-d H:i:s'),
        'delivery_date' => $data['delivery_date'] ?? null,
        'delivery_address' => $data['delivery_address'] ?? null,
        'total_amount' => $data['total_amount'],
        'discount_amount' => $data['discount_amount'] ?? 0,
        'tax_amount' => $data['tax_amount'] ?? 0,
        'shipping_cost' => $data['shipping_cost'] ?? 0,
        'installation_cost' => $data['installation_cost'] ?? 0,
        'grand_total' => $data['grand_total'],
        'payment_method' => $data['payment_method'] ?? 'cash',
        'payment_status' => 'pending',
        'status' => 'pending',
        'notes' => $data['notes'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $orderId = $db->insert('orders', $orderData);
    
    // Save order items
    foreach ($data['items'] as $item) {
        $itemData = [
            'order_id' => $orderId,
            'profile_type_id' => $item['profile_id'] ?? null,
            'glass_type_id' => $item['glass_id'] ?? null,
            'height' => $item['height'],
            'width' => $item['width'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price'],
            'notes' => $item['notes'] ?? null
        ];
        
        $db->insert('order_items', $itemData);
        
        // Update inventory
        if ($item['profile_id']) {
            $inventory->reduceStock($item['profile_id'], $item['quantity'], $_SESSION['user']['store_id']);
        }
    }
    
    // Save accessories if any
    if (isset($data['accessories']) && !empty($data['accessories'])) {
        foreach ($data['accessories'] as $accessory) {
            $accessoryData = [
                'order_id' => $orderId,
                'accessory_id' => $accessory['id'],
                'quantity' => $accessory['quantity'],
                'unit_price' => $accessory['unit_price'],
                'total_price' => $accessory['total_price']
            ];
            
            $db->insert('order_accessories', $accessoryData);
        }
    }
    
    // Get customer details
    $customer = $db->selectOne("SELECT * FROM customers WHERE id = :id", ['id' => $data['customer_id']]);
    
    // Send notifications
    $notification->send(
        $customer['user_id'] ?? null,
        'Yeni Sifariş',
        'Sifariş №' . $orderData['order_number'] . ' qəbul edildi',
        'success',
        $orderId
    );
    
    // Send WhatsApp message
    if ($customer['phone']) {
        $message = "Salam {$customer['full_name']},\n\n";
        $message .= "Sifarişiniz qəbul edildi!\n";
        $message .= "Sifariş №: {$orderData['order_number']}\n";
        $message .= "Məbləğ: " . number_format($orderData['grand_total'], 2) . " ₼\n\n";
        $message .= "Alumpro.Az";
        
        $whatsapp->sendMessage($customer['phone'], $message);
    }
    
    // Log activity
    $db->insert('activity_logs', [
        'user_id' => $_SESSION['user']['id'],
        'action' => 'order_created',
        'description' => 'Yeni sifariş yaradıldı: №' . $orderData['order_number'],
        'related_type' => 'order',
        'related_id' => $orderId,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sifariş uğurla yaradıldı',
        'order_id' => $orderId,
        'order_number' => $orderData['order_number']
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}