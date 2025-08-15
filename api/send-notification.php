<?php
header('Content-Type: application/json');
session_start();

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Notification.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['title']) || empty($data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Başlıq və mesaj tələb olunur']);
    exit;
}

$notification = new Notification();

if ($data['type'] === 'broadcast') {
    $result = $notification->sendBroadcast(
        $data['title'],
        $data['message'],
        $data['notification_type'] ?? 'info'
    );
} elseif ($data['type'] === 'role') {
    $result = $notification->sendToRole(
        $data['role'],
        $data['title'],
        $data['message'],
        $data['notification_type'] ?? 'info'
    );
} else {
    $result = $notification->send(
        $data['user_id'],
        $data['title'],
        $data['message'],
        $data['notification_type'] ?? 'info',
        $data['related_id'] ?? null
    );
}

echo json_encode($result);