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

// Get last 30 days sales data
$data = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    
    $sales = $db->selectOne("
        SELECT COALESCE(SUM(grand_total), 0) as total
        FROM orders
        WHERE DATE(order_date) = :date
        AND status != 'cancelled'
    ", ['date' => $date]);
    
    $data['labels'][] = date('d.m', strtotime($date));
    $data['values'][] = $sales['total'];
}

echo json_encode($data);