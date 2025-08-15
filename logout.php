<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Database.php';

// Update user online status
if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $db->update('users', 
        ['is_online' => 0, 'last_activity' => date('Y-m-d H:i:s')],
        'id = :id',
        ['id' => $_SESSION['user_id']]
    );
}

// Destroy session
session_unset();
session_destroy();

// Delete remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login
header('Location: login.php?logout=success');
exit;