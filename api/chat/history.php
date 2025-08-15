<?php
header('Content-Type: application/json');
session_start();

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/SupportChat.php';

$chatId = $_GET['chat_id'] ?? 0;

if (!$chatId) {
    echo json_encode([]);
    exit;
}

$chat = new SupportChat();
$messages = $chat->getChatHistory($chatId);

echo json_encode($messages);