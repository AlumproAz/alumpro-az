<?php
header('Content-Type: application/json');
session_start();

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Inventory.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['inventory_id']) || !isset($data['quantity']) || empty($data['type'])) {
    echo json_encode(['success' => false, 'message' => 'Məlumatlar tam deyil']);
    exit;
}

$inventory = new Inventory();
$result = $inventory->updateStock(
    $data['inventory_id'],
    $data['quantity'],
    $data['type'],
    $data['notes'] ?? ''
);

echo json_encode($result);