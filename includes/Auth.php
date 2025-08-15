<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function register($username, $password, $fullName, $email, $phone, $role = 'customer') {
        // Check if username already exists
        $user = $this->db->selectOne("SELECT * FROM users WHERE username = :username", ['username' => $username]);
        if ($user) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        $user = $this->db->selectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
        if ($user) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Check if phone already exists
        $user = $this->db->selectOne("SELECT * FROM users WHERE phone = :phone", ['phone' => $phone]);
        if ($user) {
            return ['success' => false, 'message' => 'Phone number already exists'];
        }
        
        // Generate verification code
        $verificationCode = rand(100000, 999999);
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user data
        $userData = [
            'username' => $username,
            'password' => $hashedPassword,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'verification_code' => $verificationCode
        ];
        
        $userId = $this->db->insert('users', $userData);
        
        if ($userId) {
            // If it's a customer, create customer record
            if ($role === 'customer') {
                $customerData = [
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'email' => $email,
                    'user_id' => $userId
                ];
                $this->db->insert('customers', $customerData);
            }
            
            // Send verification code
            $this->sendVerificationCode($phone, $verificationCode);
            
            return ['success' => true, 'user_id' => $userId, 'verification_code' => $verificationCode];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function login($username, $password) {
        $user = $this->db->selectOne("SELECT * FROM users WHERE username = :username AND is_active = 1", ['username' => $username]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        if (!$user['verified']) {
            return ['success' => false, 'message' => 'Account not verified', 'user_id' => $user['id']];
        }
        
        // Update last login time
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        
        // Start session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['store_id'] = $user['store_id'];
        
        return ['success' => true, 'user' => $user];
    }
    
    public function verifyCode($userId, $code) {
        $user = $this->db->selectOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if ($user['verification_code'] != $code) {
            return ['success' => false, 'message' => 'Invalid verification code'];
        }
        
        // Mark user as verified
        $this->db->update('users', ['verified' => 1, 'verification_code' => null], 'id = :id', ['id' => $userId]);
        
        return ['success' => true];
    }
    
    public function sendVerificationCode($phone, $code) {
        // Get Twilio settings from database
        $twilioSid = $this->getSetting('twilio_sid');
        $twilioToken = $this->getSetting('twilio_token');
        $twilioPhone = $this->getSetting('twilio_phone');
        
        if (empty($twilioSid) || empty($twilioToken) || empty($twilioPhone)) {
            return false;
        }
        
        // In a real application, you would use Twilio SDK to send SMS
        // For now, let's just simulate it
        
        // Example Twilio code (commented out)
        /*
        require_once __DIR__ . '/../vendor/autoload.php';
        use Twilio\Rest\Client;
        
        $client = new Client($twilioSid, $twilioToken);
        $message = $client->messages->create(
            $phone,
            [
                'from' => $twilioPhone,
                'body' => 'Your verification code for Alumpro.Az is: ' . $code
            ]
        );
        
        return !empty($message->sid);
        */
        
        // For development, just return true
        return true;
    }
    
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'];
        return $this->db->selectOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
    }
    
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['role'] === $role;
    }
    
    public function resetPassword($email) {
        $user = $this->db->selectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Generate random password
        $newPassword = bin2hex(random_bytes(4));
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update user password
        $this->db->update('users', ['password' => $hashedPassword], 'id = :id', ['id' => $user['id']]);
        
        // Send new password to user's email
        // In real application, use a proper email library
        
        return ['success' => true, 'password' => $newPassword];
    }
    
    private function getSetting($key) {
        $setting = $this->db->selectOne("SELECT setting_value FROM settings WHERE setting_key = :key", ['key' => $key]);
        return $setting ? $setting['setting_value'] : null;
    }
}