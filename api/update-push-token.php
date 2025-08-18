<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['push_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$pushToken = $input['push_token'];
$userId = $input['user_id'] ?? null;

try {
    if ($userId && $auth->isLoggedIn()) {
        // Update user's push token
        $db->update('users', [
            'push_token' => $pushToken,
            'push_enabled' => 1
        ], 'id = :id', ['id' => $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Push token updated']);
    } else {
        // Store anonymous push token for marketing notifications
        $existingToken = $db->selectOne("
            SELECT id FROM push_tokens 
            WHERE token = :token
        ", ['token' => $pushToken]);
        
        if (!$existingToken) {
            $db->insert('push_tokens', [
                'token' => $pushToken,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Anonymous push token stored']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>