<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['id'] ?? 0;

if ($notificationId) {
    $currentUser = $auth->getCurrentUser();
    
    $db->update('notifications',
        ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
        'id = :id AND user_id = :user_id',
        ['id' => $notificationId, 'user_id' => $currentUser['id']]
    );
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}