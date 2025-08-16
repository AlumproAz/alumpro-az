<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/WhatsApp.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn() || !$auth->hasRole('sales')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$orderId = $_POST['order_id'] ?? null;
$action = $_POST['action'] ?? '';

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Get order details
    $order = $db->selectOne("
        SELECT o.*, c.full_name as customer_name, c.phone as customer_phone, s.name as store_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN stores s ON o.store_id = s.id
        WHERE o.id = :id AND o.salesperson_id = :salesperson_id
    ", ['id' => $orderId, 'salesperson_id' => $user['id']]);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Get order items
    $orderItems = $db->select("
        SELECT oi.*, p.name as profile_name, p.type as profile_type, p.color as profile_color
        FROM order_items oi
        LEFT JOIN products p ON oi.profile_type_id = p.id
        WHERE oi.order_id = :order_id
    ", ['order_id' => $orderId]);
    
    // Generate production form content
    $productionFormData = [
        'form_number' => 'PF-' . date('Ymd') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT),
        'date' => date('d.m.Y H:i'),
        'salesperson' => $user['full_name'],
        'store_name' => $order['store_name'],
        'customer_name' => $order['customer_name'],
        'customer_phone' => $order['customer_phone'],
        'order_number' => $order['order_number'],
        'items' => $orderItems,
        'notes' => $order['notes']
    ];
    
    if ($action === 'send_production_form') {
        // Generate WhatsApp message
        $message = generateProductionMessage($productionFormData);
        
        // Get production WhatsApp number from settings
        $productionPhone = $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'production_whatsapp'");
        $productionPhone = $productionPhone['setting_value'] ?? '+994123456789'; // Default number
        
        // Send WhatsApp message
        $whatsapp = new WhatsApp();
        $result = $whatsapp->sendMessage($productionPhone, $message);
        
        if ($result['success']) {
            // Log production form generation
            $db->insert('production_forms', [
                'order_id' => $orderId,
                'form_number' => $productionFormData['form_number'],
                'created_by' => $user['id'],
                'sent_to_production' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Production form sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send WhatsApp message: ' . $result['error']]);
        }
    } else {
        // Return production form data for preview
        echo json_encode(['success' => true, 'data' => $productionFormData]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateProductionMessage($data) {
    $message = "🏭 *YENİ İSTEHSALAT SİFARİŞİ*\n\n";
    $message .= "📋 *Form №:* {$data['form_number']}\n";
    $message .= "📅 *Tarix:* {$data['date']}\n";
    $message .= "👤 *Satıçı:* {$data['salesperson']}\n";
    $message .= "🏪 *Mağaza:* {$data['store_name']}\n\n";
    
    $message .= "👥 *MÜŞTƏRİ:*\n";
    $message .= "Ad: {$data['customer_name']}\n";
    $message .= "Tel: {$data['customer_phone']}\n";
    $message .= "Sifariş №: {$data['order_number']}\n\n";
    
    $message .= "📦 *SİFARİŞ TƏFƏRRÜATLARİ:*\n";
    foreach ($data['items'] as $index => $item) {
        $itemNum = $index + 1;
        $message .= "\n*{$itemNum}.* {$item['profile_name']}\n";
        $message .= "   • Ölçü: {$item['height']}x{$item['width']} sm\n";
        $message .= "   • Say: {$item['quantity']} ədəd\n";
        $message .= "   • Tip: {$item['profile_type']}\n";
        $message .= "   • Rəng: {$item['profile_color']}\n";
        
        if ($item['glass_type_id']) {
            $glassHeight = $item['height'] - 4;
            $glassWidth = $item['width'] - 4;
            $message .= "   • Şüşə: {$glassHeight}x{$glassWidth} sm (4mm kiçik)\n";
        }
    }
    
    if (!empty($data['notes'])) {
        $message .= "\n📝 *Əlavə qeydlər:*\n{$data['notes']}\n";
    }
    
    $message .= "\n⚠️ *QEYD:* İstehsala başlamazdan əvvəl bütün ölçüləri yoxlayın və təsdiq edin.\n";
    $message .= "\n📞 Suallarınız üçün: {$data['salesperson']}";
    
    return $message;
}
?>