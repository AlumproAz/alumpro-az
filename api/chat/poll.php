<?php
header('Content-Type: application/json');
session_start();

require_once '../../config/config.php';
require_once '../../includes/Database.php';

$chatId = $_GET['chat_id'] ?? 0;
$lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));

if (!$chatId) {
    echo json_encode(['new_messages' => false]);
    exit;
}

$db = Database::getInstance();

// Check for new messages
$newMessages = $db->select("
    SELECT m.*, u.full_name as sender_name
    FROM support_messages m
    LEFT JOIN users u ON m.sender_id = u.id
    WHERE m.chat_id = :chat_id
    AND m.created_at > :last_check
    ORDER BY m.created_at ASC
", [
    'chat_id' => $chatId,
    'last_check' => $lastCheck
]);

// Check if agent joined
$chat = $db->selectOne("
    SELECT agent_id, agent_joined_at
    FROM support_chats
    WHERE id = :chat_id
", ['chat_id' => $chatId]);

$response = [
    'new_messages' => count($newMessages) > 0,
    'messages' => $newMessages,
    'agent_joined' => !empty($chat['agent_id']),
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response);