<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Customer.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || (!$auth->hasRole('sales') && !$auth->hasRole('admin'))) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = [
    'full_name' => $_POST['full_name'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'email' => $_POST['email'] ?? null,
    'address' => $_POST['address'] ?? null,
    'notes' => $_POST['notes'] ?? null,
    'create_account' => $_POST['create_account'] ?? false
];

// Validation
if (empty($data['full_name']) || empty($data['phone'])) {
    echo json_encode(['success' => false, 'message' => 'Ad və telefon nömrəsi tələb olunur']);
    exit;
}

$customer = new Customer();
$result = $customer->create($data);

echo json_encode($result);