<?php
header('Content-Type: application/json');
session_start();

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/SupportChat.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

$chat = new SupportChat();
$result = $chat->sendMessage($data['message']);

echo json_encode($result);