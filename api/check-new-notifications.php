<?php
session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    echo json_encode(['has_new' => false]);
    exit;
}

$currentUser = $auth->getCurrentUser();

// Check for unread notifications
$unreadCount = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE user_id = :user_id AND is_read = 0
", ['user_id' => $currentUser['id']])['count'];

echo json_encode([
    'has_new' => $unreadCount > 0,
    'count' => $unreadCount
]);