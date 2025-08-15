<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$term = $_GET['term'] ?? '';
$storeId = $_SESSION['user']['store_id'] ?? null;

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$query = "
    SELECT 
        id, 
        full_name, 
        phone, 
        email,
        customer_type,
        company_name
    FROM customers 
    WHERE (full_name LIKE :term 
        OR phone LIKE :term 
        OR email LIKE :term
        OR company_name LIKE :term)
";

$params = ['term' => '%' . $term . '%'];

if ($storeId && !$auth->hasRole('admin')) {
    $query .= " AND store_id = :store_id";
    $params['store_id'] = $storeId;
}

$query .= " LIMIT 10";

$customers = $db->select($query, $params);

$results = array_map(function($customer) {
    return [
        'id' => $customer['id'],
        'text' => $customer['full_name'] . ' - ' . $customer['phone'],
        'name' => $customer['full_name'],
        'phone' => $customer['phone'],
        'email' => $customer['email'],
        'type' => $customer['customer_type'],
        'company' => $customer['company_name']
    ];
}, $customers);

echo json_encode($results);