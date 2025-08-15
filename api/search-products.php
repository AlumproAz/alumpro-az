<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Inventory.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$term = $_GET['term'] ?? '';
$storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$inventory = new Inventory();
$results = $inventory->searchInventory($term, $storeId);

$response = array_map(function($item) {
    return [
        'id' => $item['id'],
        'name' => $item['item_name'],
        'details' => $item['item_code'] . ' - ' . $item['category_name'],
        'code' => $item['item_code'],
        'color' => $item['item_color'],
        'quantity' => $item['quantity'],
        'price' => $item['sale_price'] ?? 0
    ];
}, $results);

echo json_encode($response);