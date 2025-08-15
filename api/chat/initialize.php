<?php
header('Content-Type: application/json');
session_start();

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/SupportChat.php';

$data = json_decode(file_get_contents('php://input'), true);
$guestId = $data['guest_id'] ?? null;

$chat = new SupportChat();
$chatId = $chat->initializeChat($guestId);

echo json_encode([
    'success' => true,
    'chat_id' => $chatId
]);