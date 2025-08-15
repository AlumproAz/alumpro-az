<?php
header('Content-Type: application/json');
session_start();

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? 1;

// Get inventory distribution by category
$data = $db->select("
    SELECT 
        c.name as category,
        COUNT(DISTINCT COALESCE(i.product_id, i.glass_id)) as count,
        SUM(i.quantity * COALESCE(p.sale_price, g.sale_price)) as value
    FROM inventory i
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN glass_products g ON i.glass_id = g.id
    LEFT JOIN categories c ON (p.category_id = c.id OR g.category_id = c.id)
    WHERE i.store_id = :store_id
    GROUP BY c.id
", ['store_id' => $storeId]);

$response = [
    'labels' => array_column($data, 'category'),
    'values' => array_column($data, 'value')
];

echo json_encode($response);