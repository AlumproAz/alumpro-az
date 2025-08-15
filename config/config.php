<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'alumpro_az');
define('DB_USER', 'root'); // Change to your database username
define('DB_PASS', ''); // Change to your database password

// Site configuration
define('SITE_URL', 'http://localhost/alumpro-az'); // Change to your actual domain
define('SITE_NAME', 'Alumpro.Az');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Session configuration
define('SESSION_NAME', 'alumpro_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Security
define('HASH_SALT', 'alumpro_az_salt_!@#$%^&*()'); // Change this to a unique random string

// Default settings
define('DEFAULT_LANG', 'az');
define('DEFAULT_TIMEZONE', 'Asia/Baku');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);