<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$customerId = $_GET['id'] ?? 0;

if (!$customerId) {
    echo json_encode(['error' => 'Customer ID required']);
    exit;
}

$customer = $db->selectOne("
    SELECT 
        c.*,
        COUNT(DISTINCT o.id) as order_count,
        COALESCE(SUM(o.grand_total), 0) as total_spent,
        COALESCE(AVG(o.grand_total), 0) as avg_order_value,
        MAX(o.order_date) as last_order_date
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
    WHERE c.id = :id
    GROUP BY c.id
", ['id' => $customerId]);

if (!$customer) {
    echo json_encode(['error' => 'Customer not found']);
    exit;
}

$customer['created_at'] = date('d.m.Y', strtotime($customer['created_at']));
$customer['last_order_date'] = $customer['last_order_date'] ? date('d.m.Y', strtotime($customer['last_order_date'])) : null;
$customer['avg_order_value'] = number_format($customer['avg_order_value'], 2);
$customer['total_spent'] = number_format($customer['total_spent'], 2);

echo json_encode($customer);