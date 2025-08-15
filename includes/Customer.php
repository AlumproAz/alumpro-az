<?php
class Customer {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Check if phone already exists
            $existing = $this->db->selectOne("
                SELECT id FROM customers WHERE phone = :phone
            ", ['phone' => $data['phone']]);
            
            if ($existing) {
                throw new Exception('Bu telefon nömrəsi artıq qeydiyyatda mövcuddur');
            }
            
            // Create customer
            $customerId = $this->db->insert('customers', [
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create user account if requested
            if (isset($data['create_account']) && $data['create_account']) {
                $username = $this->generateUsername($data['full_name']);
                $password = $this->generatePassword();
                
                $userId = $this->db->insert('users', [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'role' => 'customer',
                    'verified' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Update customer with user_id
                $this->db->update('customers',
                    ['user_id' => $userId],
                    'id = :id',
                    ['id' => $customerId]
                );
                
                // Send credentials to customer
                $this->sendCredentials($data['phone'], $username, $password);
            }
            
            $this->db->commit();
            return ['success' => true, 'customer_id' => $customerId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function update($customerId, $data) {
        try {
            $updateData = [
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('customers', $updateData, 'id = :id', ['id' => $customerId]);
            
            // Update user account if exists
            $customer = $this->getById($customerId);
            if ($customer['user_id']) {
                $this->db->update('users',
                    [
                        'full_name' => $data['full_name'],
                        'email' => $data['email'],
                        'phone' => $data['phone']
                    ],
                    'id = :id',
                    ['id' => $customer['user_id']]
                );
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getById($customerId) {
        return $this->db->selectOne("
            SELECT c.*, u.username, u.last_login, u.is_active
            FROM customers c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = :id
        ", ['id' => $customerId]);
    }
    
    public function getAll($filters = []) {
        $query = "
            SELECT 
                c.*,
                u.username,
                u.last_login,
                u.is_active,
                COUNT(DISTINCT o.id) as order_count,
                COALESCE(SUM(o.grand_total), 0) as total_spent
            FROM customers c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN orders o ON c.id = o.customer_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $query .= " AND (c.full_name LIKE :search OR c.phone LIKE :search OR c.email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['has_account'])) {
            $query .= " AND c.user_id IS " . ($filters['has_account'] === 'yes' ? 'NOT NULL' : 'NULL');
        }
        
        $query .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $query .= " LIMIT " . intval($filters['limit']);
        }
        
        return $this->db->select($query, $params);
    }
    
    public function getOrderHistory($customerId) {
        return $this->db->select("
            SELECT 
                o.*,
                s.name as store_name,
                u.full_name as salesperson_name
            FROM orders o
            JOIN stores s ON o.store_id = s.id
            JOIN users u ON o.salesperson_id = u.id
            WHERE o.customer_id = :customer_id
            ORDER BY o.order_date DESC
        ", ['customer_id' => $customerId]);
    }
    
    public function getStatistics($customerId) {
        $stats = [];
        
        // Total orders
        $stats['total_orders'] = $this->db->selectOne("
            SELECT COUNT(*) as count FROM orders WHERE customer_id = :id
        ", ['id' => $customerId])['count'];
        
        // Total spent
        $stats['total_spent'] = $this->db->selectOne("
            SELECT COALESCE(SUM(grand_total), 0) as total FROM orders WHERE customer_id = :id
        ", ['id' => $customerId])['total'];
        
        // Average order value
        $stats['avg_order_value'] = $stats['total_orders'] > 0 ? 
            $stats['total_spent'] / $stats['total_orders'] : 0;
        
        // Last order date
        $lastOrder = $this->db->selectOne("
            SELECT order_date FROM orders WHERE customer_id = :id ORDER BY order_date DESC LIMIT 1
        ", ['id' => $customerId]);
        $stats['last_order_date'] = $lastOrder ? $lastOrder['order_date'] : null;
        
        // Favorite products
        $stats['favorite_products'] = $this->db->select("
            SELECT 
                p.name,
                COUNT(*) as order_count,
                SUM(oi.quantity) as total_quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.profile_type_id = p.id
            WHERE o.customer_id = :id
            GROUP BY p.id
            ORDER BY order_count DESC
            LIMIT 5
        ", ['id' => $customerId]);
        
        return $stats;
    }
    
    public function search($term) {
        return $this->db->select("
            SELECT * FROM customers
            WHERE full_name LIKE :term OR phone LIKE :term OR email LIKE :term
            ORDER BY full_name
            LIMIT 10
        ", ['term' => '%' . $term . '%']);
    }
    
    public function getTopCustomers($limit = 10) {
        return $this->db->select("
            SELECT 
                c.*,
                COUNT(DISTINCT o.id) as order_count,
                COALESCE(SUM(o.grand_total), 0) as total_spent,
                MAX(o.order_date) as last_order_date
            FROM customers c
            LEFT JOIN orders o ON c.id = o.customer_id
            GROUP BY c.id
            ORDER BY total_spent DESC
            LIMIT :limit
        ", ['limit' => $limit]);
    }
    
    public function sendNotification($customerId, $message, $type = 'sms') {
        $customer = $this->getById($customerId);
        
        if (!$customer) {
            return ['success' => false, 'message' => 'Customer not found'];
        }
        
        switch ($type) {
            case 'sms':
                return $this->sendSMS($customer['phone'], $message);
            case 'whatsapp':
                return $this->sendWhatsApp($customer['phone'], $message);
            case 'email':
                return $this->sendEmail($customer['email'], $message);
            default:
                return ['success' => false, 'message' => 'Invalid notification type'];
        }
    }
    
    private function generateUsername($fullName) {
        $base = strtolower(str_replace(' ', '', $fullName));
        $username = substr($base, 0, 8);
        $counter = 1;
        
        while ($this->db->selectOne("SELECT id FROM users WHERE username = :username", ['username' => $username . $counter])) {
            $counter++;
        }
        
        return $username . $counter;
    }
    
    private function generatePassword() {
        return bin2hex(random_bytes(4));
    }
    
    private function sendCredentials($phone, $username, $password) {
        $message = "Alumpro.Az - Hesab məlumatlarınız:\n";
        $message .= "İstifadəçi adı: $username\n";
        $message .= "Şifrə: $password\n";
        $message .= "Giriş: https://alumpro.az/login.php";
        
        return $this->sendSMS($phone, $message);
    }
    
    private function sendSMS($phone, $message) {
        // Implement SMS sending via Twilio
        return ['success' => true];
    }
    
    private function sendWhatsApp($phone, $message) {
        // Implement WhatsApp sending
        return ['success' => true];
    }
    
    private function sendEmail($email, $message) {
        // Implement email sending
        return ['success' => true];
    }
}