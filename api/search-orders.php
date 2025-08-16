<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn() || !$auth->hasRole('sales')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$query = $_GET['q'] ?? '';
$salespersonId = $_GET['salesperson_id'] ?? $user['id'];

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Only allow sales staff to see their own orders unless they're admin
if (!$auth->hasRole('admin') && $salespersonId != $user['id']) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        o.id,
        o.order_number,
        o.status,
        o.created_at,
        c.full_name as customer_name,
        c.phone as customer_phone,
        s.name as store_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN stores s ON o.store_id = s.id
    WHERE o.salesperson_id = :salesperson_id
    AND (
        o.order_number LIKE :query 
        OR c.full_name LIKE :query 
        OR c.phone LIKE :query
    )
    ORDER BY o.created_at DESC
    LIMIT 10
";

$params = [
    'salesperson_id' => $salespersonId,
    'query' => '%' . $query . '%'
];

try {
    $orders = $db->select($sql, $params);
    
    // Format dates for display
    foreach ($orders as &$order) {
        $order['created_at'] = date('d.m.Y H:i', strtotime($order['created_at']));
    }
    
    echo json_encode($orders);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>