<?php
header('Content-Type: application/json');
session_start();

require_once '../../config/config.php';
require_once '../../includes/Database.php';

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$chatId = $_POST['chat_id'] ?? 0;

// Validate file
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB']);
    exit;
}

// Check file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'audio/mpeg', 'audio/wav'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

// Create upload directory
$uploadDir = '../../uploads/chat/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('chat_') . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Save to database
    $db = Database::getInstance();
    $messageId = $db->insert('support_messages', [
        'chat_id' => $chatId,
        'sender_type' => 'user',
        'sender_id' => $_SESSION['user_id'] ?? null,
        'message' => strpos($file['type'], 'image/') === 0 ? 'Şəkil göndərildi' : 'Səs göndərildi',
        'attachment' => 'uploads/chat/' . $filename,
        'attachment_type' => strpos($file['type'], 'image/') === 0 ? 'image' : 'audio',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true,
        'file_path' => 'uploads/chat/' . $filename,
        'message_id' => $messageId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}