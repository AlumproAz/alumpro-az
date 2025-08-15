<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Customer.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$customerId = $_GET['customer_id'] ?? 0;

if (!$customerId) {
    echo json_encode(['error' => 'Customer ID required']);
    exit;
}

$customer = new Customer();
$customerData = $customer->getById($customerId);
$orderHistory = $customer->getOrderHistory($customerId);
$statistics = $customer->getStatistics($customerId);

$response = [
    'customer' => $customerData,
    'orders' => $orderHistory,
    'statistics' => $statistics
];

echo json_encode($response);